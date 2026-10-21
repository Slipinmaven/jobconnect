<?php

use DI\ContainerBuilder;
use function DI\create;
use function DI\get;

function buildContainer(): \DI\Container
{
    $builder = new ContainerBuilder();
    $builder->useAutowiring(true);

    // Add definitions
    $builder->addDefinitions([
        // PDO Database connection
        PDO::class => function () {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $dbname = $_ENV['DB_NAME'] ?? 'jobconnect';
            $username = $_ENV['DB_USER'] ?? 'root';
            $password = $_ENV['DB_PASS'] ?? '';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,  // Use emulated prepared statements
                PDO::ATTR_PERSISTENT => false,        // Disable persistent connections
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // Disable buffered queries
            ];

            $pdo = new PDO($dsn, $username, $password, $options);
            $pdo->exec("SET time_zone = '+00:00'");
            return $pdo;
        },

        // Repositories
        \App\Repositories\UserRepository::class => \DI\autowire(),
        \App\Repositories\SessionRepository::class => \DI\autowire(),
        \App\Repositories\TwoFactorCodeRepository::class => \DI\autowire(),
        \App\Repositories\TrustedDeviceRepository::class => \DI\autowire(),
        \App\Repositories\UserLocationRepository::class => \DI\autowire(),
        \App\Repositories\NotificationOutboxRepository::class => \DI\autowire(),
        \App\Repositories\EmailTokenRepository::class => \DI\autowire(),
        \App\Repositories\IndustryRepository::class => \DI\autowire(),
        \App\Repositories\JobSeekerRepository::class => \DI\autowire(),
        \App\Repositories\SkillRepository::class => \DI\autowire(),
        \App\Repositories\SchoolRepository::class => \DI\autowire(),
        \App\Repositories\JobRepository::class => \DI\autowire(),
        \App\Repositories\ApplicationRepository::class => \DI\autowire(),
        \App\Repositories\InterviewRepository::class => \DI\autowire(),
        \App\Repositories\NotificationRepository::class => \DI\autowire(),
        \App\Repositories\CommentRepository::class => \DI\autowire(),
        \App\Repositories\LikeRepository::class => \DI\autowire(),
        \App\Repositories\SavedJobRepository::class => \DI\autowire(),
        \App\Repositories\EmployerRepository::class => \DI\autowire(),
        \App\Repositories\JobCategoryRepository::class => \DI\autowire(),
        \App\Repositories\JobCategoryMapRepository::class => \DI\autowire(),

        // Services
        \App\Services\AuthService::class => \DI\autowire(),
        \App\Services\TokenService::class => \DI\autowire(),
        \App\Services\SessionManager::class => \DI\autowire(),
        \App\Services\GeoLocationService::class => \DI\autowire(),
        \App\Services\JobSeekerService::class => \DI\autowire(),
        \App\Services\NotificationService::class => \DI\autowire(),
        \App\Services\InterviewService::class => \DI\autowire(),
        \App\Services\EmployerService::class => \DI\autowire(),
        \App\Services\JobService::class => \DI\autowire(),
        \App\Services\ApplicationService::class => \DI\autowire(),
        \App\Services\CommentService::class => \DI\autowire(),
        \App\Services\LikeService::class => \DI\autowire(),
        \App\Services\SavedJobService::class => \DI\autowire(),
        \App\Services\SkillService::class => \DI\autowire(),
        \App\Services\SchoolService::class => \DI\autowire(),
        \App\Services\UserService::class => \DI\autowire(),
        \App\Services\JobCategoryService::class => \DI\autowire(),

        // Controllers
        'JobSeekerController' => \DI\autowire(\App\Controllers\JobSeekerController::class),
        'AuthController' => \DI\autowire(\App\Controllers\AuthController::class),
        'EmployerController' => \DI\autowire(\App\Controllers\EmployerController::class),
        'JobController' => \DI\autowire(\App\Controllers\JobController::class),
        'InterviewController' => \DI\autowire(\App\Controllers\InterviewController::class),
        'NotificationController' => \DI\autowire(\App\Controllers\NotificationController::class),
        'ApplicationController' => \DI\autowire(\App\Controllers\ApplicationController::class),
        'CommentController' => \DI\autowire(\App\Controllers\CommentController::class),
        'LikeController' => \DI\autowire(\App\Controllers\LikeController::class),
        'SavedJobController' => \DI\autowire(\App\Controllers\SavedJobController::class),
        'IndustryController' => \DI\autowire(\App\Controllers\IndustryController::class),
        'JobCategoryController' => \DI\autowire(\App\Controllers\JobCategoryController::class),
        'TestController' => \DI\autowire(\App\Controllers\TestController::class),

        // Google OAuth client configuration
        \Google\Client::class => function () {
            $client = new \Google\Client();
            $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? null;
            $clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'] ?? null;
            $redirectUri = $_ENV['GOOGLE_REDIRECT_URI'] ?? null;

            if ($clientId) {
                $client->setClientId($clientId);
            }
            if ($clientSecret) {
                $client->setClientSecret($clientSecret);
            }
            if ($redirectUri) {
                $client->setRedirectUri($redirectUri);
            }

            $client->setAccessType('offline');
            $client->setPrompt('select_account');
            $client->addScope(['openid', 'email', 'profile']);

            return $client;
        },

        \Google\Service\Oauth2::class => function (\Google\Client $googleClient) {
            return new \Google\Service\Oauth2($googleClient);
        },
        \App\Services\EmailService::class => \DI\autowire(),
        \App\Services\NotificationOutboxService::class => \DI\autowire(),
        \App\Services\IndustryService::class => \DI\autowire(),

        // You can add more definitions here later
        // UserRepository::class => autowire(),
        // UserService::class => autowire(),
    ]);

    return $builder->build();
}
