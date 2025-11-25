<?php
/**
 * Test Ad Scheduling and Expiry
 * 
 * Creates two ads:
 * 1. Ad with start_date in future (should be scheduled, not visible)
 * 2. Ad with end_date yesterday (should be expired, not shown)
 * 
 * Expected:
 * - Future ad is in scheduled state and not visible
 * - Expired ad is moved to expired state and not shown
 * - Admin can manually reactivate only if dates updated
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
use App\Models\Seller;

$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$productModel = new Product();
$sellerModel = new Seller();

echo "=== Ad Scheduling and Expiry Test ===\n\n";

// Get test data
$bannerAdType = $adTypeModel->findByName('banner_external');
$productAdType = $adTypeModel->findByName('product_internal');

if (!$bannerAdType || !$productAdType) {
    echo "ERROR: Ad types not found. Please run migrations first.\n";
    exit(1);
}

// Get first seller
$db = \App\Core\Database::getInstance();
$seller = $db->query("SELECT * FROM sellers LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: No sellers found. Please create a seller first.\n";
    exit(1);
}
$sellerId = $seller['id'];

// Get first product for product ad
$product = $db->query("SELECT * FROM products LIMIT 1")->single();
$productId = null;
if ($product) {
    $productId = $product['id'];
}

// Get ad costs
$bannerCosts = $adCostModel->getByAdType($bannerAdType['id']);
$productCosts = $adCostModel->getByAdType($productAdType['id']);

if (empty($bannerCosts) || empty($productCosts)) {
    echo "ERROR: Ad costs not found. Please create ad costs first.\n";
    exit(1);
}

$bannerCostId = $bannerCosts[0]['id'];
$productCostId = $productCosts[0]['id'];

// Calculate dates
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$nextWeek = date('Y-m-d', strtotime('+7 days'));

echo "Today: $today\n";
echo "Yesterday: $yesterday\n";
echo "Tomorrow: $tomorrow\n";
echo "Next Week: $nextWeek\n\n";

// Test 1: Create ad with start_date in future (should be scheduled)
echo "--- Test 1: Creating ad with start_date in future ---\n";
$futureAdData = [
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/banner-future.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $nextWeek,
    'end_date' => date('Y-m-d', strtotime($nextWeek . ' +30 days')),
    'ads_cost_id' => $bannerCostId,
    'status' => 'active', // Set as active initially
    'notes' => 'Test: Future scheduled ad'
];

$futureAdId = $adModel->create($futureAdData);
echo "Created future ad with ID: $futureAdId\n";
echo "Start Date: {$futureAdData['start_date']}\n";
echo "End Date: {$futureAdData['end_date']}\n";

// Create payment for future ad
$adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $futureAdId,
    'amount' => $bannerCosts[0]['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'paid'
]);
echo "Payment created for future ad\n\n";

// Test 2: Create ad with end_date yesterday (should be expired)
echo "--- Test 2: Creating ad with end_date yesterday ---\n";
$expiredAdData = [
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $productId,
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => date('Y-m-d', strtotime('-30 days')),
    'end_date' => $yesterday,
    'ads_cost_id' => $productCostId,
    'status' => 'active', // Set as active initially
    'notes' => 'Test: Expired ad'
];

$expiredAdId = $adModel->create($expiredAdData);
echo "Created expired ad with ID: $expiredAdId\n";
echo "Start Date: {$expiredAdData['start_date']}\n";
echo "End Date: {$expiredAdData['end_date']}\n";

// Create payment for expired ad
$adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $expiredAdId,
    'amount' => $productCosts[0]['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'paid'
]);
echo "Payment created for expired ad\n\n";

// Test 3: Check current status and visibility
echo "--- Test 3: Checking ad status and visibility ---\n";

$futureAd = $adModel->find($futureAdId);
$expiredAd = $adModel->find($expiredAdId);

echo "Future Ad Status: {$futureAd['status']}\n";
echo "Expired Ad Status: {$expiredAd['status']}\n\n";

// Check if ads are visible in active ads query
$activeAds = $adModel->getActiveAds();
$futureAdVisible = false;
$expiredAdVisible = false;

foreach ($activeAds as $ad) {
    if ($ad['id'] == $futureAdId) {
        $futureAdVisible = true;
    }
    if ($ad['id'] == $expiredAdId) {
        $expiredAdVisible = true;
    }
}

echo "Future Ad Visible in Active Ads: " . ($futureAdVisible ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n";
echo "Expired Ad Visible in Active Ads: " . ($expiredAdVisible ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n\n";

// Test 4: Update ad statuses based on dates using AdStatusService
echo "--- Test 4: Updating ad statuses based on dates using AdStatusService ---\n";

$adStatusService = new \App\Services\AdStatusService();
$updateResult = $adStatusService->updateAllAdStatuses();

if ($updateResult) {
    echo "Ad statuses updated successfully using AdStatusService\n";
} else {
    echo "WARNING: AdStatusService update failed, manually updating...\n";
    // Fallback: manual update
    if (strtotime($futureAd['start_date']) > strtotime($today)) {
        $adModel->updateStatus($futureAdId, 'inactive');
        echo "Future ad status updated to 'inactive' (scheduled)\n";
    }
    if (strtotime($expiredAd['end_date']) < strtotime($today)) {
        $adModel->updateStatus($expiredAdId, 'expired');
        echo "Expired ad status updated to 'expired'\n";
    }
}

// Re-check status
$futureAd = $adModel->find($futureAdId);
$expiredAd = $adModel->find($expiredAdId);

echo "Future Ad Status After Update: {$futureAd['status']}\n";
echo "Expired Ad Status After Update: {$expiredAd['status']}\n\n";

// Test 5: Verify ads are not visible
echo "--- Test 5: Verifying ads are not visible ---\n";
$activeAds = $adModel->getActiveAds();
$futureAdVisible = false;
$expiredAdVisible = false;

foreach ($activeAds as $ad) {
    if ($ad['id'] == $futureAdId) {
        $futureAdVisible = true;
    }
    if ($ad['id'] == $expiredAdId) {
        $expiredAdVisible = true;
    }
}

echo "Future Ad Visible: " . ($futureAdVisible ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n";
echo "Expired Ad Visible: " . ($expiredAdVisible ? 'YES (ERROR!)' : 'NO (CORRECT)') . "\n\n";

// Test 6: Test admin reactivation (only if dates updated)
echo "--- Test 6: Testing admin reactivation ---\n";

// Try to reactivate expired ad without updating dates (should fail or stay expired)
echo "Attempting to reactivate expired ad without updating dates...\n";
$adModel->updateStatus($expiredAdId, 'active');
$expiredAd = $adModel->find($expiredAdId);
echo "Expired Ad Status After Reactivation Attempt: {$expiredAd['status']}\n";

// Check if it's visible (should not be because end_date < today)
$activeAds = $adModel->getActiveAds();
$expiredAdVisible = false;
foreach ($activeAds as $ad) {
    if ($ad['id'] == $expiredAdId) {
        $expiredAdVisible = true;
    }
}
echo "Expired Ad Visible After Reactivation: " . ($expiredAdVisible ? 'YES (ERROR!)' : 'NO (CORRECT - dates prevent visibility)') . "\n\n";

// Update expired ad dates to future
echo "Updating expired ad dates to future...\n";
$adModel->getDb()->query(
    "UPDATE ads SET start_date = ?, end_date = ? WHERE id = ?",
    [$tomorrow, date('Y-m-d', strtotime($tomorrow . ' +30 days')), $expiredAdId]
)->execute();

$expiredAd = $adModel->find($expiredAdId);
echo "Updated Start Date: {$expiredAd['start_date']}\n";
echo "Updated End Date: {$expiredAd['end_date']}\n";

// Now reactivate
$adModel->updateStatus($expiredAdId, 'active');
$expiredAd = $adModel->find($expiredAdId);
echo "Expired Ad Status After Date Update and Reactivation: {$expiredAd['status']}\n";

// Check visibility (should still not be visible because start_date > today)
$activeAds = $adModel->getActiveAds();
$expiredAdVisible = false;
foreach ($activeAds as $ad) {
    if ($ad['id'] == $expiredAdId) {
        $expiredAdVisible = true;
    }
}
echo "Expired Ad Visible After Date Update: " . ($expiredAdVisible ? 'YES (ERROR - start_date in future!)' : 'NO (CORRECT)') . "\n\n";

// Summary
echo "=== Test Summary ===\n";
echo "Future Ad ID: $futureAdId\n";
echo "Expired Ad ID: $expiredAdId\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Future ad should be in 'inactive' state (scheduled)\n";
echo "✓ Expired ad should be in 'expired' state\n";
echo "✓ Both ads should NOT be visible in active ads query\n";
echo "✓ Admin cannot reactivate expired ad without updating dates\n";
echo "✓ Even with updated dates, ad won't show if start_date > today\n";
echo "\n";
echo "Test completed. Check the results above.\n";

