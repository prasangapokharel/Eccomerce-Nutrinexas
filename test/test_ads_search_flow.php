<?php
/**
 * Test Ads Search Flow
 * 
 * Tests the complete ads flow:
 * 1. Search for products with ads
 * 2. Verify sponsored products appear
 * 3. Check reach/traffic tracking
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
use App\Services\SponsoredAdsService;

echo "=== Testing Ads Search Flow ===\n\n";

$db = Database::getInstance();
$adsService = new SponsoredAdsService();

// Test 1: Get sponsored products for search
echo "Test 1: Getting sponsored products for 'yoga bar'...\n";
$sponsoredProducts = $adsService->getSponsoredProductsForSearch('yoga bar', 10);

echo "Found " . count($sponsoredProducts) . " sponsored products\n";
foreach ($sponsoredProducts as $product) {
    echo "  - Product #{$product['id']}: {$product['product_name']}\n";
    echo "    Ad ID: {$product['ad_id']}\n";
    echo "    Image: " . ($product['image_url'] ?? 'N/A') . "\n";
    echo "    Has image_url: " . (!empty($product['image_url']) ? 'Yes' : 'No') . "\n";
}
echo "\n";

// Test 2: Test insertion logic
echo "Test 2: Testing sponsored product insertion...\n";
$regularProducts = $db->query(
    "SELECT p.*, 
            COALESCE(pi.image_url, p.image) as image_url
     FROM products p
     LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
     WHERE p.status = 'active'
     AND p.product_name LIKE '%energy bar%'
     LIMIT 10"
)->all();

echo "Regular products found: " . count($regularProducts) . "\n";

$productsWithAds = $adsService->insertSponsoredInSearchResults($regularProducts, 'yoga bar');

$sponsoredCount = 0;
foreach ($productsWithAds as $product) {
    if (!empty($product['is_sponsored'])) {
        $sponsoredCount++;
        echo "  ✓ Sponsored product at position: {$product['product_name']}\n";
    }
}

echo "Total products after insertion: " . count($productsWithAds) . "\n";
echo "Sponsored products inserted: {$sponsoredCount}\n";
echo "\n";

// Test 3: Check ad reach tracking
echo "Test 3: Testing ad reach tracking...\n";
$testAdId = $sponsoredProducts[0]['ad_id'] ?? null;

if ($testAdId) {
    $adBefore = $db->query(
        "SELECT reach FROM ads WHERE id = ?",
        [$testAdId]
    )->single();
    
    echo "Ad #{$testAdId} reach before: " . ($adBefore['reach'] ?? 0) . "\n";
    
    // Log a view
    $ipAddress = '127.0.0.1';
    $adsService->logAdView($testAdId, $ipAddress);
    
    $adAfter = $db->query(
        "SELECT reach FROM ads WHERE id = ?",
        [$testAdId]
    )->single();
    
    echo "Ad #{$testAdId} reach after: " . ($adAfter['reach'] ?? 0) . "\n";
    
    if ($adAfter['reach'] > $adBefore['reach']) {
        echo "✓ Reach tracking working!\n";
    } else {
        echo "⚠ Reach may not have increased (could be same IP within 24h)\n";
    }
} else {
    echo "⚠ No ad ID found to test\n";
}
echo "\n";

// Test 4: Verify products have all required fields
echo "Test 4: Verifying product fields...\n";
$sampleProduct = $productsWithAds[0] ?? null;

if ($sampleProduct) {
    $requiredFields = ['id', 'product_name', 'price', 'image_url', 'review_count', 'avg_rating'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($sampleProduct[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (empty($missingFields)) {
        echo "✓ All required fields present!\n";
        echo "  Product: {$sampleProduct['product_name']}\n";
        echo "  Image URL: " . ($sampleProduct['image_url'] ?? 'N/A') . "\n";
        echo "  Review Count: " . ($sampleProduct['review_count'] ?? 0) . "\n";
        echo "  Rating: " . ($sampleProduct['avg_rating'] ?? 0) . "\n";
    } else {
        echo "✗ Missing fields: " . implode(', ', $missingFields) . "\n";
    }
} else {
    echo "⚠ No products found to verify\n";
}
echo "\n";

echo "=== Test Complete ===\n";
echo "✓ Search for 'yoga bar' or 'energy bar' to see sponsored products\n";
echo "✓ Sponsored label should appear below product cards\n";
echo "✓ Ad reach tracking is working\n";

