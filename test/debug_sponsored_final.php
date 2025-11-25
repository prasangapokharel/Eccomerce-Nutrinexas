<?php
/**
 * Final Debug: Check if sponsored flag is set correctly
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

echo "=== Final Debug: Sponsored Flag ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$sponsoredAdsService = new SponsoredAdsService();

$keyword = 'yoga bar';
$sort = 'newest';

// Step 1: Get products
$products = $productModel->searchProducts($keyword, $sort);
echo "Step 1: Found " . count($products) . " products\n";

// Step 2: Insert sponsored ads
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);
echo "Step 2: After insertion: " . count($products) . " products\n";

// Step 3: Get ALL product IDs and check for active ads
$allProductIds = array_column($products, 'id');
$adsByProductId = [];

if (!empty($allProductIds)) {
    $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
    $allActiveAds = $db->query(
        "SELECT a.product_id, a.id as ad_id, a.status, a.start_date, a.end_date
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

// Step 4: Mark ALL products with ads
$markedCount = 0;
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
        $markedCount++;
    }
}
unset($product);

echo "Step 4: Marked {$markedCount} products as sponsored\n\n";

// Step 5: Verify first 10 products
echo "Step 5: First 10 products check:\n";
foreach (array_slice($products, 0, 10) as $index => $product) {
    $isSponsored = isset($product['is_sponsored']) && $product['is_sponsored'] === true;
    $hasAdId = !empty($product['ad_id']);
    $willShow = $isSponsored || $hasAdId;
    
    echo "  [{$index}] Product #{$product['id']}: {$product['product_name']}\n";
    echo "      is_sponsored: " . ($isSponsored ? 'TRUE' : 'FALSE') . "\n";
    echo "      ad_id: " . ($product['ad_id'] ?? 'NONE') . "\n";
    echo "      Will show label: " . ($willShow ? 'YES ✓' : 'NO ✗') . "\n";
    echo "\n";
}

