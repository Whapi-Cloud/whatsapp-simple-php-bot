# File: public/index.php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Whapi;

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and trim slashes
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Simple router
switch ($path) {
    case 'whatsapp':
        if ($requestMethod === 'POST') {
            $whapi = new Whapi();
            $whapi->whatsapp();
        } else {
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    
    default:
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}