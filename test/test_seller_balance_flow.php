<?php
/**
 * Test Seller Balance Flow
 * 
 * Tests the complete order to seller balance flow:
 * 1. Order placed and paid
 * 2. Order status changes to delivered
 * 3. Payment status set to paid
 * 4. Wait period check
 * 5. Balance release
 * 6. Withdraw request
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not already defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
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

echo "=== Testing Seller Balance Flow ===\n\n";

$db = Database::getInstance();

try {
    // Test 1: Find a delivered order with seller products
    echo "Test 1: Finding delivered order with seller products...\n";
    $order = $db->query(
        "SELECT o.*, 
                COUNT(DISTINCT oi.product_id) as product_count,
                COUNT(DISTINCT p.seller_id) as seller_count
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE o.status = 'delivered' 
         AND p.seller_id IS NOT NULL 
         AND p.seller_id > 0
         GROUP BY o.id
         HAVING seller_count > 0
         ORDER BY o.id DESC
         LIMIT 1"
    )->single();
    
    if (!$order) {
        echo "⚠ No delivered orders with seller products found. Creating test scenario...\n";
        echo "   Please ensure you have:\n";
        echo "   1. An order with status='delivered'\n";
        echo "   2. Order items with products that have seller_id\n";
        echo "   3. Payment status='paid'\n";
        echo "\nSkipping balance release test...\n";
    } else {
        echo "✓ Found order #{$order['id']} (Invoice: {$order['invoice']})\n";
        echo "  Products: {$order['product_count']}, Sellers: {$order['seller_count']}\n\n";
        
        // Test 2: Check if delivered_at is set
        echo "Test 2: Checking delivered_at timestamp...\n";
        if (empty($order['delivered_at'])) {
            echo "⚠ delivered_at not set. Setting it now...\n";
            $db->query(
                "UPDATE orders SET delivered_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?",
                [$order['id']]
            )->execute();
            echo "✓ Set delivered_at to 25 hours ago (past wait period)\n";
        } else {
            echo "✓ delivered_at: {$order['delivered_at']}\n";
        }
        echo "\n";
        
        // Test 3: Check if balance already released
        echo "Test 3: Checking if balance already released...\n";
        if (!empty($order['balance_released_at'])) {
            echo "⚠ Balance already released at: {$order['balance_released_at']}\n";
            echo "   Resetting for test...\n";
            $db->query(
                "UPDATE orders SET balance_released_at = NULL WHERE id = ?",
                [$order['id']]
            )->execute();
            // Also remove transactions
            $db->query(
                "DELETE FROM seller_wallet_transactions WHERE order_id = ? AND type = 'credit'",
                [$order['id']]
            )->execute();
            echo "✓ Reset balance release status\n";
        } else {
            echo "✓ Balance not yet released\n";
        }
        echo "\n";
        
        // Test 4: Check payment status
        echo "Test 4: Checking payment status...\n";
        if ($order['payment_status'] !== 'paid') {
            echo "⚠ Payment status is '{$order['payment_status']}'. Setting to 'paid'...\n";
            $db->query(
                "UPDATE orders SET payment_status = 'paid' WHERE id = ?",
                [$order['id']]
            )->execute();
            echo "✓ Payment status set to 'paid'\n";
        } else {
            echo "✓ Payment status: paid\n";
        }
        echo "\n";
        
        // Test 5: Process balance release
        echo "Test 5: Processing balance release...\n";
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
            echo "⚠ Balance release result: " . ($result['message'] ?? 'Unknown') . "\n";
            if (isset($result['wait_period_remaining'])) {
                echo "  Wait period remaining: {$result['wait_period_remaining']} hours\n";
            }
        }
        echo "\n";
        
        // Test 6: Verify wallet balance updated
        echo "Test 6: Verifying seller wallet balances...\n";
        $sellers = $db->query(
            "SELECT DISTINCT p.seller_id, s.name as seller_name
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             JOIN sellers s ON p.seller_id = s.id
             WHERE oi.order_id = ? AND p.seller_id IS NOT NULL",
            [$order['id']]
        )->all();
        
        foreach ($sellers as $seller) {
            $wallet = $db->query(
                "SELECT * FROM seller_wallet WHERE seller_id = ?",
                [$seller['seller_id']]
            )->single();
            
            if ($wallet) {
                echo "  Seller: {$seller['seller_name']} (ID: {$seller['seller_id']})\n";
                echo "    Balance: रु " . number_format($wallet['balance'], 2) . "\n";
                echo "    Total Earnings: रु " . number_format($wallet['total_earnings'], 2) . "\n";
            }
        }
        echo "\n";
        
        // Test 7: Check transactions
        echo "Test 7: Checking wallet transactions...\n";
        $transactions = $db->query(
            "SELECT * FROM seller_wallet_transactions 
             WHERE order_id = ? AND type = 'credit'
             ORDER BY created_at DESC",
            [$order['id']]
        )->all();
        
        echo "  Transactions found: " . count($transactions) . "\n";
        foreach ($transactions as $txn) {
            echo "    - रु " . number_format($txn['amount'], 2) . " | {$txn['description']}\n";
        }
        echo "\n";
    }
    
    // Test 8: Test withdraw request flow
    echo "Test 8: Testing withdraw request flow...\n";
    $sellerWithWallet = $db->query(
        "SELECT s.id, s.name, sw.balance 
         FROM sellers s
         JOIN seller_wallet sw ON s.id = sw.seller_id
         WHERE sw.balance > 0
         LIMIT 1"
    )->single();
    
    if ($sellerWithWallet) {
        echo "✓ Found seller with balance: {$sellerWithWallet['name']}\n";
        echo "  Balance: रु " . number_format($sellerWithWallet['balance'], 2) . "\n";
        
        // Check if seller has bank account
        $bankAccount = $db->query(
            "SELECT * FROM seller_bank_accounts 
             WHERE seller_id = ? AND is_default = 1
             LIMIT 1",
            [$sellerWithWallet['id']]
        )->single();
        
        if ($bankAccount) {
            echo "  Bank Account: {$bankAccount['bank_name']} - {$bankAccount['account_number']}\n";
            echo "✓ Seller can request withdrawal\n";
        } else {
            echo "⚠ No bank account found. Seller needs to add bank account first.\n";
        }
    } else {
        echo "⚠ No seller with balance found for withdrawal test\n";
    }
    echo "\n";
    
    // Test 9: Check for pending releases
    echo "Test 9: Checking for pending balance releases...\n";
    $pendingOrders = $db->query(
        "SELECT COUNT(*) as count 
         FROM orders 
         WHERE status = 'delivered' 
         AND balance_released_at IS NULL 
         AND delivered_at IS NOT NULL
         AND DATE_ADD(delivered_at, INTERVAL 24 HOUR) <= NOW()"
    )->single();
    
    echo "  Pending releases: {$pendingOrders['count']} orders\n";
    if ($pendingOrders['count'] > 0) {
        echo "  Run cron job to process: php cron/process_seller_balance_releases.php\n";
    }
    echo "\n";
    
    echo "=== Test Complete ===\n";
    echo "✓ All checks completed\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

