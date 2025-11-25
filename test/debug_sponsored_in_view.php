<?php
/**
 * Debug: Check if sponsored flag reaches the view
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

echo "=== Debug: Sponsored Flag in View ===\n\n";

$db = Database::getInstance();
$productModel = new Product();

$keyword = 'yoga bar';

// Step 1: Get products
$products = $productModel->searchProducts($keyword, 'newest', 10, 0);
echo "Step 1: Found " . count($products) . " products\n\n";

// Step 2: Get product IDs
$productIds = array_column($products, 'id');
echo "Step 2: Product IDs: " . implode(', ', $productIds) . "\n\n";

// Step 3: Check for active ads
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$activeAds = $db->query(
    "SELECT a.product_id, a.id as ad_id, a.status, a.start_date, a.end_date
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE a.product_id IN ($placeholders)
     AND a.status = 'active'
     AND at.name = 'product_internal'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND a.product_id IS NOT NULL",
    $productIds
)->all();

echo "Step 3: Active ads found: " . count($activeAds) . "\n";
foreach ($activeAds as $ad) {
    echo "  - Product #{$ad['product_id']} has Ad #{$ad['ad_id']}\n";
    echo "    Status: {$ad['status']}, Dates: {$ad['start_date']} to {$ad['end_date']}\n";
}
echo "\n";

// Step 4: Mark products
$adsByProductId = [];
foreach ($activeAds as $ad) {
    $adsByProductId[$ad['product_id']] = $ad['ad_id'];
}

foreach ($products as &$product) {
    if (isset($adsByProductId[$product['id']])) {
        $product['is_sponsored'] = true;
        $product['ad_id'] = $adsByProductId[$product['id']];
    }
}
unset($product);

// Step 5: Insert sponsored ads
$sponsoredAdsService = new SponsoredAdsService();
$products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);

// Step 6: Final check
echo "Step 6: Final product list with sponsored flags:\n";
foreach ($products as $index => $product) {
    $isSponsored = !empty($product['is_sponsored']) ? 'YES' : 'NO';
    $adId = $product['ad_id'] ?? 'N/A';
    echo "  Position {$index}: Product #{$product['id']}\n";
    echo "    Name: {$product['product_name']}\n";
    echo "    is_sponsored: {$isSponsored}\n";
    echo "    ad_id: {$adId}\n";
    echo "\n";
}

