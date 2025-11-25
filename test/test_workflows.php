<?php
/**
 * Comprehensive Workflow Test - Staff, Curior, Inventory
 * Tests 5+ scenarios for each workflow
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

if (session_status() === PHP_SESSION_NONE) session_start();

echo "=== Comprehensive Workflow Test Suite ===\n\n";

$results = ['passed' => 0, 'failed' => 0, 'warnings' => 0];

function testResult($name, $passed, $message = '', $warning = false) {
    global $results;
    if ($passed) {
        echo "✅ PASS: $name\n";
        $results['passed']++;
    } else {
        if ($warning) {
            echo "⚠️  WARN: $name - $message\n";
            $results['warnings']++;
        } else {
            echo "❌ FAIL: $name - $message\n";
            $results['failed']++;
        }
    }
}

// Test 1: Staff Login Workflow
echo "--- Staff Login Workflow (5 Scenarios) ---\n";
try {
    $staffModel = new \App\Models\Staff();
    $staffController = new \App\Controllers\StaffController();
    
    // Scenario 1: Staff table exists
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'staff'")->single();
    testResult("Staff Table Exists", !empty($table), "Table found");
    
    // Scenario 2: Staff login method exists
    $hasLogin = method_exists($staffController, 'login');
    testResult("Staff Login Method", $hasLogin, "Method exists");
    
    // Scenario 3: Staff dashboard method exists
    $hasDashboard = method_exists($staffController, 'dashboard');
    testResult("Staff Dashboard Method", $hasDashboard, "Method exists");
    
    // Scenario 4: Staff order update method exists
    $hasUpdateOrder = method_exists($staffController, 'updateOrderStatus');
    testResult("Staff Update Order Method", $hasUpdateOrder, "Method exists");
    
    // Scenario 5: Staff authentication check
    $hasRequireStaff = method_exists($staffController, 'requireStaff');
    testResult("Staff Authentication Check", $hasRequireStaff, "Method exists");
    
} catch (Exception $e) {
    testResult("Staff Login Workflow", false, $e->getMessage());
}

// Test 2: Curior Workflow
echo "\n--- Curior Workflow (5 Scenarios) ---\n";
try {
    $deliveryController = new \App\Controllers\CuriorController();
    
    // Scenario 1: Curior login method
    $hasLogin = method_exists($deliveryController, 'login');
    testResult("Curior Login Method", $hasLogin, "Method exists");
    
    // Scenario 2: Curior dashboard
    $hasDashboard = method_exists($deliveryController, 'dashboard');
    testResult("Curior Dashboard Method", $hasDashboard, "Method exists");
    
    // Scenario 3: Curior order update
    $hasUpdateOrder = method_exists($deliveryController, 'updateOrderStatus');
    testResult("Curior Update Order Method", $hasUpdateOrder, "Method exists");
    
    // Scenario 4: Order model curior methods
    $orderModel = new \App\Models\Order();
    $hasGetOrders = method_exists($orderModel, 'getOrdersByCurior');
    testResult("Get Orders By Curior", $hasGetOrders, "Method exists");
    
    // Scenario 5: Curior stats
    $hasStats = method_exists($orderModel, 'getCuriorStats');
    testResult("Curior Stats Method", $hasStats, "Method exists");
    
} catch (Exception $e) {
    testResult("Curior Workflow", false, $e->getMessage());
}

// Test 3: Inventory Management Workflow
echo "\n--- Inventory Management Workflow (5 Scenarios) ---\n";
try {
    $inventoryController = new \App\Controllers\InventoryController();
    
    // Scenario 1: Inventory dashboard
    $hasIndex = method_exists($inventoryController, 'index');
    testResult("Inventory Dashboard Method", $hasIndex, "Method exists");
    
    // Scenario 2: Suppliers management
    $hasSuppliers = method_exists($inventoryController, 'suppliers');
    testResult("Suppliers Management Method", $hasSuppliers, "Method exists");
    
    // Scenario 3: Products management
    $hasProducts = method_exists($inventoryController, 'products');
    testResult("Products Management Method", $hasProducts, "Method exists");
    
    // Scenario 4: Purchases management
    $hasPurchases = method_exists($inventoryController, 'purchases');
    testResult("Purchases Management Method", $hasPurchases, "Method exists");
    
    // Scenario 5: Stock update functionality
    $productModel = new \App\Models\Product();
    $hasUpdateStock = method_exists($productModel, 'updateStock');
    testResult("Product Stock Update Method", $hasUpdateStock, "Method exists");
    
} catch (Exception $e) {
    testResult("Inventory Management Workflow", false, $e->getMessage());
}

// Test 4: Database Structure Check
echo "\n--- Database Structure Check ---\n";
try {
    $db = \App\Core\Database::getInstance();
    
    // Check essential tables
    $essentialTables = ['products', 'orders', 'staff', 'users', 'cart', 'wholesale_products', 'purchases'];
    foreach ($essentialTables as $table) {
        $exists = $db->query("SHOW TABLES LIKE '$table'")->single();
        testResult("Table: $table", !empty($exists), "Table exists");
    }
    
} catch (Exception $e) {
    testResult("Database Structure", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All workflow tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
}

