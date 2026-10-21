<?php

namespace App\Helpers;

use Throwable;

/**
 * Centralized Error Handler
 * Handles exceptions and errors with user-friendly display and developer logging
 */
class ErrorHandler
{

    private static $env = 'production';
    private static $logPath = '';

    /**
     * Initialize error handler
     */
    public static function initialize(string $env = 'production', string $logPath = ''): void
    {
        self::$env = $env;
        self::$logPath = $logPath ?: BASE_PATH . '/storage/logs';

        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void
    {
        $statusCode = self::getStatusCode($exception);
        $message = self::getUserMessage($statusCode);
        $details = self::getErrorDetails($exception);

        // Log the error
        self::logError($exception, $statusCode);

        // Display error page
        self::displayError($statusCode, $message, $details);
    }

    /**
     * Handle PHP errors
     */
    public static function handleError(int $severity, string $message, string $file, int $line): void
    {
        if (!(error_reporting() & $severity)) {
            return; // Suppressed error
        }

        $exception = new \ErrorException($message, 0, $severity, $file, $line);
        self::handleException($exception);
    }

    /**
     * Handle fatal errors
     */
    public static function handleFatalError(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            self::handleException($exception);
        }
    }

    /**
     * Display error to user
     */
    private static function displayError(int $statusCode, string $message, string $details = ''): void
    {
        // Prevent output buffering issues
        if (ob_get_level()) {
            ob_end_clean();
        }

        http_response_code($statusCode);

        // Check if this is an API request
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiRequest = strpos($uri, '/api/') === 0 || 
                       (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if ($isApiRequest) {
            // For API requests, return JSON
            header('Content-Type: application/json');
            
            $response = [
                'success' => false,
                'statusCode' => $statusCode,
                'message' => $message,
            ];

            // Add errors array for validation errors
            if ($statusCode === 422) {
                $response['errors'] = [];
            }

            // Add developer details in development mode
            if (self::$env === 'development' && $details) {
                $response['debug'] = $details;
            }

            echo json_encode($response);
            exit;
        }

        // For web requests, redirect to error page
        // Build error page URL
        $params = [
            'code' => $statusCode,
            'message' => $message,
            'back' => $_SERVER['HTTP_REFERER'] ?? '/',
        ];

        // Include developer details only in development mode
        if (self::$env === 'development') {
            $params['details'] = $details;
            $params['dev'] = '1';
        }

        $errorPageUrl = '/error.php?' . http_build_query($params);

        // Redirect to error page
        header("Location: $errorPageUrl");
        exit;
    }

    /**
     * Get HTTP status code from exception
     */
    private static function getStatusCode(Throwable $exception): int
    {
        // Check if exception has a status code
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        // Check exception message for status code
        if (preg_match('/\[(\d{3})\]/', $exception->getMessage(), $matches)) {
            return (int)$matches[1];
        }

        return 500;
    }

    /**
     * Get user-friendly error message
     */
    private static function getUserMessage(int $statusCode): string
    {
        $messages = [
            400 => 'The request could not be processed. Please check your input and try again.',
            401 => 'You need to log in to access this resource.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page or resource you are looking for could not be found.',
            405 => 'The request method is not allowed for this resource.',
            409 => 'There was a conflict with the existing data. Please try again.',
            422 => 'The provided data could not be validated. Please check your input.',
            429 => 'Too many requests. Please slow down and try again later.',
            500 => 'An internal server error occurred. Our team has been notified.',
            502 => 'The server is temporarily unavailable. Please try again later.',
            503 => 'The service is currently under maintenance. Please try again soon.',
        ];

        return $messages[$statusCode] ?? 'An unexpected error occurred. Please try again later.';
    }

    /**
     * Get technical error details for developers
     */
    private static function getErrorDetails(Throwable $exception): string
    {
        if (self::$env !== 'development') {
            return '';
        }

        $details = [];
        $details[] = "Exception Type: " . get_class($exception);
        $details[] = "Message: " . $exception->getMessage();
        $details[] = "Code: " . $exception->getCode();
        $details[] = "File: " . $exception->getFile() . ":" . $exception->getLine();

        $details[] = "\nStack Trace:";
        $details[] = $exception->getTraceAsString();

        if ($exception->getPrevious()) {
            $details[] = "\nPrevious Exception:";
            $details[] = self::getErrorDetails($exception->getPrevious());
        }

        return implode("\n", $details);
    }

    /**
     * Log error to file and system
     */
    private static function logError(Throwable $exception, int $statusCode): void
    {
        $logMessage = sprintf(
            "[%s] %s %d: %s in %s:%d\nStack: %s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $statusCode,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        // Log to file
        error_log($logMessage, 3, self::$logPath . '/errors.log');

        // Also log to system error log
        error_log($logMessage);

        // Log to specific status code file in development
        if (self::$env === 'development') {
            error_log($logMessage, 3, self::$logPath . "/error_{$statusCode}.log");
        }
    }

    /**
     * Throw an error with custom status code
     */
    public static function throw(string $message, int $statusCode = 500, ?Throwable $previous = null): never
    {
        throw new \Exception("[{$statusCode}] {$message}", 0, $previous);
    }

    /**
     * Create validation error (422)
     */
    public static function validationError(string $message, ?Throwable $previous = null): never
    {
        self::throw($message, 422, $previous);
    }

    /**
     * Create not found error (404)
     */
    public static function notFound(string $message = 'Resource not found', ?Throwable $previous = null): never
    {
        self::throw($message, 404, $previous);
    }

    /**
     * Create unauthorized error (401)
     */
    public static function unauthorized(string $message = 'Unauthorized', ?Throwable $previous = null): never
    {
        self::throw($message, 401, $previous);
    }

    /**
     * Create forbidden error (403)
     */
    public static function forbidden(string $message = 'Forbidden', ?Throwable $previous = null): never
    {
        self::throw($message, 403, $previous);
    }

    /**
     * Create bad request error (400)
     */
    public static function badRequest(string $message = 'Bad request', ?Throwable $previous = null): never
    {
        self::throw($message, 400, $previous);
    }

    /**
     * Create server error (500)
     */
    public static function serverError(string $message = 'Internal server error', ?Throwable $previous = null): never
    {
        self::throw($message, 500, $previous);
    }

    /**
     * Create too many requests error (429)
     */
    public static function tooManyRequests(string $message = 'Too many requests', ?Throwable $previous = null): never
    {
        self::throw($message, 429, $previous);
    }

    /**
     * Create method not allowed error (405)
     */
    public static function methodNotAllowed(string $message = 'Method not allowed', ?Throwable $previous = null): never
    {
        self::throw($message, 405, $previous);
    }

    /**
     * Create conflict error (409)
     */
    public static function conflict(string $message = 'Conflict with existing data', ?Throwable $previous = null): never
    {
        self::throw($message, 409, $previous);
    }
}