# File: server.php
<?php

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('UTC');

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