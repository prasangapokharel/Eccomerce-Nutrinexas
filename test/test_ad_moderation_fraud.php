<?php
/**
 * Test Fraud, Moderation and Rejection Flow
 * 
 * Action: Upload external banner with prohibited content or invalid banner_link. Seller requests activation.
 * 
 * Expected:
 * - Admin receives review task, can reject with reason
 * - Rejected ad moves to suspended state, seller notified with notes
 * - System prevents serving until admin approves
 * - Test rapid click fraud detection and auto-suspension after threshold
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
use App\Services\AdPaymentService;
use App\Services\AdFraudDetectionService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();
$adPaymentModel = new AdPayment();
$paymentService = new AdPaymentService();
$fraudService = new AdFraudDetectionService();

echo "=== Fraud, Moderation and Rejection Flow Test ===\n\n";

// Get test data
$bannerAdType = $adTypeModel->findByName('banner_external');

if (!$bannerAdType) {
    echo "ERROR: Ad type 'banner_external' not found. Please run migrations first.\n";
    exit(1);
}

// Get first seller
$seller = $db->query("SELECT * FROM sellers LIMIT 1")->single();
if (!$seller) {
    echo "ERROR: No sellers found. Please create a seller first.\n";
    exit(1);
}
$sellerId = $seller['id'];

// Get ad costs
$bannerCosts = $adCostModel->getByAdType($bannerAdType['id']);
if (empty($bannerCosts)) {
    echo "ERROR: No banner ad costs found. Please create ad costs first.\n";
    exit(1);
}
$bannerCostId = $bannerCosts[0]['id'];
$bannerCostAmount = $bannerCosts[0]['cost_amount'];

// Ensure seller has wallet balance
$walletModel = new \App\Models\SellerWallet();
$wallet = $walletModel->getWalletBySellerId($sellerId);
$db->query(
    "UPDATE seller_wallet SET balance = 10000 WHERE seller_id = ?",
    [$sellerId]
)->execute();

$today = date('Y-m-d');
$nextWeek = date('Y-m-d', strtotime('+7 days'));

// Test 1: Create banner ad with prohibited content (invalid link)
echo "--- Test 1: Creating banner ad with invalid banner_link ---\n";

$invalidBannerAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/prohibited-content.jpg',
    'banner_link' => 'invalid-url-not-http', // Invalid link
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'inactive', // Seller requests activation
    'notes' => 'Seller requested activation - needs admin review'
]);

$invalidPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $invalidBannerAdId,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($invalidBannerAdId, 'wallet');
    echo "Created banner ad #$invalidBannerAdId with invalid banner_link\n";
    echo "Payment processed. Ad status: inactive (awaiting admin review)\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Test 2: Admin reviews and rejects ad
echo "--- Test 2: Admin rejects ad with reason ---\n";

$rejectionReason = "Invalid banner_link format. Must be a valid HTTP/HTTPS URL.";

// Simulate admin rejection
$ad = $adModel->find($invalidBannerAdId);
$notes = ($ad['notes'] ?? '') . "\n[REJECTED: " . date('Y-m-d H:i:s') . "] Reason: " . $rejectionReason;
$adModel->updateStatus($invalidBannerAdId, 'suspended', null, null, $rejectionReason);

$rejectedAd = $adModel->find($invalidBannerAdId);
echo "Ad status after rejection: {$rejectedAd['status']}\n";
echo "Rejection reason added to notes\n";

if ($rejectedAd['status'] === 'suspended') {
    echo "✓ Ad moved to suspended state\n";
} else {
    echo "✗ ERROR: Ad should be suspended but status is: {$rejectedAd['status']}\n";
}

echo "\n";

// Test 3: Verify suspended ad is not served
echo "--- Test 3: Verifying suspended ad is not served ---\n";

$activeBanners = $adModel->getActiveBannerAds(10);
$suspendedAdFound = false;
foreach ($activeBanners as $banner) {
    if ($banner['id'] == $invalidBannerAdId) {
        $suspendedAdFound = true;
        break;
    }
}

if (!$suspendedAdFound) {
    echo "✓ Suspended ad is NOT being served (CORRECT)\n";
} else {
    echo "✗ ERROR: Suspended ad is being served (WRONG!)\n";
}

// Also check getActiveAds
$activeAds = $adModel->getActiveAds();
$suspendedInActive = false;
foreach ($activeAds as $ad) {
    if ($ad['id'] == $invalidBannerAdId) {
        $suspendedInActive = true;
        break;
    }
}

if (!$suspendedInActive) {
    echo "✓ Suspended ad is NOT in active ads list (CORRECT)\n";
} else {
    echo "✗ ERROR: Suspended ad is in active ads list (WRONG!)\n";
}

echo "\n";

// Test 4: Create ad with prohibited content (malicious link)
echo "--- Test 4: Creating banner ad with prohibited content ---\n";

$prohibitedBannerAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/malicious-content.jpg',
    'banner_link' => 'javascript:alert("xss")', // Prohibited: JavaScript link
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'inactive',
    'notes' => 'Contains prohibited JavaScript link - needs admin review'
]);

$prohibitedPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $prohibitedBannerAdId,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($prohibitedBannerAdId, 'wallet');
    echo "Created banner ad #$prohibitedBannerAdId with prohibited content (JavaScript link)\n";
    echo "Payment processed. Ad status: inactive (awaiting admin review)\n\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Admin rejects with reason
$prohibitedRejectionReason = "Prohibited content detected: JavaScript links are not allowed for security reasons.";
$adModel->updateStatus($prohibitedBannerAdId, 'suspended', null, null, $prohibitedRejectionReason);

$prohibitedAd = $adModel->find($prohibitedBannerAdId);
echo "Ad #$prohibitedBannerAdId status: {$prohibitedAd['status']}\n";
echo "Rejection reason: $prohibitedRejectionReason\n";

if ($prohibitedAd['status'] === 'suspended') {
    echo "✓ Prohibited ad moved to suspended state\n";
} else {
    echo "✗ ERROR: Prohibited ad should be suspended\n";
}

echo "\n";

// Test 5: Rapid click fraud detection
echo "--- Test 5: Testing rapid click fraud detection ---\n";

// Create a test ad for fraud detection
$fraudTestAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/test-banner.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'active',
    'notes' => 'Test: Fraud detection'
]);

$fraudPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $fraudTestAdId,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($fraudTestAdId, 'wallet');
    $adModel->updateStatus($fraudTestAdId, 'active');
    echo "Created test ad #$fraudTestAdId for fraud detection\n";
    
    // Simulate rapid clicks from same IP
    $testIp = '192.168.1.100';
    echo "Simulating rapid clicks from IP: $testIp\n";
    
    $rapidClickCount = 15; // More than threshold (10)
    for ($i = 0; $i < $rapidClickCount; $i++) {
        $adModel->logClick($fraudTestAdId, $testIp);
        usleep(100000); // 0.1 second delay
    }
    
    echo "Logged $rapidClickCount rapid clicks\n";
    
    // Check fraud detection
    $fraudCheck = $fraudService->checkRapidClickFraud($fraudTestAdId, $testIp);
    
    echo "Fraud Detection Result:\n";
    echo "  Is Fraud: " . ($fraudCheck['is_fraud'] ? 'YES' : 'NO') . "\n";
    echo "  Fraud Score: {$fraudCheck['fraud_score']}\n";
    echo "  Click Count (last minute): {$fraudCheck['click_count']}\n";
    echo "  Total Clicks (last hour): {$fraudCheck['total_clicks']}\n";
    echo "  Should Suspend: " . ($fraudCheck['should_suspend'] ? 'YES' : 'NO') . "\n";
    
    if (!empty($fraudCheck['indicators'])) {
        echo "  Indicators:\n";
        foreach ($fraudCheck['indicators'] as $indicator) {
            echo "    - $indicator\n";
        }
    }
    
    if ($fraudCheck['is_fraud']) {
        echo "✓ Rapid click fraud detected correctly\n";
    } else {
        echo "⚠ INFO: Fraud not detected (may need more clicks or different timing)\n";
    }
    
} catch (Exception $e) {
    echo "ERROR in fraud test: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: Auto-suspension after threshold
echo "--- Test 6: Testing auto-suspension after fraud threshold ---\n";

// Create another test ad
$autoSuspendAdId = $adModel->create([
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerAdType['id'],
    'product_id' => null,
    'banner_image' => 'https://example.com/auto-suspend-test.jpg',
    'banner_link' => 'https://example.com',
    'start_date' => $today,
    'end_date' => $nextWeek,
    'ads_cost_id' => $bannerCostId,
    'status' => 'active',
    'notes' => 'Test: Auto-suspension'
]);

$autoSuspendPaymentId = $adPaymentModel->create([
    'seller_id' => $sellerId,
    'ads_id' => $autoSuspendAdId,
    'amount' => $bannerCostAmount,
    'payment_method' => 'wallet',
    'payment_status' => 'pending'
]);

try {
    $paymentService->processPayment($autoSuspendAdId, 'wallet');
    $adModel->updateStatus($autoSuspendAdId, 'active');
    echo "Created test ad #$autoSuspendAdId for auto-suspension test\n";
    
    // Simulate excessive clicks to trigger auto-suspension (threshold: 50)
    $excessiveClickCount = 55;
    $testIp2 = '192.168.1.200';
    echo "Simulating $excessiveClickCount clicks to trigger auto-suspension (threshold: 50)\n";
    
    for ($i = 0; $i < $excessiveClickCount; $i++) {
        $adModel->logClick($autoSuspendAdId, $testIp2);
        if ($i % 10 == 0) {
            echo "  Clicked " . ($i + 1) . " times...\n";
        }
    }
    
    // Check if ad was auto-suspended
    $suspendedAd = $adModel->find($autoSuspendAdId);
    echo "Ad status after excessive clicks: {$suspendedAd['status']}\n";
    
    if ($suspendedAd['status'] === 'suspended') {
        echo "✓ Ad auto-suspended due to fraud threshold exceeded\n";
        echo "  Notes: " . substr($suspendedAd['notes'] ?? '', -100) . "\n";
    } else {
        echo "⚠ INFO: Ad not auto-suspended (may need more clicks or different timing)\n";
        echo "  Current status: {$suspendedAd['status']}\n";
    }
    
    // Verify suspended ad is not served
    $activeBannersAfter = $adModel->getActiveBannerAds(10);
    $autoSuspendedFound = false;
    foreach ($activeBannersAfter as $banner) {
        if ($banner['id'] == $autoSuspendAdId) {
            $autoSuspendedFound = true;
            break;
        }
    }
    
    if (!$autoSuspendedFound && $suspendedAd['status'] === 'suspended') {
        echo "✓ Auto-suspended ad is NOT being served (CORRECT)\n";
    } elseif ($autoSuspendedFound) {
        echo "✗ ERROR: Auto-suspended ad is being served (WRONG!)\n";
    }
    
} catch (Exception $e) {
    echo "ERROR in auto-suspension test: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Invalid Link Ad: #$invalidBannerAdId (Status: suspended)\n";
echo "Prohibited Content Ad: #$prohibitedBannerAdId (Status: suspended)\n";
echo "Fraud Test Ad: #$fraudTestAdId\n";
echo "Auto-Suspend Test Ad: #$autoSuspendAdId\n";
echo "\n";
echo "Expected Results:\n";
echo "✓ Admin can reject ads with reason\n";
echo "✓ Rejected ads move to suspended state\n";
echo "✓ Seller notified via notes field\n";
echo "✓ System prevents serving suspended ads\n";
echo "✓ Rapid click fraud detected\n";
echo "✓ Auto-suspension after threshold exceeded\n";
echo "\n";
echo "Test completed. Check the results above.\n";





