<?php

namespace App\Core;

/**
 * BaseService - All services extend this
 * 
 * PURPOSE: Contains business logic, validation, and coordinates between controllers and repositories
 * 
 * WHY USE SERVICES:
 * - Keep controllers thin (only handle HTTP)
 * - Reusable business logic across multiple controllers
 * - Easy to test
 * - Single Responsibility Principle
 * 
 * HOW IT WORKS:
 * 1. Controller calls service method
 * 2. Service validates data, applies business rules
 * 3. Service calls repositories to save/retrieve data
 * 4. Service returns result to controller
 * 
 * EXAMPLE:
 * class UserService extends BaseService {
 *     private UserRepository $userRepo;
 *     
 *     public function register(array $data) {
 *         $this->validateRequired($data, ['email', 'password']);
 *         $this->validateEmail($data['email']);
 *         $this->validatePassword($data['password']);
 *         return $this->userRepo->create($data);
 *     }
 * }
 */
abstract class BaseService
{
    /**
     * @var array Storage for validation errors
     */
    protected array $errors = [];

    /**
     * @var string Log directory path
     */
    protected string $logPath;

    public function __construct()
    {
        // Set default log path relative to project root
        $this->logPath = __DIR__ . '/../../storage/logs/';
        
        // Ensure log directory exists
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    // ==================== LOGGING METHODS ====================

    /**
     * Log an info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->writeLog('app.log', 'INFO', $message, $context);
    }

    /**
     * Log an error message
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->writeLog('error.log', 'ERROR', $message, $context);
    }

    /**
     * Log a warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->writeLog('app.log', 'WARNING', $message, $context);
    }

    /**
     * Write to log file
     */
    private function writeLog(string $filename, string $level, string $message, array $context = []): void
    {
        $log = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($this->logPath . $filename, $log, FILE_APPEND | LOCK_EX);
    }
    
    // ==================== VALIDATION METHODS ====================

    /**
     * Validate required fields
     * 
     * @param array $data The data to validate
     * @param array $required List of required field names
     * @return bool True if all required fields are present and not empty
     */
    protected function validateRequired(array $data, array $required): bool
    {
        $valid = true;
        foreach ($required as $field) {
            $value = $data[$field] ?? '';
            if (trim((string)$value) === '') {
                $this->errors[$field] = $this->formatFieldName($field) . ' is required';
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Validate email format
     */
    protected function validateEmail(string $email): bool
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Invalid email format';
            return false;
        }
        return true;
    }

    /**
     * Validate password strength
     */
    protected function validatePassword(string $password, int $minLength = 8): bool
    {
        if (strlen($password) < $minLength) {
            $this->errors['password'] = "Password must be at least {$minLength} characters";
            return false;
        }
        return true;
    }

    /**
     * Validate that two fields match (e.g., password and confirm)
     */
    protected function validateMatch(string $field1, string $value1, string $field2, string $value2): bool
    {
        if ($value1 !== $value2) {
            $this->errors[$field2] = $this->formatFieldName($field2) . ' does not match ' . $this->formatFieldName($field1);
            return false;
        }
        return true;
    }

    /**
     * Validate value length
     */
    protected function validateLength(string $value, string $field, ?int $min = null, ?int $max = null): bool
    {
        $length = strlen(trim($value));
        
        if ($min !== null && $length < $min) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be at least {$min} characters";
            return false;
        }
        
        if ($max !== null && $length > $max) {
            $this->errors[$field] = $this->formatFieldName($field) . " must not exceed {$max} characters";
            return false;
        }
        
        return true;
    }

    /**
     * Check if a value exists in database (for unique constraints)
     * 
     * @param callable $existsCheck Function that returns true if value exists
     * @param string $field Field name for error message
     * @param string|null $message Custom error message
     */
    protected function validateUnique(callable $existsCheck, string $field, ?string $message = null): bool
    {
        if ($existsCheck()) {
            $this->errors[$field] = $message ?? $this->formatFieldName($field) . ' already exists';
            return false;
        }
        return true;
    }

    /**
     * Validate against a set of allowed values
     */
    protected function validateInArray($value, string $field, array $allowed): bool
    {
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be one of: " . implode(', ', $allowed);
            return false;
        }
        return true;
    }

    /**
     * Validate integer range
     */
    protected function validateRange(int $value, string $field, int $min, int $max): bool
    {
        if ($value < $min || $value > $max) {
            $this->errors[$field] = $this->formatFieldName($field) . " must be between {$min} and {$max}";
            return false;
        }
        return true;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first validation error
     */
    public function getFirstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Check if validation passed
     */
    protected function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    protected function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Clear validation errors
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Add a custom error
     */
    protected function addError(string $field, string $message): void
    {
        $this->errors[$field] = $message;
    }
    public function hasErrors(): bool
{
    return !empty($this->errors);
}

    // ==================== HELPER METHODS ====================

    /**
     * Format field name for error messages
     */
    protected function formatFieldName(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Generate a cryptographically secure random token
     */
    protected function generateToken(int $bytes = 32): string
    {
        try {
            return bin2hex(random_bytes($bytes));
        } catch (\Exception $e) {
            $this->logError('Failed to generate random token', ['error' => $e->getMessage()]);
            // Fallback to less secure but still usable method
            return bin2hex(openssl_random_pseudo_bytes($bytes));
        }
    }

    /**
     * Generate a numeric code (for 2FA, verification)
     */
    protected function generateNumericCode(int $length = 6): string
    {
        try {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= random_int(0, 9);
            }
            return $code;
        } catch (\Exception $e) {
            $this->logError('Failed to generate numeric code', ['error' => $e->getMessage()]);
            // Fallback to less secure method
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= mt_rand(0, 9);
            }
            return $code;
        }
    }

    /**
     * Hash a password
     */
    protected function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    protected function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Sanitize input data - keep only allowed fields
     */
    protected function sanitize(array $data, array $allowedFields): array
    {
        return array_intersect_key($data, array_flip($allowedFields));
    }

    /**
     * Sanitize email
     */
    protected function sanitizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Get client IP address from server data
     */
    public function getClientIp(?array $server = null): ?string
    {
        $server = $server ?? $_SERVER;
        
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (isset($server[$header])) {
                $ips = explode(',', $server[$header]);
                return trim($ips[0]);
            }
        }
        
        return null;
    }

    /**
     * Get user agent from server data
     */
    protected function getUserAgent(?array $server = null): ?string
    {
        $server = $server ?? $_SERVER;
        return $server['HTTP_USER_AGENT'] ?? null;
    }
}