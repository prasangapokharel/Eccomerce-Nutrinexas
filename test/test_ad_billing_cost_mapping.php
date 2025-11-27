<?php
/**
 * Test Ad Billing and Cost Mapping
 * 
 * Action: Run an ad for a day, simulate clicks and reach. Verify ads_cost mapping and ads_payments.
 * 
 * Expected:
 * - System records cost per ads_cost plan
 * - Marks payment_status paid
 * - Deducts credits from seller if wallet model used
 * - Does not serve ads when payment fails
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
use App\Models\SellerWallet;
use App\Services\AdPaymentService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$productModel = new Product();
$walletModel = new SellerWallet();
$paymentService = new AdPaymentService();

echo "=== Ad Billing and Cost Mapping Test ===\n\n";

// Get test data
$bannerAdType = $adTypeModel->findByName('banner_external');
$productAdType = $adTypeModel->findByName('product_internal');

if (!$bannerAdType || !$productAdType) {
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
$bannerCostAmount = $bannerCosts[0]['cost_amount'];
$productCostAmount = $productCosts[0]['cost_amount'];

echo "Banner Cost: Rs " . $bannerCostAmount . " (ID: $bannerCostId)\n";
echo "Product Cost: Rs " . $productCostAmount . " (ID: $productCostId)\n\n";

// Test 1: Create ad with proper cost mapping
echo "--- Test 1: Creating ad with cost mapping ---\n";
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$nextWeek = date('Y-m-d', strtotime('+7 days'));

$adData = [
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/banner-test.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'inactive',
    'notes' => 'Test: Billing and cost mapping'
];

$adId = $adModel->create($adData);
echo "Created ad with ID: $adId\n";
echo "Cost Plan ID: {$adData['ads_cost_id']}\n";
echo "Start Date: {$adData['start_date']}\n";
echo "End Date: {$adData['end_date']}\n\n";

// Test 2: Verify ads_cost mapping
echo "--- Test 2: Verifying ads_cost mapping ---\n";
$ad = $adModel->findWithDetails($adId);
$cost = $adCostModel->find($ad['ads_cost_id']);

echo "Ad Cost ID: {$ad['ads_cost_id']}\n";
echo "Cost Amount: Rs {$cost['cost_amount']}\n";
echo "Duration Days: {$cost['duration_days']}\n";
echo "Ad Type: {$ad['ad_type_name']}\n";

if ($ad['ads_cost_id'] == $bannerCostId && $cost['cost_amount'] == $bannerCostAmount) {
    echo "✓ Cost mapping verified correctly\n\n";
} else {
    echo "✗ ERROR: Cost mapping mismatch!\n\n";
}

// Test 3: Create payment record
echo "--- Test 3: Creating payment record ---\n";
$paymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $adId,
    'amount' => $cost['cost_amount'],
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

echo "Created payment record with ID: $paymentId\n";
$payment = $adPaymentModel->getByAdId($adId);
echo "Payment Status: {$payment['payment_status']}\n";
echo "Payment Amount: Rs {$payment['amount']}\n";
echo "Payment Method: {$payment['payment_method']}\n\n";

// Test 4: Add wallet balance to seller
echo "--- Test 4: Adding wallet balance to seller ---\n";
$initialBalance = 5000.00; // Rs 5000
$wallet = $walletModel->getWalletBySellerId($sellerId);
$db->query(
    "UPDATE seller_wallet SET balance = ? WHERE seller_id = ?",
    [$initialBalance, $sellerId]
)->execute();

$wallet = $walletModel->getWalletBySellerId($sellerId);
echo "Initial Wallet Balance: Rs {$wallet['balance']}\n";
echo "Ad Cost: Rs {$cost['cost_amount']}\n";
echo "Expected Balance After Payment: Rs " . ($wallet['balance'] - $cost['cost_amount']) . "\n\n";

// Test 5: Process payment (deduct from wallet)
echo "--- Test 5: Processing payment (deducting from wallet) ---\n";
try {
    $paymentService->processPayment($adId, 'wallet');
    echo "✓ Payment processed successfully\n";
    
    $payment = $adPaymentModel->getByAdId($adId);
    echo "Payment Status After Processing: {$payment['payment_status']}\n";
    
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    echo "Wallet Balance After Payment: Rs {$wallet['balance']}\n";
    
    $expectedBalance = $initialBalance - $cost['cost_amount'];
    if (abs($wallet['balance'] - $expectedBalance) < 0.01) {
        echo "✓ Wallet balance deducted correctly\n\n";
    } else {
        echo "✗ ERROR: Wallet balance mismatch! Expected: Rs $expectedBalance, Got: Rs {$wallet['balance']}\n\n";
    }
} catch (Exception $e) {
    echo "✗ ERROR: Payment processing failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Verify wallet transaction recorded
echo "--- Test 6: Verifying wallet transaction ---\n";
$transactions = $walletModel->getTransactions($sellerId, 5, 0);
$adTransaction = null;
foreach ($transactions as $tx) {
    if (strpos($tx['description'] ?? '', "Ad #{$adId}") !== false) {
        $adTransaction = $tx;
        break;
    }
}

if ($adTransaction) {
    echo "✓ Wallet transaction found\n";
    echo "Transaction Type: {$adTransaction['type']}\n";
    echo "Transaction Amount: Rs {$adTransaction['amount']}\n";
    echo "Balance After: Rs {$adTransaction['balance_after']}\n";
    echo "Description: {$adTransaction['description']}\n\n";
} else {
    echo "✗ ERROR: Wallet transaction not found!\n\n";
}

// Test 7: Simulate clicks and reach
echo "--- Test 7: Simulating clicks and reach ---\n";
$simulatedReach = 150;
$simulatedClicks = 25;

for ($i = 0; $i < $simulatedReach; $i++) {
    $ipAddress = '192.168.1.' . rand(100, 255);
    $adModel->logReach($adId, $ipAddress);
}

for ($i = 0; $i < $simulatedClicks; $i++) {
    $ipAddress = '192.168.1.' . rand(100, 255);
    $adModel->logClick($adId, $ipAddress);
}

$ad = $adModel->find($adId);
echo "Simulated Reach: $simulatedReach\n";
echo "Simulated Clicks: $simulatedClicks\n";
echo "Ad Reach Count: {$ad['reach']}\n";
echo "Ad Click Count: {$ad['click']}\n";

if ($ad['reach'] >= $simulatedReach && $ad['click'] >= $simulatedClicks) {
    echo "✓ Clicks and reach recorded correctly\n\n";
} else {
    echo "⚠ WARNING: Reach/Click counts may not match exactly (deduplication)\n\n";
}

// Test 8: Activate ad and verify it's served
echo "--- Test 8: Activating ad and verifying it's served ---\n";
$adModel->updateStatus($adId, 'active');
$ad = $adModel->find($adId);
echo "Ad Status: {$ad['status']}\n";

$activeAds = $adModel->getActiveAds();
$adServed = false;
foreach ($activeAds as $activeAd) {
    if ($activeAd['id'] == $adId) {
        $adServed = true;
        break;
    }
}

if ($adServed) {
    echo "✓ Ad is being served (appears in active ads)\n\n";
} else {
    echo "✗ ERROR: Ad is not being served!\n\n";
}

// Test 9: Create ad with failed payment
echo "--- Test 9: Testing ad with failed payment ---\n";
$adData2 = [
    'seller_id' => $sellerId,
    'ads_type_id' => $productAdType['id'],
    'product_id' => $productId,
    'banner_image' => null,
    'banner_link' => null,
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $productCostId,
    'status' => 'active',
    'notes' => 'Test: Failed payment ad'
];

$adId2 = $adModel->create($adData2);
echo "Created ad with ID: $adId2\n";

$paymentId2 = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $adId2,
    'amount' => $productCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'failed'
]);

echo "Created payment with status: failed\n";

$activeAds = $adModel->getActiveAds();
$failedAdServed = false;
foreach ($activeAds as $activeAd) {
    if ($activeAd['id'] == $adId2) {
        $failedAdServed = true;
        break;
    }
}

if (!$failedAdServed) {
    echo "✓ Ad with failed payment is NOT being served (CORRECT)\n\n";
} else {
    echo "✗ ERROR: Ad with failed payment is being served (WRONG!)\n\n";
}

// Test 10: Test insufficient balance scenario
echo "--- Test 10: Testing insufficient wallet balance ---\n";
$adData3 = [
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/banner-insufficient.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'inactive',
    'notes' => 'Test: Insufficient balance'
];

$adId3 = $adModel->create($adData3);
echo "Created ad with ID: $adId3\n";

// Set wallet balance to very low amount
$lowBalance = 10.00;
$db->query(
    "UPDATE seller_wallet SET balance = ? WHERE seller_id = ?",
    [$lowBalance, $sellerId]
)->execute();

$paymentId3 = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $adId3,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

echo "Wallet Balance: Rs $lowBalance\n";
echo "Ad Cost: Rs $bannerCostAmount\n";

try {
    $paymentService->processPayment($adId3, 'wallet');
    echo "✗ ERROR: Payment should have failed due to insufficient balance!\n\n";
} catch (Exception $e) {
    echo "✓ Payment correctly failed: " . $e->getMessage() . "\n";
    
    $payment = $adPaymentModel->getByAdId($adId3);
    echo "Payment Status: {$payment['payment_status']}\n";
    
    if ($payment['payment_status'] === 'failed') {
        echo "✓ Payment status correctly set to 'failed'\n\n";
    } else {
        echo "✗ ERROR: Payment status should be 'failed'\n\n";
    }
}

// Test 11: Verify failed payment ad is not served
echo "--- Test 11: Verifying failed payment ad is not served ---\n";
$adModel->updateStatus($adId3, 'active');
$activeAds = $adModel->getActiveAds();
$insufficientAdServed = false;
foreach ($activeAds as $activeAd) {
    if ($activeAd['id'] == $adId3) {
        $insufficientAdServed = true;
        break;
    }
}

if (!$insufficientAdServed) {
    echo "✓ Ad with insufficient balance is NOT being served (CORRECT)\n\n";
} else {
    echo "✗ ERROR: Ad with insufficient balance is being served (WRONG!)\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "Ad ID (Paid): $adId\n";
echo "Ad ID (Failed Payment): $adId2\n";
echo "Ad ID (Insufficient Balance): $adId3\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Cost mapping correctly recorded from ads_cost\n";
echo "✓ Payment status marked as 'paid' after processing\n";
echo "✓ Wallet balance deducted correctly\n";
echo "✓ Wallet transaction recorded\n";
echo "✓ Clicks and reach logged correctly\n";
echo "✓ Paid ad is served\n";
echo "✓ Failed payment ad is NOT served\n";
echo "✓ Insufficient balance ad is NOT served\n";
echo "\n";
echo "Test completed. Check the results above.\n";





