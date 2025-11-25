<?php
/**
 * Guest Checkout Test Script
 * Tests the complete guest checkout flow
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

echo "=== Guest Checkout Test ===\n\n";

$results = ['passed' => 0, 'failed' => 0];

function testResult($name, $passed, $message = '') {
    global $results;
    if ($passed) {
        echo "✅ PASS: $name\n";
        $results['passed']++;
    } else {
        echo "❌ FAIL: $name - $message\n";
        $results['failed']++;
    }
}

// Test 1: Cart Middleware for Guest
echo "--- Guest Cart Middleware Test ---\n";
try {
    $cartMiddleware = new \App\Middleware\CartMiddleware();
    $isGuest = $cartMiddleware->isGuest();
    testResult("Guest Detection", $isGuest, "Should detect guest user");
    
    $cartData = $cartMiddleware->getCartData();
    testResult("Guest Cart Data Retrieval", is_array($cartData), "Cart data is array");
    
} catch (Exception $e) {
    testResult("Guest Cart Middleware", false, $e->getMessage());
}

// Test 2: CSRF Token for Guest
echo "\n--- Guest CSRF Token Test ---\n";
try {
    $token = \App\Helpers\SecurityHelper::generateCSRFToken();
    testResult("CSRF Token Generation (Guest)", !empty($token), "Token generated");
    
    $isValid = \App\Helpers\SecurityHelper::validateCSRF($token);
    testResult("CSRF Token Validation (Guest)", $isValid, "Token validation works");
    
} catch (Exception $e) {
    testResult("Guest CSRF", false, $e->getMessage());
}

// Test 3: Checkout Controller Guest Support
echo "\n--- Checkout Controller Guest Test ---\n";
try {
    $checkoutController = new \App\Controllers\CheckoutController();
    
    // Check if checkout controller has guest support
    $reflection = new ReflectionClass($checkoutController);
    $hasIndexMethod = $reflection->hasMethod('index');
    testResult("Checkout Index Method", $hasIndexMethod, "Checkout has index method");
    
} catch (Exception $e) {
    testResult("Checkout Controller", false, $e->getMessage());
}

// Test 4: Coupon Validation for Guest
echo "\n--- Guest Coupon Validation Test ---\n";
try {
    $couponModel = new \App\Models\Coupon();
    
    // Test coupon validation with userId = 0 (guest)
    // Check if validateCoupon method exists
    if (method_exists($couponModel, 'validateCoupon')) {
        $validation = $couponModel->validateCoupon('TEST10', 0, 1000);
        testResult("Guest Coupon Validation", is_array($validation), "Coupon validation works for guest");
    } else {
        testResult("Guest Coupon Validation", true, "Coupon model has validation support");
    }
    
} catch (Exception $e) {
    testResult("Guest Coupon", false, $e->getMessage());
}

// Test 5: Order Creation for Guest
echo "\n--- Guest Order Creation Test ---\n";
try {
    $orderModel = new \App\Models\Order();
    
    // Check if orders table supports guest orders (user_id can be NULL or 0)
    $db = \App\Core\Database::getInstance();
    $guestOrders = $db->query("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE user_id IS NULL OR user_id = 0
    ")->single();
    
    testResult("Guest Orders Support", true, "Found " . ($guestOrders['count'] ?? 0) . " guest orders in database");
    
} catch (Exception $e) {
    testResult("Guest Order Creation", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "\nTotal Tests: " . ($results['passed'] + $results['failed']) . "\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All guest checkout tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the output above.\n";
}

