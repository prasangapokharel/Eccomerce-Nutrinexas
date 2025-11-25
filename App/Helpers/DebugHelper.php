<?php

namespace App\Helpers;

/**
 * Debug Helper Class
 * Provides comprehensive debugging utilities
 */
class DebugHelper
{
    /**
     * Log debug information
     */
    public static function log($message, $context = [])
    {
        if (defined('DEBUG') && DEBUG) {
            $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message;
            if (!empty($context)) {
                $logMessage .= ' | Context: ' . json_encode($context);
            }
            error_log($logMessage);
        }
    }
    
    /**
     * Display debug information on screen
     */
    public static function display($data, $label = 'Debug')
    {
        if (defined('DEBUG') && DEBUG) {
            echo "<div style='background: #e3f2fd; border: 1px solid #2196f3; padding: 15px; margin: 10px; border-radius: 4px; font-family: monospace;'>";
            echo "<h4 style='color: #1976d2; margin: 0 0 10px 0;'>$label</h4>";
            echo "<pre style='margin: 0; white-space: pre-wrap; word-wrap: break-word;'>";
            print_r($data);
            echo "</pre>";
            echo "</div>";
        }
    }
    
    /**
     * Check if required constants are defined
     */
    public static function checkConstants()
    {
        $required = ['BASE_URL', 'DB_HOST', 'DB_NAME', 'DB_USER', 'URLROOT'];
        $missing = [];
        
        foreach ($required as $constant) {
            if (!defined($constant)) {
                $missing[] = $constant;
            }
        }
        
        if (!empty($missing)) {
            self::display($missing, 'Missing Constants');
            return false;
        }
        
        self::log('All required constants are defined');
        return true;
    }
    
    /**
     * Test database connection
     */
    public static function testDatabase()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new \PDO($dsn, DB_USER, DB_PASS, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            $result = $pdo->query("SELECT 1 as test")->fetch();
            self::log('Database connection test successful', $result);
            return true;
        } catch (\Exception $e) {
            self::display([
                'error' => $e->getMessage(),
                'host' => DB_HOST,
                'database' => DB_NAME,
                'user' => DB_USER
            ], 'Database Connection Failed');
            return false;
        }
    }
    
    /**
     * Test URL generation
     */
    public static function testUrls()
    {
        $testPaths = ['', 'products', 'products/view/test', 'auth/login'];
        $results = [];
        
        foreach ($testPaths as $path) {
            $url = \App\Core\View::url($path);
            $results[$path] = $url;
        }
        
        self::display($results, 'URL Generation Test');
        return $results;
    }
    
    /**
     * Check file permissions
     */
    public static function checkPermissions()
    {
        $directories = [
            'uploads' => ROOT_DIR . '/uploads',
            'cache' => ROOT_DIR . '/App/storage/cache',
            'logs' => ROOT_DIR . '/logs',
            'public' => ROOT_DIR . '/public'
        ];
        
        $results = [];
        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $results[$name] = [
                    'exists' => true,
                    'readable' => is_readable($path),
                    'writable' => is_writable($path),
                    'permissions' => substr(sprintf('%o', fileperms($path)), -4)
                ];
            } else {
                $results[$name] = ['exists' => false];
            }
        }
        
        self::display($results, 'Directory Permissions');
        return $results;
    }
    
    /**
     * Test image loading
     */
    public static function testImageLoading()
    {
        $testImages = [
            'default' => 'images/products/default.jpg',
            'logo' => 'images/logo.svg',
            'favicon' => 'favicon.ico'
        ];
        
        $results = [];
        foreach ($testImages as $name => $path) {
            $fullPath = ROOT_DIR . '/public/' . $path;
            $results[$name] = [
                'path' => $path,
                'exists' => file_exists($fullPath),
                'readable' => is_readable($fullPath),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 0
            ];
        }
        
        self::display($results, 'Image Loading Test');
        return $results;
    }
    
    /**
     * Run comprehensive debug check
     */
    public static function runFullCheck()
    {
        echo "<div style='background: #f5f5f5; padding: 20px; margin: 10px; border-radius: 8px;'>";
        echo "<h2 style='color: #333; margin: 0 0 20px 0;'>🔍 Comprehensive Debug Check</h2>";
        
        // Check constants
        echo "<h3>1. Constants Check</h3>";
        self::checkConstants();
        
        // Test database
        echo "<h3>2. Database Connection Test</h3>";
        self::testDatabase();
        
        // Test URLs
        echo "<h3>3. URL Generation Test</h3>";
        self::testUrls();
        
        // Check permissions
        echo "<h3>4. File Permissions Check</h3>";
        self::checkPermissions();
        
        // Test image loading
        echo "<h3>5. Image Loading Test</h3>";
        self::testImageLoading();
        
        echo "<p style='color: #4caf50; font-weight: bold; margin-top: 20px;'>✅ Debug check completed!</p>";
        echo "</div>";
    }
}
