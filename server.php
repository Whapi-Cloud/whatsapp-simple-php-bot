# File: server.php
<?php

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');

// Bootstrap autoload and do preflight checks before starting server
require_once __DIR__ . '/vendor/autoload.php';
use App\Channel;

// Load config and channel
try {
    $config = require __DIR__ . '/config/config.php';
    $channel = new Channel($config['whatsapp_token']);
    echo "[BOOT] Checking channel health..." . PHP_EOL;
    $channel->checkHealth();
    echo "[BOOT] Health OK (AUTH)." . PHP_EOL;
    echo "[BOOT] Ensuring webhook is set to {$config['app_url']}/whatsapp ..." . PHP_EOL;
    $ok = $channel->setWebHook();
    echo $ok ? "[BOOT] Webhook set/updated." . PHP_EOL : "[BOOT] Webhook not changed." . PHP_EOL;
} catch (Throwable $e) {
    // Fail-fast on boot to make issues explicit
    fwrite(STDERR, "[BOOT][ERROR] " . $e->getMessage() . PHP_EOL);
    exit(1);
}

// Start the built-in PHP server
$port = 8000;
$host = 'localhost';
$publicDir = __DIR__ . '/public';

echo "Starting PHP server at http://{$host}:{$port}" . PHP_EOL;
echo "Press Ctrl+C to stop the server" . PHP_EOL;

// Change to the public directory
chdir($publicDir);

// Start the server
$command = sprintf(
    'php -S %s:%d -t %s',
    $host,
    $port,
    escapeshellarg($publicDir)
);

system($command);