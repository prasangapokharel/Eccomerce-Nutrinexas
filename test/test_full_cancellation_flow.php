<?php
/**
 * Complete Test: User cancels order → Shows in admin → Shows in seller
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
use App\Models\Order;
use App\Models\CancelLog;
use App\Models\User;
use App\Models\Product;

echo "=== Complete Cancellation Flow Test ===\n\n";

$db = Database::getInstance();
$passed = 0;
$failed = 0;

// Test user and seller
$userId = 1;
$sellerId = 2;

// Step 1: Create a test order
echo "Step 1: Creating test order\n";
try {
    $orderModel = new Order();
    $product = $db->query(
        "SELECT id, product_name, price FROM products WHERE seller_id = ? AND stock_quantity > 0 LIMIT 1",
        [$sellerId]
    )->single();
    
    if (!$product) {
        throw new Exception("No product found for seller {$sellerId}");
    }
    
    // Create order
    $db->query(
        "INSERT INTO orders (user_id, seller_id, invoice, customer_name, contact_no, address, shipping_address, total_amount, status, payment_status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())",
        [
            $userId,
            $sellerId,
            'TEST-CANCEL-' . time(),
            'Test User',
            '9841234567',
            'Test Address',
            'Test Address',
            $product['price']
        ]
    )->execute();
    
    $orderId = $db->lastInsertId();
    
    // Add order item
    $db->query(
        "INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, total)
         VALUES (?, ?, ?, 1, ?, ?)",
        [$orderId, $product['id'], $sellerId, $product['price'], $product['price']]
    )->execute();
    
    echo "✓ Order created: ID #{$orderId}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 2: User cancels order
echo "\nStep 2: User cancels order\n";
try {
    $cancelLogModel = new CancelLog();
    $reason = "Test cancellation - " . date('Y-m-d H:i:s');
    
    // Get order to verify seller_id
    $order = $db->query("SELECT seller_id FROM orders WHERE id = ?", [$orderId])->single();
    $orderSellerId = $order['seller_id'] ?? $sellerId;
    
    $cancelId = $cancelLogModel->create([
        'order_id' => $orderId,
        'seller_id' => $orderSellerId,
        'reason' => $reason,
        'status' => 'processing'
    ]);
    
    if (!$cancelId) {
        throw new Exception("Failed to create cancellation");
    }
    
    // Update order status
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
    
    echo "✓ Order cancelled successfully (Cancel ID: {$cancelId})\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 3: Verify cancellation appears in admin view
echo "\nStep 3: Verifying admin cancellations view\n";
try {
    $allCancels = $cancelLogModel->getAllWithOrders();
    $testCancel = null;
    
    foreach ($allCancels as $cancel) {
        if ($cancel['id'] == $cancelId) {
            $testCancel = $cancel;
            break;
        }
    }
    
    if (!$testCancel) {
        throw new Exception("Cancellation not found in admin view");
    }
    
    if (empty($testCancel['customer_email'])) {
        throw new Exception("Customer email missing in admin view");
    }
    
    echo "✓ Cancellation appears in admin view\n";
    echo "✓ Customer email: {$testCancel['customer_email']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 4: Verify cancellation appears in seller view
echo "\nStep 4: Verifying seller cancellations view\n";
try {
    $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status
            FROM order_cancel_log c
            LEFT JOIN orders o ON c.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE c.seller_id = ?
            ORDER BY c.created_at DESC";
    
    $sellerCancels = $db->query($sql, [$sellerId])->all();
    $testCancel = null;
    
    foreach ($sellerCancels as $cancel) {
        if ($cancel['id'] == $cancelId) {
            $testCancel = $cancel;
            break;
        }
    }
    
    if (!$testCancel) {
        throw new Exception("Cancellation not found in seller view");
    }
    
    if (empty($testCancel['customer_email'])) {
        throw new Exception("Customer email missing in seller view");
    }
    
    echo "✓ Cancellation appears in seller view\n";
    echo "✓ Customer email: {$testCancel['customer_email']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 5: Cleanup
echo "\nStep 5: Cleaning up test data\n";
try {
    $db->query("DELETE FROM order_cancel_log WHERE id = ?", [$cancelId])->execute();
    $db->query("DELETE FROM order_items WHERE order_id = ?", [$orderId])->execute();
    $db->query("DELETE FROM orders WHERE id = ?", [$orderId])->execute();
    echo "✓ Test data cleaned up\n";
    $passed++;
} catch (Exception $e) {
    echo "⚠ Warning: Cleanup error: {$e->getMessage()}\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed == 0) {
    echo "\n✓ All tests passed! Cancellation flow works perfectly.\n";
    echo "\nFlow verified:\n";
    echo "  1. ✓ Order created with seller_id\n";
    echo "  2. ✓ User can cancel order\n";
    echo "  3. ✓ Cancellation appears in admin view with customer email\n";
    echo "  4. ✓ Cancellation appears in seller view with customer email\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}

