<?php
/**
 * Bootstrap file - Compatible with shared hosting
 * Handles case sensitivity and path issues
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths early
define('APP_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

// Load Composer autoloader FIRST (before anything else)
$composerAutoload = PROJECT_ROOT . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // Fallback paths for shared hosting
    $fallbackPaths = [
        PROJECT_ROOT . '/Vendor/autoload.php', // Capital V
        PROJECT_ROOT . '/VENDOR/autoload.php', // All caps
        dirname(PROJECT_ROOT) . '/vendor/autoload.php', // One level up
    ];
    
    foreach ($fallbackPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

// Load configuration files with case-insensitive fallbacks
$configFiles = [
    'config/config.php',
    'config/database.php',
    'config/middleware.php'
];

foreach ($configFiles as $configFile) {
    $configPaths = [
        APP_ROOT . '/' . $configFile,
        APP_ROOT . '/' . ucfirst($configFile), // Config/config.php
        APP_ROOT . '/' . strtoupper(dirname($configFile)) . '/' . basename($configFile), // CONFIG/config.php
        PROJECT_ROOT . '/' . $configFile, // Try project root
    ];
    
    $loaded = false;
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }
    
    if (!$loaded) {
        error_log("Warning: Could not load config file: $configFile");
    }
}

// Load environment variables if .env exists
$envPaths = [
    PROJECT_ROOT . '/.env',
    PROJECT_ROOT . '/.ENV',
    dirname(PROJECT_ROOT) . '/.env'
];

foreach ($envPaths as $envPath) {
    if (file_exists($envPath) && class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(dirname($envPath));
            $dotenv->load();
            break;
        } catch (Exception $e) {
            error_log("Warning: Could not load .env file: " . $e->getMessage());
        }
    }
}

// Set error reporting based on environment
$environment = $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: 'production';

if ($environment === 'development' || $environment === 'dev') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

// Define constants with fallbacks
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', true);
}

// Define cache directory - prioritize App/storage/cache
$cachePaths = [
    PROJECT_ROOT . '/App/storage/cache',
    PROJECT_ROOT . '/storage/cache',
    PROJECT_ROOT . '/Storage/cache', // Capital S
    PROJECT_ROOT . '/STORAGE/cache', // All caps
    PROJECT_ROOT . '/tmp/cache',
    sys_get_temp_dir() . '/nutrinexus_cache' // System temp as last resort
];

$cacheDir = null;
foreach ($cachePaths as $path) {
    $dir = dirname($path);
    if (is_dir($dir) && is_writable($dir)) {
        $cacheDir = $path;
        break;
    }
}

if ($cacheDir) {
    define('CACHE_DIR', $cacheDir);
    
    // Create cache directory if it doesn't exist
    if (!is_dir(CACHE_DIR)) {
        if (!mkdir(CACHE_DIR, 0755, true)) {
            error_log("Warning: Could not create cache directory: " . CACHE_DIR);
        }
    }
} else {
    // Disable cache if no writable directory found
    define('CACHE_DIR', null);
    define('CACHE_ENABLED', false);
    error_log("Warning: No writable cache directory found, caching disabled");
}

// Custom autoloader for App classes (case-insensitive)
spl_autoload_register(function ($className) {
    // Only handle App namespace classes
    if (strpos($className, 'App\\') !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $classPath = str_replace('\\', '/', $className);
    $classPath = str_replace('App/', '', $classPath); // Remove App/ prefix
    
    // Possible file paths with case variations
    $possiblePaths = [
        APP_ROOT . '/' . $classPath . '.php',
        APP_ROOT . '/' . strtolower($classPath) . '.php',
        APP_ROOT . '/' . ucfirst(strtolower($classPath)) . '.php',
    ];
    
    // Also try with different case combinations for directories
    $pathParts = explode('/', $classPath);
    if (count($pathParts) > 1) {
        // Try with capitalized directory names
        $capitalizedPath = '';
        foreach ($pathParts as $part) {
            $capitalizedPath .= ucfirst(strtolower($part)) . '/';
        }
        $capitalizedPath = rtrim($capitalizedPath, '/');
        $possiblePaths[] = APP_ROOT . '/' . $capitalizedPath . '.php';
        
        // Try with all lowercase directories
        $lowercasePath = strtolower($classPath);
        $possiblePaths[] = APP_ROOT . '/' . $lowercasePath . '.php';
    }
    
    // Try to load the class file
    foreach ($possiblePaths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Log missing class for debugging
    error_log("Autoloader: Could not find class file for: $className");
});

// Function to safely get server variables
function getServerVar($key, $default = null) {
    return $_SERVER[$key] ?? $default;
}

// Function to safely get environment variables
function getEnvVar($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Set timezone with fallback
$timezone = getEnvVar('APP_TIMEZONE', 'Asia/Kathmandu');
if (function_exists('date_default_timezone_set')) {
    try {
        date_default_timezone_set($timezone);
    } catch (Exception $e) {
        date_default_timezone_set('UTC');
        error_log("Warning: Invalid timezone '$timezone', using UTC");
    }
}

// Initialize database connection early (if needed)
try {
    if (class_exists('App\Core\Database')) {
        error_log("Bootstrap: Initializing database connection");
        $db = App\Core\Database::getInstance();
        error_log("Bootstrap: Database connection initialized successfully");
    } else {
        error_log("Bootstrap: Database class not found");
    }
} catch (Exception $e) {
    error_log("Bootstrap: Database initialization error: " . $e->getMessage());
    error_log("Bootstrap: Database error stack trace: " . $e->getTraceAsString());
}

// Set memory limit for shared hosting
$memoryLimit = getEnvVar('PHP_MEMORY_LIMIT', '256M');
if (function_exists('ini_set')) {
    ini_set('memory_limit', $memoryLimit);
}

// Set execution time limit
$timeLimit = getEnvVar('PHP_TIME_LIMIT', 30);
if (function_exists('set_time_limit')) {
    set_time_limit($timeLimit);
}

// Initialize the application with error handling
try {
    if (class_exists('App\Core\App')) {
        $app = new App\Core\App();
    } else {
        throw new Exception('App\Core\App class not found');
    }
} catch (Exception $e) {
    error_log("Application initialization error: " . $e->getMessage());
    
    // Fallback error page for production
    if ($environment === 'production') {
        http_response_code(500);
        echo "<!DOCTYPE html>
<html>
<head>
    <title>Service Temporarily Unavailable</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <h1>Service Temporarily Unavailable</h1>
    <p>We're experiencing technical difficulties. Please try again later.</p>
</body>
</html>";
        exit;
    } else {
        // Show detailed error in development
        echo "<h1>Bootstrap Error</h1>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        exit;
    }
}

// Helper function to check if running on shared hosting
function isSharedHosting() {
    $indicators = [
        'SHARED_HOSTING' => true,
        'HTTP_X_FORWARDED_FOR' => true,
        'HTTP_X_REAL_IP' => true,
    ];
    
    foreach ($indicators as $key => $value) {
        if (isset($_SERVER[$key])) {
            return true;
        }
    }
    
    // Check for common shared hosting paths
    $sharedPaths = ['/home/', '/public_html/', '/www/', '/htdocs/'];
    $currentPath = __DIR__;
    
    foreach ($sharedPaths as $path) {
        if (strpos($currentPath, $path) !== false) {
            return true;
        }
    }
    
    return false;
}

// Log environment info for debugging (only in development)
if ($environment === 'development') {
    error_log("Bootstrap loaded successfully");
    error_log("Environment: " . $environment);
    error_log("PHP Version: " . PHP_VERSION);
    error_log("App Root: " . APP_ROOT);
    error_log("Project Root: " . PROJECT_ROOT);
    error_log("Cache Dir: " . (defined('CACHE_DIR') ? CACHE_DIR : 'Not set'));
    error_log("Shared Hosting: " . (isSharedHosting() ? 'Yes' : 'No'));
}
