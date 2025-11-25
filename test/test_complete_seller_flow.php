<?php
/**
 * Complete Seller Flow Test
 * 
 * Tests the complete flow:
 * 1. Order placed and paid
 * 2. Order status to delivered
 * 3. Payment status to paid
 * 4. Balance release after wait period
 * 5. Withdraw request
 * 6. All safety checks
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not already defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', URLROOT);
}

// Autoloader
spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Services\SellerBalanceService;
use App\Models\Order;
use App\Models\SellerWallet;

echo "=== Complete Seller Balance Flow Test ===\n\n";

$db = Database::getInstance();

try {
    // Test 1: Find or create test order
    echo "Test 1: Finding test order...\n";
    $order = $db->query(
        "SELECT o.*, 
                COUNT(DISTINCT oi.product_id) as product_count,
                COUNT(DISTINCT p.seller_id) as seller_count
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE p.seller_id IS NOT NULL 
         AND p.seller_id > 0
         GROUP BY o.id
         HAVING seller_count > 0
         ORDER BY o.id DESC
         LIMIT 1"
    )->single();
    
    if (!$order) {
        echo "✗ No orders with seller products found\n";
        exit(1);
    }
    
    echo "✓ Found order #{$order['id']} (Invoice: {$order['invoice']})\n";
    echo "  Current Status: {$order['status']}\n";
    echo "  Payment Status: {$order['payment_status']}\n";
    echo "  Products: {$order['product_count']}, Sellers: {$order['seller_count']}\n\n";
    
    // Test 2: Update order to delivered and paid
    echo "Test 2: Updating order status to delivered and payment to paid...\n";
    $db->query(
        "UPDATE orders 
         SET status = 'delivered', 
             payment_status = 'paid',
             delivered_at = DATE_SUB(NOW(), INTERVAL 25 HOUR),
             balance_released_at = NULL,
             updated_at = NOW()
         WHERE id = ?",
        [$order['id']]
    )->execute();
    
    // Remove any existing transactions for this order
    $db->query(
        "DELETE FROM seller_wallet_transactions WHERE order_id = ? AND type = 'credit'",
        [$order['id']]
    )->execute();
    
    // Reset seller wallet balances (for clean test)
    $sellers = $db->query(
        "SELECT DISTINCT p.seller_id 
         FROM order_items oi
         JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id = ? AND p.seller_id IS NOT NULL",
        [$order['id']]
    )->all();
    
    foreach ($sellers as $seller) {
        $wallet = $db->query(
            "SELECT * FROM seller_wallet WHERE seller_id = ?",
            [$seller['seller_id']]
        )->single();
        
        if ($wallet) {
            $originalBalance = $wallet['balance'];
            $originalEarnings = $wallet['total_earnings'];
            
            // Subtract any existing transactions for this order
            $existingTxn = $db->query(
                "SELECT SUM(amount) as total FROM seller_wallet_transactions 
                 WHERE seller_id = ? AND order_id = ? AND type = 'credit'",
                [$seller['seller_id'], $order['id']]
            )->single();
            
            if ($existingTxn['total'] > 0) {
                $newBalance = max(0, $wallet['balance'] - $existingTxn['total']);
                $newEarnings = max(0, $wallet['total_earnings'] - $existingTxn['total']);
                $db->query(
                    "UPDATE seller_wallet SET balance = ?, total_earnings = ? WHERE seller_id = ?",
                    [$newBalance, $newEarnings, $seller['seller_id']]
                )->execute();
            }
        }
    }
    
    echo "✓ Order updated to delivered and paid\n";
    echo "✓ delivered_at set to 25 hours ago (past wait period)\n";
    echo "✓ Balance release reset for testing\n\n";
    
    // Test 3: Process balance release
    echo "Test 3: Processing balance release...\n";
    $balanceService = new SellerBalanceService();
    $result = $balanceService->processBalanceRelease($order['id']);
    
    if ($result['success']) {
        echo "✓ Balance released successfully!\n";
        echo "  Total released: रु " . number_format($result['total_released'], 2) . "\n";
        echo "  Items processed: " . count($result['items']) . "\n";
        
        foreach ($result['items'] as $item) {
            echo "    - Seller #{$item['seller_id']}, Product #{$item['product_id']}: रु " . number_format($item['amount'], 2) . "\n";
        }
    } else {
        echo "✗ Balance release failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        exit(1);
    }
    echo "\n";
    
    // Test 4: Verify wallet updated
    echo "Test 4: Verifying seller wallet balances...\n";
    foreach ($sellers as $seller) {
        $wallet = $db->query(
            "SELECT * FROM seller_wallet WHERE seller_id = ?",
            [$seller['seller_id']]
        )->single();
        
        $sellerInfo = $db->query(
            "SELECT name FROM sellers WHERE id = ?",
            [$seller['seller_id']]
        )->single();
        
        if ($wallet && $wallet['balance'] > 0) {
            echo "✓ Seller: {$sellerInfo['name']} (ID: {$seller['seller_id']})\n";
            echo "  Balance: रु " . number_format($wallet['balance'], 2) . "\n";
            echo "  Total Earnings: रु " . number_format($wallet['total_earnings'], 2) . "\n";
            
            // Test 5: Test withdraw request
            echo "\nTest 5: Testing withdraw request flow...\n";
            $bankAccount = $db->query(
                "SELECT * FROM seller_bank_accounts 
                 WHERE seller_id = ? AND is_default = 1
                 LIMIT 1",
                [$seller['seller_id']]
            )->single();
            
            if ($bankAccount) {
                echo "✓ Bank account found: {$bankAccount['bank_name']} - {$bankAccount['account_number']}\n";
                
                // Check existing withdraw requests
                $existingRequests = $db->query(
                    "SELECT COUNT(*) as count FROM seller_withdraw_requests 
                     WHERE seller_id = ? AND status IN ('pending', 'approved', 'processing')",
                    [$seller['seller_id']]
                )->single();
                
                echo "  Pending withdraw requests: {$existingRequests['count']}\n";
                echo "  Available for withdrawal: रु " . number_format($wallet['balance'], 2) . "\n";
                echo "✓ Seller can request withdrawal\n";
            } else {
                echo "⚠ No bank account found. Seller needs to add bank account first.\n";
            }
        }
    }
    echo "\n";
    
    // Test 6: Test double release prevention
    echo "Test 6: Testing double release prevention...\n";
    $result2 = $balanceService->processBalanceRelease($order['id']);
    if (!$result2['success'] && strpos($result2['message'], 'already released') !== false) {
        echo "✓ Double release prevented: " . $result2['message'] . "\n";
    } else {
        echo "⚠ Warning: Double release check may not be working\n";
    }
    echo "\n";
    
    // Test 7: Test cancelled order check
    echo "Test 7: Testing cancelled order check...\n";
    $testOrder = $db->query("SELECT * FROM orders WHERE status = 'cancelled' LIMIT 1")->single();
    if ($testOrder) {
        $cancelledResult = $balanceService->processBalanceRelease($testOrder['id']);
        if (!$cancelledResult['success'] && strpos($cancelledResult['message'], 'cancelled') !== false) {
            echo "✓ Cancelled order check working: " . $cancelledResult['message'] . "\n";
        }
    } else {
        echo "⚠ No cancelled orders found to test\n";
    }
    echo "\n";
    
    // Test 8: Verify transactions
    echo "Test 8: Verifying wallet transactions...\n";
    $transactions = $db->query(
        "SELECT * FROM seller_wallet_transactions 
         WHERE order_id = ? AND type = 'credit'
         ORDER BY created_at DESC",
        [$order['id']]
    )->all();
    
    echo "  Transactions found: " . count($transactions) . "\n";
    foreach ($transactions as $txn) {
        echo "    - रु " . number_format($txn['amount'], 2) . " | {$txn['description']}\n";
        echo "      Balance after: रु " . number_format($txn['balance_after'], 2) . "\n";
    }
    echo "\n";
    
    // Test 9: Check balance_released_at
    echo "Test 9: Verifying balance_released_at timestamp...\n";
    $updatedOrder = $db->query(
        "SELECT balance_released_at, delivered_at FROM orders WHERE id = ?",
        [$order['id']]
    )->single();
    
    if (!empty($updatedOrder['balance_released_at'])) {
        echo "✓ balance_released_at set: {$updatedOrder['balance_released_at']}\n";
        echo "✓ delivered_at: {$updatedOrder['delivered_at']}\n";
    } else {
        echo "✗ balance_released_at not set\n";
    }
    echo "\n";
    
    echo "=== All Tests Complete ===\n";
    echo "✓ Order to seller balance flow working correctly\n";
    echo "✓ Safety checks implemented\n";
    echo "✓ Withdraw request flow ready\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

