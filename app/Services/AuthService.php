<?php

namespace App\Services;

use App\Core\BaseService;
use App\Repositories\UserRepository;
use App\Repositories\JobSeekerRepository;
use App\Repositories\EmployerRepository;
use App\Repositories\SessionRepository;
use App\Repositories\TwoFactorCodeRepository;
use App\Repositories\TrustedDeviceRepository;
use App\Repositories\UserLocationRepository;
use App\Repositories\NotificationOutboxRepository;
use App\Services\NotificationOutboxService;
use App\Services\EmailService;
use App\Helpers\CryptoHelper;
use App\Helpers\TimeHelper;
use Exception;
use Google\Client as GoogleClient;
use Google\Service\Oauth2 as GoogleOauth2Service;
use Google\Service\AlertCenter\Notification;

/**
 * AuthService - Handles all authentication business logic
 * 
 * RESPONSIBILITIES:
 * 1. Login users - verify credentials, check 2FA, generate session tokens
 * 2. Register new users - validate, hash password, create account
 * 3. Logout - revoke session, clear cookies/session
 * 4. 2FA management - generate/verify OTP codes, TOTP secrets
 * 5. Password management - hash, verify, reset
 * 6. Token/session handling - create tokens, update activity
 * 7. Location tracking - record and manage user locations
 * 
 * This service contains ALL authentication logic.
 * Middleware uses this service (or its data) to verify requests.
 */
class AuthService extends BaseService
{
    private UserRepository $userRepo;
    private JobSeekerRepository $jobSeekerRepo;
    private EmployerRepository $employerRepo;
    private SessionRepository $sessionRepo;
    private TwoFactorCodeRepository $twoFactorRepo;
    private TrustedDeviceRepository $trustedDeviceRepo;
    private UserLocationRepository $locationRepo;
    private NotificationOutboxRepository $notificationOutboxRepo;
    private NotificationOutboxService $outboxService;
    private EmailService $emailService;
    private CryptoHelper $crypto;
    private SessionManager $session;
    private TokenService $tokenService;
    private GeoLocationService $geoService;
    private \Google\Client $googleClient;
    private \Google\Service\Oauth2 $googleOauth2;
    
    // Max failed attempts before blocking
    private const MAX_FAILED_ATTEMPTS = 5;
    private const BLOCK_MINUTES = 15;
    
    public function __construct(
        UserRepository $userRepo,
        JobSeekerRepository $jobSeekerRepo,
        EmployerRepository $employerRepo,
        SessionRepository $sessionRepo,
        TwoFactorCodeRepository $twoFactorRepo,
        TrustedDeviceRepository $trustedDeviceRepo,
        UserLocationRepository $locationRepo,
        NotificationOutboxRepository $notificationOutboxRepo,
        CryptoHelper $crypto,
        NotificationOutboxService $outboxService,
        EmailService $emailService,
        SessionManager $session,
        TokenService $tokenService,
        GeoLocationService $geoService,
        \Google\Client $googleClient,
        \Google\Service\Oauth2 $googleOauth2
    ) {
        parent::__construct();
        $this->userRepo = $userRepo;
        $this->jobSeekerRepo = $jobSeekerRepo;
        $this->employerRepo = $employerRepo;
        $this->sessionRepo = $sessionRepo;
        $this->twoFactorRepo = $twoFactorRepo;
        $this->trustedDeviceRepo = $trustedDeviceRepo;
        $this->locationRepo = $locationRepo;
        $this->notificationOutboxRepo = $notificationOutboxRepo;
        $this->crypto = $crypto;
        $this->outboxService = $outboxService;
        $this->emailService = $emailService;
        $this->session = $session;
        $this->tokenService = $tokenService;
        $this->geoService = $geoService;
        $this->googleClient = $googleClient;
        $this->googleOauth2 = $googleOauth2;
    }
    
    // ==================== 1. LOGIN ====================
    
    /**
     * Login a user
     * 
     * @param array $credentials Email and password
     * @param array $requestData Request data (ip, user_agent, etc.)
     * @param bool $rememberMe Whether to extend session lifetime
     * @return array Login result with status and data
     */
    public function login(array $credentials, array $requestData, bool $rememberMe = false): array
    {
        $this->clearErrors();
        
        // Validate required fields
        $this->validateRequired($credentials, ['email', 'password']);
        
        if ($this->fails()) {
            return [
                'success' => false,
                'errors' => $this->getErrors()
            ];
        }
        
        // Get user with sensitive data for authentication
        $user = $this->userRepo->getForAuthentication($credentials['email']);
        
        if (!$user) {
            // User not found - use same message to prevent user enumeration
            $this->logFailedAttempt($credentials['email'], $requestData);
            return [
                'success' => false,
                'errors' => ['email' => 'Invalid email or password']
            ];
        }
        
        // Check if account is blocked
        $blockCheck = $this->checkAccountBlocked($user);
        if (!$blockCheck['allowed']) {
            return [
                'success' => false,
                'errors' => ['email' => $blockCheck['message']],
                'blocked_until' => $blockCheck['blocked_until']
            ];
        }
        
        // OAuth accounts may not have a local password hash.
        $passwordHash = $user['password_hash'] ?? null;
        if (!is_string($passwordHash) || $passwordHash === '') {
            $this->logFailedAttempt($credentials['email'], $requestData);
            return [
                'success' => false,
                'errors' => ['email' => 'Invalid email or password']
            ];
        }

        // Verify password
        if (!$this->verifyPassword($credentials['password'], $passwordHash)) {
            $this->handleFailedLogin($user, $requestData);
            return [
                'success' => false,
                'errors' => ['email' => 'Invalid email or password']
            ];
        }
        
        // Check if email is verified
        if ($user['email_verified_at'] === null) {
            return [
                'success' => false,
                'requires_verification' => true,
                'user_id' => $user['id'],
                'message' => 'Please verify your email before logging in'
            ];
        }
        
        // Reset failed attempts on successful password verification
        $this->userRepo->update($user['id'], [
            'failed_login_attempts' => 0,
            'last_failed_login' => null
        ]);
        
        // Generate device fingerprint
        $fingerprint = $this->tokenService->generateDeviceFingerprint($requestData);
        
        // Check if 2FA is required
        if ($user['two_factor_enabled']) {
            return $this->handleTwoFactorRequired($user, $fingerprint, $rememberMe, $requestData);
        }
        
        // Complete login (create session, record location, etc.)
        return $this->completeLogin($user, $fingerprint, $rememberMe, $requestData);
    }
    
    /**
     * Complete login after successful authentication
     */
  private function completeLogin(array $user, string $fingerprint, bool $rememberMe, array $requestData): array
{
    // Create session token
    $tokenData = $this->tokenService->createSessionToken(
        $user['id'],
        [
            'ip_address' => $requestData['ip_address'] ?? null,
            'user_agent' => $requestData['user_agent'] ?? null,
            'fingerprint' => $fingerprint
        ],
        $rememberMe
    );
    
    // Set session cookie
    $this->session->setCookie(
        'auth_token',
        $tokenData['token'],
        $rememberMe ? 2592000 : 604800 // 30 days or 7 days
    );
    // Set PHP session data
    $this->session->regenerate();
    $this->session->set('user_id', $user['id']);
    $this->session->set('user_role', $user['role']);
    $this->session->set('user_email', $user['email']);
    $this->session->set('session_db_id', $tokenData['session_id']);
    $this->session->set('fingerprint', $fingerprint);
    
    // ==================== LOCATION TRACKING ====================
    // Record location using GeoLocationService
    $location = $this->geoService->recordUserLocation($user['id'], $requestData);
    
    // Store location in session for quick access
    $this->session->set('current_location', [
        'city' => $location['city'] ?? 'Unknown',
        'region' => $location['region'] ?? 'Unknown',
        'country' => $location['country'] ?? 'Unknown',
        'country_code' => $location['country_code'] ?? null,
        'latitude' => $location['latitude'] ?? 0,
        'longitude' => $location['longitude'] ?? 0
    ]);
    
    // Check if this is a new location for security alert
    $newLocationCheck = $this->geoService->detectNewLocation($user['id'], $requestData['ip_address'] ?? '');
    
    if ($newLocationCheck['is_new'] && !$newLocationCheck['is_first']) {
        // Store flag for potential security alert
        $this->session->set('new_location_detected', true);
        $this->session->set('previous_location', $newLocationCheck['previous_location'] ?? 'Unknown');
        
        // Log security event
        $this->logSecurityEvent('Login from new location', [
            'user_id' => $user['id'],
            'location' => $this->geoService->getLocationString($location)
        ]);
    }
    
    // Update last login info
    $this->userRepo->update($user['id'], [
        'last_login_at' => date('Y-m-d H:i:s'),
        'last_login_ip' => $requestData['ip_address'] ?? null
    ]);
    
    $this->logInfo('User logged in', [
        'user_id' => $user['id'],
        'session_id' => $tokenData['session_id'],
        'remember_me' => $rememberMe,
        'location' => $this->geoService->getLocationString($location)
    ]);
    
    // Check if profile is complete
    $profileComplete = $this->checkProfileComplete($user);
    
    // Determine redirect path based on role and profile completion
    $redirectPath = $this->getLoginRedirectPath($user, $profileComplete);
    
    // Prepare location data for response
    $locationData = [
        'city' => $location['city'] ?? 'Unknown',
        'region' => $location['region'] ?? 'Unknown',
        'country' => $location['country'] ?? 'Unknown',
        'country_code' => $location['country_code'] ?? null,
        'flag' => isset($location['country_code']) ? $this->geoService->getCountryFlag($location['country_code']) : null,
        'formatted' => $this->geoService->getLocationString($location)
    ];
    
    return [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'theme' => $user['theme']
        ],
        'session_id' => $tokenData['session_id'],
        'expires_at' => $tokenData['expires_at'],
        'profile_complete' => $profileComplete,
        'requires_profile_setup' => !$profileComplete,
        'redirect' => $redirectPath,
        'location' => $locationData,
        'new_location' => $newLocationCheck['is_new'] && !$newLocationCheck['is_first']
    ];
}

/**
 * Get login redirect path based on user role and profile completion
 * 
 * @param array $user
 * @param bool $profileComplete
 * @return string
 */
private function getLoginRedirectPath(array $user, bool $profileComplete): string
{
    // Base paths by role (now fully role-aware)
    $basePaths = [
        'jobseeker' => '/jobseeker/dashboard',
        'employer' => '/employer/dashboard',
        'admin' => '/admin/dashboard',
        'super_admin' => '/admin/dashboard'
    ];
    
    // Setup paths by role (where users go to complete their profile)
    $setupPaths = [
        'jobseeker' => '/jobseeker/profile-setup',
        'employer' => '/employer/profile-setup'
    ];
    
    // If profile is not complete, redirect to setup
    if (!$profileComplete) {
            $this->logInfo("Profile is not complete for user: " . $user['id'] . 'and role: ' . $user['role']);
        // Check if role has a setup path
        if (isset($setupPaths[$user['role']])) {
            $this->logInfo("Redirecting to profile setup for user: " . $user['id'] . 'and role: ' . $user['role']);
            return $setupPaths[$user['role']];
        }
        
        // For other roles (admin), still go to dashboard even if incomplete
        // (they don't have profiles to complete)
        return $basePaths[$user['role']] ?? '/';
    }
    
    // Profile is complete - go to appropriate dashboard
    return $basePaths[$user['role']] ?? '/';
}
    
    /**
     * Handle 2FA required scenario
     */
    private function handleTwoFactorRequired(array $user, string $fingerprint, bool $rememberMe, array $requestData): array
    {
        // Check if device is trusted
        $trusted = $this->tokenService->verifyTrustedDeviceToken($user['id'], $fingerprint);
        
        if ($trusted) {
            // Device is trusted, bypass 2FA
            return $this->completeLogin($user, $fingerprint, $rememberMe, $requestData);
        }
        
        // Generate and send 2FA code
        $codeData = $this->tokenService->createTwoFactorCode($user['id'], 'login_2fa', 10);
        
        // Store user ID in session for 2FA verification
        $this->session->set('2fa_user_id', $user['id']);
        $this->session->set('2fa_fingerprint', $fingerprint);
        $this->session->set('2fa_remember', $rememberMe);
        $this->session->set('2fa_request_data', $requestData);
        
        // In production, send code via email/SMS
        $this->sendTwoFactorCode($user['email'], $codeData['code']);
        
        return [
            'success' => true,
            'requires_2fa' => true,
            'message' => '2FA code sent to your email',
            'user_id' => $user['id']
        ];
    }
    
    /**
     * Verify 2FA code during login
     */
    public function verifyTwoFactorLogin(string $code, array $requestData): array
    {
        $userId = $this->session->get('2fa_user_id');
        $fingerprint = $this->session->get('2fa_fingerprint');
        $rememberMe = $this->session->get('2fa_remember');
        $requestData = $this->session->get('2fa_request_data') ?: $requestData;
        
        if (!$userId) {
            return [
                'success' => false,
                'errors' => ['code' => '2FA session expired']
            ];
        }
        
        // Verify the code
        $valid = $this->tokenService->verifyTwoFactorCode($userId, 'login_2fa', $code);
        
        if (!$valid) {
            return [
                'success' => false,
                'errors' => ['code' => 'Invalid verification code']
            ];
        }
        
        // Get user
        $user = $this->userRepo->findById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['code' => 'User not found']
            ];
        }
        
        // Optionally remember this device
        if ($rememberMe && isset($requestData['trust_device']) && $requestData['trust_device']) {
            $this->tokenService->createTrustedDeviceToken($userId, $fingerprint);
        }
        
        // Clear 2FA session data
        $this->session->remove('2fa_user_id');
        $this->session->remove('2fa_fingerprint');
        $this->session->remove('2fa_remember');
        $this->session->remove('2fa_request_data');
        
        // Complete login
        return $this->completeLogin($user, $fingerprint, $rememberMe, $requestData);
    }
    
    // ==================== 2. REGISTER ====================
    
    /**
     * Register a new user
     */
    public function register(array $data): array
    {
        $this->clearErrors();
        
        // Validate required fields
        $this->validateRequired($data, ['email', 'password', 'role']);
        
        // Validate email
        $this->validateEmail($data['email']);
        
        // Validate password strength
        $this->validatePassword($data['password'], 8);
        
        // Validate role
        $this->validateInArray($data['role'], 'role', ['jobseeker', 'employer']);
        
        // Check if email already exists
        $this->validateUnique(
            fn() => (bool)$this->userRepo->findByEmail($data['email']),
            'email'
        );
        
        if ($this->fails()) {
            return [
                'success' => false,
                'errors' => $this->getErrors()
            ];
        }
        
        // Prepare user data
        $userData = [
            'email' => $this->sanitizeEmail($data['email']),
            'password_hash' => $this->hashPassword($data['password']),
            'role' => $data['role']
        ];
        
        // Add optional fields
        if (isset($data['auth_provider'])) {
            $userData['auth_provider'] = $data['auth_provider'];
        }
        
        if (isset($data['google_id'])) {
            $userData['google_id'] = $data['google_id'];
        }
        
        // Create user
        $userId = $this->userRepo->createUser($userData);
        
        if (!$userId) {
            $this->logError('Failed to create user', ['email' => $data['email']]);
            return [
                'success' => false,
                'errors' => ['general' => 'Registration failed. Please try again.']
            ];
        }
        
                // Send verification email (outside transaction)
        $emailResult = $this->sendVerificationEmail($userId, $data['email']);
        
        // Handle email sending result
        if (!$emailResult['success']) {
            $this->logError('User created but verification email failed', [
                'user_id' => $userId,
                'email' => $data['email'],
                'error' => $emailResult['message']
            ]);
            
            return [
                'success' => true,
                'user_id' => $userId,
                'message' => 'Registration successful but verification email could not be sent. Please contact support to verify your account.',
                'email_sent' => false,
                'email_method' => $emailResult['method'] ?? null,
                'email_error' => $emailResult['message']
            ];
        }
        
        // Email sent successfully
        $this->logInfo('User registered and verification email sent', [
            'user_id' => $userId,
            'role' => $data['role'],
            'email_method' => $emailResult['method']
        ]);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'email_sent' => true,
            'email_method' => $emailResult['method']
        ];

    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getGoogleAuthUrl(string $role = 'jobseeker'): string
    {
        $role = in_array($role, ['jobseeker', 'employer']) ? $role : 'jobseeker';

        $this->googleClient->setScopes(['openid', 'email', 'profile']);
        $this->googleClient->setAccessType('offline');
        $this->googleClient->setPrompt('select_account');

        // preserve role for callback logic in controller
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['google_oauth_role'] = $role;

        return $this->googleClient->createAuthUrl();
    }

    /**
     * Handle Google callback and login/register user
     */
    public function loginWithGoogle(string $authCode, string $role = 'jobseeker', array $requestData = [], bool $rememberMe = false): array
    {
        $this->clearErrors();

        try {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($authCode);

            if (isset($token['error'])) {
                return [
                    'success' => false,
                    'message' => 'Google authentication failed: ' . ($token['error_description'] ?? $token['error']),
                    'errors' => ['google' => $token['error_description'] ?? $token['error']]
                ];
            }

            $idToken = $token['id_token'] ?? null;
            if (!$idToken) {
                return [
                    'success' => false,
                    'message' => 'Google did not return ID token',
                    'errors' => ['google' => 'Missing ID token']
                ];
            }

            $payload = $this->googleClient->verifyIdToken($idToken);
            if (!$payload || empty($payload['sub']) || empty($payload['email'])) {
                return [
                    'success' => false,
                    'message' => 'Unable to verify Google ID token',
                    'errors' => ['google' => 'Invalid token payload']
                ];
            }

            $googleId = $payload['sub'];
            $email = strtolower(trim($payload['email']));
            $emailVerified = !empty($payload['email_verified']);

            // User lookup by google id then email
            $user = $this->userRepo->findByGoogleId($googleId, true);
            if (!$user) {
                $user = $this->userRepo->findByEmail($email, true);
            }

            if ($user) {
                if ($user['auth_provider'] !== 'google' || empty($user['google_id'])) {
                    $this->userRepo->update($user['id'], [
                        'auth_provider' => 'google',
                        'google_id' => $googleId
                    ]);
                }

                // only update role from provided when current role is not employer and request role is employer; avoid accidental downgrade.
                if ($role === 'employer' && $user['role'] !== 'employer') {
                    $this->userRepo->update($user['id'], ['role' => 'employer']);
                    $user['role'] = 'employer';
                }
            } else {
                $role = in_array($role, ['jobseeker', 'employer']) ? $role : 'jobseeker';
                $newUserId = $this->userRepo->createUser([
                    'email' => $email,
                    'password_hash' => null,
                    'role' => $role,
                    'auth_provider' => 'google',
                    'google_id' => $googleId,
                    'email_verified_at' => $emailVerified ? date('Y-m-d H:i:s') : null
                ]);

                if (!$newUserId) {
                    return [
                        'success' => false,
                        'message' => 'Could not create user from Google account',
                        'errors' => ['google' => 'creation_failed']
                    ];
                }

                $user = $this->userRepo->findById($newUserId);
            }

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Unable to fetch user record after Google login',
                    'errors' => ['google' => 'no_user']
                ];
            }

            // Ensure email is verified for Google users.
            if ($emailVerified && empty($user['email_verified_at'])) {
                $this->userRepo->verifyEmail($user['id']);
                $user['email_verified_at'] = date('Y-m-d H:i:s');
            }

            // Resume normal login flow
            return $this->completeLogin($user, $this->tokenService->generateDeviceFingerprint($requestData), $rememberMe, $requestData);

        } catch (\Exception $e) {
            $this->logError('Google login exception', ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Google login failed. Please try again.',
                'errors' => ['google' => $e->getMessage()]
            ];
        }
    }

    /**
     * Verify email
     */
    public function verifyEmail(string $email, string $token): array
{
    error_log("AuthService::verifyEmail - Email: " . $email);
    error_log("AuthService::verifyEmail - Token (base64): " . $token);
    
    // Find user by email
    $user = $this->userRepo->findByEmail($email);
    
    if (!$user) {
        error_log("User not found for email: " . $email);
        return [
            'success' => false,
            'errors' => ['email' => 'User not found']
        ];
    }
    
    try {
        // Step 1: Decode from base64
        $binaryToken = base64_decode($token, true); // true for strict mode
        
        if ($binaryToken === false) {
            error_log("Base64 decode failed for token: " . $token);
            return [
                'success' => false,
                'errors' => ['code' => 'Invalid token format']
            ];
        }
        
        error_log("Base64 decoded, binary length: " . strlen($binaryToken));
        
        // Step 2: Decrypt the binary token
        $decryptedCode = $this->crypto->decrypt($binaryToken);
        
        if ($decryptedCode === null || $decryptedCode === '') {
            error_log("Decryption returned null or empty");
            return [
                'success' => false,
                'errors' => ['code' => 'Invalid verification token']
            ];
        }
        
        error_log("Decrypted code: " . $decryptedCode);
        
        // Step 3: Verify with token service
        $valid = $this->tokenService->verifyTwoFactorCode(
            $user['id'], 
            'email_verification', 
            $decryptedCode
        );
        
        if (!$valid) {
            error_log("Token verification failed for user: " . $user['id']);
            return [
                'success' => false,
                'errors' => ['code' => 'Invalid or expired verification code']
            ];
        }
        
        // Step 4: Mark email as verified
        $this->userRepo->verifyEmail($user['id']);
        error_log("Email verified for user ID: " . $user['id']);
        
        return [
            'success' => true,
            'message' => 'Email verified successfully'
        ];
        
    } catch (\Exception $e) {
        error_log("Verification error: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['code' => 'Verification failed: ' . $e->getMessage()]
        ];
    }
}
    
   
    /**
     * Resend verification email
     */
    public function resendVerificationEmail(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['email' => 'User not found']
            ];
        }
        
        if ($user['email_verified_at'] !== null) {
            return [
                'success' => false,
                'errors' => ['email' => 'Email already verified']
            ];
        }
        
        $this->sendVerificationEmail($user['id'], $email);
        
        return [
            'success' => true,
            'message' => 'Verification email sent'
        ];
    }
    
    // ==================== 3. LOGOUT ====================
    
    /**
     * Logout the current user
     */
    public function logout(): array
    {
        // Get current session token from cookie
        $token = $this->session->getCookie('auth_token');
        
        if ($token) {
            // Revoke the token in database
            $this->tokenService->revokeToken($token);
        }
        
        // Get session DB ID from PHP session
        $sessionDbId = $this->session->get('session_db_id');
        
        if ($sessionDbId) {
            // Revoke session in database
            $this->sessionRepo->update($sessionDbId, ['is_revoked' => 1]);
        }
        
        // Clear auth cookie
        $this->session->deleteCookie('auth_token');
        
        // Destroy PHP session
        $this->session->destroy();
        
        $this->logInfo('User logged out', ['session_db_id' => $sessionDbId]);
        
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    /**
     * Logout from all devices
     */
    public function logoutAllDevices(int $userId): array
    {
        // Revoke all session tokens for user
        $this->tokenService->revokeAllUserTokens($userId);
        
        // Clear current session
        $this->session->deleteCookie('auth_token');
        $this->session->destroy();
        
        $this->logInfo('User logged out from all devices', ['user_id' => $userId]);
        
        return [
            'success' => true,
            'message' => 'Logged out from all devices'
        ];
    }
    
    // ==================== 4. 2FA MANAGEMENT ====================
    
    /**
     * Enable TOTP 2FA for a user
     */
    public function enableTotp(int $userId, string $secret): array
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['user' => 'User not found']
            ];
        }
        
        // Update user with TOTP secret
        $this->userRepo->setTwoFactorStatus($userId, true, $secret);
        
        $this->logInfo('TOTP 2FA enabled', ['user_id' => $userId]);
        
        return [
            'success' => true,
            'message' => '2FA enabled successfully'
        ];
    }
    
    /**
     * Disable TOTP 2FA for a user
     */
    public function disableTotp(int $userId): array
    {
        $this->userRepo->setTwoFactorStatus($userId, false);
        
        // Remove all trusted devices for this user
        $this->trustedDeviceRepo->removeAllForUser($userId);
        
        $this->logInfo('TOTP 2FA disabled', ['user_id' => $userId]);
        
        return [
            'success' => true,
            'message' => '2FA disabled successfully'
        ];
    }
    
    /**
     * Verify TOTP code
     */
    public function verifyTotp(int $userId, string $code): array
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || !$user['totp_secret']) {
            return [
                'success' => false,
                'errors' => ['code' => '2FA not enabled']
            ];
        }
        
        // Verify TOTP code (would use a TOTP library)
        // $valid = $this->verifyTotpCode($user['totp_secret'], $code);
        
        // For now, assume valid
        $valid = true;
        
        if (!$valid) {
            return [
                'success' => false,
                'errors' => ['code' => 'Invalid verification code']
            ];
        }
        
        // Mark 2FA as verified in session
        $this->session->set('2fa_verified', true);
        
        return [
            'success' => true,
            'message' => '2FA verified successfully'
        ];
    }
    
    // ==================== 5. PASSWORD MANAGEMENT ====================
    
    /**
     * Request password reset
     */
    public function requestPasswordReset(string $email): array
    {
        $user = $this->userRepo->findByEmail($email);
        
        if (!$user) {
            // Don't reveal that email doesn't exist
            return [
                'success' => true,
                'message' => 'If your email exists, you will receive a reset link'
            ];
        }
        
        // Create reset token
        $resetData = $this->tokenService->createTwoFactorCode($user['id'], 'password_reset', 30);
        
        // Send reset email
        $this->sendPasswordResetEmail($email, $resetData['code']);
        
        $this->logInfo('Password reset requested', ['user_id' => $user['id']]);
        
        return [
            'success' => true,
            'message' => 'Password reset instructions sent to your email'
        ];
    }
    
    public function verifyPasswordResetToken(string $email, string $token): array
{
    try {
        $user = $this->userRepo->findByEmail($email);
        
        if (!$user) {
            error_log("Password reset verification: User not found for email: $email");
            return [
                'success' => false,
                'errors' => ['email' => 'User not found']
            ];
        }
        
        // Decode from base64
        if (empty($token)) {
            error_log("Password reset verification: Empty token received");
            return [
                'success' => false,
                'errors' => ['token' => 'Invalid token format']
            ];
        }
        
        $binaryToken = base64_decode($token, true);
        if ($binaryToken === false) {
            error_log("Password reset verification: Base64 decode failed for token: $token");
            return [
                'success' => false,
                'errors' => ['token' => 'Invalid token format']
            ];
        }
        
        error_log("Password reset verification: Base64 decoded. Binary length: " . strlen($binaryToken));
        
        // Decrypt the token
        $decryptedCode = $this->crypto->decrypt($binaryToken);
        
        if ($decryptedCode === null || $decryptedCode === '') {
            error_log("Password reset verification: Decryption failed for user: {$user['id']}");
            return [
                'success' => false,
                'errors' => ['token' => 'Invalid token format']
            ];
        }
        
        error_log("Password reset verification: Decrypted code for user: {$user['id']}");
        
        // Validate with token service (non-destructive - doesn't delete)
        $valid = $this->tokenService->validateTwoFactorCode(
            $user['id'], 
            'password_reset', 
            $decryptedCode
        );
        
        if (!$valid) {
            error_log("Password reset verification: Token validation failed for user: {$user['id']}");
            return [
                'success' => false,
                'errors' => ['token' => 'Invalid or expired reset token']
            ];
        }
        
        error_log("Password reset verification: SUCCESS for user: {$user['id']}");
        
        return [
            'success' => true,
            'message' => 'Reset token is valid',
            'user_id' => $user['id']
        ];
        
    } catch (\Exception $e) {
        $this->logError('Token verification failed', [
            'error' => $e->getMessage(),
            'email' => $email,
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'errors' => ['token' => 'Token verification failed']
        ];
    }
}
    /**
     * Reset password with valid reset token
     * Verifies the token first, then resets the password
     */
    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        $this->clearErrors();
        
        // Validate password strength
        $this->validatePassword($newPassword, 8);
        
        if ($this->fails()) {
            return [
                'success' => false,
                'errors' => $this->getErrors()
            ];
        }
        
        $user = $this->userRepo->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['email' => 'User not found']
            ];
        }
        
        $userId = $user['id'];
        
        // Update password
        $this->userRepo->updatePassword($userId, $this->hashPassword($newPassword));
        
        // Revoke all sessions for this user (they need to login again)
        $this->tokenService->revokeAllUserTokens($userId);
        
        // Delete the used reset token
        $this->tokenService->invalidateUserCodes($userId, 'password_reset');
        
        $this->logInfo('Password reset completed', ['user_id' => $userId]);
        
        return [
            'success' => true,
            'message' => 'Password reset successfully'
        ];
    }
    
    
    /*
     * Change password (when logged in)
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $this->clearErrors();
        
        $this->validateRequired(
            ['current_password' => $currentPassword, 'new_password' => $newPassword],
            ['current_password', 'new_password']
        );
        
        $this->validatePassword($newPassword, 8);
        
        if ($this->fails()) {
            return [
                'success' => false,
                'errors' => $this->getErrors()
            ];
        }
        
        $user = $this->userRepo->getForAuthenticationById($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'errors' => ['user' => 'User not found']
            ];
        }
        
        // OAuth accounts may not have a local password hash yet.
        $passwordHash = $user['password_hash'] ?? null;
        if (!is_string($passwordHash) || $passwordHash === '' || !$this->verifyPassword($currentPassword, $passwordHash)) {
            return [
                'success' => false,
                'errors' => ['current_password' => 'Current password is incorrect']
            ];
        }
        
        // Update password
        $this->userRepo->updatePassword($userId, $this->hashPassword($newPassword));
        
        $this->logInfo('Password changed', ['user_id' => $userId]);
        
        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }
    
    // ==================== 6. TOKEN/SESSION HANDLING ====================
    
    /**
     * Validate a session token
     */
    public function validateSessionToken(string $token, ?string $fingerprint = null): ?array
    {
        return $this->tokenService->verifySessionToken($token, $fingerprint);
    }
    
    /**
     * Get user by email (for verification)
     */
    public function getUserByEmail(string $email): ?array
    {
        return $this->userRepo->findByEmail($email);
    }
    
    /**
     * Refresh session token
     */
    public function refreshSession(string $currentToken, bool $rememberMe = false): ?array
    {
        return $this->tokenService->refreshSessionToken($currentToken, $rememberMe);
    }
    
    // ==================== 7. LOCATION MANAGEMENT ====================
    
    /**
     * Get user's location history
     */
    public function getUserLocationHistory(int $userId, int $limit = 50): array
    {
        return $this->geoService->getUserLocationHistory($userId, $limit);
    }
    
    /**
     * Get current user location
     */
    public function getCurrentLocation(int $userId): ?array
    {
        return $this->geoService->getCurrentUserLocation($userId);
    }
    
    /**
     * Check if login is from new location (for security alerts)
     */
    public function isNewLocation(int $userId, string $ip): array
    {
        return $this->geoService->detectNewLocation($userId, $ip);
    }
    
    /**
     * Export location data for GDPR
     */
    public function exportLocationData(int $userId): array
    {
        return $this->geoService->exportUserData($userId);
    }
    
    // ==================== HELPER METHODS ====================
    
    /**
     * Check if account is blocked
     */
    private function checkAccountBlocked(array $user): array
    {
        if (!$user['is_blocked']) {
            return ['allowed' => true];
        }
        
        if ($user['blocked_until'] && strtotime($user['blocked_until']) > time()) {
            return [
                'allowed' => false,
                'message' => 'Account is temporarily blocked. Please try again later.',
                'blocked_until' => $user['blocked_until']
            ];
        }
        
        // Block expired, unblock the user
        $this->userRepo->unblockUser($user['id']);
        return ['allowed' => true];
    }
    
    /**
     * Handle failed login attempt
     */
    private function handleFailedLogin(array $user, array $requestData): void
    {
        $attempts = ($user['failed_login_attempts'] ?? 0) + 1;
        
        $this->userRepo->update($user['id'], [
            'failed_login_attempts' => $attempts,
            'last_failed_login' => date('Y-m-d H:i:s')
        ]);
        
        $this->logFailedAttempt($user['email'], $requestData);
        
        // Block if too many attempts
        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            $this->userRepo->blockUser($user['id'], self::BLOCK_MINUTES);
            $this->logInfo('User blocked due to failed attempts', [
                'user_id' => $user['id'],
                'attempts' => $attempts
            ]);
        }
    }
    
    /**
     * Log failed login attempt
     */
    private function logFailedAttempt(string $email, array $requestData): void
    {
        $this->logWarning('Failed login attempt', [
            'email' => $email,
            'ip' => $requestData['ip_address'] ?? null,
            'user_agent' => $requestData['user_agent'] ?? null
        ]);
    }
    
    /**
     * Log security event
     */
    private function logSecurityEvent(string $message, array $context = []): void
    {
        $this->logWarning('SECURITY: ' . $message, $context);
    }
    
    /**
     * Check if profile is complete (for jobseekers)
     */
/**
 * Check if jobseeker profile is complete enough to proceed
 * 
 * @param array $user The user array
 * @return bool True if profile is complete or user is not jobseeker
 */
/**
 * Main dispatcher - checks profile completion based on user role
 * 
 * @param array $user The user array
 * @return bool True if profile is complete or user doesn't need profile check
 */
public function checkProfileComplete(array $user): bool
{
    $this->logInfo("Checking profile completion", [
        'user_id' => $user['id'],
        'role' => $user['role']
    ]);

    try {
        return match($user['role']) {
            'jobseeker' => $this->checkJobseekerProfileComplete($user),
            'employer'  => $this->checkEmployerProfileComplete($user),
            default     => $this->handleNonProfileRole($user)
        };
    } catch (Exception $e) {
        $this->logError("Error in profile completion check", [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'error' => $e->getMessage()
        ]);
        
        // Fail safe - assume incomplete to be safe
        return false;
    }
}

/**
 * Check jobseeker profile completion status
 * 
 * @param array $user
 * @return bool
 */
private function checkJobseekerProfileComplete(array $user): bool
{
    $this->logInfo("Checking jobseeker profile completion", ['user_id' => $user['id']]);

    try {
        // Get jobseeker profile
        $profile = $this->jobSeekerRepo->findByUser($user['id']);

        // If no profile exists at all, it's incomplete
        if (!$profile) {
            $this->logInfo("Jobseeker profile not found", ['user_id' => $user['id']]);
            return false;
        }

        // Check required fields for minimal profile completion
        $requiredFields = [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'professional_title' => 'Professional title'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($profile[$field])) {
                $this->logInfo("Jobseeker profile incomplete - missing {$label}", [
                    'user_id' => $user['id'],
                    'missing_field' => $field
                ]);
                return false;
            }
        }

        // Check if they have at least one skill
        $skills = $this->jobSeekerRepo->getSkills($profile['id']);
        if (count($skills) === 0) {
            $this->logInfo("Jobseeker profile incomplete - no skills added", [
                'user_id' => $user['id'],
                'profile_id' => $profile['id']
            ]);
            return false;
        }

        // Optional: Check profile completion percentage
        $completionPercentage = $profile['profile_completion'] ?? 0;
        if ($completionPercentage < 50) {
            $this->logInfo("Jobseeker profile incomplete - only {$completionPercentage}% complete", [
                'user_id' => $user['id']
            ]);
            return false;
        }

        $this->logInfo("Jobseeker profile is complete", [
            'user_id' => $user['id'],
            'profile_id' => $profile['id'],
            'skill_count' => count($skills),
            'completion' => $completionPercentage
        ]);

        return true;

    } catch (Exception $e) {
        $this->logError("Error checking jobseeker profile completion", [
            'user_id' => $user['id'],
            'error' => $e->getMessage()
        ]);
        
        return false;
    }
}

/**
 * Check employer profile completion status
 * 
 * @param array $user
 * @return bool
 */
private function checkEmployerProfileComplete(array $user): bool
{
    $this->logInfo("Checking employer profile completion", ['user_id' => $user['id']]);

    try {
        // Get employer profile
        $profile = $this->employerRepo->findByUser($user['id']);

        // If no profile exists, it's incomplete
        if (!$profile) {
            $this->logInfo("Employer profile not found", ['user_id' => $user['id']]);
            return false;
        }

        // Check required fields for minimal profile completion
        $requiredFields = [
            'company_name' => 'Company name',
            'contact_email' => 'Contact email',
            'employer_type' => 'Employer type'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($profile[$field])) {
                $this->logInfo("Employer profile incomplete - missing {$label}", [
                    'user_id' => $user['id'],
                    'missing_field' => $field
                ]);
                return false;
            }
        }

        // Check if they have an industry selected
        if (empty($profile['industry_id'])) {
            $this->logInfo("Employer profile incomplete - no industry selected", [
                'user_id' => $user['id']
            ]);
            return false;
        }

        // Check if they have a company description (at least 50 characters)
        if (empty($profile['company_description']) || strlen($profile['company_description']) < 10) {
            $this->logInfo("Employer profile incomplete - description too short", [
                'user_id' => $user['id'],
                'profile_id' => $profile['id'],
                'description_length' => strlen($profile['company_description'] ?? '')
            ]);
            return false;
        }

        // Optional: Check profile completion percentage
        $completionPercentage = $profile['profile_completion'] ?? 0;
        if ($completionPercentage < 60) {
            $this->logInfo("Employer profile incomplete - only {$completionPercentage}% complete", [
                'user_id' => $user['id']
            ]);
            return false;
        }

        $this->logInfo("Employer profile is complete", [
            'user_id' => $user['id'],
            'profile_id' => $profile['id'],
            'completion' => $completionPercentage
        ]);

        return true;

    } catch (Exception $e) {
        $this->logError("Error checking employer profile completion", [
            'user_id' => $user['id'],
            'error' => $e->getMessage()
        ]);
        
        return false;
    }
}

/**
 * Handle roles that don't require profile completion checks
 * 
 * @param array $user
 * @return bool Always true for admin/super_admin/etc.
 */
private function handleNonProfileRole(array $user): bool
{
    $this->logInfo("User role '{$user['role']}' does not require profile completion check", [
        'user_id' => $user['id']
    ]);
    
    // Admin, super_admin, moderator don't need profile checks
    return true;
}
    
    /**
     * Send verification email
     */
private function getAppBaseUrl(): string
{
    foreach (['APP_URL', 'BASE_URL'] as $key) {
        foreach ([
            $_ENV[$key] ?? null,
            $_SERVER[$key] ?? null,
            getenv($key) ?: null,
        ] as $value) {
            if (is_string($value) && trim($value) !== '') {
                return rtrim(trim($value), '/') . '/';
            }
        }
    }

    return 'http://jobconnect.local/';
}

private function sendVerificationEmail(int $userId, string $email): array
{
    $result = [
        'success' => false,
        'method' => null,
        'message' => ''
    ];
    
    try {
        $codeData = $this->tokenService->createTwoFactorCode($userId, 'email_verification', 1440);
        $encryptedToken = $this->crypto->encrypt($codeData['code']);
        
        // Use base64 for URL-safe encoding
        $token = base64_encode($encryptedToken);
        
        $baseUrl = $this->getAppBaseUrl();
        $link = $baseUrl . 'verify?token=' . urlencode($token) . '&email=' . urlencode($email);

        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $vars = [
            'link' => $link,
            'expires_at' => $expires_at,
            'name' => $email,
            'token' => $token
        ];
        
        $formattedExpires = TimeHelper::formatTime($expires_at);
        
        // Log attempt
        $this->logInfo('Attempting to send verification email', [
            'user_id' => $userId,
            'email' => $email
        ]);
        
        // Try to send email first
        $directSent = false;
        try {
            $directSent = $this->emailService->sendEmailVerification($email, $link, $formattedExpires);
            
            if ($directSent) {
                // Email sent successfully
                $result['success'] = true;
                $result['method'] = 'direct';
                $result['message'] = 'Verification email sent successfully';
                
                $this->logInfo('Verification email sent successfully', [
                    'user_id' => $userId,
                    'email' => $email,
                    'method' => 'direct'
                ]);
                
                return $result;
            }
            
            // Email service returned false (failed to send)
            $result['message'] = 'Email service failed, queuing in outbox';
            
            $this->logError('Email service failed to send verification email', [
                'user_id' => $userId,
                'email' => $email,
                'reason' => 'email_service_returned_false'
            ]);
            
        } catch (Exception $e) {
            // Email service threw an exception
            $result['message'] = 'Email service error, queuing in outbox: ' . $e->getMessage();
            
            $this->logError('Email service exception when sending verification email', [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback: Use outbox service
        $this->logInfo('Falling back to outbox service for verification email', [
            'user_id' => $userId,
            'email' => $email
        ]);
        
        $queued = $this->outboxService->sendEmail($userId, 'email_verification', $vars);
        
        if ($queued) {
            $result['success'] = true;
            $result['method'] = 'outbox';
            $result['message'] = $directSent === false ? 
                'Email service failed, verification email queued for later delivery' : 
                'Verification email queued for delivery';
            
            $this->logInfo('Verification email queued in outbox', [
                'user_id' => $userId,
                'email' => $email,
                'method' => 'outbox'
            ]);
        } else {
            $result['success'] = false;
            $result['method'] = null;
            $result['message'] = 'Failed to send or queue verification email';
            
            $this->logError('Failed to queue verification email in outbox', [
                'user_id' => $userId,
                'email' => $email
            ]);
        }
        
        return $result;
        
    } catch (Exception $e) {
        // Critical error - both email service and outbox failed
        $result['success'] = false;
        $result['method'] = null;
        $result['message'] = 'Critical error: ' . $e->getMessage();
        
        $this->logError('Critical error sending verification email', [
            'user_id' => $userId,
            'email' => $email,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return $result;
    }
}
    
    /**
     * Send 2FA code
     */
    private function sendTwoFactorCode(string $email, string $code): void
    {
        // In production, send actual email/SMS
        $this->logInfo('2FA code sent', [
            'email' => $email,
            'code' => $code // Only for development!
        ]);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(string $email, string $code): void
{
    // Find user to get ID
    $user = $this->userRepo->findByEmail($email);
    if (!$user) {
        return;
    }
    
    // Encrypt the code for URL safety
    $encryptedToken = $this->crypto->encrypt($code);
    $token = base64_encode($encryptedToken);
    
    // Generate reset link
    $baseUrl = $this->getAppBaseUrl();
    $link = $baseUrl . 'forgot-password?token=' . urlencode($token) . '&email=' . urlencode($email);


    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    $formattedExpires = TimeHelper::formatTime($expires_at);

    // Prepare email variables
    $vars = [
        'link' => $link,
        'expires_at' => $expires_at,
        'name' => $email,
        'token' => $token
    ];
    $resetSent = $this->emailService->sendPasswordReset($email, $link, $formattedExpires);
    if(!$resetSent){
        $this->logError('Failed to send password reset email via email service', [
            'email' => $email
        ]);
        $this->outboxService->sendEmail($user['id'], 'password_reset', $vars);
    }
        
    $this->logInfo('Password reset email sent', [
        'user_id' => $user['id'],
        'email' => $email
    ]);
}

    /**
     * Get the current authenticated user
     */
    public function getCurrentUser(): ?array
    {
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return null;
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return null;
        }

        // Add employer_id if user is employer
        if ($user['role'] === 'employer') {
            $employer = $this->employerRepo->findByUser($userId);
            if ($employer) {
                $user['employer_id'] = $employer['id'];
            }
        }

        return $user;
    }

    /**
     * Get the current authenticated user ID
     */
    public function getCurrentUserId(): ?int
    {
        return $this->session->get('user_id');
    }
}