<?php
/**
 * Final Verification: Search page sponsored label
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

echo "=== Final Verification: Search Sponsored Label ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$sponsoredAdsService = new SponsoredAdsService();

$keyword = 'yoga bar';
$sort = 'newest';

// Simulate exact controller logic
$products = $productModel->searchProducts($keyword, $sort);

// Insert sponsored ads
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);

// Get ALL product IDs and check for active ads
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
}

// Mark products
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

// Verify view logic
echo "View Logic Check (first 10 products):\n\n";
foreach (array_slice($products, 0, 10) as $index => $product) {
    $hasActiveAd = (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) || !empty($product['ad_id']);
    
    echo "Product #{$product['id']}: {$product['product_name']}\n";
    echo "  is_sponsored: " . (isset($product['is_sponsored']) ? ($product['is_sponsored'] ? 'true' : 'false') : 'not set') . "\n";
    echo "  ad_id: " . ($product['ad_id'] ?? 'none') . "\n";
    echo "  hasActiveAd (view check): " . ($hasActiveAd ? 'YES - LABEL WILL SHOW ✓' : 'NO') . "\n";
    echo "\n";
}

echo "=== Summary ===\n";
$sponsoredCount = 0;
foreach ($products as $product) {
    $hasActiveAd = (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) || !empty($product['ad_id']);
    if ($hasActiveAd) {
        $sponsoredCount++;
    }
}
echo "Total products: " . count($products) . "\n";
echo "Products that will show 'Sponsored': {$sponsoredCount}\n";
echo "\n";
echo "✓ If count > 0, labels should display in search results!\n";

