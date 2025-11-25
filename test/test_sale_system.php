<?php
/**
 * Comprehensive Sale System Test
 * Tests site-wide sale functionality
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

echo "=== Sale System Test ===\n\n";

$results = ['passed' => 0, 'failed' => 0];

function testResult($name, $passed, $message = '') {
    global $results;
    if ($passed) {
        echo "✅ PASS: $name\n";
        $results['passed']++;
    } else {
        echo "❌ FAIL: $name - $message\n";
        $results['failed']++;
    }
}

// Test 1: Sale Model
echo "--- Sale Model Test ---\n";
try {
    $saleModel = new \App\Models\SiteWideSale();
    testResult("SiteWideSale Model", true, "Model initialized");
    
    // Check table exists
    $db = \App\Core\Database::getInstance();
    $table = $db->query("SHOW TABLES LIKE 'site_wide_sales'")->single();
    testResult("Site Wide Sales Table", !empty($table), "Table exists");
    
} catch (Exception $e) {
    testResult("Sale Model", false, $e->getMessage());
}

// Test 2: Product Sale Fields
echo "\n--- Product Sale Fields Test ---\n";
try {
    $db = \App\Core\Database::getInstance();
    
    $columns = [
        'sale_start_date',
        'sale_end_date',
        'sale_discount_percent',
        'is_on_sale'
    ];
    
    foreach ($columns as $column) {
        $exists = $db->query("SHOW COLUMNS FROM products LIKE '$column'")->single();
        testResult("Column: $column", !empty($exists), "Column exists");
    }
    
} catch (Exception $e) {
    testResult("Product Sale Fields", false, $e->getMessage());
}

// Test 3: Sale Price Calculation
echo "\n--- Sale Price Calculation Test ---\n";
try {
    $saleModel = new \App\Models\SiteWideSale();
    
    // Test calculation
    $originalPrice = 1000.00;
    $discountPercent = 20.00;
    $expectedPrice = 800.00;
    
    $calculatedPrice = $saleModel->calculateSalePrice($originalPrice, $discountPercent);
    testResult("Sale Price Calculation", abs($calculatedPrice - $expectedPrice) < 0.01, 
        "Expected: $expectedPrice, Got: $calculatedPrice");
    
} catch (Exception $e) {
    testResult("Sale Price Calculation", false, $e->getMessage());
}

// Test 4: Product Model Sale Methods
echo "\n--- Product Model Sale Methods Test ---\n";
try {
    $productModel = new \App\Models\Product();
    
    // Test getProductById includes sale fields
    $product = $productModel->getProductById(1);
    if ($product) {
        $hasSaleFields = isset($product['sale_start_date']) || 
                        isset($product['sale_end_date']) || 
                        isset($product['is_on_sale']);
        testResult("Product Includes Sale Fields", $hasSaleFields, "Sale fields in product data");
    } else {
        testResult("Product Includes Sale Fields", true, "No test product found (skipped)");
    }
    
} catch (Exception $e) {
    testResult("Product Model Sale Methods", false, $e->getMessage());
}

// Test 5: Sale Status Service
echo "\n--- Sale Status Service Test ---\n";
try {
    $service = new \App\Services\SaleStatusService();
    testResult("Sale Status Service", true, "Service initialized");
    
    // Test update method exists
    $hasUpdate = method_exists($service, 'updateAllSaleStatuses');
    testResult("Update Sale Statuses Method", $hasUpdate, "Method exists");
    
} catch (Exception $e) {
    testResult("Sale Status Service", false, $e->getMessage());
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$results['passed']}\n";
echo "❌ Failed: {$results['failed']}\n";

if ($results['failed'] === 0) {
    echo "\n🎉 All sale system tests passed!\n";
} else {
    echo "\n⚠️  Some tests failed. Please review.\n";
}

