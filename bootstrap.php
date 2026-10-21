<?php
// bootstrap.php
session_start();

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Helpers\ErrorHandler;
use App\Helpers\TimeHelper; // Add this import

define('BASE_PATH', __DIR__);

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$env = $_ENV['APP_ENV'] ?? 'production';

// Set timezone
date_default_timezone_set('UTC');

// Initialize TimeHelper with default settings
TimeHelper::setDbTimezone('UTC'); // Set database timezone (default UTC)

// Set user timezone from session if available
if (isset($_SESSION['user_timezone'])) {
    TimeHelper::setUserTimezone($_SESSION['user_timezone']);
} else {
    // Default timezone - you can change this to your region
    TimeHelper::setUserTimezone('Africa/Nairobi');
}

// Initialize error handler
ErrorHandler::initialize($env, BASE_PATH . '/storage/logs');

// Build DI container
require __DIR__ . '/app/Helpers/container.php';
$container = buildContainer();

// Set error logging
ini_set('log_errors', 'On');
ini_set('error_log', BASE_PATH . '/storage/logs/error.log');

// Set security headers only for web requests (not CLI)
if (php_sapi_name() !== 'cli') {
    header(
        "Content-Security-Policy: " .
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
            "frame-ancestors 'none'; " .
            "base-uri 'self'; " .
            "form-action 'self'"
    );
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}