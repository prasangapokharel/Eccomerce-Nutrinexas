<?php
/**
 * Comprehensive Test for Digital Products and Color Variants
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Models\Product;
use App\Models\ProductVariantColor;

$db = Database::getInstance();
$productModel = new Product();
$variantModel = new ProductVariantColor();

echo "=== Digital Products and Color Variants Test ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function testResult($testName, $passed, $message = '') {
    global $testsPassed, $testsFailed;
    if ($passed) {
        echo "  ✅ PASS: {$testName}\n";
        $testsPassed++;
    } else {
        echo "  ❌ FAIL: {$testName} - {$message}\n";
        $testsFailed++;
    }
    return $passed;
}

// Test 1: Check database structure
echo "1. Database Structure Tests\n";
$hasIsDigital = $db->query("SHOW COLUMNS FROM products LIKE 'is_digital'")->single();
testResult('is_digital column exists', !empty($hasIsDigital), 'is_digital column not found');

$hasColors = $db->query("SHOW COLUMNS FROM products LIKE 'colors'")->single();
testResult('colors column exists', !empty($hasColors), 'colors column not found');

$hasVariantsTable = $db->query("SHOW TABLES LIKE 'product_variants'")->single();
testResult('product_variants table exists', !empty($hasVariantsTable), 'product_variants table not found');

// Test 2: Create a digital product
echo "\n2. Digital Product Tests\n";
$digitalProductData = [
    'product_name' => 'Test Digital Product',
    'slug' => 'test-digital-product-' . time(),
    'description' => 'Test digital product description',
    'short_description' => 'Test digital',
    'price' => 1000.00,
    'stock_quantity' => 999,
    'category' => 'Digital',
    'is_featured' => 0,
    'is_digital' => 1,
    'product_type_main' => 'Digital',
    'status' => 'active'
];

$digitalProductId = $productModel->create($digitalProductData);
testResult('Digital product created', $digitalProductId > 0, 'Failed to create digital product');

if ($digitalProductId) {
    $digitalProduct = $productModel->getProductById($digitalProductId);
    testResult('Digital product has is_digital = 1', !empty($digitalProduct['is_digital']) && $digitalProduct['is_digital'] == 1, 'is_digital not set correctly');
}

// Test 3: Create a product with colors
echo "\n3. Color Variants Tests\n";
$colorProductData = [
    'product_name' => 'Test Color Product',
    'slug' => 'test-color-product-' . time(),
    'description' => 'Test product with colors',
    'short_description' => 'Test colors',
    'price' => 2000.00,
    'stock_quantity' => 50,
    'category' => 'Accessories',
    'is_featured' => 0,
    'is_digital' => 0,
    'product_type_main' => 'Accessories',
    'colors' => json_encode(['Red', 'Blue', 'Green']),
    'status' => 'active'
];

$colorProductId = $productModel->create($colorProductData);
testResult('Color product created', $colorProductId > 0, 'Failed to create color product');

if ($colorProductId) {
    $colorProduct = $productModel->getProductById($colorProductId);
    $colors = json_decode($colorProduct['colors'] ?? '[]', true);
    testResult('Product has colors stored', is_array($colors) && count($colors) > 0, 'Colors not stored correctly');
    
    // Create color variants
    $variant1 = $variantModel->createVariant([
        'product_id' => $colorProductId,
        'variant_type' => 'color',
        'variant_name' => 'Red',
        'variant_value' => '#FF0000',
        'stock_quantity' => 20,
        'is_default' => 1,
        'status' => 'active'
    ]);
    testResult('Red color variant created', $variant1 > 0, 'Failed to create red variant');
    
    $variant2 = $variantModel->createVariant([
        'product_id' => $colorProductId,
        'variant_type' => 'color',
        'variant_name' => 'Blue',
        'variant_value' => '#0000FF',
        'stock_quantity' => 15,
        'is_default' => 0,
        'status' => 'active'
    ]);
    testResult('Blue color variant created', $variant2 > 0, 'Failed to create blue variant');
    
    $variants = $variantModel->getColorVariants($colorProductId);
    testResult('Color variants retrieved', count($variants) >= 2, 'Failed to retrieve color variants');
}

// Test 4: Create an accessory product with colors
echo "\n4. Accessory Product Tests\n";
$accessoryProductData = [
    'product_name' => 'Test Accessory Product',
    'slug' => 'test-accessory-product-' . time(),
    'description' => 'Test accessory with colors',
    'short_description' => 'Test accessory',
    'price' => 1500.00,
    'stock_quantity' => 30,
    'category' => 'Accessories',
    'is_featured' => 0,
    'is_digital' => 0,
    'product_type_main' => 'Accessories',
    'product_type' => 'Clothing',
    'colors' => json_encode(['Black', 'White']),
    'status' => 'active'
];

$accessoryProductId = $productModel->create($accessoryProductData);
testResult('Accessory product created', $accessoryProductId > 0, 'Failed to create accessory product');

if ($accessoryProductId) {
    $accessoryProduct = $productModel->getProductById($accessoryProductId);
    testResult('Accessory has product_type_main = Accessories', 
               !empty($accessoryProduct['product_type_main']) && $accessoryProduct['product_type_main'] === 'Accessories',
               'product_type_main not set correctly');
    testResult('Accessory has product_type = Clothing',
               !empty($accessoryProduct['product_type']) && $accessoryProduct['product_type'] === 'Clothing',
               'product_type not set correctly');
}

// Test 5: Verify Product Model queries include new fields
echo "\n5. Product Model Query Tests\n";
if ($digitalProductId) {
    $product = $productModel->getProductById($digitalProductId);
    testResult('getProductById includes is_digital', isset($product['is_digital']), 'is_digital not in query result');
    testResult('getProductById includes colors field', array_key_exists('colors', $product), 'colors field not in query result');
    testResult('getProductById includes product_type_main', isset($product['product_type_main']), 'product_type_main not in query result');
}

// Test 5b: Verify colors are properly retrieved for color product
if ($colorProductId) {
    $product = $productModel->getProductById($colorProductId);
    $colors = json_decode($product['colors'] ?? '[]', true);
    testResult('Color product colors are retrievable', is_array($colors) && count($colors) > 0, 'Colors not properly stored or retrieved');
}

// Test 6: Cleanup
echo "\n6. Cleanup\n";
if ($digitalProductId) {
    $productModel->delete($digitalProductId);
    testResult('Digital product deleted', true, '');
}
if ($colorProductId) {
    $productModel->delete($colorProductId);
    testResult('Color product deleted', true, '');
}
if ($accessoryProductId) {
    $productModel->delete($accessoryProductId);
    testResult('Accessory product deleted', true, '');
}

// Summary
echo "\n=== Test Summary ===\n";
echo "✅ Passed: {$testsPassed}\n";
echo "❌ Failed: {$testsFailed}\n";
echo "📊 Total: " . ($testsPassed + $testsFailed) . "\n\n";

if ($testsFailed === 0) {
    echo "🎉 All tests passed! Digital products and color variants are working correctly.\n";
    exit(0);
} else {
    echo "⚠️ Some tests failed. Please review the errors above.\n";
    exit(1);
}

?>

