<?php
/**
 * NutriNexus Application Entry Point
 * 
 * This is the main entry point for the NutriNexus application in a production environment.
 * It routes all requests to the appropriate controllers.
 */

// Define the application root directory
define('APP_ROOT', __DIR__);

// Check if the request is for a static file
$requestUri = urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

// If the request is for a static file that exists, let the web server handle it
// This part is typically handled by the web server configuration, but this is a fallback
if ($requestUri !== "/" && file_exists(__DIR__ . "/public" . $requestUri)) {
    // For images, CSS, JS, etc.
    $extension = pathinfo($requestUri, PATHINFO_EXTENSION);
    
    // Set appropriate content type headers based on file extension
    switch ($extension) {
        case 'css':
            header('Content-Type: text/css');
            break;
        case 'js':
            header('Content-Type: application/javascript');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        // Add more content types as needed
    }
    
    // Output the file contents
    readfile(__DIR__ . "/public" . $requestUri);
    exit;
}

// For all other requests, route to the application's front controller
include_once __DIR__ . "/public/index.php";