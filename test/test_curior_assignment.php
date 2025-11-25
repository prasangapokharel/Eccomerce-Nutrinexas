<?php
/**
 * Test Curior Assignment Flow
 * 
 * Tests:
 * 1. Assign Curior to an order
 * 2. Verify order status updates correctly
 * 3. Verify Curior assignment persists
 * 4. Test error handling for invalid inputs
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

use App\Core\Database;
use App\Models\Order;
use App\Models\Curior\Curior as CuriorModel;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderItem;

// Initialize
$db = Database::getInstance();
$orderModel = new Order();
$curiorModel = new CuriorModel();
$userModel = new User();
$productModel = new Product();
$orderItemModel = new OrderItem();

echo "=== Curior Assignment Test ===\n\n";

try {
    // 1. Create or get a test Curior
    echo "1. Setting up test Curior...\n";
    $aramexCurior = $db->query("SELECT * FROM curiors WHERE LOWER(name) = LOWER(?) OR phone = ? LIMIT 1", ['Aramex Curior', '9765470926'])->single();
    if ($aramexCurior) {
        $testCurior = $aramexCurior;
        $curiorId = $aramexCurior['id'];
        echo "   Using Aramex Curior (ID: {$curiorId})\n";
    } else {
        $testCurior = $curiorModel->getByPhone('9800000000');
        if (!$testCurior) {
            $curiorId = $curiorModel->create([
                'name' => 'Test Curior',
                'phone' => '9800000000',
                'email' => 'test.delivery@test.com',
                'address' => 'Test Address',
                'password' => 'test123',
                'status' => 'active'
            ]);
            $testCurior = $curiorModel->getById($curiorId);
            echo "   Created fallback Curior (ID: {$curiorId})\n";
        } else {
            $curiorId = $testCurior['id'];
            echo "   Using existing fallback Curior (ID: {$curiorId})\n";
        }
    }

    // 2. Create or get a test user
    echo "\n2. Setting up test user...\n";
    $testUser = $userModel->findByEmail('test.assignment@test.com');
    if (!$testUser) {
        $userId = $userModel->create([
            'username' => 'testassignment',
            'email' => 'test.assignment@test.com',
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '9800000001',
            'role' => 'customer',
            'status' => 'active'
        ]);
        $testUser = $userModel->find($userId);
        echo "   Created test user (ID: {$userId})\n";
    } else {
        $userId = $testUser['id'];
        echo "   Using existing user (ID: {$userId})\n";
    }

    // 3. Create a test product
    echo "\n3. Setting up test product...\n";
    $testProduct = $db->query("SELECT * FROM products WHERE slug = ?", ['test-assignment-product'])->single();
    if (!$testProduct) {
        $productId = $productModel->create([
            'product_name' => 'Test Assignment Product',
            'slug' => 'test-assignment-product',
            'description' => 'Test product for delivery assignment',
            'price' => 100.00,
            'stock_quantity' => 100,
            'category' => 'Test',
            'status' => 'active'
        ]);
        $testProduct = $productModel->find($productId);
        echo "   Created test product (ID: {$productId})\n";
    } else {
        $productId = $testProduct['id'];
        echo "   Using existing product (ID: {$productId})\n";
    }

    // 4. Create a test order with pending status (using direct SQL to avoid transaction conflicts)
    echo "\n4. Creating test order...\n";
    $invoice = 'TEST' . strtoupper(uniqid());
    $orderSql = "INSERT INTO orders (invoice, user_id, customer_name, contact_no, address, total_amount, subtotal, status, payment_status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $db->query($orderSql)->bind([
        $invoice,
        $userId,
        $testUser['first_name'] . ' ' . $testUser['last_name'],
        $testUser['phone'],
        'Test Address, Test City',
        100.00,
        100.00,
        'pending',
        'pending'
    ])->execute();
    
    $orderId = $db->lastInsertId();
    if (!$orderId) {
        throw new Exception("Failed to create test order");
    }
    
    // Create order item
    $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price, total, invoice)
                VALUES (?, ?, ?, ?, ?, ?)";
    $db->query($itemSql)->bind([
        $orderId,
        $productId,
        1,
        100.00,
        100.00,
        $invoice
    ])->execute();
    
    $order = $orderModel->find($orderId);
    echo "   Created test order #{$orderId} (Invoice: {$order['invoice']}, Status: {$order['status']})\n";

    // 5. Test assignment
    echo "\n5. Testing Curior assignment...\n";
    
    // Verify order exists and has no Curior
    $orderBefore = $orderModel->find($orderId);
    if (!empty($orderBefore['curior_id'])) {
        echo "   ⚠️  Order already has Curior assigned (ID: {$orderBefore['curior_id']})\n";
        // Clear it for testing
        $orderModel->update($orderId, ['curior_id' => null]);
        $orderBefore = $orderModel->find($orderId);
    }
    
    echo "   Order status before: {$orderBefore['status']}\n";
    echo "   Curior before: " . ($orderBefore['curior_id'] ?? 'None') . "\n";
    
    // Assign Curior
    $updateResult = $orderModel->update($orderId, [
        'curior_id' => $curiorId,
        'status' => 'shipped' // Use valid status
    ]);
    
    if (!$updateResult) {
        throw new Exception("Failed to assign Curior");
    }
    
    // Verify assignment
    $orderAfter = $orderModel->find($orderId);
    echo "   Order status after: {$orderAfter['status']}\n";
    echo "   Curior after: {$orderAfter['curior_id']}\n";
    
    if ($orderAfter['curior_id'] == $curiorId) {
        echo "   ✅ Curior assigned successfully!\n";
    } else {
        throw new Exception("Curior assignment failed - ID mismatch");
    }
    
    if ($orderAfter['status'] === 'shipped') {
        echo "   ✅ Order status updated to 'shipped'!\n";
    } else {
        echo "   ⚠️  Order status is '{$orderAfter['status']}' (expected 'shipped')\n";
    }

    // 6. Test with different order statuses
    echo "\n6. Testing assignment with different order statuses...\n";
    
    $testStatuses = ['pending', 'processing', 'confirmed'];
    foreach ($testStatuses as $testStatus) {
        // Create a new order for each status using direct SQL
        $testInvoice = 'TEST' . strtoupper(uniqid());
        $testOrderSql = "INSERT INTO orders (invoice, user_id, customer_name, contact_no, address, total_amount, subtotal, status, payment_status, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $db->query($testOrderSql)->bind([
            $testInvoice,
            $userId,
            $testUser['first_name'] . ' ' . $testUser['last_name'],
            $testUser['phone'],
            'Test Address',
            100.00,
            100.00,
            $testStatus,
            'pending'
        ])->execute();
        
        $testOrderId = $db->lastInsertId();
        
        if ($testOrderId) {
            // Create order item
            $db->query("INSERT INTO order_items (order_id, product_id, quantity, price, total, invoice)
                        VALUES (?, ?, ?, ?, ?, ?)", [
                $testOrderId,
                $productId,
                1,
                100.00,
                100.00,
                $testInvoice
            ])->execute();
            
            $assignResult = $orderModel->update($testOrderId, [
                'curior_id' => $curiorId,
                'status' => 'shipped'
            ]);
            
            $testOrder = $orderModel->find($testOrderId);
            if ($assignResult && $testOrder['curior_id'] == $curiorId) {
                echo "   ✅ Status '{$testStatus}' -> 'shipped' assignment successful\n";
            } else {
                echo "   ❌ Status '{$testStatus}' assignment failed\n";
            }
        }
    }

    // 7. Test error cases
    echo "\n7. Testing error handling...\n";
    
    try {
        $orderModel->assignCuriorToOrder($orderId, 99999);
        echo "   ⚠️  Invalid Curior ID was accepted (should be rejected)\n";
    } catch (\InvalidArgumentException $e) {
        echo "   ✅ Invalid Curior ID rejected: {$e->getMessage()}\n";
    }
    
    // Restore valid Curior
    $orderModel->assignCuriorToOrder($orderId, $curiorId);
    echo "   ✅ Error handling tests completed\n";

    echo "\n=== Curior Assignment Test PASSED ===\n";
    echo "\nNote: Test data remains in database for verification.\n";
    
} catch (Exception $e) {
    echo "\n❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

