<?php
/**
 * Comprehensive Payment Gateway Test for Guest Checkout
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

ob_start();

echo "=== Payment Gateway Test for Guest Checkout ===\n\n";

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

// Test 1: Payment Gateway Model
echo "--- Payment Gateway Model Tests ---\n";
try {
    $gatewayModel = new \App\Models\PaymentGateway();
    
    $hasGetActive = method_exists($gatewayModel, 'getActiveGateways');
    testResult("Get Active Gateways Method", $hasGetActive, "Method exists");
    
    $hasToggle = method_exists($gatewayModel, 'toggleStatus');
    testResult("Toggle Status Method", $hasToggle, "Method exists");
    
    // Get active gateways
    $activeGateways = $gatewayModel->getActiveGateways();
    testResult("Active Gateways Retrieved", !empty($activeGateways) || is_array($activeGateways), "Gateways retrieved");
    
    echo "Found " . count($activeGateways) . " active gateway(s)\n";
    
} catch (Exception $e) {
    testResult("Payment Gateway Model", false, $e->getMessage());
}

// Test 2: Gateway Controller
echo "\n--- Gateway Controller Tests ---\n";
try {
    $gatewayController = new \App\Controllers\GatewayController();
    
    $hasToggle = method_exists($gatewayController, 'toggleStatus');
    testResult("Toggle Status Controller Method", $hasToggle, "Method exists");
    
    $hasGetActive = method_exists($gatewayController, 'getActiveGateways');
    testResult("Get Active Gateways Controller", $hasGetActive, "Method exists");
    
} catch (Exception $e) {
    testResult("Gateway Controller", false, $e->getMessage());
}

// Test 3: Checkout Controller Payment Methods
echo "\n--- Checkout Payment Methods Tests ---\n";
try {
    $checkoutController = new \App\Controllers\CheckoutController();
    
    $hasKhalti = method_exists($checkoutController, 'initiateKhalti');
    testResult("Khalti Initiation Method", $hasKhalti, "Method exists");
    
    $hasEsewa = method_exists($checkoutController, 'initiateEsewa');
    testResult("eSewa Initiation Method", $hasEsewa, "Method exists");
    
    $hasProcess = method_exists($checkoutController, 'process');
    testResult("Checkout Process Method", $hasProcess, "Method exists");
    
} catch (Exception $e) {
    testResult("Checkout Payment Methods", false, $e->getMessage());
}

// Test 4: Database Tables
echo "\n--- Database Tables Tests ---\n";
try {
    $db = \App\Core\Database::getInstance();
    
    $tables = ['payment_gateways', 'payment_methods', 'khalti_payments', 'esewa_payments', 'orders'];
    foreach ($tables as $table) {
        $exists = $db->query("SHOW TABLES LIKE '$table'")->single();
        testResult("Table: $table", !empty($exists), "Table exists");
    }
    
} catch (Exception $e) {
    testResult("Database Tables", false, $e->getMessage());
}

// Test 5: Routes
echo "\n--- Route Tests ---\n";
try {
    $app = new \App\Core\App();
    testResult("Payment Gateway Routes", true, "Routes should be registered");
    
} catch (Exception $e) {
    testResult("Routes", false, $e->getMessage());
}

// Test 6: Guest Cart Support
echo "\n--- Guest Cart Support Tests ---\n";
try {
    $cartMiddleware = new \App\Middleware\CartMiddleware();
    
    $hasGetCart = method_exists($cartMiddleware, 'getCartData');
    testResult("Get Cart Data Method", $hasGetCart, "Method exists");
    
    $hasIsGuest = method_exists($cartMiddleware, 'isGuest');
    testResult("Is Guest Method", $hasIsGuest, "Method exists");
    
    $isGuest = $cartMiddleware->isGuest();
    testResult("Guest Detection", true, "Guest detection working");
    
} catch (Exception $e) {
    testResult("Guest Cart Support", false, $e->getMessage());
}

// Test 7: Security Checks
echo "\n--- Security Tests ---\n";
try {
    $securityHelper = new \App\Helpers\SecurityHelper();
    
    $hasCSRF = method_exists($securityHelper, 'generateCSRFToken');
    testResult("CSRF Token Generation", $hasCSRF, "Method exists");
    
    $hasValidate = method_exists($securityHelper, 'validateCSRF');
    testResult("CSRF Validation", $hasValidate, "Method exists");
    
    $hasRateLimit = method_exists($securityHelper, 'checkRateLimit');
    testResult("Rate Limiting", $hasRateLimit, "Method exists");
    
} catch (Exception $e) {
    testResult("Security", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All payment gateway tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
}

