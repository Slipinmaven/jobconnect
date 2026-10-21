<?php
// clear_cache.php - DELETE AFTER RUNNING!

require_once __DIR__ . '/vendor/autoload.php';

use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->enableCompilation(__DIR__ . '/storage/cache');
$containerBuilder->writeProxiesToFile(true, __DIR__ . '/storage/cache/proxies');

try {
    $container = $containerBuilder->build();
    echo "Container cache cleared successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Also clear any compiled container files
$cacheDir = __DIR__ . '/storage/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.php');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "Deleted: " . basename($file) . "\n";
        }
    }
}

echo "Cache cleared. You can now delete this file.\n";