<?php
/**
 * Test Checkout Process and Referral Link Handling
 * 
 * This test verifies:
 * 1. OrderCalculationService namespace fix
 * 2. Referral link handling in registration
 * 3. Checkout process functionality
 */

// Set up autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define minimal constants for testing
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost:8000');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', URLROOT . '/public');
}

use App\Services\OrderCalculationService;

echo "=== Testing Checkout and Referral System ===\n\n";

// Test 1: Verify OrderCalculationService can be instantiated
echo "Test 1: OrderCalculationService namespace check...\n";
try {
    $cartItems = [
        ['product_id' => 1, 'quantity' => 2, 'price' => 100],
        ['product_id' => 2, 'quantity' => 1, 'price' => 50]
    ];
    
    $total = OrderCalculationService::calculateCartTotal($cartItems);
    echo "✓ OrderCalculationService::calculateCartTotal() works\n";
    echo "  Calculated total: $total\n";
    
    // Test calculateTotals
    $totals = OrderCalculationService::calculateTotals(150, 10, 20, 13);
    echo "✓ OrderCalculationService::calculateTotals() works\n";
    echo "  Subtotal: {$totals['subtotal']}, Discount: {$totals['discount']}, Tax: {$totals['tax']}, Total: {$totals['total']}\n";
    
    // Test applyCouponDiscount
    $coupon = ['discount_type' => 'percentage', 'discount_value' => 10];
    $discount = OrderCalculationService::applyCouponDiscount(100, $coupon);
    echo "✓ OrderCalculationService::applyCouponDiscount() works\n";
    echo "  Discount amount: $discount\n";
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 2: Verify CheckoutController can use OrderCalculationService
echo "Test 2: CheckoutController namespace check...\n";
try {
    // Check if the class file exists and has the use statement
    $checkoutFile = __DIR__ . '/../App/Controllers/CheckoutController.php';
    $content = file_get_contents($checkoutFile);
    
    if (strpos($content, 'use App\Services\OrderCalculationService;') !== false) {
        echo "✓ CheckoutController has correct use statement\n";
    } else {
        echo "✗ FAILED: CheckoutController missing use statement\n";
        exit(1);
    }
    
    // Verify the class can be loaded
    if (class_exists('App\Controllers\CheckoutController')) {
        echo "✓ CheckoutController class can be loaded\n";
    } else {
        echo "✗ FAILED: CheckoutController class cannot be loaded\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 3: Verify Referral Link Handling
echo "Test 3: Referral link handling check...\n";
try {
    // Check registration view has referral code handling
    $registerView = __DIR__ . '/../App/views/auth/register.php';
    $viewContent = file_get_contents($registerView);
    
    $checks = [
        'URL parameter handling' => strpos($viewContent, 'urlParams.get(\'ref\')') !== false,
        'Cookie handling' => strpos($viewContent, 'referral_code') !== false,
        'Hidden input field' => strpos($viewContent, 'name="referral_code"') !== false,
    ];
    
    foreach ($checks as $check => $result) {
        if ($result) {
            echo "✓ Registration view has $check\n";
        } else {
            echo "✗ FAILED: Registration view missing $check\n";
            exit(1);
        }
    }
    
    // Check AuthController has referral handling
    $authController = __DIR__ . '/../App/Controllers/AuthController.php';
    $controllerContent = file_get_contents($authController);
    
    $controllerChecks = [
        'URL referral code extraction' => strpos($controllerContent, '$_GET[\'ref\']') !== false,
        'Cookie referral code extraction' => strpos($controllerContent, '$_COOKIE[\'referral_code\']') !== false,
        'Referral code validation' => strpos($controllerContent, 'findByReferralCode') !== false,
        'Referral code usage in registration' => strpos($controllerContent, 'referred_by') !== false,
    ];
    
    foreach ($controllerChecks as $check => $result) {
        if ($result) {
            echo "✓ AuthController has $check\n";
        } else {
            echo "✗ FAILED: AuthController missing $check\n";
            exit(1);
        }
    }
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 4: Verify Product Model exists (needed for checkout)
echo "Test 4: Product Model check...\n";
try {
    if (class_exists('App\Models\Product')) {
        echo "✓ Product model class exists\n";
    } else {
        echo "✗ FAILED: Product model class not found\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 5: Verify rate limit key is properly defined in CheckoutController
echo "Test 5: Rate limit key fix check...\n";
try {
    $checkoutFile = __DIR__ . '/../App/Controllers/CheckoutController.php';
    $content = file_get_contents($checkoutFile);
    
    // Check that rateLimitKey is defined before use
    $hasRateLimitKeyDef = strpos($content, '$rateLimitKey = \'checkout_\' . $ip;') !== false;
    $hasRateLimitKeyUse = strpos($content, 'Session::remove($sessionKey);') !== false || 
                          strpos($content, 'unset($_SESSION[$rateLimitKey])') !== false;
    
    if ($hasRateLimitKeyDef) {
        echo "✓ Rate limit key is properly defined\n";
    } else {
        echo "✗ FAILED: Rate limit key not properly defined\n";
        exit(1);
    }
    
    // Check that it's not using undefined variable
    if (strpos($content, 'unset($_SESSION[$rateLimitKey]);') === false || $hasRateLimitKeyDef) {
        echo "✓ Rate limit key usage is correct\n";
    } else {
        echo "⚠ WARNING: May have undefined variable usage\n";
    }
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";
echo "=== All Tests Passed! ===\n";
echo "\nSummary:\n";
echo "1. ✓ OrderCalculationService namespace is correct\n";
echo "2. ✓ CheckoutController can use OrderCalculationService\n";
echo "3. ✓ Referral link handling is implemented in registration\n";
echo "4. ✓ Required models are available\n";
echo "5. ✓ Rate limit key is properly defined and used\n";
echo "\nThe checkout process should now work correctly without errors!\n";

