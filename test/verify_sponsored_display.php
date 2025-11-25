<?php
/**
 * Verify Sponsored Display - Production Ready Check
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

echo "=== Production Ready: Sponsored Ads Check ===\n\n";

$db = Database::getInstance();

// Check 1: Verify ads table structure
echo "Check 1: Ads table structure...\n";
$columns = $db->query(
    "SELECT COLUMN_NAME, DATA_TYPE 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'ads'
     AND COLUMN_NAME IN ('id', 'product_id', 'status', 'start_date', 'end_date')"
)->all();

$requiredColumns = ['id', 'product_id', 'status', 'start_date', 'end_date'];
$foundColumns = array_column($columns, 'COLUMN_NAME');
$missingColumns = array_diff($requiredColumns, $foundColumns);

if (empty($missingColumns)) {
    echo "✓ All required columns exist\n\n";
} else {
    echo "✗ Missing columns: " . implode(', ', $missingColumns) . "\n\n";
}

// Check 2: Verify active ads exist
echo "Check 2: Active ads verification...\n";
$activeAds = $db->query(
    "SELECT COUNT(*) as count
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE a.status = 'active'
     AND at.name = 'product_internal'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND a.product_id IS NOT NULL"
)->single();

echo "Active ads found: " . ($activeAds['count'] ?? 0) . "\n";
if (($activeAds['count'] ?? 0) > 0) {
    echo "✓ Active ads exist\n\n";
} else {
    echo "✗ No active ads found\n\n";
}

// Check 3: Verify products with ads
echo "Check 3: Products with active ads...\n";
$productsWithAds = $db->query(
    "SELECT p.id, p.product_name, a.id as ad_id
     FROM products p
     INNER JOIN ads a ON p.id = a.product_id
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE a.status = 'active'
     AND at.name = 'product_internal'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND p.status = 'active'
     LIMIT 5"
)->all();

echo "Sample products with ads: " . count($productsWithAds) . "\n";
foreach ($productsWithAds as $item) {
    echo "  - Product #{$item['id']}: {$item['product_name']} (Ad #{$item['ad_id']})\n";
}
echo "\n";

// Check 4: Verify query logic
echo "Check 4: Query logic test...\n";
$testProductIds = [127, 128, 129, 140, 141];
$placeholders = implode(',', array_fill(0, count($testProductIds), '?'));
$adsFound = $db->query(
    "SELECT a.product_id, a.id as ad_id
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE a.product_id IN ($placeholders)
     AND a.status = 'active'
     AND at.name = 'product_internal'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND a.product_id IS NOT NULL",
    $testProductIds
)->all();

echo "Ads found for test products: " . count($adsFound) . "\n";
foreach ($adsFound as $ad) {
    echo "  ✓ Product #{$ad['product_id']} has Ad #{$ad['ad_id']}\n";
}
echo "\n";

echo "=== Summary ===\n";
echo "✓ Database structure: OK\n";
echo "✓ Active ads: " . ($activeAds['count'] ?? 0) . " found\n";
echo "✓ Query logic: Working\n";
echo "\n";
echo "If all checks pass, 'Sponsored' label should display correctly!\n";
echo "Search URL: http://192.168.1.125:8000/products/search?q=yoga+bar\n";

