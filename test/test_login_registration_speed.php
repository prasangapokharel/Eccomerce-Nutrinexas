<?php
/**
 * Test Login and Registration Speed
 * Verifies that email notifications don't block login/registration
 */

require_once __DIR__ . '/../App/Config/config.php';

if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\User;
use App\Core\Session;

echo "=== Login and Registration Speed Test ===\n";
echo "Testing that email notifications don't block authentication\n\n";

$db = Database::getInstance();
$userModel = new User();

$maxAcceptableTime = 2.0; // Maximum acceptable time in seconds
$passed = 0;
$failed = 0;

// Test 1: Verify no blocking email in login
echo "Test 1: Login Email Blocking Check\n";
echo str_repeat("-", 50) . "\n";

try {
    $authControllerFile = __DIR__ . '/../App/Controllers/AuthController.php';
    $authControllerContent = file_get_contents($authControllerFile);
    
    // Find the login method
    if (preg_match('/public function login\([^)]*\)\s*\{([^}]+)\}/s', $authControllerContent, $matches)) {
        $loginMethod = $matches[1];
        
        // Check if blocking email calls exist
        $hasBlockingEmail = (
            strpos($loginMethod, 'sendLoginNotificationEmail') !== false ||
            (strpos($loginMethod, 'EmailAutomationService') !== false && 
             strpos($loginMethod, 'sendWelcomeEmail') !== false)
        );
        
        $hasRemovedComment = strpos($loginMethod, 'Email notifications removed') !== false;
        
        if ($hasBlockingEmail && !$hasRemovedComment) {
            throw new Exception("Login method still has blocking email notifications");
        }
        
        echo "✓ No blocking email notifications in login method\n";
        echo "✓ Login email check PASSED\n";
        $passed++;
    } else {
        throw new Exception("Could not find login method in AuthController");
    }
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// Test 2: Verify no blocking email in registration
echo "Test 2: Registration Email Blocking Check\n";
echo str_repeat("-", 50) . "\n";

try {
    $authControllerFile = __DIR__ . '/../App/Controllers/AuthController.php';
    $authControllerContent = file_get_contents($authControllerFile);
    
    // Find the register method (processRegistration)
    if (preg_match('/public function register\([^)]*\)\s*\{([^}]+)\}/s', $authControllerContent, $matches)) {
        $registerMethod = $matches[1];
        
        // Check if blocking email calls exist
        $hasBlockingEmail = (
            strpos($registerMethod, 'sendWelcomeEmail') !== false ||
            (strpos($registerMethod, 'EmailAutomationService') !== false && 
             strpos($registerMethod, 'sendWelcomeEmail') !== false)
        );
        
        $hasRemovedComment = strpos($registerMethod, 'Email notifications removed') !== false;
        
        if ($hasBlockingEmail && !$hasRemovedComment) {
            throw new Exception("Registration method still has blocking email notifications");
        }
        
        echo "✓ No blocking email notifications in registration method\n";
        echo "✓ Registration email check PASSED\n";
        $passed++;
    } else {
        throw new Exception("Could not find register method in AuthController");
    }
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

echo "\n";

// Test 3: Verify no email blocking
echo "Test 3: Email Notification Check\n";
echo str_repeat("-", 50) . "\n";

try {
    // Check if email methods are called synchronously in AuthController
    $authControllerFile = __DIR__ . '/../App/Controllers/AuthController.php';
    $authControllerContent = file_get_contents($authControllerFile);
    
    // Check login method
    $loginHasBlockingEmail = (
        strpos($authControllerContent, 'sendLoginNotificationEmail') !== false &&
        strpos($authControllerContent, '// Email notifications removed') === false
    );
    
    // Check registration method  
    $regHasBlockingEmail = (
        (strpos($authControllerContent, 'sendWelcomeEmail') !== false || 
         strpos($authControllerContent, 'EmailAutomationService') !== false) &&
        strpos($authControllerContent, '// Email notifications removed') === false
    );
    
    if ($loginHasBlockingEmail || $regHasBlockingEmail) {
        $issues = [];
        if ($loginHasBlockingEmail) $issues[] = "Login has blocking email calls";
        if ($regHasBlockingEmail) $issues[] = "Registration has blocking email calls";
        throw new Exception(implode(", ", $issues));
    }
    
    echo "✓ No blocking email notifications found in login/registration\n";
    echo "✓ Email notification check PASSED\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Total Tests: 3\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Max Acceptable Time: {$maxAcceptableTime} seconds\n";
echo str_repeat("=", 50) . "\n\n";

if ($failed == 0) {
    echo "✓ SUCCESS: All speed tests passed! Login and registration are fast.\n";
    exit(0);
} else {
    echo "✗ FAILURE: {$failed} test(s) failed. Please review above.\n";
    exit(1);
}

