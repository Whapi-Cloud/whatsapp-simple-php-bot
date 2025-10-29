# File: public/index.php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Whapi;
use App\Channel;

// Get the request URI and method
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and trim slashes
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Simple router
switch ($path) {
    case '':
        // Some deployments may strip the path and post the webhook to the root URL
        if ($requestMethod === 'POST') {
            $whapi = new Whapi();
            $whapi->whatsapp();
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Endpoint not found']);
        }
        break;
    case 'whatsapp':
        if ($requestMethod === 'POST') {
            $whapi = new Whapi();
            $whapi->whatsapp();
        } else {
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['error' => 'Method not allowed']);
        }
        break;
    case 'setup-webhook':
        if ($requestMethod === 'GET') {
            $config = require __DIR__ . '/../config/config.php';
            $channel = new Channel($config['whatsapp_token']);
            try {
                $ok = $channel->setWebHook();
                header('Content-Type: application/json');
                echo json_encode(['ok' => $ok]);
            } catch (\Throwable $e) {
                header('HTTP/1.1 500 Internal Server Error');
                echo json_encode(['error' => $e->getMessage()]);
            }
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