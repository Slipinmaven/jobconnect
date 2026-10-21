<?php
function registerExceptionHandler(string $env = 'production')
{
    set_exception_handler(function ($e) use ($env) {
        if ($env === 'development') {
            echo "<pre>{$e}</pre>";
        } else {

            error_log($e->getMessage());
            http_response_code(500);
            require BASE_PATH . '/app/Views/errors/500.php';
        }
    });
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
}
