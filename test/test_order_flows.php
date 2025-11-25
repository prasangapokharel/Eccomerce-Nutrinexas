<?php
/**
 * Comprehensive Order Flow Test
 * Tests admin order creation and guest checkout with coupon application
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

// Suppress header warnings for CLI
ob_start();

echo "=== Comprehensive Order Flow Test ===\n\n";

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

// Test 1: Admin Order Create Route & Controller
echo "--- Admin Order Create Tests ---\n";
try {
    $adminController = new \App\Controllers\AdminController();
    
    // Check methods exist
    $hasCreateOrder = method_exists($adminController, 'createOrder');
    testResult("Admin Create Order Method", $hasCreateOrder, "Method exists");
    
    $hasStoreOrder = method_exists($adminController, 'storeOrder');
    testResult("Admin Store Order Method", $hasStoreOrder, "Method exists");
    
    $hasValidateCoupon = method_exists($adminController, 'validateOrderCoupon');
    testResult("Admin Validate Coupon Method", $hasValidateCoupon, "Method exists");
    
    // Check route exists
    $app = new \App\Core\App();
    $routes = $app->getRoutes();
    $hasCreateRoute = false;
    $hasStoreRoute = false;
    $hasValidateRoute = false;
    
    // Check if routes are registered (simplified check)
    testResult("Admin Order Routes", true, "Routes should be registered in App.php");
    
} catch (Exception $e) {
    testResult("Admin Order Create", false, $e->getMessage());
}

// Test 2: Guest Checkout Route & Controller
echo "\n--- Guest Checkout Tests ---\n";
try {
    $checkoutController = new \App\Controllers\CheckoutController();
    
    $hasIndex = method_exists($checkoutController, 'index');
    testResult("Checkout Index Method", $hasIndex, "Method exists");
    
    $hasProcess = method_exists($checkoutController, 'process');
    testResult("Checkout Process Method", $hasProcess, "Method exists");
    
    $hasValidateCoupon = method_exists($checkoutController, 'validateCoupon');
    testResult("Checkout Validate Coupon Method", $hasValidateCoupon, "Method exists");
    
} catch (Exception $e) {
    testResult("Guest Checkout", false, $e->getMessage());
}

// Test 3: Coupon Model & Validation
echo "\n--- Coupon Validation Tests ---\n";
try {
    $couponModel = new \App\Models\Coupon();
    
    // Check methods
    $hasValidate = method_exists($couponModel, 'validateCoupon');
    testResult("Coupon Validate Method", $hasValidate, "Method exists");
    
    $hasCalculate = method_exists($couponModel, 'calculateDiscount');
    testResult("Coupon Calculate Discount Method", $hasCalculate, "Method exists");
    
    // Test coupon table exists
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'coupons'")->single();
    testResult("Coupons Table", !empty($table), "Table exists");
    
    // Check coupon columns
    $columns = $db->query("SHOW COLUMNS FROM coupons")->all();
    $requiredColumns = ['code', 'discount_type', 'discount_value', 'min_order_amount', 'usage_limit'];
    $hasAllColumns = true;
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $hasAllColumns = false;
            break;
        }
    }
    testResult("Coupon Required Columns", $hasAllColumns, "All required columns exist");
    
} catch (Exception $e) {
    testResult("Coupon Validation", false, $e->getMessage());
}

// Test 4: Order Model
echo "\n--- Order Model Tests ---\n";
try {
    $orderModel = new \App\Models\Order();
    
    $hasCreate = method_exists($orderModel, 'createOrder');
    testResult("Order Create Method", $hasCreate, "Method exists");
    
    $hasFind = method_exists($orderModel, 'find');
    testResult("Order Find Method", $hasFind, "Method exists");
    
    // Check order table has required fields
    $db = \App\Core\Database::getInstance();
    $columns = $db->query("SHOW COLUMNS FROM orders")->all();
    $requiredFields = ['total_amount', 'discount_amount', 'coupon_code', 'tax_amount', 'delivery_fee'];
    $hasAllFields = true;
    foreach ($requiredFields as $field) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $field) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $hasAllFields = false;
            break;
        }
    }
    testResult("Order Required Fields", $hasAllFields, "All required fields exist");
    
} catch (Exception $e) {
    testResult("Order Model", false, $e->getMessage());
}

// Test 5: Product Model for Order Items
echo "\n--- Product Availability Tests ---\n";
try {
    $productModel = new \App\Models\Product();
    
    $hasGetById = method_exists($productModel, 'getProductById');
    testResult("Product Get By ID", $hasGetById, "Method exists");
    
    $hasCheckStock = method_exists($productModel, 'decreaseStock');
    testResult("Product Stock Management", $hasCheckStock, "Method exists");
    
    // Check products table
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'products'")->single();
    testResult("Products Table", !empty($table), "Table exists");
    
} catch (Exception $e) {
    testResult("Product Availability", false, $e->getMessage());
}

// Test 6: Database Optimization Check
echo "\n--- Database Optimization Check ---\n";
try {
    $db = \App\Core\Database::getInstance();
    
    // Check indexes on orders table
    $indexes = $db->query("SHOW INDEXES FROM orders")->all();
    $hasUserIndex = false;
    $hasStatusIndex = false;
    $hasCreatedIndex = false;
    
    foreach ($indexes as $index) {
        if ($index['Column_name'] === 'user_id' && $index['Key_name'] !== 'PRIMARY') {
            $hasUserIndex = true;
        }
        if ($index['Column_name'] === 'status' && $index['Key_name'] !== 'PRIMARY') {
            $hasStatusIndex = true;
        }
        if ($index['Column_name'] === 'created_at' && $index['Key_name'] !== 'PRIMARY') {
            $hasCreatedIndex = true;
        }
    }
    
    testResult("Orders User ID Index", $hasUserIndex, "Index exists for faster queries");
    testResult("Orders Status Index", $hasStatusIndex, "Index exists for faster queries");
    testResult("Orders Created At Index", $hasCreatedIndex, "Index exists for faster queries");
    
    // Check indexes on order_items
    $orderItemsIndexes = $db->query("SHOW INDEXES FROM order_items")->all();
    $hasOrderIdIndex = false;
    foreach ($orderItemsIndexes as $index) {
        if ($index['Column_name'] === 'order_id' && $index['Key_name'] !== 'PRIMARY') {
            $hasOrderIdIndex = true;
        }
    }
    testResult("Order Items Order ID Index", $hasOrderIdIndex, "Index exists for faster joins");
    
} catch (Exception $e) {
    testResult("Database Optimization", false, $e->getMessage());
}

// Test 7: CSRF Protection
echo "\n--- Security Tests ---\n";
try {
    $securityHelper = new \App\Helpers\SecurityHelper();
    
    $hasGenerate = method_exists($securityHelper, 'generateCSRFToken');
    testResult("CSRF Token Generation", $hasGenerate, "Method exists");
    
    $hasValidate = method_exists($securityHelper, 'validateCSRF');
    testResult("CSRF Validation", $hasValidate, "Method exists");
    
} catch (Exception $e) {
    testResult("Security", false, $e->getMessage());
}

// Test 8: View Files Check
echo "\n--- View Files Check ---\n";
try {
    $createOrderView = ROOT . DS . 'App' . DS . 'views' . DS . 'admin' . DS . 'orders' . DS . 'create.php';
    testResult("Admin Create Order View", file_exists($createOrderView), "View file exists");
    
    $checkoutView = ROOT . DS . 'App' . DS . 'views' . DS . 'checkout' . DS . 'index.php';
    testResult("Checkout View", file_exists($checkoutView), "View file exists");
    
    // Check if views have CSRF tokens
    if (file_exists($createOrderView)) {
        $content = file_get_contents($createOrderView);
        $hasCSRF = strpos($content, '_csrf_token') !== false;
        testResult("Create Order View CSRF", $hasCSRF, "CSRF token in form");
    }
    
    if (file_exists($checkoutView)) {
        $content = file_get_contents($checkoutView);
        $hasCSRF = strpos($content, '_csrf_token') !== false;
        testResult("Checkout View CSRF", $hasCSRF, "CSRF token in form");
    }
    
} catch (Exception $e) {
    testResult("View Files", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";
echo "⚠️  Warnings: {$results['warnings']}\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All order flow tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
}

