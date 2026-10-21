<?php
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/../storage/logs/log_cleanup_worker.log');

// Optional bootstrapping existing environment and error handlers
require __DIR__ . '/../bootstrap.php';

// Worker starts
error_log('Log cleanup worker started at ' . gmdate('Y-m-d H:i:s'));

$logDir = __DIR__ . '/../storage/logs';
$markerFile = $logDir . '/.log_cleanup_last_run';
$now = time();
$weekSeconds = 7 * 24 * 60 * 60;

// Ensure log directory exists
if (!is_dir($logDir)) {
    error_log('Log directory not found: ' . $logDir);
    exit(1);
}

// If we ran in the past week, exit gracefully
if (file_exists($markerFile)) {
    $lastRun = (int)trim(file_get_contents($markerFile));
    if ($lastRun > 0 && ($now - $lastRun) < $weekSeconds) {
        error_log('Log cleanup worker: already run in the last 7 days. Last run at ' . date('Y-m-d H:i:s', $lastRun));
        exit(0);
    }
}

try {
    $files = scandir($logDir);
    if ($files === false) {
        throw new \RuntimeException('Unable to scan log directory');
    }

    $cleared = 0;
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        // Skip marker file itself and worker log file if desired
        if ($file === basename($markerFile) || $file === basename(__FILE__) || $file === basename(__DIR__ . '/../storage/logs/log_cleanup_worker.log')) {
            continue;
        }

        $path = $logDir . '/' . $file;

        if (is_file($path) && is_writable($path)) {
            file_put_contents($path, '');
            $cleared++;
            error_log('Cleared log file: ' . $path);
        }
    }

    file_put_contents($markerFile, (string)$now);
    error_log("Log cleanup worker completed at " . gmdate('Y-m-d H:i:s') . " - cleared {$cleared} files");
    exit(0);
} catch (\Throwable $e) {
    error_log('Log cleanup worker failed: ' . $e->getMessage());
    exit(1);
}
