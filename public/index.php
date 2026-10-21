<?php
date_default_timezone_set('UTC');
require __DIR__ . '/../bootstrap.php';

use App\Router\Router;

// Dispatch routes
$router = new Router($container);
$router->dispatch();
