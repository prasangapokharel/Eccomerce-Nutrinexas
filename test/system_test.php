<?php
/**
 * Comprehensive System Test Script
 * Tests checkout, SMS, staff code, and admin order display
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

echo "=== Nutrinexus System Test Suite ===\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

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

// Test 1: Database Connection
echo "\n--- Database Connection Test ---\n";
try {
    $db = \App\Core\Database::getInstance();
    $tables = $db->query("SHOW TABLES")->all();
    testResult("Database Connection", true, "Connected to database with " . count($tables) . " tables");
} catch (Exception $e) {
    testResult("Database Connection", false, $e->getMessage());
}

// Test 2: Check Orders with Zero Total Amount
echo "\n--- Admin Order Total Amount Test ---\n";
try {
    $orderModel = new \App\Models\Order();
    $orders = $orderModel->getAllOrders();
    
    $zeroAmountOrders = [];
    foreach ($orders as $order) {
        if (empty($order['total_amount']) || $order['total_amount'] == 0) {
            $zeroAmountOrders[] = [
                'id' => $order['id'],
                'invoice' => $order['invoice'] ?? 'N/A',
                'total_amount' => $order['total_amount'],
                'status' => $order['status'] ?? 'N/A'
            ];
        }
    }
    
    if (empty($zeroAmountOrders)) {
        testResult("Order Total Amount Display", true, "All orders have valid total amounts");
    } else {
        testResult("Order Total Amount Display", false, "Found " . count($zeroAmountOrders) . " orders with zero total amount", true);
        echo "  Orders with zero amount:\n";
        foreach ($zeroAmountOrders as $order) {
            echo "    - Order #{$order['invoice']} (ID: {$order['id']}) - Status: {$order['status']}\n";
        }
    }
} catch (Exception $e) {
    testResult("Order Total Amount Display", false, $e->getMessage());
}

// Test 3: CSRF Token Generation
echo "\n--- CSRF Security Test ---\n";
try {
    $token1 = \App\Helpers\SecurityHelper::generateCSRFToken();
    $token2 = \App\Helpers\SecurityHelper::generateCSRFToken();
    
    testResult("CSRF Token Generation", !empty($token1), "Token generated: " . substr($token1, 0, 10) . "...");
    testResult("CSRF Token Persistence", $token1 === $token2, "Token should persist in session");
    
    // Test validation
    $isValid = \App\Helpers\SecurityHelper::validateCSRF($token1);
    testResult("CSRF Token Validation", $isValid, "Token validation works");
    
    $isInvalid = \App\Helpers\SecurityHelper::validateCSRF("invalid_token");
    testResult("CSRF Token Invalid Check", !$isInvalid, "Invalid tokens are rejected");
} catch (Exception $e) {
    testResult("CSRF Security", false, $e->getMessage());
}

// Test 4: SMS Configuration
echo "\n--- SMS Configuration Test ---\n";
try {
    $smsController = new \App\Controllers\SMSController();
    
    // Check if SMS API config is loaded
    $reflection = new ReflectionClass($smsController);
    $apiConfigProp = $reflection->getProperty('apiConfig');
    $apiConfigProp->setAccessible(true);
    $apiConfig = $apiConfigProp->getValue($smsController);
    
    testResult("SMS API Configuration", !empty($apiConfig), "API config loaded");
    testResult("SMS API Key", !empty($apiConfig['api_key']), "API key is set");
    testResult("SMS Base URL", !empty($apiConfig['base_url']), "Base URL: " . ($apiConfig['base_url'] ?? 'N/A'));
    
    // Test SMS sending capability (dry run)
    $testPhone = "9765470926";
    echo "  Testing SMS to: $testPhone\n";
    echo "  Note: This is a configuration test only. Actual SMS sending requires API credentials.\n";
    
} catch (Exception $e) {
    testResult("SMS Configuration", false, $e->getMessage());
}

// Test 5: Staff Code Functionality
echo "\n--- Staff Code Test ---\n";
try {
    $staffModel = new \App\Models\Staff();
    
    // Check if staff table exists
    $db = \App\Core\Database::getInstance();
    $staffTable = $db->query("SHOW TABLES LIKE 'staff'")->single();
    testResult("Staff Table Exists", !empty($staffTable), "Staff table found");
    
    if (!empty($staffTable)) {
        $staffCount = $db->query("SELECT COUNT(*) as count FROM staff")->single();
        testResult("Staff Records", true, "Found " . ($staffCount['count'] ?? 0) . " staff records");
    }
    
} catch (Exception $e) {
    testResult("Staff Code", false, $e->getMessage());
}

// Test 6: Checkout Form CSRF Token
echo "\n--- Checkout Form Test ---\n";
try {
    $checkoutViewPath = ROOT . DS . 'App' . DS . 'views' . DS . 'checkout' . DS . 'index.php';
    if (file_exists($checkoutViewPath)) {
        $checkoutContent = file_get_contents($checkoutViewPath);
        $hasCSRFToken = strpos($checkoutContent, '_csrf_token') !== false;
        testResult("Checkout CSRF Token", $hasCSRFToken, "CSRF token field in checkout form");
    } else {
        testResult("Checkout Form", false, "Checkout view file not found");
    }
} catch (Exception $e) {
    testResult("Checkout Form", false, $e->getMessage());
}

// Test 7: Order Model Methods
echo "\n--- Order Model Test ---\n";
try {
    $orderModel = new \App\Models\Order();
    
    // Test getAllOrders
    $allOrders = $orderModel->getAllOrders();
    testResult("getAllOrders Method", is_array($allOrders), "Retrieved " . count($allOrders) . " orders");
    
    // Check if orders have total_amount
    if (!empty($allOrders)) {
        $sampleOrder = $allOrders[0];
        $hasTotalAmount = isset($sampleOrder['total_amount']);
        testResult("Order Total Amount Field", $hasTotalAmount, "Orders have total_amount field");
        
        if ($hasTotalAmount) {
            $validAmounts = array_filter($allOrders, function($o) {
                return isset($o['total_amount']) && $o['total_amount'] > 0;
            });
            $validPercentage = count($allOrders) > 0 ? (count($validAmounts) / count($allOrders)) * 100 : 0;
            testResult("Valid Total Amounts", $validPercentage > 80, 
                sprintf("%.1f%% of orders have valid amounts", $validPercentage));
        }
    }
    
} catch (Exception $e) {
    testResult("Order Model", false, $e->getMessage());
}

// Test 8: Database Indexes
echo "\n--- Database Indexes Test ---\n";
try {
    $db = \App\Core\Database::getInstance();
    
    $tablesToCheck = ['orders', 'products', 'cart', 'wishlist', 'users'];
    $indexesFound = 0;
    $indexesTotal = 0;
    
    foreach ($tablesToCheck as $table) {
        $indexes = $db->query("SHOW INDEXES FROM `$table`")->all();
        $indexesTotal += count($indexes);
        $indexesFound += count($indexes);
    }
    
    testResult("Database Indexes", $indexesFound > 0, "Found $indexesFound indexes across " . count($tablesToCheck) . " tables");
    
} catch (Exception $e) {
    testResult("Database Indexes", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";
echo "\nTotal Tests: " . ($results['passed'] + $results['failed'] + $results['warnings']) . "\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All critical tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the output above.\n";
}

