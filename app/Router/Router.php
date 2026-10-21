<?php

namespace App\Router;

use FastRoute;
use DI\Container;

class Router
{
    private $dispatcher;
    private $container;
    private $basePath;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->dispatcher = FastRoute\simpleDispatcher(require __DIR__ . '/../Routes/web.php');
        $this->basePath = $this->detectBasePath();
    }

    /**
     * Detect the base path from the environment or server
     */
    private function detectBasePath(): string
    {
        // First check if explicitly set in .env
        if (!empty($_ENV['BASE_PATH'])) {
            return '/' . trim($_ENV['BASE_PATH'], '/');
        }
        
        // Auto-detect from script name
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if ($scriptName) {
            $scriptDir = dirname($scriptName);
            if ($scriptDir !== '/' && $scriptDir !== '\\' && $scriptDir !== '.') {
                return $scriptDir;
            }
        }
        
        return '';
    }

    public function dispatch()
    {
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Remove query string
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        // Strip base path (for subdirectory installations)
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Ensure URI starts with /
        $uri = '/' . ltrim($uri, '/');

        // Log routing info in debug mode
        if ($_ENV['APP_ENV'] === 'development') {
            error_log("Routing: {$httpMethod} {$uri}");
        }

        $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                $this->handleNotFound();
                break;

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $this->handleMethodNotAllowed($routeInfo[1]);
                break;

            case FastRoute\Dispatcher::FOUND:
                $this->handleFound($routeInfo[1], $routeInfo[2]);
                break;
        }
    }

    private function handleFound($handler, $vars)
    {
        try {
            // Resolve controller from container
            $controller = $this->container->get($handler[0]);
            
            // Call controller method with vars as first argument
            call_user_func_array([$controller, $handler[1]], [$vars]);
            
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    private function handleNotFound()
    {
        http_response_code(404);
        
        // Check if it's an API request
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'statusCode' => 404,
                'message' => 'Route not found'
            ]);
        } else {
            // Try to load 404 view
            if (file_exists(__DIR__ . '/../Views/errors/404.php')) {
                require __DIR__ . '/../Views/errors/404.php';
            } else {
                echo "<h1>404 - Page Not Found</h1>";
                if ($_ENV['APP_ENV'] === 'development') {
                    echo "<p>The requested URL '{$_SERVER['REQUEST_URI']}' was not found.</p>";
                }
            }
        }
    }

    private function handleMethodNotAllowed($allowedMethods)
    {
        http_response_code(405);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'statusCode' => 405,
                'message' => 'Method not allowed',
                'allowed_methods' => $allowedMethods
            ]);
        } else {
            echo "<h1>405 - Method Not Allowed</h1>";
            echo "<p>Allowed methods: " . implode(', ', $allowedMethods) . "</p>";
        }
    }

    private function handleError(\Exception $e)
    {
        http_response_code(500);
        
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            $response = [
                'success' => false,
                'statusCode' => 500,
                'message' => 'Internal server error'
            ];
            
            if ($_ENV['APP_ENV'] === 'development') {
                $response['errors'] = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }
            
            echo json_encode($response);
        } else {
            if ($_ENV['APP_ENV'] === 'development') {
                throw $e; // Let ErrorHandler handle it
            } else {
                require __DIR__ . '/../Views/errors/500.php';
            }
        }
    }

    private function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0 ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }
}