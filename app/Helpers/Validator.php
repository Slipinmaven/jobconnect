<?php

namespace App\Helpers;

/**
 * Simple Validator - Easy to understand
 * 
 * Just pass data and rules, it returns errors if any
 * Supports both form POST and JSON requests
 */
class Validator
{
    /**
     * Get input data from request (supports both POST and JSON)
     * 
     * @return array
     */
    public static function getInputData(): array
    {
        // Check if it's a JSON request
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            // Get JSON input
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];

            // Merge with POST data (POST takes precedence)
            return array_merge($data, $_POST);
        }

        // Regular POST request
        return $_POST;
    }

    /**
     * Validate request data against rules
     * Automatically detects JSON or POST input
     * 
     * @param array|null $data Optional data array. If null, gets from request
     * @param array $rules Rules like ['email' => 'required|email', 'password' => 'required|min:6']
     * @return array Array of errors, empty if validation passes
     */
    public static function validate(?array $data = null, array $rules): array
    {
        // If data not provided, get from request
        if ($data === null) {
            $data = self::getInputData();
        }

        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            foreach ($rules as $rule) {
                // Check if rule has a parameter (like min:6)
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $parameter] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $parameter = null;
                }

                // Run the validation rule
                $error = self::runRule($field, $value, $ruleName, $parameter, $data);

                if ($error) {
                    $errors[$field][] = $error;
                    break; // Stop on first error for this field
                }
            }
        }

        return $errors;
    }

    /**
     * Validate with custom data source
     * 
     * @param string $source 'post', 'json', or 'both'
     */
    public static function validateRequest(string $source = 'both', array $rules): array
    {
        $data = [];

        switch ($source) {
            case 'post':
                $data = $_POST;
                break;
            case 'json':
                $json = file_get_contents('php://input');
                $data = json_decode($json, true) ?? [];
                break;
            case 'both':
            default:
                $data = self::getInputData();
                break;
        }

        return self::validate($data, $rules);
    }

    /**
     * Run a single validation rule
     */
    private static function runRule($field, $value, $rule, $parameter, $allData = []): ?string
    {
        $fieldName = str_replace('_', ' ', $field);

        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    return ucfirst($fieldName) . ' is required';
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'Please enter a valid email address';
                }
                break;

            case 'min':
                if (!empty($value) && strlen($value) < $parameter) {
                    return ucfirst($fieldName) . ' must be at least ' . $parameter . ' characters';
                }
                break;

            case 'max':
                if (!empty($value) && strlen($value) > $parameter) {
                    return ucfirst($fieldName) . ' must not exceed ' . $parameter . ' characters';
                }
                break;

            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return ucfirst($fieldName) . ' must be a number';
                }
                break;

            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return ucfirst($fieldName) . ' must be a whole number';
                }
                break;

            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return 'Please enter a valid URL';
                }
                break;

            case 'confirmed':
                $confirmValue = $allData[$field . '_confirmation'] ?? null;
                if ($value !== $confirmValue) {
                    return ucfirst($fieldName) . ' confirmation does not match';
                }
                break;

            case 'in':
                $allowed = explode(',', $parameter);
                if (!empty($value) && !in_array($value, $allowed)) {
                    return ucfirst($fieldName) . ' must be one of: ' . implode(', ', $allowed);
                }
                break;

            case 'phone':
                if (!empty($value)) {
                    $cleaned = preg_replace('/[^0-9+]/', '', $value);
                    if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
                        return 'Please enter a valid phone number';
                    }
                }
                break;

            case 'password':
                if (!empty($value)) {
                    if (strlen($value) < 8) {
                        return 'Password must be at least 8 characters';
                    }
                    if (!preg_match('/[A-Z]/', $value)) {
                        return 'Password must contain at least one uppercase letter';
                    }
                    if (!preg_match('/[a-z]/', $value)) {
                        return 'Password must contain at least one lowercase letter';
                    }
                    if (!preg_match('/[0-9]/', $value)) {
                        return 'Password must contain at least one number';
                    }
                }
                break;

            case 'array':
                if (!empty($value) && !is_array($value)) {
                    return ucfirst($fieldName) . ' must be an array';
                }
                break;

            case 'boolean':
                if (!empty($value) && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    return ucfirst($fieldName) . ' must be a boolean value';
                }
                break;
        }

        return null;
    }

    /**
     * Check if request is JSON
     * 
     * @return bool
     */
    public static function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    /**
     * Get raw JSON input
     * 
     * @return array
     */
    public static function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
}
