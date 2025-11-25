<?php
/**
 * Test: Simulate exact controller logic and check what data reaches view
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
use App\Models\Product;
use App\Services\SponsoredAdsService;

echo "=== Testing View Data ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$sponsoredAdsService = new SponsoredAdsService();

$keyword = 'yoga bar';
$sort = 'newest';

// Step 1: Get products (simulating cache)
$products = $productModel->searchProducts($keyword, $sort);
echo "Step 1: Found " . count($products) . " products\n";

// Step 2: Get product IDs
$allProductIds = array_column($products, 'id');
echo "Step 2: Product IDs: " . implode(', ', array_slice($allProductIds, 0, 5)) . "...\n\n";

// Step 3: Check for active ads
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
    echo "Step 3: Found " . count($adsByProductId) . " products with active ads\n\n";
}

// Step 4: Insert sponsored ads FIRST (matches controller logic)
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);

// Step 5: Get ALL product IDs (including newly inserted ones) to check for active ads
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
    echo "Step 5: Found " . count($adsByProductId) . " products with active ads (after insertion)\n\n";
}

// Step 6: Mark ALL products with ads as sponsored
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

// Step 7: Simulate view check - show first 10 products
echo "Step 7: View Data Check (first 10 products):\n";
foreach (array_slice($products, 0, 10) as $index => $product) {
    $hasActiveAd = !empty($product['is_sponsored']) || !empty($product['ad_id']);
    $isSponsored = isset($product['is_sponsored']) ? ($product['is_sponsored'] ? 'true' : 'false') : 'not set';
    $adId = $product['ad_id'] ?? 'not set';
    
    echo "  Position {$index}: Product #{$product['id']}: {$product['product_name']}\n";
    echo "    is_sponsored: {$isSponsored}\n";
    echo "    ad_id: {$adId}\n";
    echo "    hasActiveAd: " . ($hasActiveAd ? 'YES - WILL SHOW LABEL ✓' : 'NO') . "\n";
    echo "\n";
}

echo "=== Summary ===\n";
$sponsoredCount = 0;
foreach ($products as $product) {
    if (!empty($product['is_sponsored']) || !empty($product['ad_id'])) {
        $sponsoredCount++;
    }
}
echo "Total products: " . count($products) . "\n";
echo "Products that should show 'Sponsored': {$sponsoredCount}\n";

