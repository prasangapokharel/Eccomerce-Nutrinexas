<?php
/**
 * Test Real-Time Ad Billing System
 * 
 * Tests:
 * 1. Create ad with daily budget
 * 2. Create ad with per-click rate
 * 3. Check wallet before showing ad
 * 4. Charge for impression
 * 5. Charge for click
 * 6. Auto-pause when balance is low
 * 7. Resume ad functionality
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Seller;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;
use App\Models\SellerWallet;
use App\Services\RealTimeAdBillingService;
use App\Services\SponsoredAdsService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$productModel = new Product();
$userModel = new User();
$sellerModel = new Seller();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$walletModel = new SellerWallet();
$billingService = new RealTimeAdBillingService();
$sponsoredService = new SponsoredAdsService();

echo "=== Real-Time Ad Billing System Test ===\n\n";

// Step 1: Get seller and ensure wallet has balance
echo "--- Step 1: Setting up seller wallet ---\n";
$seller = $db->query("SELECT * FROM sellers WHERE id = 2 LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: Seller ID 2 not found\n";
    exit(1);
}
$sellerId = 2;

$wallet = $walletModel->getWalletBySellerId($sellerId);
$initialBalance = (float)($wallet['balance'] ?? 0);
echo "Initial wallet balance: Rs $initialBalance\n";

// Top up wallet to 5000 for testing
$targetBalance = 5000.00;
if ($initialBalance < $targetBalance) {
    $topUp = $targetBalance - $initialBalance;
    $newBalance = $initialBalance + $topUp;
    $db->query(
        "UPDATE seller_wallet SET balance = ? WHERE seller_id = ?",
        [$newBalance, $sellerId]
    )->execute();
    echo "Topped up wallet: Rs $topUp\n";
}

$wallet = $walletModel->getWalletBySellerId($sellerId);
echo "Current wallet balance: Rs {$wallet['balance']}\n\n";

// Step 2: Get product
echo "--- Step 2: Getting seller product ---\n";
$product = $db->query(
    "SELECT * FROM products WHERE seller_id = ? AND status = 'active' LIMIT 1",
    [$sellerId]
)->single();

if (!$product) {
    echo "ERROR: No products found for seller\n";
    exit(1);
}

echo "Product ID: {$product['id']}\n";
echo "Product Name: {$product['product_name']}\n\n";

// Step 3: Get ad type and cost
echo "--- Step 3: Getting ad type and cost ---\n";
$adType = $adTypeModel->findByName('product_internal');
if (!$adType) {
    echo "ERROR: product_internal ad type not found\n";
    exit(1);
}

$adCosts = $adCostModel->getByAdType($adType['id']);
if (empty($adCosts)) {
    echo "ERROR: No ad costs found\n";
    exit(1);
}

$adCost = $adCosts[0];
echo "Ad Type ID: {$adType['id']}\n";
echo "Ad Cost ID: {$adCost['id']}\n";
echo "Duration: {$adCost['duration_days']} days\n\n";

// Step 4: Create ad with daily budget
echo "--- Step 4: Creating ad with daily budget ---\n";
$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+' . $adCost['duration_days'] . ' days'));
$dailyBudget = 100.00;

$adData1 = [
    'seller_id' => $sellerId,
    'ads_type_id' => $adType['id'],
    'product_id' => $product['id'],
    'banner_image' => $product['image'] ?? '',
    'banner_link' => '/products/view/' . $product['id'],
    'start_date' => $startDate,
    'end_date' => $endDate,
    'ads_cost_id' => $adCost['id'],
    'billing_type' => 'daily_budget',
    'daily_budget' => $dailyBudget,
    'per_click_rate' => 0,
    'per_impression_rate' => 0,
    'current_daily_spend' => 0,
    'last_spend_reset_date' => date('Y-m-d'),
    'auto_paused' => 0,
    'status' => 'active',
    'notes' => 'Test: Daily budget ad'
];

$adId1 = $adModel->create($adData1);
echo "✓ Ad created with daily budget: ID $adId1\n";
echo "  Daily budget: Rs $dailyBudget\n\n";

// Step 5: Create ad with per-click rate
echo "--- Step 5: Creating ad with per-click rate ---\n";
$perClickRate = 5.00;

$adData2 = [
    'seller_id' => $sellerId,
    'ads_type_id' => $adType['id'],
    'product_id' => $product['id'],
    'banner_image' => $product['image'] ?? '',
    'banner_link' => '/products/view/' . $product['id'],
    'start_date' => $startDate,
    'end_date' => $endDate,
    'ads_cost_id' => $adCost['id'],
    'billing_type' => 'per_click',
    'daily_budget' => 0,
    'per_click_rate' => $perClickRate,
    'per_impression_rate' => 0,
    'current_daily_spend' => 0,
    'last_spend_reset_date' => date('Y-m-d'),
    'auto_paused' => 0,
    'status' => 'active',
    'notes' => 'Test: Per-click ad'
];

$adId2 = $adModel->create($adData2);
echo "✓ Ad created with per-click rate: ID $adId2\n";
echo "  Per-click rate: Rs $perClickRate\n\n";

// Step 6: Test wallet checking
echo "--- Step 6: Testing wallet checking ---\n";
$canShow1 = $billingService->canShowAd($adId1);
echo "Ad #$adId1 (daily budget): " . ($canShow1['can_show'] ? '✓ Can show' : '✗ Cannot show') . "\n";
if (!$canShow1['can_show']) {
    echo "  Reason: {$canShow1['reason']}\n";
}

$canShow2 = $billingService->canShowAd($adId2);
echo "Ad #$adId2 (per-click): " . ($canShow2['can_show'] ? '✓ Can show' : '✗ Cannot show') . "\n";
if (!$canShow2['can_show']) {
    echo "  Reason: {$canShow2['reason']}\n";
}
echo "\n";

// Step 7: Test impression charging (daily budget)
echo "--- Step 7: Testing impression charging (daily budget) ---\n";
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
echo "Wallet before: Rs {$walletBefore['balance']}\n";

for ($i = 1; $i <= 5; $i++) {
    $result = $billingService->chargeImpression($adId1);
    if ($result['success']) {
        echo "  Impression #$i: Charged Rs {$result['charged']}\n";
    } else {
        echo "  Impression #$i: Failed - {$result['message']}\n";
        break;
    }
}

$walletAfter = $walletModel->getWalletBySellerId($sellerId);
$spent = $walletBefore['balance'] - $walletAfter['balance'];
echo "Total spent on impressions: Rs $spent\n";
echo "Wallet after: Rs {$walletAfter['balance']}\n";

$ad = $adModel->find($adId1);
echo "Current daily spend: Rs {$ad['current_daily_spend']}\n\n";

// Step 8: Test click charging (per-click)
echo "--- Step 8: Testing click charging (per-click) ---\n";
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
echo "Wallet before: Rs {$walletBefore['balance']}\n";

for ($i = 1; $i <= 3; $i++) {
    $result = $billingService->chargeClick($adId2);
    if ($result['success']) {
        echo "  Click #$i: Charged Rs {$result['charged']}\n";
    } else {
        echo "  Click #$i: Failed - {$result['message']}\n";
        break;
    }
}

$walletAfter = $walletModel->getWalletBySellerId($sellerId);
$spent = $walletBefore['balance'] - $walletAfter['balance'];
echo "Total spent on clicks: Rs $spent\n";
echo "Wallet after: Rs {$walletAfter['balance']}\n\n";

// Step 9: Test auto-pause when balance is low
echo "--- Step 9: Testing auto-pause when balance is low ---\n";
// Set wallet to very low balance
$db->query(
    "UPDATE seller_wallet SET balance = 0.01 WHERE seller_id = ?",
    [$sellerId]
)->execute();

$canShow = $billingService->canShowAd($adId1);
echo "Can show ad with low balance: " . ($canShow['can_show'] ? 'Yes' : 'No') . "\n";
if (!$canShow['can_show']) {
    echo "  Reason: {$canShow['reason']}\n";
}

$ad = $adModel->find($adId1);
echo "Ad auto_paused status: " . ($ad['auto_paused'] ? 'Yes' : 'No') . "\n";
echo "Ad status: {$ad['status']}\n\n";

// Step 10: Test resume functionality
echo "--- Step 10: Testing resume functionality ---\n";
// Top up wallet again
$db->query(
    "UPDATE seller_wallet SET balance = 1000 WHERE seller_id = ?",
    [$sellerId]
)->execute();

$result = $billingService->resumeAd($adId1);
if ($result['success']) {
    echo "✓ Ad resumed successfully\n";
} else {
    echo "✗ Failed to resume: {$result['message']}\n";
}

$ad = $adModel->find($adId1);
echo "Ad auto_paused status: " . ($ad['auto_paused'] ? 'Yes' : 'No') . "\n";
echo "Ad status: {$ad['status']}\n\n";

// Step 11: Test SponsoredAdsService integration
echo "--- Step 11: Testing SponsoredAdsService integration ---\n";
$sponsoredProducts = $sponsoredService->getSponsoredProductsForSearch('test', 5);
echo "Found " . count($sponsoredProducts) . " sponsored products\n";

// Test logging view (should charge)
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
$sponsoredService->logAdView($adId1);
$walletAfter = $walletModel->getWalletBySellerId($sellerId);
$charged = $walletBefore['balance'] - $walletAfter['balance'];
echo "Charged for view: Rs $charged\n";

// Test logging click (should charge for per-click ad)
$walletBefore = $walletModel->getWalletBySellerId($sellerId);
$sponsoredService->logAdClick($adId2);
$walletAfter = $walletModel->getWalletBySellerId($sellerId);
$charged = $walletBefore['balance'] - $walletAfter['balance'];
echo "Charged for click: Rs $charged\n\n";

// Summary
echo "=== Test Summary ===\n";
$finalWallet = $walletModel->getWalletBySellerId($sellerId);
echo "Initial wallet balance: Rs $initialBalance\n";
echo "Final wallet balance: Rs {$finalWallet['balance']}\n";
echo "Total spent: Rs " . ($initialBalance - $finalWallet['balance']) . "\n";
echo "Ad #$adId1 (daily budget): Status = {$adModel->find($adId1)['status']}\n";
echo "Ad #$adId2 (per-click): Status = {$adModel->find($adId2)['status']}\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Ads created with daily budget and per-click rates\n";
echo "✓ Wallet checked before showing ads\n";
echo "✓ Impressions charged for daily budget ads\n";
echo "✓ Clicks charged for per-click ads\n";
echo "✓ Ads auto-paused when balance is low\n";
echo "✓ Ads can be resumed when balance is sufficient\n";
echo "✓ SponsoredAdsService integrates with billing system\n";
echo "\n";
echo "Test completed!\n";

