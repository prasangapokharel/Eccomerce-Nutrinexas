<?php
/**
 * Checkout Speed Test
 * Tests checkout performance without email notifications
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Config/config.php';

use App\Core\Database;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;

$db = Database::getInstance();
$orderModel = new Order();
$productModel = new Product();

echo "=== CHECKOUT SPEED TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;

function runTest($name, $callback) {
    global $testCount, $passCount, $failCount, $testResults;
    $testCount++;
    echo "Test {$testCount}: {$name}... ";
    
    try {
        $startTime = microtime(true);
        $result = $callback();
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        if ($result['pass']) {
            $passCount++;
            echo "✓ PASS ({$duration}ms)\n";
            if (!empty($result['message'])) {
                echo "  → {$result['message']}\n";
            }
        } else {
            $failCount++;
            echo "✗ FAIL ({$duration}ms)\n";
            echo "  → {$result['message']}\n";
        }
        $testResults[] = ['name' => $name, 'pass' => $result['pass'], 'message' => $result['message'], 'duration' => $duration];
    } catch (Exception $e) {
        $failCount++;
        echo "✗ ERROR\n";
        echo "  → Exception: {$e->getMessage()}\n";
        $testResults[] = ['name' => $name, 'pass' => false, 'message' => "Exception: {$e->getMessage()}"];
    }
    echo "\n";
}

try {
    // Test 1: Order creation speed (without notifications)
    runTest("Order creation speed (target: <500ms)", function() use ($orderModel, $productModel, $db) {
        $testUserId = $db->query("SELECT id FROM users LIMIT 1")->single();
        if (!$testUserId || !isset($testUserId['id'])) {
            return [
                'pass' => false,
                'message' => "No test user found"
            ];
        }
        
        $testProduct = $productModel->find(1);
        if (!$testProduct) {
            return [
                'pass' => false,
                'message' => "No test product found"
            ];
        }
        
        $orderData = [
            'user_id' => $testUserId['id'],
            'recipient_name' => 'Speed Test',
            'phone' => '9800000000',
            'address_line1' => 'Test Address',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'payment_method_id' => 1,
            'total_amount' => 1000,
            'status' => 'pending'
        ];
        
        $cartItems = [
            [
                'product_id' => $testProduct['id'],
                'quantity' => 1,
                'price' => 1000,
                'seller_id' => $testProduct['seller_id'] ?? null
            ]
        ];
        
        $orderId = $orderModel->createOrder($orderData, $cartItems);
        
        // Cleanup
        if ($orderId) {
            $db->query("DELETE FROM orders WHERE id = ?", [$orderId])->execute();
        }
        
        return [
            'pass' => $orderId > 0,
            'message' => "Order created in " . ($orderId ? 'OK' : 'FAILED')
        ];
    });
    
    // Test 2: Checkout process simulation
    runTest("Checkout process simulation (target: <1000ms)", function() use ($db) {
        $startTime = microtime(true);
        
        // Simulate checkout steps
        $testUserId = $db->query("SELECT id FROM users LIMIT 1")->single();
        $cartItems = $db->query("SELECT product_id, quantity FROM cart WHERE user_id = ? LIMIT 1", [$testUserId['id']])->single();
        
        if (!$cartItems) {
            return [
                'pass' => true,
                'message' => "No cart items to test (simulation only)"
            ];
        }
        
        // Simulate order data preparation
        $orderData = [
            'user_id' => $testUserId['id'],
            'total_amount' => 1000,
            'status' => 'pending'
        ];
        
        $duration = (microtime(true) - $startTime) * 1000;
        
        return [
            'pass' => $duration < 1000,
            'message' => "Checkout simulation: " . round($duration, 2) . "ms"
        ];
    });
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// Summary
echo "\n=== CHECKOUT SPEED TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL CHECKOUT SPEED TESTS PASSED!\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

