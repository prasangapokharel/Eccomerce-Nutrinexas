<?php
/**
 * Final Test - Verify Sponsored Label Shows
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

echo "=== Final Test: Sponsored Label Display ===\n\n";

$db = Database::getInstance();
$productModel = new Product();
$adsService = new SponsoredAdsService();

$keyword = 'yoga bar';

// Simulate exact search controller logic
echo "Step 1: Getting search products...\n";
$products = $productModel->searchProducts($keyword, 'newest', 10, 0);
echo "Found " . count($products) . " products\n\n";

// Get product IDs
$productIds = array_column($products, 'id');

// Check for active ads
echo "Step 2: Checking for active ads...\n";
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
    
    $adsByProductId = [];
    foreach ($activeAds as $ad) {
        $adsByProductId[$ad['product_id']] = $ad['ad_id'];
    }
    
    echo "Active ads found: " . count($activeAds) . "\n";
    foreach ($activeAds as $ad) {
        echo "  - Product #{$ad['product_id']} has Ad #{$ad['ad_id']}\n";
    }
    echo "\n";
    
    // Mark products as sponsored
    echo "Step 3: Marking products as sponsored...\n";
    foreach ($products as &$product) {
        if (isset($adsByProductId[$product['id']])) {
            $product['is_sponsored'] = true;
            $product['ad_id'] = $adsByProductId[$product['id']];
            echo "  ✓ Product #{$product['id']}: {$product['product_name']} - MARKED AS SPONSORED\n";
        }
    }
    unset($product);
} else {
    echo "No product IDs found\n";
}

echo "\nStep 4: Final verification...\n";
$sponsoredCount = 0;
foreach ($products as $product) {
    if (!empty($product['is_sponsored'])) {
        $sponsoredCount++;
        echo "  ✓ Product #{$product['id']} is_sponsored = " . ($product['is_sponsored'] ? 'true' : 'false') . "\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total products: " . count($products) . "\n";
echo "Products marked as sponsored: {$sponsoredCount}\n";
echo "\n";
echo "✓ If sponsored count > 0, 'Sponsored' label should show in search results!\n";
echo "✓ Search URL: http://192.168.1.125:8000/products/search?q=yoga+bar\n";

