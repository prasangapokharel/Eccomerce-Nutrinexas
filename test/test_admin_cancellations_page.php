<?php
/**
 * Test Admin Cancellations Page
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

echo "=== Testing Admin Cancellations Page ===\n\n";

$db = Database::getInstance();
$passed = 0;
$failed = 0;

// Test 1: Check table exists
echo "Test 1: Checking table exists\n";
try {
    $result = $db->query("SELECT COUNT(*) as count FROM order_cancel_log")->single();
    echo "✓ Table exists with {$result['count']} records\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Test 2: Test getAllWithOrders method
echo "\nTest 2: Testing getAllWithOrders method\n";
try {
    $cancelLog = new CancelLog();
    $cancels = $cancelLog->getAllWithOrders();
    echo "✓ Method executed successfully\n";
    echo "✓ Returned " . count($cancels) . " records\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Test 3: Test with pagination
echo "\nTest 3: Testing pagination logic\n";
try {
    $allCancels = $cancelLog->getAllWithOrders();
    $page = 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    $cancels = array_slice($allCancels, $offset, $perPage);
    $totalCount = count($allCancels);
    $totalPages = ceil($totalCount / $perPage);
    
    echo "✓ Pagination calculated: Page {$page}, Total: {$totalCount}, Pages: {$totalPages}\n";
    echo "✓ Sliced array has " . count($cancels) . " items\n";
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
    echo "\n✓ All tests passed! Admin cancellations page should render correctly.\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}





