<?php

namespace App\Core;

/**
 * BaseController - All controllers extend this
 * 
 * PURPOSE: Handles HTTP requests, renders views, returns JSON responses
 * 
 * HOW IT WORKS:
 * 1. Controllers receive requests from routes
 * 2. They process the request (validate, call services)
 * 3. They return responses (views, JSON, redirects)
 * 
 * EXAMPLE:
 * class UserController extends BaseController {
 *     public function profile() {
 *         $this->requireAuth();
 *         $user = $this->userService->getProfile($this->userId);
 *         $this->view('user/profile', ['user' => $user]);
 *     }
 * }
 */
abstract class BaseController
{
    protected ?array $user = null;
    protected ?int $userId = null;
    protected ?string $userRole = null;

    /**
     * Constructor - runs for every controller
     * Automatically loads authenticated user if logged in
     */
    public function __construct()
    {
        $this->loadUserFromSession();
    }

    /**
     * Load user data from session
     */
    protected function loadUserFromSession(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->userId = (int)$_SESSION['user_id'];
            $this->userRole = $_SESSION['user_role'] ?? null;
            $this->user = [
                'id' => $this->userId,
                'email' => $_SESSION['user_email'] ?? null,
                'role' => $this->userRole,
                'name' => $_SESSION['user_name'] ?? null
            ];
        }
    }
    
    // ==================== VIEW RENDERING ====================

    /**
     * Render a view with layout
     * 
     * @param string $view Path to view (e.g., 'jobseeker/dashboard')
     * @param array $data Data to pass to view
     * @param string $layout Layout name (defaults to 'main')
     * 
     * HOW IT WORKS:
     * 1. Extracts data array into variables
     * 2. Starts output buffer
     * 3. Includes the view file
     * 4. Captures view output into $content
     * 5. Includes layout which uses $content
     */
    protected function view(string $view, array $data = [], string $layout = 'main'): void
    {
        try {
            // Make user available to all views
            $data['currentUser'] = $this->user;
            $data['currentUserId'] = $this->userId;
            $data['currentUserRole'] = $this->userRole;

            // Add CSRF token to all views
            $data['csrf_token'] = $this->csrfToken();

            // Extract data to variables (e.g., ['name' => 'John'] becomes $name = 'John')
            extract($data);

            // Start output buffer
            ob_start();

            // Include the view file
            $viewFile = $this->getViewPath($view);
            require $viewFile;

            // Get view content and clean buffer
            $content = ob_get_clean();

            // Include the layout
            $layoutFile = $this->getLayoutPath($layout);
            require $layoutFile;
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Render a view without layout (for partials/HTMX)
     */
    protected function partial(string $view, array $data = []): void
    {
        extract($data);

        $viewFile = $this->getViewPath($view);
        require $viewFile;
    }

    /**
     * Get full path to view file
     */
    protected function getViewPath(string $view): string
    {
        $path = dirname(__DIR__, 2) . "/app/views/{$view}.php";

        if (!file_exists($path)) {
            throw new \Exception("View not found: {$view} (looked in: {$path})");
        }

        return $path;
    }

    /**
     * Get full path to layout file
     */
    protected function getLayoutPath(string $layout): string
    {
        $path = dirname(__DIR__, 2) . "/app/views/layouts/{$layout}.php";

        if (!file_exists($path)) {
            throw new \Exception("Layout not found: {$layout} (looked in: {$path})");
        }

        return $path;
    }
    
    // ==================== JSON RESPONSES ====================

    /**
     * Send JSON response (for API endpoints)
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     */
    protected function json($data, int $statusCode = 200): void
    {
        // Clear any output that might have been sent accidentally
        if (ob_get_level()) ob_clean();

        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response (standard format)
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Send error response (standard format)
     */
    protected function error(string $message, array $errors = [], int $code = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Send validation error response
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): void
    {
        $this->error($message, $errors, 422);
    }

    /**
     * Send not found response
     */
    protected function notFound(string $message = 'Resource not found'): void
    {
        $this->error($message, [], 404);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, [], 401);
    }

    /**
     * Send forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, [], 403);
    }
    
    // ==================== AUTHENTICATION ====================

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return $this->userId !== null;
    }

    /**
     * Require authentication - redirects to login if not authenticated
     * Use for web pages that need login
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $_SESSION['_flash']['error'] = 'Please login first';
            $this->redirect('/login');
        }
    }

    /**
     * Require authentication for API - returns JSON error if not authenticated
     * Use for API endpoints that need login
     */
    protected function requireApiAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->unauthorized();
        }
    }

    /**
     * Require specific role
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if ($this->userRole !== $role) {
            $this->redirect('/403');
        }
    }

    /**
     * Require specific role for API
     */
    protected function requireApiRole(string $role): void
    {
        $this->requireApiAuth();

        if ($this->userRole !== $role) {
            $this->forbidden();
        }
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole(string $role): bool
    {
        return $this->userRole === $role;
    }

    /**
     * Check if user is jobseeker
     */
    protected function isJobSeeker(): bool
    {
        return $this->userRole === 'jobseeker';
    }

    /**
     * Check if user is employer
     */
    protected function isEmployer(): bool
    {
        return $this->userRole === 'employer';
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool
    {
        return $this->userRole === 'admin';
    }
    
    // ==================== INPUT HANDLING ====================

    /**
     * Get GET parameter
     */
    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get all POST data
     */
    protected function postAll(): array
    {
        return $_POST;
    }

    /**
     * Get JSON input from request body (for API PUT/POST)
     */
    protected function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');

        // Log what PHP actually sees
        error_log('php://input raw: ' . $raw);

        $input = json_decode($raw, true);

        // Log the parsed array (convert back to string)
        error_log('Decoded input: ' . print_r($input, true));

        return is_array($input) ? $input : [];
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request expects JSON response
     */
    protected function wantsJson(): bool
    {
        return $this->isAjax() ||
            (isset($_SERVER['HTTP_ACCEPT']) &&
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    // ==================== REDIRECTS ====================

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        // Clear output buffer before redirect
        if (ob_get_level()) ob_clean();

        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect back to previous page
     */
    public function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    /**
     * Redirect with flash message
     */
    protected function withFlash(string $key, string $message): self
    {
        $_SESSION['_flash'][$key] = $message;
        return $this;
    }

    /**
     * Redirect with input data (for form errors)
     */
    protected function withInput(array $input = null): self
    {
        $_SESSION['_old'] = $input ?? $_POST;
        return $this;
    }

    /**
     * Redirect with errors
     */
    protected function withErrors(array $errors): self
    {
        $_SESSION['_errors'] = $errors;
        return $this;
    }
    
    // ==================== CSRF PROTECTION ====================

    /**
     * Generate CSRF token
     */
    protected function csrfToken(): string
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_token'];
    }

    /**
     * CSRF field for forms
     */
    protected function csrfField(): void
    {
        echo '<input type="hidden" name="_token" value="' . $this->csrfToken() . '">';
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrf(string $token): bool
    {
        if (!isset($_SESSION['_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_token'], $token);
    }
    
    // ==================== FLASH MESSAGES ====================

    /**
     * Get flash message
     */
    protected function getFlash(string $key, $default = null)
    {
        return $_SESSION['_flash'][$key] ?? $default;
    }

    /**
     * Get all flash messages
     */
    protected function getFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    /**
     * Get old input data
     */
    protected function old(string $key, $default = null)
    {
        return $_SESSION['_old'][$key] ?? $default;
    }

    /**
     * Get validation errors
     */
    protected function errors(string $key = null)
    {
        if ($key) {
            return $_SESSION['_errors'][$key] ?? null;
        }
        return $_SESSION['_errors'] ?? [];
    }

    /**
     * Clear old data and errors
     */
    protected function clearOld(): void
    {
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }
}
