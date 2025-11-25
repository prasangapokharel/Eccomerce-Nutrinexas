<?php
/**
 * Complete Test: Affiliate Commission Flow
 * Tests: Seller sets affiliate commission, user referred, buys order, delivered, paid, check stats
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
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ReferralEarningService;

echo "=== Complete Affiliate Commission Flow Test ===\n\n";

$db = Database::getInstance();
$userModel = new User();
$productModel = new Product();
$orderModel = new Order();
$orderItemModel = new OrderItem();
$settingModel = new \App\Models\Setting();
$referralService = new ReferralEarningService();

$passed = 0;
$failed = 0;

// Step 1: Get or create VIP referrer
echo "Step 1: Setting up VIP referrer\n";
try {
    $referrer = $db->query(
        "SELECT id, first_name, last_name, referral_code, sponsor_status 
         FROM users 
         WHERE sponsor_status = 'active' 
         AND referral_code IS NOT NULL 
         LIMIT 1"
    )->single();
    
    if (!$referrer) {
        $referrerData = [
            'first_name' => 'VIP',
            'last_name' => 'Referrer',
            'email' => 'vipref' . time() . '@test.com',
            'phone' => '984' . rand(1000000, 9999999),
            'password' => password_hash('test123', PASSWORD_DEFAULT),
            'referral_code' => 'VIP' . strtoupper(substr(md5(time()), 0, 6)),
            'sponsor_status' => 'active',
            'role' => 'customer'
        ];
        $referrerId = $userModel->create($referrerData);
        $referrer = $userModel->find($referrerId);
    }
    
    echo "✓ VIP Referrer: {$referrer['first_name']} {$referrer['last_name']} (ID: {$referrer['id']})\n";
    echo "✓ Referral Code: {$referrer['referral_code']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 2: Get or create seller
echo "\nStep 2: Setting up seller\n";
try {
    $seller = $db->query(
        "SELECT id, name, company_name 
         FROM sellers 
         WHERE status = 'active' 
         AND is_approved = 1 
         LIMIT 1"
    )->single();
    
    if (!$seller) {
        throw new Exception("No active seller found. Please create a seller first.");
    }
    
    echo "✓ Seller: {$seller['name']} (ID: {$seller['id']})\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 3: Create product with custom affiliate commission
echo "\nStep 3: Creating product with custom affiliate commission\n";
try {
    $productData = [
        'product_name' => 'Test Product ' . time(),
        'slug' => 'test-product-' . time(),
        'price' => 1000.00,
        'stock_quantity' => 100,
        'category' => 'Test',
        'status' => 'active',
        'seller_id' => $seller['id'],
        'affiliate_commission' => 15.00 // Custom 15% commission
    ];
    
    $productId = $productModel->create($productData);
    $product = $productModel->find($productId);
    
    if (!$product || $product['affiliate_commission'] != 15.00) {
        throw new Exception("Product affiliate_commission not set correctly");
    }
    
    echo "✓ Product created (ID: {$productId})\n";
    echo "✓ Product affiliate_commission: {$product['affiliate_commission']}%\n";
    echo "✓ Product price: Rs {$product['price']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 4: Create referred user
echo "\nStep 4: Creating referred user\n";
try {
    $referredUserData = [
        'full_name' => 'Referred User',
        'first_name' => 'Referred',
        'last_name' => 'User',
        'email' => 'referred' . time() . '@test.com',
        'phone' => '984' . rand(1000000, 9999999),
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'username' => 'referred' . time(),
        'referral_code' => 'REF' . strtoupper(substr(md5(time()), 0, 6)),
        'referred_by' => $referrer['id'],
        'role' => 'customer',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $referredUserId = $userModel->create($referredUserData);
    $referredUser = $userModel->find($referredUserId);
    
    if (!$referredUser || $referredUser['referred_by'] != $referrer['id']) {
        throw new Exception("Referred user not created correctly");
    }
    
    echo "✓ Referred user created (ID: {$referredUserId})\n";
    echo "✓ Referred by: {$referrer['first_name']} {$referrer['last_name']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 5: Create order
echo "\nStep 5: Creating order\n";
try {
    $invoice = 'INV-' . time();
    
    // Create order using direct SQL
    $orderId = $db->query(
        "INSERT INTO orders (invoice, user_id, customer_name, contact_no, total_amount, status, payment_status, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$invoice, $referredUserId, 'Referred User', '9841234567', 1000.00, 'pending', 'pending', 'Test Address']
    )->execute();
    
    if (!$orderId) {
        throw new Exception("Failed to create order");
    }
    
    $orderId = $db->lastInsertId();
    
    // Create order item using direct SQL
    $orderItemId = $db->query(
        "INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (?, ?, ?, ?)",
        [$orderId, $productId, 1, 1000.00]
    )->execute();
    
    if (!$orderItemId) {
        throw new Exception("Failed to create order item");
    }
    
    $orderItemId = $db->lastInsertId();
    
    echo "✓ Order created (ID: {$orderId})\n";
    echo "✓ Order item created (ID: {$orderItemId})\n";
    echo "✓ Order total: Rs 1000.00\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 6: Create pending referral earning
echo "\nStep 6: Creating pending referral earning\n";
try {
    $earningCreated = $referralService->createPendingReferralEarning($orderId);
    
    if (!$earningCreated) {
        throw new Exception("Failed to create pending referral earning");
    }
    
    $earning = $db->query(
        "SELECT * FROM referral_earnings WHERE order_id = ?",
        [$orderId]
    )->single();
    
    // Expected commission: 1000 * 15% = 150
    $expectedCommission = 150.00;
    
    if (abs($earning['amount'] - $expectedCommission) > 0.01) {
        throw new Exception("Commission amount incorrect. Expected: {$expectedCommission}, Got: {$earning['amount']}");
    }
    
    echo "✓ Pending referral earning created\n";
    echo "✓ Commission amount: Rs {$earning['amount']} (15% of Rs 1000)\n";
    echo "✓ Status: {$earning['status']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 7: Mark order as delivered and paid
echo "\nStep 7: Marking order as delivered and paid\n";
try {
    $orderModel->update($orderId, [
        'status' => 'delivered',
        'payment_status' => 'paid'
    ]);
    
    // Process referral earning
    $processed = $referralService->processReferralEarning($orderId);
    
    if (!$processed) {
        throw new Exception("Failed to process referral earning");
    }
    
    $earning = $db->query(
        "SELECT * FROM referral_earnings WHERE order_id = ?",
        [$orderId]
    )->single();
    
    if ($earning['status'] !== 'paid') {
        throw new Exception("Earning status not updated to paid");
    }
    
    echo "✓ Order marked as delivered and paid\n";
    echo "✓ Referral earning processed\n";
    echo "✓ Earning status: {$earning['status']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 8: Check referrer stats
echo "\nStep 8: Checking referrer stats\n";
try {
    $referrerUpdated = $userModel->find($referrer['id']);
    $expectedEarnings = 150.00;
    
    if (abs($referrerUpdated['referral_earnings'] - $expectedEarnings) > 0.01) {
        throw new Exception("Referrer earnings incorrect. Expected: {$expectedEarnings}, Got: {$referrerUpdated['referral_earnings']}");
    }
    
    $totalEarnings = $db->query(
        "SELECT SUM(amount) as total FROM referral_earnings WHERE user_id = ? AND status = 'paid'",
        [$referrer['id']]
    )->single();
    
    echo "✓ Referrer referral_earnings: Rs {$referrerUpdated['referral_earnings']}\n";
    echo "✓ Total paid earnings: Rs {$totalEarnings['total']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 9: Test with product without custom commission (should use default)
echo "\nStep 9: Testing with product without custom commission\n";
try {
    $product2Data = [
        'product_name' => 'Test Product 2 ' . time(),
        'slug' => 'test-product-2-' . time(),
        'price' => 500.00,
        'stock_quantity' => 100,
        'category' => 'Test',
        'status' => 'active',
        'seller_id' => $seller['id'],
        'affiliate_commission' => null // No custom commission
    ];
    
    $product2Id = $productModel->create($product2Data);
    
    $invoice2 = 'INV-2-' . time();
    
    // Create order 2 using direct SQL
    $order2Id = $db->query(
        "INSERT INTO orders (invoice, user_id, customer_name, contact_no, total_amount, status, payment_status, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$invoice2, $referredUserId, 'Referred User', '9841234567', 500.00, 'delivered', 'paid', 'Test Address']
    )->execute();
    
    if (!$order2Id) {
        throw new Exception("Failed to create order 2");
    }
    
    $order2Id = $db->lastInsertId();
    
    // Create order item 2 using direct SQL
    $orderItem2Id = $db->query(
        "INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (?, ?, ?, ?)",
        [$order2Id, $product2Id, 1, 500.00]
    )->execute();
    
    if (!$orderItem2Id) {
        throw new Exception("Failed to create order item 2");
    }
    
    $orderItem2Id = $db->lastInsertId();
    
    // Create pending earning
    $referralService->createPendingReferralEarning($order2Id);
    
    // Process earning
    $referralService->processReferralEarning($order2Id);
    
    $earning2 = $db->query(
        "SELECT * FROM referral_earnings WHERE order_id = ?",
        [$order2Id]
    )->single();
    
    $defaultCommissionRate = $settingModel->get('commission_rate', 10);
    $expectedCommission2 = 500.00 * ($defaultCommissionRate / 100);
    
    if (abs($earning2['amount'] - $expectedCommission2) > 0.01) {
        throw new Exception("Default commission incorrect. Expected: {$expectedCommission2}, Got: {$earning2['amount']}");
    }
    
    echo "✓ Product without custom commission uses default ({$defaultCommissionRate}%)\n";
    echo "✓ Commission amount: Rs {$earning2['amount']}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed == 0) {
    echo "\n✓ All tests passed! Affiliate commission system is working correctly.\n";
    echo "\nFeatures verified:\n";
    echo "  1. ✓ Seller can set affiliate commission per product\n";
    echo "  2. ✓ Product affiliate commission is used when calculating referral earnings\n";
    echo "  3. ✓ Default commission rate is used when product has no custom commission\n";
    echo "  4. ✓ Referral earnings are calculated correctly per order item\n";
    echo "  5. ✓ Referrer stats are updated correctly\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}

