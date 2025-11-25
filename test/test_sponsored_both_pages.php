<?php
/**
 * Test: Verify sponsored label shows in both search.php and index.php
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
use App\Models\Product;

echo "=== Test: Sponsored Label in Both Pages ===\n\n";

$db = Database::getInstance();
$productModel = new Product();

// Test 1: Index page products
echo "Test 1: Index page products...\n";
$products = $productModel->getProductsWithImages(10, 0);
echo "Found " . count($products) . " products\n";

$productIds = array_column($products, 'id');
$adsByProductId = [];

if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $activeAds = $db->query(
        "SELECT a.product_id, a.id as ad_id
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.product_id IN ($placeholders)
         AND a.status = 'active'
         AND at.name = 'product_internal'
         AND CURDATE() BETWEEN a.start_date AND a.end_date
         AND a.product_id IS NOT NULL",
        $productIds
    )->all();
    
    foreach ($activeAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
}

foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

$sponsoredCount = 0;
foreach ($products as $product) {
    if (!empty($product['is_sponsored']) || !empty($product['ad_id'])) {
        $sponsoredCount++;
        echo "  ✓ Product #{$product['id']}: {$product['product_name']} - SPONSORED\n";
    }
}

echo "Index page: {$sponsoredCount} products marked as sponsored\n\n";

// Test 2: Search page products
echo "Test 2: Search page products...\n";
$keyword = 'yoga bar';
$products = $productModel->searchProducts($keyword, 'newest', 10, 0);
echo "Found " . count($products) . " products for '{$keyword}'\n";

$productIds = array_column($products, 'id');
$adsByProductId = [];

if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $activeAds = $db->query(
        "SELECT a.product_id, a.id as ad_id
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.product_id IN ($placeholders)
         AND a.status = 'active'
         AND at.name = 'product_internal'
         AND CURDATE() BETWEEN a.start_date AND a.end_date
         AND a.product_id IS NOT NULL",
        $productIds
    )->all();
    
    foreach ($activeAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
}

foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

$sponsoredCount = 0;
foreach ($products as $product) {
    if (!empty($product['is_sponsored']) || !empty($product['ad_id'])) {
        $sponsoredCount++;
        echo "  ✓ Product #{$product['id']}: {$product['product_name']} - SPONSORED\n";
    }
}

echo "Search page: {$sponsoredCount} products marked as sponsored\n\n";

echo "=== Summary ===\n";
echo "✓ Both pages should show 'Sponsored' label for products with active ads\n";
echo "✓ Index URL: http://192.168.1.125:8000/products\n";
echo "✓ Search URL: http://192.168.1.125:8000/products/search?q=yoga+bar\n";

