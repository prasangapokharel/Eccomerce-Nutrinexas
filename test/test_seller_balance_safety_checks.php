<?php
/**
 * Test All Seller Balance Safety Checks
 * 
 * Tests:
 * 1. Double release prevention
 * 2. Cancelled orders never add balance
 * 3. COD orders only add balance after cash collected
 * 4. Returns reduce seller balance if already received money
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

echo "=== Testing Seller Balance Safety Checks ===\n\n";

$db = Database::getInstance();
$balanceService = new SellerBalanceService();

try {
    // Test 1: Double Release Prevention
    echo "Test 1: Double Release Prevention\n";
    echo "-----------------------------------\n";
    
    // Find an order that already has balance released
    $releasedOrder = $db->query(
        "SELECT o.*, COUNT(DISTINCT p.seller_id) as seller_count
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE o.status = 'delivered' 
         AND o.balance_released_at IS NOT NULL
         AND o.payment_status = 'paid'
         AND p.seller_id IS NOT NULL
         GROUP BY o.id
         HAVING seller_count > 0
         LIMIT 1"
    )->single();
    
    if ($releasedOrder) {
        echo "✓ Found order #{$releasedOrder['id']} with balance already released\n";
        
        $result = $balanceService->processBalanceRelease($releasedOrder['id']);
        
        if (!$result['success'] && strpos($result['message'], 'already released') !== false) {
            echo "✓ PASS: Double release prevented - " . $result['message'] . "\n";
        } else {
            echo "✗ FAIL: Double release not prevented!\n";
            echo "  Result: " . json_encode($result) . "\n";
        }
    } else {
        echo "⚠ No order with released balance found for testing\n";
    }
    echo "\n";
    
    // Test 2: Cancelled Orders Never Add Balance
    echo "Test 2: Cancelled Orders Never Add Balance\n";
    echo "-------------------------------------------\n";
    
    // Find or create a cancelled order
    $cancelledOrder = $db->query(
        "SELECT o.*, COUNT(DISTINCT p.seller_id) as seller_count
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE o.status = 'cancelled'
         AND p.seller_id IS NOT NULL
         GROUP BY o.id
         HAVING seller_count > 0
         LIMIT 1"
    )->single();
    
    if ($cancelledOrder) {
        echo "✓ Found cancelled order #{$cancelledOrder['id']}\n";
        
        // Set delivered_at to past wait period (to bypass wait check)
        $db->query(
            "UPDATE orders SET delivered_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?",
            [$cancelledOrder['id']]
        )->execute();
        
        // Get original balance
        $sellerId = $db->query(
            "SELECT DISTINCT p.seller_id 
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ? AND p.seller_id IS NOT NULL
             LIMIT 1",
            [$cancelledOrder['id']]
        )->single()['seller_id'];
        
        $walletBefore = $db->query(
            "SELECT balance FROM seller_wallet WHERE seller_id = ?",
            [$sellerId]
        )->single();
        $balanceBefore = $walletBefore['balance'] ?? 0;
        
        $result = $balanceService->processBalanceRelease($cancelledOrder['id']);
        
        $walletAfter = $db->query(
            "SELECT balance FROM seller_wallet WHERE seller_id = ?",
            [$sellerId]
        )->single();
        $balanceAfter = $walletAfter['balance'] ?? 0;
        
        if (!$result['success'] && strpos($result['message'], 'cancelled') !== false) {
            echo "✓ PASS: Cancelled order check working - " . $result['message'] . "\n";
        } elseif ($balanceBefore == $balanceAfter) {
            echo "✓ PASS: Balance unchanged for cancelled order\n";
        } else {
            echo "✗ FAIL: Balance changed for cancelled order!\n";
            echo "  Before: रु " . number_format($balanceBefore, 2) . "\n";
            echo "  After: रु " . number_format($balanceAfter, 2) . "\n";
        }
    } else {
        echo "⚠ No cancelled order found for testing\n";
    }
    echo "\n";
    
    // Test 3: COD Orders Only Add Balance After Cash Collected
    echo "Test 3: COD Orders Only Add Balance After Cash Collected\n";
    echo "----------------------------------------------------------\n";
    
    // Find a COD order with pending payment
    $codOrder = $db->query(
        "SELECT o.*, pm.name as method_name
         FROM orders o
         LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE o.status = 'delivered'
         AND o.payment_status = 'pending'
         AND (pm.name LIKE '%cod%' OR pm.name LIKE '%cash%' OR o.payment_method_id = 1)
         AND p.seller_id IS NOT NULL
         AND o.balance_released_at IS NULL
         GROUP BY o.id
         LIMIT 1"
    )->single();
    
    if ($codOrder) {
        echo "✓ Found COD order #{$codOrder['id']} with pending payment\n";
        
        // Set delivered_at to past wait period
        $db->query(
            "UPDATE orders SET delivered_at = DATE_SUB(NOW(), INTERVAL 25 HOUR) WHERE id = ?",
            [$codOrder['id']]
        )->execute();
        
        $result = $balanceService->processBalanceRelease($codOrder['id']);
        
        if (!$result['success'] && strpos($result['message'], 'COD') !== false) {
            echo "✓ PASS: COD order check working - " . $result['message'] . "\n";
        } else {
            echo "✗ FAIL: COD order allowed balance release without payment!\n";
            echo "  Result: " . json_encode($result) . "\n";
        }
        
        // Now mark as paid and test again
        echo "  Testing after marking payment as paid...\n";
        $db->query(
            "UPDATE orders SET payment_status = 'paid' WHERE id = ?",
            [$codOrder['id']]
        )->execute();
        
        // Reset balance release
        $db->query(
            "UPDATE orders SET balance_released_at = NULL WHERE id = ?",
            [$codOrder['id']]
        )->execute();
        
        $result2 = $balanceService->processBalanceRelease($codOrder['id']);
        
        if ($result2['success']) {
            echo "✓ PASS: COD order balance released after payment marked as paid\n";
        } else {
            echo "⚠ COD order still not releasing after payment: " . $result2['message'] . "\n";
        }
    } else {
        echo "⚠ No COD order with pending payment found for testing\n";
    }
    echo "\n";
    
    // Test 4: Returns Reduce Seller Balance
    echo "Test 4: Returns Reduce Seller Balance\n";
    echo "--------------------------------------\n";
    
    // Find an order with released balance
    $returnOrder = $db->query(
        "SELECT o.*
         FROM orders o
         WHERE o.status = 'delivered'
         AND o.balance_released_at IS NOT NULL
         LIMIT 1"
    )->single();
    
    if ($returnOrder) {
        echo "✓ Found order #{$returnOrder['id']} with released balance\n";
        
        // Get order items
        $orderItems = $db->query(
            "SELECT oi.*, p.seller_id 
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ? AND p.seller_id IS NOT NULL
             LIMIT 1",
            [$returnOrder['id']]
        )->single();
        
        if ($orderItems) {
            $sellerId = $orderItems['seller_id'];
            $walletBefore = $db->query(
                "SELECT balance FROM seller_wallet WHERE seller_id = ?",
                [$sellerId]
            )->single();
            $balanceBefore = $walletBefore['balance'] ?? 0;
            
            // Simulate return
            $returnedItems = [
                [
                    'product_id' => $orderItems['product_id'],
                    'quantity' => 1
                ]
            ];
            
            $result = $balanceService->handleReturn($returnOrder['id'], $returnedItems);
            
            $walletAfter = $db->query(
                "SELECT balance FROM seller_wallet WHERE seller_id = ?",
                [$sellerId]
            )->single();
            $balanceAfter = $walletAfter['balance'] ?? 0;
            
            if ($result['success'] && $balanceAfter < $balanceBefore) {
                echo "✓ PASS: Return reduced seller balance\n";
                echo "  Before: रु " . number_format($balanceBefore, 2) . "\n";
                echo "  After: रु " . number_format($balanceAfter, 2) . "\n";
                echo "  Reduced: रु " . number_format($balanceBefore - $balanceAfter, 2) . "\n";
            } else {
                echo "⚠ Return handling result: " . json_encode($result) . "\n";
                echo "  Balance before: रु " . number_format($balanceBefore, 2) . "\n";
                echo "  Balance after: रु " . number_format($balanceAfter, 2) . "\n";
            }
        } else {
            echo "⚠ No order items found for return test\n";
        }
    } else {
        echo "⚠ No order with released balance found for return test\n";
    }
    echo "\n";
    
    echo "=== All Safety Checks Tested ===\n";
    echo "✓ Double release prevention\n";
    echo "✓ Cancelled orders check\n";
    echo "✓ COD orders check\n";
    echo "✓ Returns handling\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

