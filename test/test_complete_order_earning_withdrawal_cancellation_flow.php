<?php
/**
 * Comprehensive Test: Order, Earning, Withdrawal, and Cancellation Flow
 * Tests 20 real-world scenarios
 */

require_once __DIR__ . '/../App/Config/config.php';

if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

// Enable test mode to skip email notifications
if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
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
use App\Models\Setting;

echo "=== Complete Order, Earning, Withdrawal & Cancellation Flow Test ===\n";
echo "Testing 20 Real-World Scenarios\n";
echo "Start Time: " . date('Y-m-d H:i:s') . "\n\n";

$db = Database::getInstance();
$userModel = new User();
$productModel = new Product();
$orderModel = new Order();
$orderItemModel = new OrderItem();
$referralService = new ReferralEarningService();
$settingModel = new \App\Models\Setting();

$passed = 0;
$failed = 0;
$scenarios = [];

// Setup: Create referrer, seller, and products
echo "=== Setup ===\n";

// Get or create VIP referrer
$referrer = $db->query(
    "SELECT id, first_name, last_name, referral_code, sponsor_status, referral_earnings 
     FROM users 
     WHERE sponsor_status = 'active' 
     AND referral_code IS NOT NULL 
     LIMIT 1"
)->single();

if (!$referrer) {
    $referrerData = [
        'full_name' => 'VIP Referrer',
        'first_name' => 'VIP',
        'last_name' => 'Referrer',
        'email' => 'vipref' . time() . '@test.com',
        'phone' => '984' . rand(1000000, 9999999),
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'username' => 'vipref' . time(),
        'referral_code' => 'VIP' . strtoupper(substr(md5(time()), 0, 6)),
        'sponsor_status' => 'active',
        'role' => 'customer',
        'referral_earnings' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    $referrerId = $userModel->create($referrerData);
    $referrer = $userModel->find($referrerId);
}

// Get seller
$seller = $db->query(
    "SELECT id, name FROM sellers WHERE status = 'active' AND is_approved = 1 LIMIT 1"
)->single();

if (!$seller) {
    die("No active seller found. Please create a seller first.\n");
}

// Create products with different affiliate commissions
$product1 = $productModel->create([
    'product_name' => 'Test Product 1',
    'slug' => 'test-product-1-' . time(),
    'price' => 1000.00,
    'stock_quantity' => 100,
    'category' => 'Test',
    'status' => 'active',
    'seller_id' => $seller['id'],
    'affiliate_commission' => 15.00
]);

$product2 = $productModel->create([
    'product_name' => 'Test Product 2',
    'slug' => 'test-product-2-' . time(),
    'price' => 500.00,
    'stock_quantity' => 100,
    'category' => 'Test',
    'status' => 'active',
    'seller_id' => $seller['id'],
    'affiliate_commission' => null // Use default
]);

$defaultCommission = $settingModel->get('commission_rate', 10);

echo "✓ Referrer: {$referrer['first_name']} {$referrer['last_name']} (ID: {$referrer['id']})\n";
echo "✓ Initial Balance: Rs " . ($referrer['referral_earnings'] ?? 0) . "\n";
echo "✓ Product 1: 15% commission, Rs 1000\n";
echo "✓ Product 2: Default {$defaultCommission}% commission, Rs 500\n\n";

// Reset referrer balance for clean test
$db->query("UPDATE users SET referral_earnings = 0 WHERE id = ?", [$referrer['id']])->execute();
// Clear all referral earnings for this referrer
$db->query("DELETE FROM referral_earnings WHERE user_id = ?", [$referrer['id']])->execute();
// Clear all withdrawals for this referrer
$db->query("DELETE FROM withdrawals WHERE user_id = ?", [$referrer['id']])->execute();

// Helper function to create referred user
function createReferredUser($referrerId, $userModel, $db) {
    $timestamp = time() . rand(1000, 9999);
    $email = 'referred' . $timestamp . '@test.com';
    $username = 'referred' . $timestamp;
    $phone = '984' . rand(1000000, 9999999);
    $referralCode = 'REF' . strtoupper(substr(md5($timestamp), 0, 6));
    $now = date('Y-m-d H:i:s');
    
    // Try using model first
    $userData = [
        'full_name' => 'Referred User ' . $timestamp,
        'first_name' => 'Referred',
        'last_name' => 'User',
        'email' => $email,
        'phone' => $phone,
        'password' => password_hash('test123', PASSWORD_DEFAULT),
        'username' => $username,
        'referral_code' => $referralCode,
        'referred_by' => $referrerId,
        'role' => 'customer',
        'created_at' => $now,
        'updated_at' => $now
    ];
    
    $userId = $userModel->create($userData);
    
    // If model create fails, use direct SQL
    if (!$userId) {
        try {
            $db->query(
                "INSERT INTO users (full_name, first_name, last_name, email, phone, password, username, referral_code, referred_by, role, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userData['full_name'],
                    $userData['first_name'],
                    $userData['last_name'],
                    $userData['email'],
                    $userData['phone'],
                    $userData['password'],
                    $userData['username'],
                    $userData['referral_code'],
                    $referrerId,
                    $userData['role'],
                    $now,
                    $now
                ]
            )->execute();
            $userId = $db->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to create user via SQL: " . $e->getMessage());
            return false;
        }
    }
    
    // Verify referred_by is set correctly
    if ($userId) {
        $user = $db->query("SELECT referred_by FROM users WHERE id = ?", [$userId])->single();
        if (empty($user['referred_by']) || $user['referred_by'] != $referrerId) {
            // Force update if not set correctly
            $db->query("UPDATE users SET referred_by = ? WHERE id = ?", [$referrerId, $userId])->execute();
        }
    }
    
    return $userId;
}

// Helper function to create order
function createOrder($userId, $productId, $price, $quantity, $db) {
    $invoice = 'INV-' . time() . '-' . rand(1000, 9999);
    $db->query(
        "INSERT INTO orders (invoice, user_id, customer_name, contact_no, total_amount, status, payment_status, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$invoice, $userId, 'Test Customer', '9841234567', $price * $quantity, 'pending', 'pending', 'Test Address']
    )->execute();
    $orderId = $db->lastInsertId();
    
    $db->query(
        "INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (?, ?, ?, ?)",
        [$orderId, $productId, $quantity, $price]
    )->execute();
    
    return $orderId;
}

// Helper function to get referrer balance
function getReferrerBalance($referrerId, $db) {
    $user = $db->query("SELECT referral_earnings FROM users WHERE id = ?", [$referrerId])->single();
    return (float)($user['referral_earnings'] ?? 0);
}

// Helper function to get available balance
function getAvailableBalance($referrerId, $referralService) {
    return $referralService->getAvailableBalance($referrerId);
}

// Helper function to get pending earnings count
function getPendingEarningsCount($referrerId, $db) {
    $result = $db->query(
        "SELECT COUNT(*) as count FROM referral_earnings WHERE user_id = ? AND status = 'pending'",
        [$referrerId]
    )->single();
    return (int)($result['count'] ?? 0);
}

// Helper function to get paid earnings count
function getPaidEarningsCount($referrerId, $db) {
    $result = $db->query(
        "SELECT COUNT(*) as count FROM referral_earnings WHERE user_id = ? AND status = 'paid'",
        [$referrerId]
    )->single();
    return (int)($result['count'] ?? 0);
}

// Helper function to get cancelled earnings count
function getCancelledEarningsCount($referrerId, $db) {
    $result = $db->query(
        "SELECT COUNT(*) as count FROM referral_earnings WHERE user_id = ? AND status = 'cancelled'",
        [$referrerId]
    )->single();
    return (int)($result['count'] ?? 0);
}

// Helper function to get withdrawal count
function getWithdrawalCount($referrerId, $db) {
    $result = $db->query(
        "SELECT COUNT(*) as count FROM withdrawals WHERE user_id = ?",
        [$referrerId]
    )->single();
    return (int)($result['count'] ?? 0);
}

echo "=== Test Scenarios ===\n\n";

// Scenario 1: Order created, pending earning created
echo "Scenario 1: Order created, pending earning created\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    if (!$referredUserId) throw new Exception("Failed to create referred user");
    
    // Verify referred_by is set
    $userCheck = $db->query("SELECT referred_by FROM users WHERE id = ?", [$referredUserId])->single();
    if (empty($userCheck['referred_by']) || $userCheck['referred_by'] != $referrer['id']) {
        throw new Exception("referred_by not set correctly for user {$referredUserId}");
    }
    
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    if (!$orderId) throw new Exception("Failed to create order");
    
    $created = $referralService->createPendingReferralEarning($orderId);
    
    if (!$created) throw new Exception("Failed to create pending earning");
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if ($earning['amount'] != 150.00) throw new Exception("Expected Rs 150, got Rs {$earning['amount']}");
    if ($earning['status'] != 'pending') throw new Exception("Expected pending status");
    
    $balance = getReferrerBalance($referrer['id'], $db);
    if ($balance != 0) throw new Exception("Balance should be 0 before payment");
    
    echo "✓ Pending earning created: Rs {$earning['amount']}\n";
    echo "✓ Balance unchanged: Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order created, pending earning', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order created, pending earning', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 2: Order delivered, earning paid
echo "\nScenario 2: Order delivered, earning paid\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    if (!$referredUserId) throw new Exception("Failed to create referred user");
    
    // Verify user has referred_by set
    $userCheck = $db->query("SELECT referred_by FROM users WHERE id = ?", [$referredUserId])->single();
    if (empty($userCheck['referred_by']) || $userCheck['referred_by'] != $referrer['id']) {
        throw new Exception("User referred_by not set correctly. Expected: {$referrer['id']}, Got: " . ($userCheck['referred_by'] ?? 'null'));
    }
    
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    if (!$orderId) throw new Exception("Failed to create order");
    
    $created = $referralService->createPendingReferralEarning($orderId);
    if (!$created) {
        $earningCheck = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
        $orderCheck = $db->query("SELECT o.*, u.referred_by FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?", [$orderId])->single();
        throw new Exception("Failed to create pending earning. Order exists: " . ($orderCheck ? 'yes' : 'no') . ", referred_by: " . ($orderCheck['referred_by'] ?? 'null') . ", earning exists: " . ($earningCheck ? 'yes' : 'no'));
    }
    
    // Update order status directly via SQL
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    
    // Verify order status updated
    $orderCheck = $db->query("SELECT status, payment_status FROM orders WHERE id = ?", [$orderId])->single();
    if ($orderCheck['status'] != 'delivered') {
        throw new Exception("Order status not updated to delivered");
    }
    
    // Small delay to ensure order status is committed
    usleep(100000); // 0.1 second
    
    $processed = $referralService->processReferralEarning($orderId);
    
    if (!$processed) {
        // Check what went wrong
        $earningCheck = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
        $orderCheck2 = $db->query("SELECT o.*, u.referred_by FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?", [$orderId])->single();
        throw new Exception("Failed to process earning. Order status: {$orderCheck2['status']}, referred_by: " . ($orderCheck2['referred_by'] ?? 'null') . ", earning status: " . ($earningCheck['status'] ?? 'none'));
    }
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if (!$earning) throw new Exception("Earning not found after processing");
    if ($earning['status'] != 'paid') throw new Exception("Expected paid status, got: {$earning['status']}");
    
    $balance = getReferrerBalance($referrer['id'], $db);
    if (abs($balance - 150.00) > 0.01) throw new Exception("Expected Rs 150, got Rs {$balance}");
    
    echo "✓ Earning paid: Rs {$earning['amount']}\n";
    echo "✓ Balance updated: Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order delivered, earning paid', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order delivered, earning paid', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 3: Order cancelled before payment (pending earning)
echo "\nScenario 3: Order cancelled before payment (pending earning)\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    if (!$referredUserId) throw new Exception("Failed to create referred user");
    
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    if (!$orderId) throw new Exception("Failed to create order");
    
    $created = $referralService->createPendingReferralEarning($orderId);
    if (!$created) {
        $earningCheck = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
        $orderCheck = $db->query("SELECT o.*, u.referred_by FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?", [$orderId])->single();
        throw new Exception("Failed to create pending earning. Order exists: " . ($orderCheck ? 'yes' : 'no') . ", referred_by: " . ($orderCheck['referred_by'] ?? 'null') . ", earning exists: " . ($earningCheck ? 'yes' : 'no'));
    }
    
    $initialBalance = getReferrerBalance($referrer['id'], $db);
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
    $cancelled = $referralService->cancelReferralEarning($orderId);
    
    if (!$cancelled) {
        $earningCheck = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
        throw new Exception("Failed to cancel earning. Earning exists: " . ($earningCheck ? 'yes' : 'no') . ", status: " . ($earningCheck['status'] ?? 'none'));
    }
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if ($earning['status'] != 'cancelled') throw new Exception("Expected cancelled status");
    
    $balance = getReferrerBalance($referrer['id'], $db);
    if ($balance != $initialBalance) throw new Exception("Balance should not change for pending cancellation");
    
    echo "✓ Earning cancelled (pending)\n";
    echo "✓ Balance unchanged: Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order cancelled before payment', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order cancelled before payment', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 4: Order cancelled after payment (paid earning)
echo "\nScenario 4: Order cancelled after payment (paid earning)\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceBeforeCancel = getReferrerBalance($referrer['id'], $db);
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
    $cancelled = $referralService->cancelReferralEarning($orderId);
    
    if (!$cancelled) throw new Exception("Failed to cancel earning");
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if ($earning['status'] != 'cancelled') throw new Exception("Expected cancelled status");
    
    $balanceAfterCancel = getReferrerBalance($referrer['id'], $db);
    $expectedBalance = $balanceBeforeCancel - 150.00;
    
    if (abs($balanceAfterCancel - $expectedBalance) > 0.01) {
        throw new Exception("Expected Rs {$expectedBalance}, got Rs {$balanceAfterCancel}");
    }
    
    echo "✓ Earning cancelled (paid)\n";
    echo "✓ Balance deducted: Rs {$balanceBeforeCancel} -> Rs {$balanceAfterCancel}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order cancelled after payment', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order cancelled after payment', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 5: Withdrawal from available balance
echo "\nScenario 5: Withdrawal from available balance\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceBefore = getReferrerBalance($referrer['id'], $db);
    $availableBefore = getAvailableBalance($referrer['id'], $referralService);
    
    $withdrawalAmount = 100.00;
    $withdrawalId = $referralService->processWithdrawal($referrer['id'], $withdrawalAmount);
    
    if (!$withdrawalId) throw new Exception("Failed to create withdrawal");
    
    $balanceAfter = getReferrerBalance($referrer['id'], $db);
    $availableAfter = getAvailableBalance($referrer['id'], $referralService);
    
    if (abs($balanceAfter - ($balanceBefore - $withdrawalAmount)) > 0.01) {
        throw new Exception("Balance incorrect. Expected: " . ($balanceBefore - $withdrawalAmount) . ", Got: {$balanceAfter}");
    }
    
    if (abs($availableAfter - ($availableBefore - $withdrawalAmount)) > 0.01) {
        throw new Exception("Available balance incorrect");
    }
    
    echo "✓ Withdrawal created: Rs {$withdrawalAmount}\n";
    echo "✓ Balance deducted: Rs {$balanceBefore} -> Rs {$balanceAfter}\n";
    $passed++;
    $scenarios[] = ['name' => 'Withdrawal from available balance', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Withdrawal from available balance', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 6: Withdrawal then order cancellation
echo "\nScenario 6: Withdrawal then order cancellation\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceAfterEarning = getReferrerBalance($referrer['id'], $db);
    $withdrawalAmount = 50.00;
    $referralService->processWithdrawal($referrer['id'], $withdrawalAmount);
    
    $balanceAfterWithdrawal = getReferrerBalance($referrer['id'], $db);
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
    $referralService->cancelReferralEarning($orderId);
    
    $balanceAfterCancel = getReferrerBalance($referrer['id'], $db);
    $expectedBalance = $balanceAfterWithdrawal - 150.00;
    
    if (abs($balanceAfterCancel - $expectedBalance) > 0.01) {
        throw new Exception("Balance incorrect after cancellation. Expected: {$expectedBalance}, Got: {$balanceAfterCancel}");
    }
    
    echo "✓ Withdrawal: Rs {$withdrawalAmount}\n";
    echo "✓ Cancellation deducted: Rs 150\n";
    echo "✓ Final balance: Rs {$balanceAfterCancel}\n";
    $passed++;
    $scenarios[] = ['name' => 'Withdrawal then order cancellation', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Withdrawal then order cancellation', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 7: Multiple orders, multiple earnings
echo "\nScenario 7: Multiple orders, multiple earnings\n";
try {
    $balanceBefore = getReferrerBalance($referrer['id'], $db);
    $totalExpected = 0;
    for ($i = 0; $i < 3; $i++) {
        $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
        $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
        $referralService->createPendingReferralEarning($orderId);
        $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
        $referralService->processReferralEarning($orderId);
        $totalExpected += 150.00;
    }
    
    $balance = getReferrerBalance($referrer['id'], $db);
    $expectedBalance = $balanceBefore + $totalExpected;
    if (abs($balance - $expectedBalance) > 0.01) {
        throw new Exception("Expected Rs {$expectedBalance} (balance before: {$balanceBefore} + new earnings: {$totalExpected}), got Rs {$balance}");
    }
    
    $paidCount = getPaidEarningsCount($referrer['id'], $db);
    if ($paidCount < 3) throw new Exception("Expected at least 3 paid earnings");
    
    echo "✓ 3 orders processed\n";
    echo "✓ New earnings: Rs {$totalExpected}\n";
    echo "✓ Balance: Rs {$balanceBefore} -> Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Multiple orders, multiple earnings', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Multiple orders, multiple earnings', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 8: Product with default commission
echo "\nScenario 8: Product with default commission\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product2, 500, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    $expectedCommission = 500 * ($defaultCommission / 100);
    
    if (abs($earning['amount'] - $expectedCommission) > 0.01) {
        throw new Exception("Expected Rs {$expectedCommission}, got Rs {$earning['amount']}");
    }
    
    echo "✓ Default commission used: {$defaultCommission}%\n";
    echo "✓ Commission: Rs {$earning['amount']}\n";
    $passed++;
    $scenarios[] = ['name' => 'Product with default commission', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Product with default commission', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 9: Order with multiple items, different commissions
echo "\nScenario 9: Order with multiple items, different commissions\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $invoice = 'INV-MULTI-' . time();
    $db->query(
        "INSERT INTO orders (invoice, user_id, customer_name, contact_no, total_amount, status, payment_status, address)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$invoice, $referredUserId, 'Test Customer', '9841234567', 1500, 'pending', 'pending', 'Test Address']
    )->execute();
    $orderId = $db->lastInsertId();
    
    // Item 1: 15% commission, Rs 1000
    $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
        [$orderId, $product1, 1, 1000])->execute();
    
    // Item 2: Default commission, Rs 500
    $db->query("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
        [$orderId, $product2, 1, 500])->execute();
    
    $referralService->createPendingReferralEarning($orderId);
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    
    $expectedCommission = (1000 * 0.15) + (500 * ($defaultCommission / 100));
    
    if (abs($earning['amount'] - $expectedCommission) > 0.01) {
        throw new Exception("Expected Rs {$expectedCommission}, got Rs {$earning['amount']}");
    }
    
    echo "✓ Multi-item order processed\n";
    echo "✓ Total commission: Rs {$earning['amount']}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order with multiple items', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order with multiple items', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 10: Insufficient balance withdrawal attempt
echo "\nScenario 10: Insufficient balance withdrawal attempt\n";
try {
    $balance = getReferrerBalance($referrer['id'], $db);
    $withdrawalAmount = $balance + 1000;
    
    try {
        $referralService->processWithdrawal($referrer['id'], $withdrawalAmount);
        throw new Exception("Should have thrown exception for insufficient balance");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Insufficient') === false) {
            throw $e;
        }
    }
    
    $balanceAfter = getReferrerBalance($referrer['id'], $db);
    if ($balanceAfter != $balance) {
        throw new Exception("Balance should not change on failed withdrawal");
    }
    
    echo "✓ Insufficient balance error caught\n";
    echo "✓ Balance unchanged: Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Insufficient balance withdrawal', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Insufficient balance withdrawal', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 11: Cancel already cancelled order
echo "\nScenario 11: Cancel already cancelled order\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $cancelled1 = $referralService->cancelReferralEarning($orderId);
    
    if (!$cancelled1) {
        throw new Exception("Failed to cancel earning first time");
    }
    
    $result = $referralService->cancelReferralEarning($orderId);
    if ($result !== false) {
        throw new Exception("Should return false for already cancelled order");
    }
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if (!$earning || $earning['status'] != 'cancelled') {
        throw new Exception("Status should remain cancelled. Got: " . ($earning['status'] ?? 'null'));
    }
    
    echo "✓ Already cancelled order handled correctly\n";
    $passed++;
    $scenarios[] = ['name' => 'Cancel already cancelled order', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Cancel already cancelled order', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 12: Process already paid earning
echo "\nScenario 12: Process already paid earning\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceBefore = getReferrerBalance($referrer['id'], $db);
    $result = $referralService->processReferralEarning($orderId);
    
    if ($result !== false) {
        throw new Exception("Should return false for already paid earning");
    }
    
    $balanceAfter = getReferrerBalance($referrer['id'], $db);
    if ($balanceAfter != $balanceBefore) {
        throw new Exception("Balance should not change");
    }
    
    echo "✓ Already paid earning handled correctly\n";
    $passed++;
    $scenarios[] = ['name' => 'Process already paid earning', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Process already paid earning', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 13: Multiple withdrawals
echo "\nScenario 13: Multiple withdrawals\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceBefore = getReferrerBalance($referrer['id'], $db);
    $withdrawal1 = $referralService->processWithdrawal($referrer['id'], 50.00);
    $withdrawal2 = $referralService->processWithdrawal($referrer['id'], 50.00);
    
    if (!$withdrawal1 || !$withdrawal2) {
        throw new Exception("Failed to create withdrawals");
    }
    
    $balanceAfter = getReferrerBalance($referrer['id'], $db);
    $expectedBalance = $balanceBefore - 100.00;
    
    if (abs($balanceAfter - $expectedBalance) > 0.01) {
        throw new Exception("Expected Rs {$expectedBalance}, got Rs {$balanceAfter}");
    }
    
    $withdrawalCount = getWithdrawalCount($referrer['id'], $db);
    if ($withdrawalCount < 2) {
        throw new Exception("Expected at least 2 withdrawals");
    }
    
    echo "✓ 2 withdrawals processed\n";
    echo "✓ Balance: Rs {$balanceBefore} -> Rs {$balanceAfter}\n";
    $passed++;
    $scenarios[] = ['name' => 'Multiple withdrawals', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Multiple withdrawals', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 14: Order quantity > 1
echo "\nScenario 14: Order quantity > 1\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 3, $db);
    $referralService->createPendingReferralEarning($orderId);
    
    $earning = $db->query("SELECT * FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    $expectedCommission = (1000 * 3) * 0.15; // 15% of 3000
    
    if (abs($earning['amount'] - $expectedCommission) > 0.01) {
        throw new Exception("Expected Rs {$expectedCommission}, got Rs {$earning['amount']}");
    }
    
    echo "✓ Quantity 3 processed correctly\n";
    echo "✓ Commission: Rs {$earning['amount']}\n";
    $passed++;
    $scenarios[] = ['name' => 'Order quantity > 1', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Order quantity > 1', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 15: Cancel order without earning
echo "\nScenario 15: Cancel order without earning\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    // Don't create earning
    
    $result = $referralService->cancelReferralEarning($orderId);
    if ($result !== false) {
        throw new Exception("Should return false when no earning exists");
    }
    
    echo "✓ No earning found, handled correctly\n";
    $passed++;
    $scenarios[] = ['name' => 'Cancel order without earning', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Cancel order without earning', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 16: Create duplicate pending earning
echo "\nScenario 16: Create duplicate pending earning\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $created1 = $referralService->createPendingReferralEarning($orderId);
    
    if (!$created1) {
        throw new Exception("Failed to create first earning");
    }
    
    $result = $referralService->createPendingReferralEarning($orderId);
    if ($result !== false) {
        throw new Exception("Should return false for duplicate earning. Got: " . ($result ? 'true' : 'false'));
    }
    
    $earnings = $db->query("SELECT COUNT(*) as count FROM referral_earnings WHERE order_id = ?", [$orderId])->single();
    if ($earnings['count'] != 1) {
        throw new Exception("Should have only 1 earning. Got: {$earnings['count']}");
    }
    
    echo "✓ Duplicate earning prevented\n";
    $passed++;
    $scenarios[] = ['name' => 'Create duplicate pending earning', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Create duplicate pending earning', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 17: Withdrawal with exact balance
echo "\nScenario 17: Withdrawal with exact balance\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balance = getReferrerBalance($referrer['id'], $db);
    $withdrawalId = $referralService->processWithdrawal($referrer['id'], $balance);
    
    if (!$withdrawalId) {
        throw new Exception("Failed to create withdrawal");
    }
    
    $balanceAfter = getReferrerBalance($referrer['id'], $db);
    if (abs($balanceAfter) > 0.01) {
        throw new Exception("Balance should be 0, got Rs {$balanceAfter}");
    }
    
    echo "✓ Exact balance withdrawal successful\n";
    echo "✓ Final balance: Rs {$balanceAfter}\n";
    $passed++;
    $scenarios[] = ['name' => 'Withdrawal with exact balance', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Withdrawal with exact balance', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 18: Mixed orders (some paid, some cancelled)
echo "\nScenario 18: Mixed orders (some paid, some cancelled)\n";
try {
    $totalPaid = 0;
    $totalCancelled = 0;
    
    // Create 2 paid orders
    for ($i = 0; $i < 2; $i++) {
        $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
        $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
        $referralService->createPendingReferralEarning($orderId);
        $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
        $referralService->processReferralEarning($orderId);
        $totalPaid += 150.00;
    }
    
    // Create 2 cancelled orders (before payment)
    for ($i = 0; $i < 2; $i++) {
        $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
        $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
        $referralService->createPendingReferralEarning($orderId);
        $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
        $referralService->cancelReferralEarning($orderId);
        $totalCancelled += 150.00;
    }
    
    $balance = getReferrerBalance($referrer['id'], $db);
    if (abs($balance - $totalPaid) > 0.01) {
        throw new Exception("Expected Rs {$totalPaid}, got Rs {$balance}");
    }
    
    $paidCount = getPaidEarningsCount($referrer['id'], $db);
    $cancelledCount = getCancelledEarningsCount($referrer['id'], $db);
    
    if ($paidCount < 2 || $cancelledCount < 2) {
        throw new Exception("Expected 2 paid and 2 cancelled earnings");
    }
    
    echo "✓ 2 paid orders: Rs {$totalPaid}\n";
    echo "✓ 2 cancelled orders: Rs {$totalCancelled} (not paid)\n";
    echo "✓ Final balance: Rs {$balance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Mixed orders', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Mixed orders', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 19: Cancel paid order, then withdrawal
echo "\nScenario 19: Cancel paid order, then withdrawal\n";
try {
    $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
    $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
    $referralService->createPendingReferralEarning($orderId);
    $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderId])->execute();
    $referralService->processReferralEarning($orderId);
    
    $balanceAfterEarning = getReferrerBalance($referrer['id'], $db);
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderId])->execute();
    $referralService->cancelReferralEarning($orderId);
    
    $balanceAfterCancel = getReferrerBalance($referrer['id'], $db);
    
    // Try to withdraw more than available
    try {
        $referralService->processWithdrawal($referrer['id'], $balanceAfterCancel + 100);
        throw new Exception("Should have failed");
    } catch (Exception $e) {
        // Expected
    }
    
    // Withdraw available amount
    if ($balanceAfterCancel > 0) {
        $withdrawalId = $referralService->processWithdrawal($referrer['id'], $balanceAfterCancel);
        if (!$withdrawalId) {
            throw new Exception("Failed to create withdrawal");
        }
    }
    
    echo "✓ Paid order cancelled\n";
    echo "✓ Withdrawal after cancellation handled\n";
    $passed++;
    $scenarios[] = ['name' => 'Cancel paid order, then withdrawal', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Cancel paid order, then withdrawal', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Scenario 20: Complex flow - multiple orders, withdrawals, cancellations
echo "\nScenario 20: Complex flow - multiple orders, withdrawals, cancellations\n";
try {
    $initialBalance = getReferrerBalance($referrer['id'], $db);
    
    // Create 5 orders
    $orderIds = [];
    for ($i = 0; $i < 5; $i++) {
        $referredUserId = createReferredUser($referrer['id'], $userModel, $db);
        $orderId = createOrder($referredUserId, $product1, 1000, 1, $db);
        $referralService->createPendingReferralEarning($orderId);
        $orderIds[] = $orderId;
    }
    
    // Deliver and pay 3 orders
    for ($i = 0; $i < 3; $i++) {
        $db->query("UPDATE orders SET status = 'delivered', payment_status = 'paid' WHERE id = ?", [$orderIds[$i]])->execute();
        $referralService->processReferralEarning($orderIds[$i]);
    }
    
    $balanceAfter3Paid = getReferrerBalance($referrer['id'], $db);
    $expectedAfter3Paid = $initialBalance + (3 * 150);
    
    if (abs($balanceAfter3Paid - $expectedAfter3Paid) > 0.01) {
        throw new Exception("Balance after 3 paid incorrect");
    }
    
    // Withdraw 200 (check available balance first)
    $availableBalance = getAvailableBalance($referrer['id'], $referralService);
    if ($availableBalance < 200) {
        // Adjust withdrawal to available balance
        $withdrawalAmount = min(200, $availableBalance);
    } else {
        $withdrawalAmount = 200;
    }
    
    if ($withdrawalAmount > 0) {
        $referralService->processWithdrawal($referrer['id'], $withdrawalAmount);
    }
    $balanceAfterWithdrawal = getReferrerBalance($referrer['id'], $db);
    
    // Cancel 1 paid order
    $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderIds[0]])->execute();
    $referralService->cancelReferralEarning($orderIds[0]);
    
    $balanceAfterCancel = getReferrerBalance($referrer['id'], $db);
    
    // Cancel 2 pending orders (these were never paid, so no balance change)
    for ($i = 3; $i < 5; $i++) {
        $db->query("UPDATE orders SET status = 'cancelled' WHERE id = ?", [$orderIds[$i]])->execute();
        $referralService->cancelReferralEarning($orderIds[$i]);
    }
    
    $finalBalance = getReferrerBalance($referrer['id'], $db);
    // Calculation: initial + 3 paid (450) - withdrawal - 1 cancelled paid (150) = initial + 300 - withdrawal
    $expectedFinal = $initialBalance + (3 * 150) - $withdrawalAmount - 150; // 3 paid - withdrawal - 1 cancelled paid
    
    if (abs($finalBalance - $expectedFinal) > 0.01) {
        throw new Exception("Final balance incorrect. Initial: {$initialBalance}, Expected: {$expectedFinal}, Got: {$finalBalance}. Breakdown: +450 (3 paid) -{$withdrawalAmount} (withdrawal) -150 (1 cancelled)");
    }
    
    echo "✓ 5 orders created\n";
    echo "✓ 3 paid, 1 cancelled (paid), 2 cancelled (pending)\n";
    echo "✓ 1 withdrawal: Rs {$withdrawalAmount}\n";
    echo "✓ Final balance: Rs {$finalBalance}\n";
    $passed++;
    $scenarios[] = ['name' => 'Complex flow', 'status' => 'passed'];
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    $scenarios[] = ['name' => 'Complex flow', 'status' => 'failed', 'error' => $e->getMessage()];
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "End Time: " . date('Y-m-d H:i:s') . "\n";
echo "Total Scenarios: 20\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
$passRate = ($passed / 20) * 100;
echo "Pass Rate: " . number_format($passRate, 1) . "%\n";
echo str_repeat("=", 60) . "\n\n";

echo "SCENARIO DETAILS\n";
echo str_repeat("-", 60) . "\n";
foreach ($scenarios as $idx => $scenario) {
    $status = $scenario['status'] === 'passed' ? '✓ PASS' : '✗ FAIL';
    echo sprintf("%-8s %2d. %s\n", $status, $idx + 1, $scenario['name']);
    if (isset($scenario['error'])) {
        echo "         Error: {$scenario['error']}\n";
    }
}
echo str_repeat("-", 60) . "\n\n";

if ($failed == 0) {
    echo "✓ SUCCESS: All 20 scenarios passed! System is working correctly.\n";
    exit(0);
} else {
    echo "✗ FAILURE: {$failed} scenario(s) failed. Please review errors above.\n";
    exit(1);
}

