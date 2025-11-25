<?php
/**
 * Debug: Check why "Sponsored" label not showing in search
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
use App\Services\SponsoredAdsService;

echo "=== Debug: Search Sponsored Label ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$sponsoredAdsService = new SponsoredAdsService();

$keyword = 'yoga bar';
$sort = 'newest';

// Step 1: Get products (simulating controller)
echo "Step 1: Getting products...\n";
$products = $productModel->searchProducts($keyword, $sort);
echo "Found " . count($products) . " products\n\n";

// Step 2: Check for active ads BEFORE insertion
echo "Step 2: Checking for active ads (BEFORE insertion)...\n";
$allProductIds = array_column($products, 'id');
$adsByProductId = [];

if (!empty($allProductIds)) {
    $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
    $allActiveAds = $db->query(
        "SELECT a.product_id, a.id as ad_id
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.product_id IN ($placeholders)
         AND a.status = 'active'
         AND at.name = 'product_internal'
         AND CURDATE() BETWEEN a.start_date AND a.end_date
         AND a.product_id IS NOT NULL",
        $allProductIds
    )->all();
    
    foreach ($allActiveAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
    echo "Found " . count($adsByProductId) . " products with active ads\n";
    foreach ($adsByProductId as $pid => $aid) {
        echo "  - Product #{$pid} has Ad #{$aid}\n";
    }
    echo "\n";
}

// Step 3: Mark existing products
echo "Step 3: Marking existing products...\n";
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
        echo "  ✓ Marked Product #{$product['id']} as sponsored\n";
    }
}
unset($product);
echo "\n";

// Step 4: Insert sponsored ads
echo "Step 4: Inserting sponsored ads...\n";
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);
echo "After insertion: " . count($products) . " products\n\n";

// Step 5: Re-check ALL products
echo "Step 5: Re-checking ALL products for ads...\n";
$allProductIds = array_column($products, 'id');
if (!empty($allProductIds)) {
    $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
    $allActiveAds = $db->query(
        "SELECT a.product_id, a.id as ad_id
         FROM ads a
         INNER JOIN ads_types at ON a.ads_type_id = at.id
         WHERE a.product_id IN ($placeholders)
         AND a.status = 'active'
         AND at.name = 'product_internal'
         AND CURDATE() BETWEEN a.start_date AND a.end_date
         AND a.product_id IS NOT NULL",
        $allProductIds
    )->all();
    
    foreach ($allActiveAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
    echo "Total products with ads: " . count($adsByProductId) . "\n\n";
}

// Step 6: Final marking
echo "Step 6: Final marking of ALL products...\n";
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

// Step 7: Verify first 10 products
echo "Step 7: Verification (first 10 products):\n";
foreach (array_slice($products, 0, 10) as $index => $product) {
    $isSponsored = isset($product['is_sponsored']) && $product['is_sponsored'] === true;
    $hasAdId = !empty($product['ad_id']);
    $showSponsored = $isSponsored || $hasAdId;
    
    $status = $showSponsored ? '✓ WILL SHOW' : '✗ NOT SHOWING';
    echo "  [{$index}] Product #{$product['id']}: {$product['product_name']}\n";
    echo "      is_sponsored: " . ($isSponsored ? 'TRUE' : 'FALSE') . "\n";
    echo "      ad_id: " . ($product['ad_id'] ?? 'NONE') . "\n";
    echo "      Status: {$status}\n";
    echo "\n";
}

