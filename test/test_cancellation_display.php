<?php
/**
 * Test Cancellation Display in Admin and Seller Views
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
use App\Models\CancelLog;
use App\Models\Order;

echo "=== Testing Cancellation Display ===\n\n";

$db = Database::getInstance();
$passed = 0;
$failed = 0;

// Test 1: Admin cancellations view
echo "Test 1: Testing admin cancellations view\n";
try {
    $cancelLog = new CancelLog();
    $allCancels = $cancelLog->getAllWithOrders();
    
    echo "✓ Admin getAllWithOrders() executed successfully\n";
    echo "✓ Found " . count($allCancels) . " cancellation records\n";
    
    // Verify structure
    if (count($allCancels) > 0) {
        $firstCancel = $allCancels[0];
        $requiredFields = ['id', 'order_id', 'reason', 'status', 'created_at'];
        foreach ($requiredFields as $field) {
            if (!isset($firstCancel[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        echo "✓ All required fields present\n";
    }
    
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Test 2: Seller cancellations view
echo "\nTest 2: Testing seller cancellations view\n";
try {
    $sellerId = 2; // Test seller
    
    $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status
            FROM order_cancel_log c
            LEFT JOIN orders o ON c.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE c.seller_id = ?
            ORDER BY c.created_at DESC";
    
    $sellerCancels = $db->query($sql, [$sellerId])->all();
    
    echo "✓ Seller cancellations query executed successfully\n";
    echo "✓ Found " . count($sellerCancels) . " cancellation records for seller {$sellerId}\n";
    
    // Verify structure
    if (count($sellerCancels) > 0) {
        $firstCancel = $sellerCancels[0];
        $requiredFields = ['id', 'order_id', 'reason', 'status', 'invoice', 'customer_name'];
        foreach ($requiredFields as $field) {
            if (!isset($firstCancel[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        echo "✓ All required fields present\n";
    }
    
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Test 3: Test with status filter
echo "\nTest 3: Testing status filter\n";
try {
    $sellerId = 2;
    $status = 'processing';
    
    $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status
            FROM order_cancel_log c
            LEFT JOIN orders o ON c.order_id = o.id
            LEFT JOIN users u ON o.user_id = u.id
            WHERE c.seller_id = ? AND c.status = ?
            ORDER BY c.created_at DESC";
    
    $filteredCancels = $db->query($sql, [$sellerId, $status])->all();
    
    echo "✓ Status filter query executed successfully\n";
    echo "✓ Found " . count($filteredCancels) . " '{$status}' cancellations for seller {$sellerId}\n";
    
    // Verify all have correct status
    foreach ($filteredCancels as $cancel) {
        if (($cancel['status'] ?? '') !== $status) {
            throw new Exception("Status filter not working correctly");
        }
    }
    echo "✓ All filtered results have correct status\n";
    
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
    echo "\n✓ All tests passed! Cancellation display should work correctly.\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}




