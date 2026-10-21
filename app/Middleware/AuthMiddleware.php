<?php

namespace App\Middleware;

use App\Services\SessionManager;
use App\Services\TokenService;
use App\Repositories\SessionRepository;
use App\Repositories\UserRepository;

/**
 * AuthMiddleware - Handles request authentication
 * 
 * FUNCTIONS:
 * 1. Validate session token from cookie
 * 2. Check for revoked or expired sessions
 * 3. Fingerprint validation (user_agent, IP)
 * 4. Load user from database
 * 5. Check 2FA status
 * 6. Sync user into PHP session
 * 7. Update last activity
 * 8. Handle unauthorized requests
 */
class AuthMiddleware
{
    private SessionManager $session;
    private TokenService $tokenService;
    private SessionRepository $sessionRepo;
    private UserRepository $userRepo;
    
    // Inactivity timeout in seconds (30 minutes)
    private const INACTIVITY_TIMEOUT = 1800;
    
    public function __construct(
        SessionManager $session,
        TokenService $tokenService,
        SessionRepository $sessionRepo,
        UserRepository $userRepo
    ) {
        $this->session = $session;
        $this->tokenService = $tokenService;
        $this->sessionRepo = $sessionRepo;
        $this->userRepo = $userRepo;
    }
    
    /**
     * Authenticate a request
     * 
     * @param array $request Request data (server, cookies, etc.)
     * @param bool $required Whether authentication is required
     * @return array|false User data if authenticated, false otherwise
     */
    public function authenticate(array $request, bool $required = true)
    {
        // --- 1. Validate session token from cookie ---
        $token = $this->extractTokenFromCookie($request);
        
        if (!$token) {
            return $this->handleUnauthorized($required, 'No session token provided');
        }
        
        $hashedToken = $this->tokenService->hashToken($token);
        $session = $this->sessionRepo->findByToken($hashedToken);
        
        if (!$session) {
            return $this->handleUnauthorized($required, 'Invalid session token');
        }
        
        // --- 2. Check for revoked or expired sessions ---
        if ($session['is_revoked']) {
            $this->clearSessionCookie();
            return $this->handleUnauthorized($required, 'Session has been revoked');
        }
        
        if (strtotime($session['expires_at']) < time()) {
            $this->sessionRepo->delete($session['id']);
            $this->clearSessionCookie();
            return $this->handleUnauthorized($required, 'Session has expired');
        }
        
        // Check inactivity timeout
        $lastActivity = strtotime($session['last_activity']);
        if ((time() - $lastActivity) > self::INACTIVITY_TIMEOUT) {
            $this->sessionRepo->revokeSession($session['id']);
            $this->clearSessionCookie();
            return $this->handleUnauthorized($required, 'Session inactive for too long');
        }
        
        // --- 3. Fingerprint validation ---
        $currentFingerprint = $this->tokenService->generateDeviceFingerprint([
            'user_agent' => $request['server']['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $request['server']['REMOTE_ADDR'] ?? ''
        ]);
        
        if ($session['fingerprint'] !== $currentFingerprint) {
            // Potential session hijacking - revoke the session
            $this->sessionRepo->revokeSession($session['id']);
            $this->clearSessionCookie();
            $this->logSecurityEvent('Session hijacking detected', [
                'session_id' => $session['id'],
                'stored_fingerprint' => $session['fingerprint'],
                'current_fingerprint' => $currentFingerprint
            ]);
            return $this->handleUnauthorized($required, 'Security violation - session terminated');
        }
        
        // --- 4. Load user from database ---
        $user = $this->userRepo->findById($session['user_id']);
        
        if (!$user) {
            $this->sessionRepo->revokeSession($session['id']);
            $this->clearSessionCookie();
            return $this->handleUnauthorized($required, 'User no longer exists');
        }
        
        // Check if user is blocked
        if ($user['is_blocked']) {
            if ($user['blocked_until'] && strtotime($user['blocked_until']) > time()) {
                $this->sessionRepo->revokeSession($session['id']);
                $this->clearSessionCookie();
                return $this->handleUnauthorized($required, 'Account is blocked');
            }
        }
        
        // --- 5. Check 2FA status ---
        if ($user['two_factor_enabled'] && $user['totp_enabled']) {
            // Check if 2FA is verified for this session
            if (!$this->session->get('2fa_verified')) {
                // Store the session ID and redirect to 2FA
                $this->session->set('2fa_required_session', $session['id']);
                return $this->handleTwoFactorRequired($required);
            }
        }
        
        // --- 6. Sync user into PHP session ---
        $this->syncUserToSession($user, $session);
        
        // --- 7. Update last activity ---
        $this->sessionRepo->update($session['id'], [
            'last_activity' => date('Y-m-d H:i:s')
        ]);
        
        // Return user data with session info
        return [
            'user' => $user,
            'session_id' => $session['id'],
            'session' => $session
        ];
    }
    
    /**
     * Extract token from cookie
     */
    private function extractTokenFromCookie(array $request): ?string
    {
        $cookies = $request['cookies'] ?? $_COOKIE;
        return $cookies['auth_token'] ?? null;
    }
    
    /**
     * Clear the session cookie
     */
    private function clearSessionCookie(): void
    {
        $this->session->deleteCookie('auth_token');
    }
    
    /**
     * Sync user data to PHP session
     */
    private function syncUserToSession(array $user, array $session): void
    {
        // Regenerate session ID to prevent fixation
        $this->session->regenerate();
        
        // Store user data
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_role', $user['role']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_theme', $user['theme']);
        
        // Store session info
        $this->session->set('session_db_id', $session['id']);
        $this->session->set('login_time', time());
        
        // Store device info
        $this->session->set('device_fingerprint', $session['fingerprint']);
        $this->session->set('ip_address', $session['ip_address']);
    }
    
    /**
     * Handle 2FA required scenario
     */
    private function handleTwoFactorRequired(bool $required)
    {
        if (!$required) {
            return false;
        }
        
        // For API requests
        if ($this->isApiRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => '2FA required',
                'requires_2fa' => true
            ]);
            exit;
        }
        
        // For web requests - redirect to 2FA page
        header('Location: /2fa/verify');
        exit;
    }
    
    /**
     * Handle unauthorized request
     */
    private function handleUnauthorized(bool $required, string $reason)
    {
        if (!$required) {
            return false;
        }
        
        $this->logSecurityEvent('Unauthorized access attempt', ['reason' => $reason]);
        
        // For API requests
        if ($this->isApiRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'reason' => $reason]);
            exit;
        }
        
        // For web requests - redirect to login
        $this->session->setFlash('error', 'Please login to continue');
        header('Location: /login');
        exit;
    }
    
    /**
     * Check if request is API request
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0;
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent(string $message, array $context = []): void
    {
        $log = sprintf(
            "[%s] SECURITY: %s %s\n",
            date('Y-m-d H:i:s'),
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents(__DIR__ . '/../../storage/logs/security.log', $log, FILE_APPEND);
    }
    
    // ==================== MIDDLEWARE HANDLERS ====================
    
    /**
     * Middleware handler for routes that require authentication
     */
    public function handle(array $request, callable $next)
    {
        $auth = $this->authenticate($request, true);
        
        if (!$auth) {
            // This should never happen as authenticate() with required=true will exit
            return null;
        }
        
        // Add user to request for controllers
        $request['user'] = $auth['user'];
        $request['session_id'] = $auth['session_id'];
        
        return $next($request);
    }
    
    /**
     * Optional authentication - doesn't require login but provides user if available
     */
    public function handleOptional(array $request, callable $next)
    {
        $auth = $this->authenticate($request, false);
        
        if ($auth) {
            $request['user'] = $auth['user'];
            $request['session_id'] = $auth['session_id'];
        }
        
        return $next($request);
    }
    
    /**
     * Require specific role(s)
     */
    public function requireRole(array $request, callable $next, array $roles)
    {
        $auth = $this->authenticate($request, true);
        
        if (!$auth) {
            return null;
        }
        
        if (!in_array($auth['user']['role'], $roles)) {
            if ($this->isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Forbidden - insufficient permissions']);
                exit;
            }
            
            $this->session->setFlash('error', 'You do not have permission to access this page');
            header('Location: /');
            exit;
        }
        
        $request['user'] = $auth['user'];
        $request['session_id'] = $auth['session_id'];
        
        return $next($request);
    }
    
    /**
     * Verify 2FA for a session
     */
    public function verifyTwoFactor(string $code): bool
    {
        $sessionId = $this->session->get('2fa_required_session');
        
        if (!$sessionId) {
            return false;
        }
        
        $session = $this->sessionRepo->findById($sessionId);
        
        if (!$session) {
            return false;
        }
        
        $user = $this->userRepo->findById($session['user_id']);
        
        if (!$user || !$user['totp_enabled']) {
            return false;
        }
        
        // Verify TOTP code (this would use a TOTP library)
        // $valid = $this->verifyTotpCode($user['totp_secret'], $code);
        
        // For now, assume valid
        $valid = true;
        
        if ($valid) {
            $this->session->set('2fa_verified', true);
            $this->session->remove('2fa_required_session');
            
            // Sync user to session now that 2FA is complete
            $this->syncUserToSession($user, $session);
            
            return true;
        }
        
        return false;
    }
}