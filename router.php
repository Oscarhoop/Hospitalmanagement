<?php
// Simple router for PHP built-in server
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route API requests to backend
if (preg_match('/^\/backend\/api\/(.+\.php)$/', $uri, $matches)) {
    $apiFile = __DIR__ . '/backend/api/' . $matches[1];
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
}

// Route backend requests
if (preg_match('/^\/backend\/(.+\.php)$/', $uri, $matches)) {
    $backendFile = __DIR__ . '/backend/' . $matches[1];
    if (file_exists($backendFile)) {
        require $backendFile;
        exit;
    }
}

// Serve static files
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // Let PHP's built-in server handle it
}

// Default to frontend/index.html for root access
if ($uri === '/' || $uri === '/index.html') {
    if (file_exists(__DIR__ . '/frontend/index.html')) {
        // Read and output the HTML file, adjusting paths for root access
        $html = file_get_contents(__DIR__ . '/frontend/index.html');
        // Fix CSS and JS paths to work from root
        $html = str_replace('href="css/', 'href="frontend/css/', $html);
        $html = str_replace('src="js/', 'src="frontend/js/', $html);
        // Fix API_BASE path in JavaScript (from '../backend/api/' to 'backend/api/')
        $html = str_replace("const API_BASE = '../backend/api/';", "const API_BASE = 'backend/api/';", $html);
        // Ensure Font Awesome CDN link is preserved (it's already absolute, so no change needed)
        echo $html;
        exit;
    }
}

// Serve other frontend files
if (preg_match('/^\/frontend\/(.+)$/', $uri, $matches)) {
    $frontendFile = __DIR__ . '/frontend/' . $matches[1];
    if (file_exists($frontendFile)) {
        return false; // Let PHP's built-in server handle it
    }
}

// 404
http_response_code(404);
echo '404 Not Found';

