<?php
/**
 * Test Sponsored Display
 * 
 * Tests if products with active ads show "Sponsored" label
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not already defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
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

echo "=== Testing Sponsored Display ===\n\n";

$db = Database::getInstance();

// Test 1: Check ads table structure
echo "Test 1: Checking ads table structure...\n";
$columns = $db->query(
    "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'ads'
     ORDER BY ORDINAL_POSITION"
)->all();

echo "Ads table columns:\n";
foreach ($columns as $col) {
    echo "  - {$col['COLUMN_NAME']} ({$col['DATA_TYPE']})\n";
}
echo "\n";

// Test 2: Get products with active ads
echo "Test 2: Getting products with active ads...\n";
$productsWithAds = $db->query(
    "SELECT p.id, p.product_name, a.id as ad_id, a.status, a.start_date, a.end_date, at.name as ad_type
     FROM products p
     INNER JOIN ads a ON p.id = a.product_id
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE a.status = 'active'
     AND at.name = 'product_internal'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND p.product_name LIKE '%yoga bar%'
     LIMIT 5"
)->all();

echo "Products with active ads: " . count($productsWithAds) . "\n";
foreach ($productsWithAds as $item) {
    echo "  - Product #{$item['id']}: {$item['product_name']}\n";
    echo "    Ad ID: {$item['ad_id']}, Status: {$item['status']}, Type: {$item['ad_type']}\n";
    echo "    Date Range: {$item['start_date']} to {$item['end_date']}\n";
}
echo "\n";

// Test 3: Simulate search query
echo "Test 3: Simulating search query logic...\n";
$keyword = 'yoga bar';
$searchPattern = "%{$keyword}%";

$products = $db->query(
    "SELECT p.*, 
            COALESCE(pi.image_url, p.image) as image_url
     FROM products p
     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
     WHERE (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
     AND p.status = 'active'
     LIMIT 10",
    [$searchPattern, $searchPattern, $searchPattern]
)->all();

echo "Search results: " . count($products) . " products\n";

// Check which have ads
$productIds = array_column($products, 'id');
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $activeAds = $db->query(
        "SELECT a.product_id, a.id as ad_id
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.status = 'active'
         AND at.name = 'product_internal'
         AND CURDATE() BETWEEN a.start_date AND a.end_date
         AND a.product_id IS NOT NULL
         AND a.product_id IN ($placeholders)",
        $productIds
    )->all();
    
    $adsByProductId = [];
    foreach ($activeAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
    
    echo "\nProducts with active ads:\n";
    foreach ($products as $product) {
        $hasAd = isset($adsByProductId[$product['id']]);
        $status = $hasAd ? '✓ SPONSORED' : '✗ Not sponsored';
        echo "  - Product #{$product['id']}: {$product['product_name']} - {$status}\n";
        if ($hasAd) {
            echo "    Ad ID: {$adsByProductId[$product['id']]}\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
echo "✓ If products show '✓ SPONSORED', they should display 'Sponsored' label in search\n";

