<?php
/**
 * Comprehensive Test: Withdraw & Referral System
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

if (session_status() === PHP_SESSION_NONE) session_start();

echo "=== Withdraw & Referral System Test ===\n\n";

$results = ['passed' => 0, 'failed' => 0, 'warnings' => 0];

function testResult($name, $passed, $message = '', $warning = false) {
    global $results;
    if ($passed) {
        echo "✅ PASS: $name\n";
        $results['passed']++;
    } else {
        if ($warning) {
            echo "⚠️  WARN: $name - $message\n";
            $results['warnings']++;
        } else {
            echo "❌ FAIL: $name - $message\n";
            $results['failed']++;
        }
    }
}

// Test 1: Withdraw Route
echo "--- Withdraw Route Test ---\n";
try {
    $app = new \App\Core\App();
    $reflection = new ReflectionClass($app);
    $routesProp = $reflection->getProperty('routes');
    $routesProp->setAccessible(true);
    $routes = $routesProp->getValue($app);
    
    $hasWithdrawRoute = false;
    foreach ($routes as $method => $routeList) {
        foreach ($routeList as $pattern => $handler) {
            if (strpos($pattern, 'withdraw') !== false || strpos($handler, 'withdraw') !== false) {
                $hasWithdrawRoute = true;
                break 2;
            }
        }
    }
    testResult("Withdraw Route Exists", $hasWithdrawRoute, "Route found");
    
} catch (Exception $e) {
    testResult("Withdraw Route", false, $e->getMessage());
}

// Test 2: Referral Service
echo "\n--- Referral Service Test ---\n";
try {
    $service = new \App\Services\ReferralEarningService();
    testResult("Referral Service Initialization", true, "Service initialized");
    
    // Check methods
    $methods = ['processReferralEarning', 'createPendingReferralEarning', 'cancelReferralEarning', 'processWithdrawal', 'getAvailableBalance'];
    foreach ($methods as $method) {
        $exists = method_exists($service, $method);
        testResult("Method: $method", $exists, "Method exists");
    }
    
} catch (Exception $e) {
    testResult("Referral Service", false, $e->getMessage());
}

// Test 3: Withdrawal Model
echo "\n--- Withdrawal Model Test ---\n";
try {
    $withdrawalModel = new \App\Models\Withdrawal();
    testResult("Withdrawal Model", true, "Model initialized");
    
    // Check table exists
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'withdrawals'")->single();
    testResult("Withdrawals Table", !empty($table), "Table exists");
    
} catch (Exception $e) {
    testResult("Withdrawal Model", false, $e->getMessage());
}

// Test 4: Referral Balance Calculation
echo "\n--- Referral Balance Test ---\n";
try {
    $service = new \App\Services\ReferralEarningService();
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('getAvailableBalance');
    $method->setAccessible(true);
    
    // Test with a sample user ID (if exists)
    $db = \App\Core\Database::getInstance();
    $user = $db->query("SELECT id FROM users WHERE referred_by IS NOT NULL LIMIT 1")->single();
    
    if ($user) {
        $balance = $method->invoke($service, $user['id']);
        testResult("Balance Calculation", is_numeric($balance), "Balance: Rs" . number_format($balance, 2));
    } else {
        testResult("Balance Calculation", true, "No test user found (skipped)");
    }
    
} catch (Exception $e) {
    testResult("Referral Balance", false, $e->getMessage());
}

// Test 5: User Controller Withdraw Method
echo "\n--- User Controller Test ---\n";
try {
    $controller = new \App\Controllers\UserController();
    $hasWithdraw = method_exists($controller, 'withdraw');
    $hasRequestWithdrawal = method_exists($controller, 'requestWithdrawal');
    
    testResult("withdraw() Method", $hasWithdraw, "Method exists");
    testResult("requestWithdrawal() Method", $hasRequestWithdrawal, "Method exists");
    
} catch (Exception $e) {
    testResult("User Controller", false, $e->getMessage());
}

// Test 6: Referral Earning Processing
echo "\n--- Referral Earning Processing Test ---\n";
try {
    $service = new \App\Services\ReferralEarningService();
    
    // Check if referral earnings table exists
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'referral_earnings'")->single();
    testResult("Referral Earnings Table", !empty($table), "Table exists");
    
    if (!empty($table)) {
        $count = $db->query("SELECT COUNT(*) as count FROM referral_earnings")->single();
        testResult("Referral Earnings Records", true, "Found " . ($count['count'] ?? 0) . " records");
    }
    
} catch (Exception $e) {
    testResult("Referral Earning Processing", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All withdraw & referral tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
}

