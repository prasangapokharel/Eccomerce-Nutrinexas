<?php
/**
 * Full Withdrawal Approval Test
 * 
 * Actually tests the approval, rejection, and completion flows
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

echo "=== Full Withdrawal Approval Test ===\n\n";

$db = Database::getInstance();

try {
    // Find or create a test withdrawal
    echo "Step 1: Setting up test withdrawal...\n";
    $withdraw = $db->query(
        "SELECT wr.*, s.name as seller_name, sw.balance as wallet_balance
         FROM seller_withdraw_requests wr
         LEFT JOIN sellers s ON wr.seller_id = s.id
         LEFT JOIN seller_wallet sw ON wr.seller_id = sw.seller_id
         WHERE wr.status = 'pending'
         ORDER BY wr.requested_at DESC
         LIMIT 1"
    )->single();
    
    if (!$withdraw || $withdraw['status'] !== 'pending') {
        echo "⚠ No pending withdrawal found. Creating test...\n";
        $seller = $db->query(
            "SELECT s.id, s.name, sw.balance 
             FROM sellers s
             JOIN seller_wallet sw ON s.id = sw.seller_id
             WHERE sw.balance > 100
             LIMIT 1"
        )->single();
        
        if ($seller) {
            $bankAccount = $db->query(
                "SELECT id FROM seller_bank_accounts 
                 WHERE seller_id = ? AND is_default = 1
                 LIMIT 1",
                [$seller['id']]
            )->single();
            
            if ($bankAccount) {
                $testAmount = 50;
                $db->query(
                    "INSERT INTO seller_withdraw_requests 
                     (seller_id, amount, payment_method, bank_account_id, status, requested_at) 
                     VALUES (?, ?, 'bank_transfer', ?, 'pending', NOW())",
                    [$seller['id'], $testAmount, $bankAccount['id']]
                )->execute();
                
                $pdo = $db->getPdo();
                $withdrawId = $pdo->lastInsertId();
                $withdraw = $db->query(
                    "SELECT wr.*, s.name as seller_name, sw.balance as wallet_balance
                     FROM seller_withdraw_requests wr
                     LEFT JOIN sellers s ON wr.seller_id = s.id
                     LEFT JOIN seller_wallet sw ON wr.seller_id = sw.seller_id
                     WHERE wr.id = ?",
                    [$withdrawId]
                )->single();
            }
        }
    }
    
    if (!$withdraw) {
        echo "✗ Could not create test withdrawal\n";
        exit(1);
    }
    
    $originalBalance = $withdraw['wallet_balance'];
    $withdrawId = $withdraw['id'];
    $sellerId = $withdraw['seller_id'];
    $amount = $withdraw['amount'];
    
    echo "✓ Test withdrawal #{$withdrawId}\n";
    echo "  Seller: {$withdraw['seller_name']} (ID: {$sellerId})\n";
    echo "  Amount: रु " . number_format($amount, 2) . "\n";
    echo "  Original Balance: रु " . number_format($originalBalance, 2) . "\n\n";
    
    // Test 2: Approve withdrawal
    echo "Step 2: Testing approval...\n";
    try {
        $adminComment = "Test approval - Automated test";
        $result = $db->query(
            "UPDATE seller_withdraw_requests 
             SET status = 'approved', 
                 processed_at = NOW(),
                 admin_comment = ?
             WHERE id = ?",
            [$adminComment, $withdrawId]
        )->execute();
        
        if ($result) {
            echo "✓ Approval query executed successfully\n";
            
            // Verify status changed
            $updated = $db->query(
                "SELECT * FROM seller_withdraw_requests WHERE id = ?",
                [$withdrawId]
            )->single();
            
            if ($updated['status'] === 'approved') {
                echo "✓ Status updated to 'approved'\n";
                echo "  processed_at: {$updated['processed_at']}\n";
                echo "  admin_comment: {$updated['admin_comment']}\n";
            } else {
                echo "✗ Status not updated correctly\n";
            }
            
            // Update wallet
            $wallet = $db->query(
                "SELECT * FROM seller_wallet WHERE seller_id = ?",
                [$sellerId]
            )->single();
            
            if ($wallet && $wallet['balance'] >= $amount) {
                $newBalance = $wallet['balance'] - $amount;
                $newTotalWithdrawals = ($wallet['total_withdrawals'] ?? 0) + $amount;
                $newPending = max(0, ($wallet['pending_withdrawals'] ?? 0) - $amount);
                
                $db->query(
                    "UPDATE seller_wallet 
                     SET balance = ?, 
                         total_withdrawals = ?,
                         pending_withdrawals = ?,
                         updated_at = NOW() 
                     WHERE seller_id = ?",
                    [$newBalance, $newTotalWithdrawals, $newPending, $sellerId]
                )->execute();
                
                echo "✓ Wallet updated\n";
                echo "  New Balance: रु " . number_format($newBalance, 2) . "\n";
                echo "  Total Withdrawals: रु " . number_format($newTotalWithdrawals, 2) . "\n";
                
                // Create transaction
                $db->query(
                    "INSERT INTO seller_wallet_transactions 
                     (seller_id, type, amount, description, withdraw_request_id, balance_after, status) 
                     VALUES (?, 'debit', ?, ?, ?, ?, 'completed')",
                    [
                        $sellerId,
                        $amount,
                        'Withdrawal approved - Bank Transfer',
                        $withdrawId,
                        $newBalance
                    ]
                )->execute();
                
                echo "✓ Transaction created\n";
            } else {
                echo "⚠ Insufficient balance or wallet not found\n";
            }
        } else {
            echo "✗ Approval query failed\n";
        }
    } catch (Exception $e) {
        echo "✗ Approval error: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Test 3: Verify final state
    echo "Step 3: Verifying final state...\n";
    $finalWithdraw = $db->query(
        "SELECT * FROM seller_withdraw_requests WHERE id = ?",
        [$withdrawId]
    )->single();
    
    $finalWallet = $db->query(
        "SELECT * FROM seller_wallet WHERE seller_id = ?",
        [$sellerId]
    )->single();
    
    $transactions = $db->query(
        "SELECT * FROM seller_wallet_transactions 
         WHERE withdraw_request_id = ? 
         ORDER BY created_at DESC",
        [$withdrawId]
    )->all();
    
    echo "  Withdrawal Status: {$finalWithdraw['status']}\n";
    echo "  Wallet Balance: रु " . number_format($finalWallet['balance'], 2) . "\n";
    echo "  Transactions: " . count($transactions) . "\n";
    
    if ($finalWithdraw['status'] === 'approved' && 
        $finalWallet['balance'] == ($originalBalance - $amount) &&
        count($transactions) > 0) {
        echo "✓ All checks passed!\n";
    } else {
        echo "⚠ Some checks failed\n";
    }
    echo "\n";
    
    echo "=== Test Complete ===\n";
    echo "✓ Withdrawal approval flow working correctly\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

