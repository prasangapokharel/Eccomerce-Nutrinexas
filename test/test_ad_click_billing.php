<?php
/**
 * Test Ad Click Billing Integration
 * Verifies that clicking ads in search results charges the wallet
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
use App\Models\SellerWallet;
use App\Services\SponsoredAdsService;
use App\Helpers\AdTrackingHelper;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$walletModel = new SellerWallet();
$sponsoredService = new SponsoredAdsService();

echo "=== Ad Click Billing Integration Test ===\n\n";

// Step 1: Get an active ad with per-click billing
echo "--- Step 1: Finding active ad with per-click billing ---\n";
$ad = $db->query(
    "SELECT * FROM ads WHERE billing_type = 'per_click' AND status = 'active' AND per_click_rate > 0 LIMIT 1"
)->single();

if (!$ad) {
    echo "No active per-click ad found. Creating test ad...\n";
    // Create a test ad
    $sellerId = 2;
    $product = $db->query("SELECT * FROM products WHERE seller_id = ? LIMIT 1", [$sellerId])->single();
    $adType = $db->query("SELECT * FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    $adCost = $db->query("SELECT * FROM ads_costs LIMIT 1")->single();
    
    $adData = [
        'seller_id' => $sellerId,
        'ads_type_id' => $adType['id'],
        'product_id' => $product['id'],
        'banner_image' => $product['image'] ?? '',
        'banner_link' => '/products/view/' . $product['id'],
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+7 days')),
        'ads_cost_id' => $adCost['id'],
        'billing_type' => 'per_click',
        'daily_budget' => 0,
        'per_click_rate' => 5.00,
        'per_impression_rate' => 0,
        'current_daily_spend' => 0,
        'last_spend_reset_date' => date('Y-m-d'),
        'auto_paused' => 0,
        'status' => 'active',
        'notes' => 'Test ad for click billing'
    ];
    
    $adId = $adModel->create($adData);
    $ad = $adModel->find($adId);
    echo "Created test ad: ID $adId\n";
}

$adId = $ad['id'];
$sellerId = $ad['seller_id'];
$perClickRate = (float)$ad['per_click_rate'];

echo "Ad ID: $adId\n";
echo "Seller ID: $sellerId\n";
echo "Per-click rate: Rs $perClickRate\n\n";

// Step 2: Get wallet balance before
echo "--- Step 2: Checking wallet balance before click ---\n";
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
$balanceBefore = (float)$walletBefore['balance'];
echo "Wallet balance before: Rs $balanceBefore\n\n";

// Step 3: Test AdTrackingHelper (simulates what happens when user clicks)
echo "--- Step 3: Testing AdTrackingHelper->trackClick() ---\n";
$result = AdTrackingHelper::trackClick($adId);
echo "Track click result: " . ($result ? 'Success' : 'Failed') . "\n\n";

// Step 4: Check wallet balance after
echo "--- Step 4: Checking wallet balance after click ---\n";
$walletAfter = $walletModel->getWalletBySellerId($sellerId);
$balanceAfter = (float)$walletAfter['balance'];
$charged = $balanceBefore - $balanceAfter;
echo "Wallet balance after: Rs $balanceAfter\n";
echo "Amount charged: Rs $charged\n\n";

// Step 5: Verify
echo "--- Step 5: Verification ---\n";
if ($charged >= $perClickRate) {
    echo "✓ SUCCESS: Wallet was charged correctly (Rs $charged >= Rs $perClickRate)\n";
} else {
    echo "✗ FAILED: Wallet was not charged correctly (Rs $charged < Rs $perClickRate)\n";
}

// Step 6: Test SponsoredAdsService directly
echo "\n--- Step 6: Testing SponsoredAdsService->logAdClick() directly ---\n";
$walletBefore2 = $walletModel->getWalletBySellerId($sellerId);
$balanceBefore2 = (float)$walletBefore2['balance'];
echo "Wallet balance before: Rs $balanceBefore2\n";

$sponsoredService->logAdClick($adId);

$walletAfter2 = $walletModel->getWalletBySellerId($sellerId);
$balanceAfter2 = (float)$walletAfter2['balance'];
$charged2 = $balanceBefore2 - $balanceAfter2;
echo "Wallet balance after: Rs $balanceAfter2\n";
echo "Amount charged: Rs $charged2\n";

if ($charged2 >= $perClickRate) {
    echo "✓ SUCCESS: SponsoredAdsService charged correctly\n";
} else {
    echo "✗ FAILED: SponsoredAdsService did not charge correctly\n";
}

echo "\n=== Test Summary ===\n";
echo "Ad ID: $adId\n";
echo "Per-click rate: Rs $perClickRate\n";
echo "Total charged (AdTrackingHelper): Rs $charged\n";
echo "Total charged (SponsoredAdsService): Rs $charged2\n";
echo "\nTest completed!\n";

