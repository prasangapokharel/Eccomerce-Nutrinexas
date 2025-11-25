<?php
/**
 * Main entry point for the application
 */

// Set up error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Log the error
    error_log("PHP Error: $message in $file on line $line");
    
    // Redirect to 500 error page in production
    if (!defined('DEBUG') || !DEBUG) {
        http_response_code(500);
        header('Location: /500');
        exit;
    }
    
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Set up exception handling
set_exception_handler(function($exception) {
    // Log the exception
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    // Redirect to 500 error page in production
    if (!defined('DEBUG') || !DEBUG) {
        http_response_code(500);
        header('Location: /500');
        exit;
    }
    
    // In development, show the error
    echo "<h1>Uncaught Exception</h1>";
    echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
    echo "<pre>" . $exception->getTraceAsString() . "</pre>";
    exit;
});

// Error reporting - only show in development
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
// Define constants
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__DIR__));

define('APPROOT', ROOT . DS . 'App');

// Load Composer autoloader first
$composerAutoload = ROOT . DS . 'vendor' . DS . 'autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Load configuration to get BASE_URL
require_once APPROOT . DS . 'Config' . DS . 'config.php';

// Use BASE_URL from config as URLROOT
define('URLROOT', trim(BASE_URL));

// ASSETS_URL: In development we don't use /public; in production we add /public
// Use PRODUCTION constant from config.php to determine environment
if (defined('PRODUCTION') && PRODUCTION) {
    // Production environment: include /public in ASSETS_URL
    define('ASSETS_URL', URLROOT . '/public');
} else {
    // Development environment: no /public in ASSETS_URL
    define('ASSETS_URL', URLROOT);
}

// Autoload classes
spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize the application
$app = new App\Core\App();
