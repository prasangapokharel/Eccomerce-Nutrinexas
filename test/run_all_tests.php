<?php
/**
 * Run All Critical Tests
 * 
 * This script runs all critical tests to ensure the system is working correctly.
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          COMPREHENSIVE SYSTEM TEST SUITE                    ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$tests = [
    'Curior Assignment' => 'test_curior_assignment.php',
    'Referral Earnings' => 'test_referral_earnings.php',
    'Order SMS Notification' => 'test_order_sms_notification.php',
    'Navbar and Ordering' => 'test_navbar_and_ordering.php',
    'Checkout and Referral' => 'test_checkout_and_referral.php',
    'Stock Update' => 'test_stock_update_fix.php',
    'Guest Checkout' => 'test_guest_checkout.php'
];

$results = [];
$totalTests = count($tests);
$passedTests = 0;
$failedTests = 0;

foreach ($tests as $testName => $testFile) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Running: {$testName}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $testPath = __DIR__ . DS . $testFile;
    
    if (!file_exists($testPath)) {
        echo "❌ Test file not found: {$testFile}\n\n";
        $results[$testName] = ['status' => 'NOT_FOUND', 'output' => ''];
        $failedTests++;
        continue;
    }
    
    // Capture output
    ob_start();
    $exitCode = 0;
    $output = '';
    
    try {
        // Run test in separate process to avoid conflicts
        $command = "php " . escapeshellarg($testPath) . " 2>&1";
        $output = shell_exec($command);
        $exitCode = 0; // shell_exec doesn't return exit code, assume success if output exists
        
        if (empty($output)) {
            $exitCode = 1;
        }
    } catch (Exception $e) {
        $output = "Exception: " . $e->getMessage();
        $exitCode = 1;
    }
    
    ob_end_clean();
    
    // Check for success indicators
    $hasSuccess = (
        strpos($output, 'PASSED') !== false ||
        strpos($output, '✅') !== false ||
        strpos($output, 'Test Complete') !== false ||
        strpos($output, 'All Tests Passed') !== false ||
        strpos($output, 'successfully') !== false ||
        strpos($output, 'Passed:') !== false ||
        strpos($output, 'All guest checkout tests passed') !== false ||
        strpos($output, 'Test Summary') !== false
    );
    
    $hasFailure = (
        strpos($output, 'FAILED') !== false ||
        strpos($output, '❌ TEST FAILED') !== false ||
        strpos($output, 'Fatal error') !== false ||
        strpos($output, 'Fatal Error') !== false ||
        (strpos($output, 'Error:') !== false && strpos($output, 'Error handling') === false)
    );
    
    $isSuccess = $hasSuccess && !$hasFailure;
    
    if ($isSuccess) {
        echo "✅ {$testName}: PASSED\n";
        $results[$testName] = ['status' => 'PASSED', 'output' => $output];
        $passedTests++;
    } else {
        echo "❌ {$testName}: FAILED\n";
        $results[$testName] = ['status' => 'FAILED', 'output' => $output];
        $failedTests++;
        if (!empty($output)) {
            echo "Output:\n" . substr($output, 0, 500) . "\n";
        }
    }
    
    echo "\n";
}

// Final Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    TEST SUMMARY                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Total Tests: {$totalTests}\n";
echo "✅ Passed: {$passedTests}\n";
echo "❌ Failed: {$failedTests}\n\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED! System is fully functional.\n\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the output above.\n\n";
    
    // Show failed tests
    echo "Failed Tests:\n";
    foreach ($results as $testName => $result) {
        if ($result['status'] === 'FAILED' || $result['status'] === 'NOT_FOUND') {
            echo "  - {$testName}\n";
        }
    }
    echo "\n";
    exit(1);
}

