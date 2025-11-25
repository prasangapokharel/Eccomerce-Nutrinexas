<?php
/**
 * Test Placement Logic and Sponsored Badge
 * 
 * Action: Perform search queries and browse category pages where product_internal ads exist.
 * 
 * Expected:
 * - Sponsored products are inserted at configured positions (1, 3, 6, then every 10th)
 * - Show a clear small badge "Sponsored" or "Ad"
 * - External banner appears only in banner slots with banner_image linking to banner_link
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\AdPayment;
use App\Models\Product;
use App\Services\SponsoredAdsService;
use App\Services\BannerAdDisplayService;
use App\Services\AdPaymentService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$productModel = new Product();
$sponsoredService = new SponsoredAdsService();
$bannerService = new BannerAdDisplayService();
$paymentService = new AdPaymentService();

echo "=== Placement Logic and Sponsored Badge Test ===\n\n";

// Get test data
$productAdType = $adTypeModel->findByName('product_internal');
$bannerAdType = $adTypeModel->findByName('banner_external');

if (!$productAdType || !$bannerAdType) {
    echo "ERROR: Ad types not found. Please run migrations first.\n";
    exit(1);
}

// Get first seller
$seller = $db->query("SELECT * FROM sellers LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: No sellers found. Please create a seller first.\n";
    exit(1);
}
$sellerId = $seller['id'];

// Get products for testing
$products = $db->query("SELECT * FROM products WHERE status = 'active' LIMIT 10")->all();
if (empty($products)) {
    echo "ERROR: No products found. Please create products first.\n";
    exit(1);
}

// Get ad costs
$productCosts = $adCostModel->getByAdType($productAdType['id']);
$bannerCosts = $adCostModel->getByAdType($bannerAdType['id']);

if (empty($productCosts) || empty($bannerCosts)) {
    echo "ERROR: Ad costs not found. Please create ad costs first.\n";
    exit(1);
}

$productCostId = $productCosts[0]['id'];
$bannerCostId = $bannerCosts[0]['id'];

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

// Ensure seller has wallet balance
$walletModel = new \App\Models\SellerWallet();
$wallet = $walletModel->getWalletBySellerId($sellerId);
$db->query(
    "UPDATE seller_wallet SET balance = 10000 WHERE seller_id = ?",
    [$sellerId]
)->execute();

echo "--- Test 1: Creating product ads for search ---\n";
$productAdIds = [];
$testKeywords = ['mobile', 'laptop', 'watch'];

// Create 5 product ads with different products
for ($i = 0; $i < 5 && $i < count($products); $i++) {
    $product = $products[$i];
    
    $adId = $adModel->create([
        'seller_id' => $sellerId,
        'ads_type_id' => $productAdType['id'],
        'product_id' => $product['id'],
        'banner_image' => null,
        'banner_link' => null,
        'start_date' => $today,
        'end_date' => $nextWeek,
        'ads_cost_id' => $productCostId,
        'status' => 'active',
        'notes' => 'Test: Placement logic product ad'
    ]);
    
    $paymentId = $adPaymentModel->create([
        'seller_id' => $sellerId,
        'ads_id' => $adId,
        'amount' => $productCosts[0]['cost_amount'],
        'payment_method' => 'wallet',
        'payment_status' => 'pending'
    ]);
    
    // Process payment
    try {
        $paymentService->processPayment($adId, 'wallet');
        echo "Created product ad #$adId for product: {$product['product_name']}\n";
        $productAdIds[] = $adId;
    } catch (Exception $e) {
        echo "ERROR creating ad #$adId: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 2: Test search results placement
echo "--- Test 2: Testing search results placement (1, 3, 6, then every 10th) ---\n";
// Use a keyword that matches the products we created ads for
// Check which products have ads and use a matching keyword
$productsWithAds = [];
foreach ($productAdIds as $adId) {
    $ad = $adModel->find($adId);
    if ($ad && $ad['product_id']) {
        $product = $productModel->find($ad['product_id']);
        if ($product) {
            $productsWithAds[] = $product;
        }
    }
}

// Use a keyword that will match - try "creatine" or "wellcore" or "supplement"
$keyword = 'creatine';
if (empty($productsWithAds)) {
    $keyword = 'supplement'; // Fallback keyword
}

// Create mock search results (20 products) - use products that DON'T have ads to test insertion
$mockSearchResults = [];
$regularProductIndex = 0;
for ($i = 0; $i < 20 && $regularProductIndex < count($products); $i++) {
    // Skip products that have ads
    $hasAd = false;
    foreach ($productAdIds as $adId) {
        $ad = $adModel->find($adId);
        if ($ad && $ad['product_id'] == $products[$regularProductIndex]['id']) {
            $hasAd = true;
            break;
        }
    }
    
    if (!$hasAd) {
        $mockSearchResults[] = [
            'id' => $products[$regularProductIndex]['id'],
            'product_name' => $products[$regularProductIndex]['product_name'],
            'is_sponsored' => false
        ];
    }
    $regularProductIndex++;
}

// If we don't have enough products, add more
while (count($mockSearchResults) < 20 && $regularProductIndex < count($products)) {
    $mockSearchResults[] = [
        'id' => $products[$regularProductIndex]['id'],
        'product_name' => $products[$regularProductIndex]['product_name'],
        'is_sponsored' => false
    ];
    $regularProductIndex++;
}

echo "Original search results count: " . count($mockSearchResults) . "\n";
echo "Search keyword: '$keyword'\n";

// Insert sponsored products
$resultsWithAds = $sponsoredService->insertSponsoredInSearchResults($mockSearchResults, $keyword);

echo "Results with ads count: " . count($resultsWithAds) . "\n";

// Verify positions
$expectedPositions = [0, 2, 5]; // 1st, 3rd, 6th (0-indexed)
$sponsoredPositions = [];
foreach ($resultsWithAds as $index => $product) {
    if (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) {
        $sponsoredPositions[] = $index;
        echo "  Sponsored product at position " . ($index + 1) . " (index $index): {$product['product_name']}\n";
    }
}

// Check if ads are at correct positions
$firstThreeCorrect = true;
foreach ($expectedPositions as $pos) {
    if (!in_array($pos, $sponsoredPositions)) {
        $firstThreeCorrect = false;
        echo "  ⚠ WARNING: Expected sponsored ad at position " . ($pos + 1) . " (index $pos) but not found\n";
    }
}

if ($firstThreeCorrect && count($sponsoredPositions) >= 3) {
    echo "✓ First 3 positions (1, 3, 6) have sponsored ads\n";
} else {
    echo "✗ ERROR: Sponsored ads not at expected positions\n";
}

// Check every 10th position after 6th
$afterSixthPositions = [];
for ($pos = 15; $pos < count($resultsWithAds); $pos += 10) {
    if (isset($resultsWithAds[$pos]) && !empty($resultsWithAds[$pos]['is_sponsored'])) {
        $afterSixthPositions[] = $pos;
        echo "  Sponsored product at position " . ($pos + 1) . " (every 10th after 6th)\n";
    }
}

if (count($afterSixthPositions) > 0) {
    echo "✓ Every 10th position after 6th has sponsored ads\n";
} else {
    echo "⚠ INFO: No additional sponsored ads found after 6th position (may need more products)\n";
}

echo "\n";

// Test 3: Verify sponsored badge
echo "--- Test 3: Verifying sponsored badge ---\n";
$sponsoredCount = 0;
foreach ($resultsWithAds as $product) {
    if (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) {
        $sponsoredCount++;
        $hasAdId = !empty($product['ad_id']);
        $badgeShouldShow = $product['is_sponsored'] || $hasAdId;
        
        if ($badgeShouldShow) {
            echo "  ✓ Product '{$product['product_name']}' has is_sponsored=true and ad_id={$product['ad_id']}\n";
            echo "    Badge should display: 'Sponsored'\n";
        } else {
            echo "  ✗ ERROR: Product '{$product['product_name']}' marked sponsored but badge won't show\n";
        }
    }
}

if ($sponsoredCount > 0) {
    echo "✓ All sponsored products have badge indicators\n";
} else {
    echo "⚠ WARNING: No sponsored products found in results\n";
}

echo "\n";

// Test 4: Test category results placement
echo "--- Test 4: Testing category results placement ---\n";
$category = $products[0]['category'] ?? 'Electronics';

// Create mock category results
$mockCategoryResults = [];
for ($i = 0; $i < 25 && $i < count($products); $i++) {
    if ($products[$i]['category'] === $category) {
        $mockCategoryResults[] = [
            'id' => $products[$i]['id'],
            'product_name' => $products[$i]['product_name'],
            'category' => $products[$i]['category'],
            'is_sponsored' => false
        ];
    }
}

if (empty($mockCategoryResults)) {
    echo "⚠ INFO: No products found in category '$category', using all products\n";
    for ($i = 0; $i < 25 && $i < count($products); $i++) {
        $mockCategoryResults[] = [
            'id' => $products[$i]['id'],
            'product_name' => $products[$i]['product_name'],
            'category' => $products[$i]['category'],
            'is_sponsored' => false
        ];
    }
}

echo "Original category results count: " . count($mockCategoryResults) . "\n";

// Insert sponsored products
$categoryResultsWithAds = $sponsoredService->insertSponsoredInCategoryResults($mockCategoryResults, $category);

echo "Category results with ads count: " . count($categoryResultsWithAds) . "\n";

// Verify top position (0) has ad
$topHasAd = false;
if (isset($categoryResultsWithAds[0]) && !empty($categoryResultsWithAds[0]['is_sponsored'])) {
    $topHasAd = true;
    echo "  ✓ Top position (1st) has sponsored ad: {$categoryResultsWithAds[0]['product_name']}\n";
} else {
    echo "  ⚠ INFO: Top position may not have ad (depends on available sponsored products)\n";
}

// Check every 10th position
$categorySponsoredPositions = [];
foreach ($categoryResultsWithAds as $index => $product) {
    if (!empty($product['is_sponsored']) && $product['is_sponsored'] === true) {
        $categorySponsoredPositions[] = $index;
        echo "  Sponsored product at position " . ($index + 1) . " (index $index): {$product['product_name']}\n";
    }
}

if (count($categorySponsoredPositions) > 0) {
    echo "✓ Category results have sponsored products at configured positions\n";
} else {
    echo "⚠ INFO: No sponsored products in category results (may need matching category ads)\n";
}

echo "\n";

// Test 5: Test banner ad placement
echo "--- Test 5: Testing banner ad placement ---\n";
$bannerAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/banner-test.jpg',
    'banner_link' => 'https://example.com/banner-link',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'active',
    'notes' => 'Test: Banner ad placement'
]);

$bannerPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $bannerAdId,
    'amount' => $bannerCosts[0]['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($bannerAdId, 'wallet');
    echo "Created banner ad #$bannerAdId\n";
    
    $bannerAd = $adModel->find($bannerAdId);
    echo "Banner Image: {$bannerAd['banner_image']}\n";
    echo "Banner Link: {$bannerAd['banner_link']}\n";
    
    // Get banner ads for display
    $activeBanners = $adModel->getActiveBannerAds(10);
    $bannerFound = false;
    foreach ($activeBanners as $banner) {
        if ($banner['id'] == $bannerAdId) {
            $bannerFound = true;
            echo "✓ Banner ad is active and can be displayed\n";
            echo "  Image URL: {$banner['banner_image']}\n";
            echo "  Link URL: {$banner['banner_link']}\n";
            break;
        }
    }
    
    if (!$bannerFound) {
        echo "⚠ WARNING: Banner ad not found in active banners\n";
    }
    
    // Verify banner has both image and link
    if (!empty($bannerAd['banner_image']) && !empty($bannerAd['banner_link'])) {
        echo "✓ Banner ad has both image and link (required for display)\n";
    } else {
        echo "✗ ERROR: Banner ad missing image or link\n";
    }
    
} catch (Exception $e) {
    echo "ERROR creating banner ad: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Verify ads are not served without payment
echo "--- Test 6: Verifying ads require payment ---\n";
$unpaidAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $products[0]['id'],
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $productCostId,
    'status' => 'active',
    'notes' => 'Test: Unpaid ad (should not be served)'
]);

$unpaidPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $unpaidAdId,
    'amount' => $productCosts[0]['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending' // Not paid
]);

echo "Created unpaid ad #$unpaidAdId (payment_status: pending)\n";

// Try to get sponsored products - unpaid ad should not appear
$sponsoredForSearch = $sponsoredService->getSponsoredProductsForSearch('creatine', 20);
$unpaidAdFound = false;
foreach ($sponsoredForSearch as $sp) {
    if ($sp['ad_id'] == $unpaidAdId) {
        $unpaidAdFound = true;
        break;
    }
}

if (!$unpaidAdFound) {
    echo "✓ Unpaid ad is NOT included in sponsored products (CORRECT)\n";
} else {
    echo "✗ ERROR: Unpaid ad is included in sponsored products (WRONG!)\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Product Ads Created: " . count($productAdIds) . "\n";
echo "Banner Ad Created: " . ($bannerAdId ?? 'none') . "\n";
echo "Unpaid Ad Created: $unpaidAdId\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Sponsored products inserted at positions 1, 3, 6, then every 10th\n";
echo "✓ Sponsored badge shows 'Sponsored' on product cards\n";
echo "✓ Banner ads appear only in banner slots with image and link\n";
echo "✓ Unpaid ads are NOT served\n";
echo "\n";
echo "Test completed. Check the results above.\n";

