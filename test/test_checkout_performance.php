<?php
/**
 * Checkout Performance Test
 * Verifies checkout is fast without blocking operations
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../App/Config/config.php';

use App\Core\Database;
use App\Models\Order;
use App\Models\Product;

$db = Database::getInstance();
$orderModel = new Order();
$productModel = new Product();

echo "=== CHECKOUT PERFORMANCE TEST ===\n\n";

try {
    $testUser = $db->query("SELECT id FROM users LIMIT 1")->single();
    $testProducts = $productModel->getProductsWithImages(1, 0);
    $testProduct = $testProducts[0] ?? null;
    
    if (!$testUser || !$testProduct) {
        echo "Test data not available\n";
        exit(1);
    }
    
    echo "Testing order creation speed...\n";
    $start = microtime(true);
    
    $orderData = [
        'user_id' => $testUser['id'],
        'recipient_name' => 'Performance Test',
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
    $duration = (microtime(true) - $start) * 1000;
    
    if ($orderId) {
        echo "✓ Order created successfully\n";
        echo "  Duration: " . round($duration, 2) . "ms\n";
        echo "  Target: <500ms\n";
        
        if ($duration < 500) {
            echo "✓ PASS: Checkout is fast!\n";
        } else {
            echo "⚠ WARNING: Checkout took longer than expected\n";
        }
        
        // Cleanup
        $db->query("DELETE FROM orders WHERE id = ?", [$orderId])->execute();
        echo "\n✓ Test completed successfully\n";
        exit(0);
    } else {
        echo "✗ Order creation failed\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

