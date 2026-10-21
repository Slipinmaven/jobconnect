<?php

namespace App\Helpers;

class ApiResponse
{
    private $statusCode = 200;
    private $data = null;
    private $message = '';
    private $errors = [];

    public function __construct($statusCode = 200, $data = null, $message = '', $errors = [])
    {
        $this->statusCode = $statusCode;
        $this->data = $data;
        $this->message = $message;
        $this->errors = $errors;
    }

    /**
     * Set Content-Type header based on data type and send response
     */
    public function send($contentType = 'application/json')
    {
        header("Content-Type: {$contentType}");
        http_response_code($this->statusCode);

        $response = [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'statusCode' => $this->statusCode,
            'message' => $this->message,
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200)
    {
        $response = new self($statusCode, $data, $message);
        $response->send();
    }

    /**
     * Error response
     */
    public static function error($message = 'Error', $errors = [], $statusCode = 400)
    {
        $response = new self($statusCode, null, $message, $errors);
        $response->send();
    }

    /**
     * Not found response
     */
    public static function notFound($message = 'Resource not found')
    {
        self::error($message, [], 404);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, [], 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, [], 403);
    }

    /**
     * Validation error response
     */
    public static function validationError($errors, $message = 'Validation failed')
    {
        self::error($message, $errors, 422);
    }

    /**
     * Server error response
     */
    public static function serverError($message = 'Internal server error')
    {
        self::error($message, [], 500);
    }
}
