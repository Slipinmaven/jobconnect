<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AuthService;
use App\Services\GeoLocationService;
use App\Services\IndustryService;
use App\Services\SkillService;
use App\Services\SchoolService;
use App\Services\SessionManager;
use App\Helpers\ApiResponse;
use App\Helpers\Validator;
use App\Helpers\ErrorHandler;
use App\Services\EmployerService;

class AuthController extends BaseController
{
    private AuthService $authService;
    private GeoLocationService $geoService;
    private IndustryService $industryService;
    private SchoolService $schoolService;
    private SkillService $skillService;
    private SessionManager $session;
    private EmployerService $employerService;

    public function __construct(AuthService $authService, GeoLocationService $geoService, IndustryService $industryService, SchoolService $schoolService, SkillService $skillService, SessionManager $sessionManager, EmployerService $employerService)
    {
        parent::__construct();
        $this->authService = $authService;
        $this->geoService = $geoService;
        $this->industryService = $industryService;
        $this->schoolService = $schoolService;
        $this->skillService = $skillService;
        $this->session = $sessionManager;
        $this->employerService = $employerService;
    }

    // ==================== REGISTRATION ====================

    public function showRegister()
    {
        try {
            if ($this->isAuthenticated()) {
                $this->getRedirectPath($this->user['role'] ?? '');
            }

            $this->view('auth/register', [
                'title' => 'Create Account - JobConnect',
                'css' => ['auth/login'],
                'js' => ['auth/auth']
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function register()
    {
        try {
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : [
                    'email' => $this->post('email'),
                    'password' => $this->post('password'),
                    'password_confirmation' => $this->post('password_confirmation'),
                    'role' => $this->post('role'),
                    'terms' => $this->post('terms')
                ];

            $rules = [
                'email' => 'required|email',
                'password' => 'required',
                'password_confirmation' => 'required',
                'role' => 'required|in:jobseeker,employer',
                'terms' => 'required'
            ];

            $errors = Validator::validate($data, $rules);

            if ($data['password'] !== $data['password_confirmation']) {
                $errors['password_confirmation'][] = 'Passwords do not match';
            }

            $terms = filter_var($data['terms'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$terms) {
                $errors['terms'][] = 'You must agree to the terms and conditions';
            }

            if (!empty($errors)) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError($errors, 'Please fix the errors below');
                }
                ErrorHandler::validationError('Please fix the errors below');
            }

            $result = $this->authService->register([
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role']
            ]);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError($result['errors'] ?? [], 'Registration failed');
                }
                ErrorHandler::validationError($result['errors']['email'] ?? 'Registration failed');
            }

            if ($this->wantsJson()) {
                return ApiResponse::success(
                    [
                        'redirect' => '/verify?email=' . urlencode($data['email']),
                        'user' => [
                            'email' => $data['email'],
                            'role' => $data['role']
                        ]
                    ],
                    $result['message'] ?? 'Registration successful! Please check your email.'
                );
            }

            $this->session->setFlash('success', $result['message'] ?? 'Registration successful! Please check your email.');

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    // ==================== EMAIL VERIFICATION ====================

   public function showVerify()
{
    try {
        // DEBUG: Log all incoming parameters
        error_log("========== VERIFY DEBUG ==========");
        error_log("GET parameters: " . print_r($_GET, true));
        
        // Get token and email from query string
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        
        error_log("Token from URL: " . $token);
        error_log("Email from URL: " . $email);
        error_log("Token length: " . strlen($token));
        
        // Check if token exists
        if (empty($token)) {
            error_log("ERROR: No token provided");
            $this->view('auth/verify', [
                'title' => 'Verify Email - JobConnect',
                'error' => 'No verification token provided.',
                'token' => '',
                'email' => $email
            ]);
            return;
        }
        
        // Your existing verification logic here
        // Verify the token with your token service
        
        $result = $this->authService->verifyEmail($email, $token);
        
        if ($result['success']) {
            // Redirect to login with success message
            $this->redirect('/login?verified=true&email=' . urlencode($email));
        } else {
            // Show error
            $this->view('auth/verify', [
                'css' => ['auth/verify'],
                'title' => 'Verify Email - JobConnect',
                'error' => $result['message'] ?? 'Invalid or expired token.',
                'token' => $token,
                'email' => $email
            ]);
        }
        
    } catch (\Throwable $e) {
        ErrorHandler::handleException($e);
    }
}

   public function verifyEmail()
{
    try {
        // Get data from either JSON, POST, or GET
        if ($this->wantsJson()) {
            $data = $this->getJsonInput();
        } else {
            $data = [
                'token' => $this->post('token') ?: $this->get('token'),
                'email' => $this->post('email') ?: $this->get('email')
            ];
        }

        // Debug log
        error_log("VerifyEmail - Data: " . print_r($data, true));

        if (empty($data['token']) || empty($data['email'])) {
            if ($this->wantsJson()) {
                return ApiResponse::validationError([], 'Token and email are required');
            }
            
            // If it's a GET request with missing data, show the verify page
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->view('auth/verify', [
                    'title' => 'Verify Email - JobConnect',
                    'error' => 'Missing verification information.',
                    'token' => $data['token'] ?? '',
                    'email' => $data['email'] ?? ''
                ]);
                return;
            }
            
            ErrorHandler::validationError('Token and email are required');
        }

        $user = $this->authService->getUserByEmail($data['email']);

        if (!$user) {
            if ($this->wantsJson()) {
                return ApiResponse::error('User not found', [], 404);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->view('auth/verify', [
                    'title' => 'Verify Email - JobConnect',
                    'error' => 'User not found with this email address.',
                    'token' => $data['token'],
                    'email' => $data['email']
                ]);
                return;
            }
            
            ErrorHandler::badRequest('User not found');
        }

        $result = $this->authService->verifyEmail($user['email'], $data['token']);

        if (!$result['success']) {
            if ($this->wantsJson()) {
                return ApiResponse::error($result['errors']['code'] ?? 'Verification failed', [], 400);
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $this->view('auth/verify', [
                    'title' => 'Verify Email - JobConnect',
                    'error' => $result['errors']['code'] ?? 'Invalid or expired verification link.',
                    'token' => $data['token'],
                    'email' => $data['email']
                ]);
                return;
            }
            
            ErrorHandler::validationError($result['errors']['code'] ?? 'Verification failed');
        }

        $this->session->remove('verification_user_id');

        if ($this->wantsJson()) {
            return ApiResponse::success(['redirect' => '/login'], $result['message']);
        }

        // Handle success based on request method
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->session->setFlash('success', $result['message']);
            $this->redirect('/login?verified=true&email=' . urlencode($data['email']));
        }

        $this->session->setFlash('success', $result['message']);
        $this->redirect('/login');

    } catch (\Throwable $e) {
        ErrorHandler::handleException($e);
    }
}

    public function showReverify()
    {
        try {
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $email = $_GET['email'] ?? '';

            $mode = match(true) {
                strpos($referer, '/verify') !== false => 'token_failed',
                strpos($referer, '/login') !== false => 'from_login',
                default => 'direct'
            };

            $messages = [
                'token_failed' => 'Your verification code has expired or is invalid. Request a new one below.',
                'from_login' => 'Please verify your email before logging in.',
                'direct' => 'Verify your email address to continue.'
            ];

            $this->view('auth/reverify', [
                'title' => 'Resend Verification Email - JobConnect',
                'css' => ['auth/reverify'],
                'mode' => $mode,
                'message' => $messages[$mode],
                'email' => $email,
                'button_text' => 'Resend Verification Email'
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function reverifyEmail()
    {
        try {
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : ['email' => $this->post('email')];

            $errors = Validator::validate($data, ['email' => 'required|email']);

            if (!empty($errors)) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError($errors, 'Please fix the errors below');
                }
                ErrorHandler::validationError('Please fix the errors below');
            }

            $result = $this->authService->resendVerificationEmail($data['email']);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::error($result['errors']['email'] ?? 'Failed to resend verification', [], 400);
                }
                ErrorHandler::badRequest($result['errors']['email'] ?? 'Failed to resend verification');
            }

            if ($this->wantsJson()) {
                return ApiResponse::success([], $result['message']);
            }

            $this->session->setFlash('success', $result['message']);
            $this->redirect('/verify?email=' . urlencode($data['email']));

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    // ==================== LOGIN ====================

    public function showLogin()
    {
        try {
            if ($this->isAuthenticated()) {
                // Use the user from BaseController
                $role = $this->user['role'] ?? null;

                // If profile is incomplete, redirect to profile setup
                if (!$this->authService->checkProfileComplete($this->user)) {
                    if ($role === 'jobseeker') {
                        $this->redirect('/jobseeker/profile-setup');
                        return;
                    } elseif ($role === 'employer') {
                        $this->redirect('/employer/profile-setup');
                        return;
                    } else {
                        $this->redirect('/login');
                        return;
                    }
                }

                // Profile is complete, redirect to dashboard
                $redirectPath = $this->getRedirectPath($role);
                $this->session->setFlash('info', 'You are already logged in');
                $this->redirect($redirectPath);
            }

            // Show login page for unauthenticated users
            $this->view('auth/login', [
                'title' => 'Sign In - JobConnect',
                'css' => ['auth/login'],
                'js' => ['utils/Spinner']
            ]);

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }


    public function login()
{
    try {
        $data = $this->wantsJson()
            ? $this->getJsonInput()
            : [
                'email' => $this->post('email'),
                'password' => $this->post('password'),
                'remember' => $this->post('remember') === 'on' || $this->post('remember') === 'true'
            ];

        $errors = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            if ($this->wantsJson()) {
                return ApiResponse::validationError($errors, 'Please fix the errors below');
            }
            ErrorHandler::validationError('Please fix the errors below');
            return;
        }

        $requestData = [
            'ip_address' => $this->geoService->getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null
        ];

        $result = $this->authService->login(
            ['email' => $data['email'], 'password' => $data['password']],
            $requestData,
            $data['remember'] ?? false
        );

        // Handle 2FA required case
        if (isset($result['requires_2fa'])) {
            return $this->handleTwoFactorRequired($result, $data['email']);
        }

        // Handle verification required case
        if (isset($result['requires_verification'])) {
            return $this->handleVerificationRequired($result, $data['email']);
        }

        // Handle account blocked case
        if (isset($result['blocked_until'])) {
            if ($this->wantsJson()) {
                return ApiResponse::error(
                    $result['errors']['email'] ?? 'Account is temporarily blocked',
                    ['blocked_until' => $result['blocked_until']],
                    403
                );
            }
            
            // Render blocked account page
            $this->view('auth/blocked', [
                'title' => 'Account Blocked - JobConnect',
                'css' => ['auth/login'],
                'email' => $data['email'],
                'blockedUntil' => $result['blocked_until']
            ]);
            return;
        }

        // Handle login failure (invalid credentials)
        if (!$result['success'] && !isset($result['requires_2fa']) && !isset($result['requires_verification'])) {
            if ($this->wantsJson()) {
                return ApiResponse::error($result['errors']['email'] ?? 'Login failed', [], 401);
            }
            ErrorHandler::unauthorized($result['errors']['email'] ?? 'Login failed');
            return;
        }

        // SUCCESSFUL LOGIN - Process the complete login result
        if ($result['success']) {
            return $this->handleLoginSuccess($result);
        }

        // Fallback for any unhandled cases
        if ($this->wantsJson()) {
            return ApiResponse::serverError('Login failed');
        }
        ErrorHandler::serverError('Login failed');

    } catch (\Throwable $e) {
        ErrorHandler::handleException($e);
    }
}

/**
 * Handle successful login response
 * 
 * @param array $result The complete login result from auth service
 */
private function handleLoginSuccess(array $result)
{
    // Set flash message for new location if needed
    if ($result['new_location'] ?? false) {
        $location = $result['location']['formatted'] ?? 'Unknown';
        $this->session->setFlash('info', "New login location detected: {$location}");
    }

    // Handle JSON response for API
    if ($this->wantsJson()) {
        $responseData = [
            'user' => $result['user'],
            'session_id' => $result['session_id'],
            'expires_at' => $result['expires_at'],
            'profile_complete' => $result['profile_complete'],
            'location' => $result['location'],
            'new_location' => $result['new_location'] ?? false
        ];

        // Add profile setup flag for jobseekers
        if ($result['requires_profile_setup'] ?? false) {
            $responseData['requires_profile_setup'] = true;
            $responseData['redirect'] = $result['redirect'];
            return ApiResponse::success($responseData, 'Login successful. Please complete your profile.');
        }

        // Add redirect based on role
        $responseData['redirect'] = $this->getRedirectPath($result['user']['role']);
        return ApiResponse::success($responseData, 'Login successful');
    }

    // Handle web response (redirect)
    if ($result['requires_profile_setup'] ?? false) {
        // Jobseeker needs to complete profile
        $this->session->setFlash('warning', 'Please complete your profile to start applying for jobs');
        $this->redirect('/jobseeker/profile-setup');
        return;
    }

    // Redirect based on user role
    $redirectPath = $this->getRedirectPath($result['user']['role']);
    $this->session->setFlash('success', 'Welcome back!');
    $this->redirect($redirectPath);
}

/**
 * Get redirect path based on user role
 * 
 * @param string $role
 * @return string
 */
    private function getRedirectPath(?string $role): string
    {
        return match ($role) {
            'jobseeker' => '/jobseeker/dashboard',
            'employer' => '/employer/dashboard',
            'admin' => '/admin/dashboard',
            default => '/'  // This will catch null and any other value
        };
    }

    /**
     * Redirect user to Google OAuth consent screen
     */
    public function redirectToGoogle(): void
    {
        try {
            $role = $this->get('role') ?? 'jobseeker';
            if (!in_array($role, ['jobseeker', 'employer', 'admin'])) {
                $role = 'jobseeker';
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $_SESSION['google_oauth_role'] = $role;

            $authUrl = $this->authService->getGoogleAuthUrl($role);
            header('Location: ' . $authUrl);
            exit;
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    /**
     * Handle Google OAuth callback
     */
    public function googleCallback()
    {
        try {
            $code = $this->get('code');
            if (empty($code)) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError([], 'Google code is required');
                }
                $this->session->setFlash('error', 'Failed to authenticate with Google.');
                $this->redirect('/login');
                return;
            }

            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $role = $_SESSION['google_oauth_role'] ?? 'jobseeker';
            unset($_SESSION['google_oauth_role']);

            $requestData = [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            $result = $this->authService->loginWithGoogle($code, $role, $requestData, false);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::error($result['message'] ?? 'Google login failed', $result['errors'] ?? [], 400);
                }
                $this->session->setFlash('error', $result['message'] ?? 'Google login failed');
                $this->redirect('/login');
                return;
            }

            if ($this->wantsJson()) {
                return ApiResponse::success($result, 'Google login successful');
            }

            // Preserve the profile-completion redirect returned by the login service.
            $redirectPath = $result['redirect'] ?? $this->getRedirectPath($result['user']['role'] ?? 'jobseeker');
            $this->redirect($redirectPath);

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }


/**
 * Handle two-factor authentication required response
 * 
 * @param array $result
 * @param string $email
 */
private function handleTwoFactorRequired(array $result, string $email)
{
    // Store 2FA data in session temporarily
    $this->session->set('2fa_temp', [
        'user_id' => $result['user_id'],
        'fingerprint' => $result['fingerprint'] ?? null,
        'remember' => $result['remember'] ?? false,
        'expires_at' => time() + 600 // 10 minutes
    ]);

    if ($this->wantsJson()) {
        return ApiResponse::success([
            'requires_2fa' => true,
            'user_id' => $result['user_id'],
            'method' => $result['method'] ?? 'authenticator'
        ], 'Two-factor authentication required');
    }

    // Store email for 2FA page
    $this->session->setFlash('2fa_email', $email);
    $this->redirect('/auth/2fa');
}

/**
 * Handle email verification required response
 * 
 * @param array $result
 * @param string $email
 */
private function handleVerificationRequired(array $result, string $email)
{
    if ($this->wantsJson()) {
        return ApiResponse::success([
            'requires_verification' => true,
            'user_id' => $result['user_id'],
            'email' => $email
        ], 'Please verify your email before logging in');
    }

    // Store in session and redirect to verification page
    $this->session->setFlash('verification_email', $email);
    $this->session->setFlash('verification_user_id', $result['user_id']);
    $this->session->setFlash('info', 'Please verify your email before logging in');
    $this->redirect('/auth/verify-email');
}

 public function showEmployerProfileSetup(){
        try {
            if (!$this->isAuthenticated()) {
                $this->redirect('/login');
            }
            $industries = $this->industryService->getIndustriesForDropdown();
            $employerProfile = $this->employerService->getProfile($this->getUserId());
            $this->view('profile/employerProfile', [
                'title' => 'Profile Setup - JobConnect',
                'css' => ['employer/profile'],
                'js' => ['employer/profile'],
                'industries' => $industries,
                'employerProfile' => $employerProfile
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

     public function showJobseekerProfileSetup(){
        try {
            if (!$this->isAuthenticated()) {
                $this->redirect('/login');
            }
            $userId = $this->getUserId();

            $skillCategories = $this->skillService->getAllCategories();
            $skills = $this->skillService->getAllSkills();
            $location = $this->geoService->getCurrentUserLocation($userId);
            $schools = $this->schoolService->getAllSchools();

            $this->view('profile/jobseekerProfile', [
                'title' => 'Profile Setup - JobConnect',
                'css' => ['jobseeker/profile'],
                'js' => ['jobseeker/profile'],
                'skillCategories' => $skillCategories,
                'skills' => $skills,
                'userLocation' => $location,
                'schools' => $schools
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }
    public function logout()
    {
        try {
            $result = $this->authService->logout();

            if ($this->wantsJson()) {
                return ApiResponse::success(['redirect' => '/'], $result['message']);
            }

            $this->session->setFlash('success', $result['message']);
            $this->redirect('/');

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

   

    public function logoutAllDevices()
    {
        try {
            $userId = $this->getUserId();
            if (!$userId) {
                if ($this->wantsJson()) {
                    return ApiResponse::error('Not authenticated', [], 401);
                }
                ErrorHandler::unauthorized('Not authenticated');
            }

            $result = $this->authService->logoutAllDevices($userId);

            if ($this->wantsJson()) {
                return ApiResponse::success(['redirect' => '/login'], $result['message']);
            }

            $this->session->setFlash('success', $result['message']);
            $this->redirect('/login');

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    // ==================== 2FA ====================

    public function showTwoFactor()
    {
        try {
            if (!$this->session->has('2fa_user_id')) {
                $this->redirect('/login');
            }

            $this->view('auth/2fa', [
                'title' => 'Two-Factor Authentication - JobConnect',
                'css' => ['auth/2fa'],
                'email' => $this->session->get('2fa_email')
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function verifyTwoFactor()
    {
        try {
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : ['code' => $this->post('code')];

            if (empty($data['code'])) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError(['code' => 'Verification code is required']);
                }
                ErrorHandler::validationError('Verification code is required');
            }

            $requestData = [
                'ip_address' => $this->geoService->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'trust_device' => $this->post('trust_device') === 'on'
            ];

            $result = $this->authService->verifyTwoFactorLogin($data['code'], $requestData);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::error($result['errors']['code'] ?? 'Invalid code', [], 400);
                }
                ErrorHandler::badRequest($result['errors']['code'] ?? 'Invalid code');
            }

            $this->session->remove('2fa_user_id');
            $this->session->remove('2fa_email');

            if ($this->wantsJson()) {
                return ApiResponse::success(
                    [
                        'redirect' => $this->getPostLoginRedirect($result['user']['role']),
                        'user' => $result['user']
                    ],
                    '2FA verified successfully'
                );
            }

            $this->redirect($this->getPostLoginRedirect($result['user']['role']));

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    // ==================== PASSWORD MANAGEMENT ====================

    public function showForgotPassword()
    {
        try {
            $this->view('auth/forgot-password', [
                'title' => 'Reset Password - JobConnect',
                'css' => ['auth/forgot-password'],
                'js' => ['auth/forgot-password']
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function forgotPassword()
    {
        try {
            // If user is logged in, use their email from session
            if ($this->userId) {
                $email = $this->user['email'] ?? null;
                
                if (!$email) {
                    if ($this->wantsJson()) {
                        ApiResponse::error('Email not found in session', [], 400);
                    }
                    ErrorHandler::badRequest('Email not found in session');
                }
                
                $result = $this->authService->requestPasswordReset($email);
                
                if ($this->wantsJson()) {
                    ApiResponse::success([], $result['message']);
                }
                
                $this->session->setFlash('success', $result['message']);
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
            }
            
            // For unauthenticated users, require email in request
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : ['email' => $this->post('email')];
                
            $errors = Validator::validate($data, ['email' => 'required|email']);

            if (!empty($errors)) {
                if ($this->wantsJson()) {
                    ApiResponse::validationError($errors, 'Please fix the errors below');
                }
                ErrorHandler::validationError('Please fix the errors below');
            }

            $result = $this->authService->requestPasswordReset($data['email']);

            if ($this->wantsJson()) {
                ApiResponse::success([], $result['message']);
            }

            $this->session->setFlash('success', $result['message']);
            $this->redirect('/login');

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function verifyResetToken()
    {
        try {
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : [
                    'token' => $this->post('token'),
                    'email' => $this->post('email')
                ];

            if (empty($data['token']) || empty($data['email'])) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError([], 'Token and email are required');
                }
                ErrorHandler::validationError('Token and email are required');
            }

            $result = $this->authService->verifyPasswordResetToken($data['email'], $data['token']);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::error($result['errors']['token'] ?? 'Invalid token', [], 400);
                }
                ErrorHandler::badRequest($result['errors']['token'] ?? 'Invalid token');
            }

            if ($this->wantsJson()) {
                return ApiResponse::success(['redirect' => '/reset-password?token=' . urlencode($data['token']) . '&email=' . urlencode($data['email'])], $result['message']);
            }

            $this->redirect('/reset-password?token=' . urlencode($data['token']) . '&email=' . urlencode($data['email']));

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function showResetPassword()
    {
        try {
            $token = $_GET['token'] ?? '';
            if (empty($token)) {
                $this->redirect('/forgot-password');
            }

            $this->view('auth/reset-password', [
                'title' => 'Reset Password - JobConnect',
                'css' => ['auth/reset'],
                'token' => $token
            ]);
        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    public function resetPassword()
    {
        try {
            $data = $this->wantsJson()
                ? $this->getJsonInput()
                : [
                    'email' => $this->post('email'),
                    'token' => $this->post('token'),
                    'password' => $this->post('password'),
                    'password_confirmation' => $this->post('password_confirmation')
                ];

            if (empty($data['email']) || empty($data['token'])) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError([], 'Email and token are required');
                }
                ErrorHandler::validationError('Email and token are required');
            }

            if ($data['password'] !== $data['password_confirmation']) {
                if ($this->wantsJson()) {
                    return ApiResponse::validationError(['password_confirmation' => 'Passwords do not match']);
                }
                ErrorHandler::validationError('Passwords do not match');
            }

            $result = $this->authService->resetPassword($data['email'], $data['token'], $data['password']);

            if (!$result['success']) {
                if ($this->wantsJson()) {
                    return ApiResponse::error($result['errors']['token'] ?? $result['errors']['password'] ?? 'Password reset failed', [], 400);
                }
                ErrorHandler::badRequest($result['errors']['token'] ?? $result['errors']['password'] ?? 'Password reset failed');
            }

            if ($this->wantsJson()) {
                return ApiResponse::success(['redirect' => '/login'], $result['message']);
            }

            $this->session->setFlash('success', $result['message']);
            $this->redirect('/login');

        } catch (\Throwable $e) {
            ErrorHandler::handleException($e);
        }
    }

    // ==================== HELPER METHODS ====================

    private function getPostLoginRedirect(string $role): string
    {
        return match ($role) {
            'admin' => '/admin/dashboard',
            'employer' => '/employer/dashboard',
            default => '/jobseeker/dashboard'
        };
    }
    public function showLandingPage(){
        $this->view('index', [
            'title' => 'Welcome to JobConnect'
        ]);
    }

    protected function isAuthenticated(): bool
    {
        return $this->session->isLoggedIn();
    }

    private function getUserId(): ?int
    {
        return $this->session->getUserId();
    }

    public function goBack(){
        $this->back();
    }
}