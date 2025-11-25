<?php
/**
 * Verify Search UI - Check if sponsored data reaches view
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

echo "=== Verify Search UI Data ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$sponsoredAdsService = new SponsoredAdsService();

$keyword = 'yoga bar';
$sort = 'newest';

// Simulate exact controller flow
$products = $productModel->searchProducts($keyword, $sort);
echo "Step 1: Found " . count($products) . " products\n";

// Insert sponsored ads
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);
echo "Step 2: After insertion: " . count($products) . " products\n";

// Check for active ads
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
    echo "Step 3: Found " . count($adsByProductId) . " products with active ads\n\n";
}

// Mark products
foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

// Simulate view logic
echo "View Logic Simulation (first 10 products):\n\n";
foreach (array_slice($products, 0, 10) as $index => $product) {
    $isSponsored = isset($product['is_sponsored']) && $product['is_sponsored'] === true;
    $hasAdId = !empty($product['ad_id']);
    $showSponsored = $isSponsored || $hasAdId;
    
    $status = $showSponsored ? '✓ WILL SHOW LABEL' : '✗ NO LABEL';
    echo "[{$index}] Product #{$product['id']}: {$product['product_name']}\n";
    echo "    is_sponsored: " . ($isSponsored ? 'true' : 'false') . "\n";
    echo "    ad_id: " . ($product['ad_id'] ?? 'none') . "\n";
    echo "    View check: {$status}\n\n";
}

$sponsoredCount = 0;
foreach ($products as $product) {
    $isSponsored = isset($product['is_sponsored']) && $product['is_sponsored'] === true;
    $hasAdId = !empty($product['ad_id']);
    if ($isSponsored || $hasAdId) {
        $sponsoredCount++;
    }
}

echo "=== Summary ===\n";
echo "Total products: " . count($products) . "\n";
echo "Products that will show 'Sponsored': {$sponsoredCount}\n";
echo "\n✓ If count > 0, labels should display in UI!\n";

