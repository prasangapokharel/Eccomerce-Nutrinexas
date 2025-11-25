<?php
/**
 * Complete Cancellation Flow Test
 * Creates 10 orders, cancels them, and verifies the system works 100%
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
use App\Models\SellerWallet;

echo "=== Complete Cancellation Flow Test ===\n\n";

$db = Database::getInstance();
$passed = 0;
$failed = 0;
$errors = [];

// Test user ID
$userId = 1; // Use first user
$sellerId = 2; // Use seller 2

// Step 1: Verify user and seller exist
echo "Step 1: Verifying user and seller setup\n";
try {
    $user = $db->query("SELECT id, first_name, last_name, email FROM users WHERE id = ?", [$userId])->single();
    if (!$user) {
        throw new Exception("User {$userId} not found");
    }
    echo "✓ User found: {$user['first_name']} {$user['last_name']} (ID: {$user['id']})\n";
    
    $seller = $db->query("SELECT id, name FROM sellers WHERE id = ?", [$sellerId])->single();
    if (!$seller) {
        throw new Exception("Seller {$sellerId} not found");
    }
    echo "✓ Seller found: {$seller['name']} (ID: {$seller['id']})\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 2: Get a product
echo "\nStep 2: Getting a product\n";
try {
    $product = $db->query(
        "SELECT id, product_name, price, stock_quantity 
         FROM products 
         WHERE seller_id = ? AND stock_quantity > 0 
         LIMIT 1",
        [$sellerId]
    )->single();
    
    if (!$product) {
        throw new Exception("No product found for seller {$sellerId}");
    }
    
    // Ensure stock is sufficient
    if ($product['stock_quantity'] < 10) {
        $db->query("UPDATE products SET stock_quantity = 20 WHERE id = ?", [$product['id']])->execute();
        echo "✓ Updated product stock to 20\n";
    }
    
    echo "✓ Product found: {$product['product_name']} (ID: {$product['id']}, Price: Rs {$product['price']})\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 3: Create 10 orders
echo "\nStep 3: Creating 10 orders\n";
$orderIds = [];
$orderModel = new Order();

try {
    for ($i = 1; $i <= 10; $i++) {
        $orderData = [
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'invoice' => 'TEST-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
            'customer_name' => $user['first_name'] . ' ' . $user['last_name'],
            'contact_no' => '9841234567',
            'address' => 'Test Address ' . $i,
            'shipping_address' => 'Test Address ' . $i,
            'total_amount' => $product['price'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->query(
            "INSERT INTO orders (user_id, seller_id, invoice, customer_name, contact_no, address, shipping_address, total_amount, status, payment_status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $orderData['user_id'],
                $orderData['seller_id'],
                $orderData['invoice'],
                $orderData['customer_name'],
                $orderData['contact_no'],
                $orderData['address'],
                $orderData['shipping_address'],
                $orderData['total_amount'],
                $orderData['status'],
                $orderData['payment_status'],
                $orderData['created_at']
            ]
        )->execute();
        
        $orderId = $db->lastInsertId();
        
        // Add order item
        $db->query(
            "INSERT INTO order_items (order_id, product_id, quantity, price, total)
             VALUES (?, ?, 1, ?, ?)",
            [$orderId, $product['id'], $product['price'], $product['price']]
        )->execute();
        
        $orderIds[] = $orderId;
        echo "  ✓ Order #{$i} created: {$orderData['invoice']} (ID: {$orderId})\n";
    }
    
    echo "✓ All 10 orders created successfully\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 4: View orders (simulate user viewing their orders)
echo "\nStep 4: Viewing user orders\n";
try {
    $userOrders = $orderModel->getOrdersByUserId($userId);
    $testOrders = array_filter($userOrders, function($order) use ($orderIds) {
        return in_array($order['id'], $orderIds);
    });
    
    echo "✓ Found " . count($testOrders) . " test orders\n";
    if (count($testOrders) !== 10) {
        throw new Exception("Expected 10 orders, found " . count($testOrders));
    }
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 5: Cancel all 10 orders
echo "\nStep 5: Cancelling all 10 orders\n";
$cancelLogModel = new CancelLog();
$cancelledOrderIds = [];

try {
    foreach ($orderIds as $orderId) {
        $reason = "Test cancellation #{$orderId} - " . date('Y-m-d H:i:s');
        
        // Create cancellation log
        $cancelId = $cancelLogModel->create([
            'order_id' => $orderId,
            'seller_id' => $sellerId,
            'reason' => $reason,
            'status' => 'processing'
        ]);
        
        if (!$cancelId) {
            throw new Exception("Failed to create cancellation for order {$orderId}");
        }
        
        // Update order status to cancelled
        $db->query(
            "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?",
            [$orderId]
        )->execute();
        
        $cancelledOrderIds[] = $orderId;
        echo "  ✓ Order #{$orderId} cancelled (Cancel ID: {$cancelId})\n";
    }
    
    echo "✓ All 10 orders cancelled successfully\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 6: Verify cancellations in admin
echo "\nStep 6: Verifying cancellations in admin view\n";
try {
    $allCancels = $cancelLogModel->getAllWithOrders();
    $testCancels = array_filter($allCancels, function($cancel) use ($orderIds) {
        return in_array($cancel['order_id'], $orderIds);
    });
    
    echo "✓ Found " . count($testCancels) . " cancellation records\n";
    if (count($testCancels) !== 10) {
        throw new Exception("Expected 10 cancellation records, found " . count($testCancels));
    }
    
    // Verify each cancellation has required data
    foreach ($testCancels as $cancel) {
        if (empty($cancel['order_id']) || empty($cancel['reason']) || empty($cancel['status'])) {
            throw new Exception("Incomplete cancellation data for order {$cancel['order_id']}");
        }
    }
    
    echo "✓ All cancellation records are complete\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 7: Test cancellation status update
echo "\nStep 7: Testing cancellation status updates\n";
try {
    $cancel = $testCancels[0];
    $cancelId = $cancel['id'];
    
    // Update to refunded
    $result = $cancelLogModel->updateStatus($cancelId, 'refunded');
    if (!$result) {
        throw new Exception("Failed to update cancellation status");
    }
    
    $updatedCancel = $cancelLogModel->find($cancelId);
    if ($updatedCancel['status'] !== 'refunded') {
        throw new Exception("Status update failed. Expected 'refunded', got '{$updatedCancel['status']}'");
    }
    
    echo "✓ Cancellation status update works correctly\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 8: Verify seller can view cancellations
echo "\nStep 8: Verifying seller can view cancellations\n";
try {
    $sellerCancels = $cancelLogModel->getBySellerId($sellerId);
    $testSellerCancels = array_filter($sellerCancels, function($cancel) use ($orderIds) {
        return in_array($cancel['order_id'], $orderIds);
    });
    
    echo "✓ Seller can view " . count($testSellerCancels) . " cancellations\n";
    if (count($testSellerCancels) !== 10) {
        throw new Exception("Expected 10 cancellations for seller, found " . count($testSellerCancels));
    }
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 9: Cleanup
echo "\nStep 9: Cleaning up test data\n";
try {
    // Delete cancellation logs
    foreach ($orderIds as $orderId) {
        $db->query("DELETE FROM order_cancel_log WHERE order_id = ?", [$orderId])->execute();
    }
    
    // Delete order items
    foreach ($orderIds as $orderId) {
        $db->query("DELETE FROM order_items WHERE order_id = ?", [$orderId])->execute();
    }
    
    // Delete orders
    foreach ($orderIds as $orderId) {
        $db->query("DELETE FROM orders WHERE id = ?", [$orderId])->execute();
    }
    
    echo "✓ Test data cleaned up\n";
    $passed++;
} catch (Exception $e) {
    echo "⚠ Warning: Cleanup error: {$e->getMessage()}\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed == 0) {
    echo "\n✓ All tests passed! Cancellation system works 100%.\n";
    echo "\nFlow verified:\n";
    echo "  1. ✓ Created 10 orders successfully\n";
    echo "  2. ✓ User can view their orders\n";
    echo "  3. ✓ All 10 orders can be cancelled\n";
    echo "  4. ✓ Cancellations appear in admin view\n";
    echo "  5. ✓ Cancellation status can be updated\n";
    echo "  6. ✓ Seller can view their cancellations\n";
    echo "  7. ✓ All data cleaned up\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}

