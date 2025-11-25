<?php
/**
 * SMS Test Script
 * Tests SMS sending to phone number 9765470926
 */

// Set up paths
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', ROOT);

// Load config first
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

// Autoload classes
spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "=== SMS Test Script ===\n\n";

$testPhone = "9765470926";
$testMessage = "Test SMS from Nutrinexus System - " . date('Y-m-d H:i:s');

echo "Testing SMS to: $testPhone\n";
echo "Message: $testMessage\n\n";

try {
    // Initialize SMS Controller
    $smsController = new \App\Controllers\SMSController();
    
    // Use reflection to access private sendBirSMS method
    $reflection = new ReflectionClass($smsController);
    $method = $reflection->getMethod('sendBirSMS');
    $method->setAccessible(true);
    
    echo "Sending SMS...\n";
    $result = $method->invoke($smsController, $testPhone, $testMessage);
    
    echo "\n=== SMS Result ===\n";
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
    
    if (isset($result['response'])) {
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    }
    
    if (isset($result['cost'])) {
        echo "Cost: Rs" . number_format($result['cost'], 2) . "\n";
    }
    
    if ($result['success']) {
        echo "\n✅ SMS sent successfully!\n";
    } else {
        echo "\n❌ SMS sending failed. Check API configuration.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

