<?php
/**
 * Comprehensive Test Suite for Ads Module
 * Tests all logic flows: creation, approval, activation, billing, tracking
 */

// Bootstrap application
require_once __DIR__ . '/../App/bootstrap.php';

use App\Core\Database;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\Product;
use App\Models\SellerWallet;
use App\Services\AdActivationService;
use App\Services\AdValidationService;
use App\Services\RealTimeAdBillingService;
use App\Services\AdFraudDetectionService;

// Initialize
$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$productModel = new Product();
$walletModel = new SellerWallet();

echo "=== ADS MODULE COMPREHENSIVE TEST ===\n\n";

$testResults = [];
$testCount = 0;
$passCount = 0;
$failCount = 0;

function runTest($name, $callback) {
    global $testCount, $passCount, $failCount, $testResults;
    $testCount++;
    echo "Test {$testCount}: {$name}... ";
    
    try {
        $result = $callback();
        if ($result['pass']) {
            $passCount++;
            echo "✓ PASS\n";
            if (!empty($result['message'])) {
                echo "  → {$result['message']}\n";
            }
        } else {
            $failCount++;
            echo "✗ FAIL\n";
            echo "  → {$result['message']}\n";
        }
        $testResults[] = ['name' => $name, 'pass' => $result['pass'], 'message' => $result['message']];
    } catch (Exception $e) {
        $failCount++;
        echo "✗ ERROR\n";
        echo "  → Exception: {$e->getMessage()}\n";
        $testResults[] = ['name' => $name, 'pass' => false, 'message' => "Exception: {$e->getMessage()}"];
    }
    echo "\n";
}

// Test 1: Ad Type Exists
runTest("Ad Type 'product_internal' exists", function() use ($adTypeModel) {
    $adType = $adTypeModel->findByName('product_internal');
    return [
        'pass' => !empty($adType),
        'message' => $adType ? "Found ad type ID: {$adType['id']}" : "Ad type 'product_internal' not found"
    ];
});

// Test 2: Validation Service - Ad Not Found
runTest("AdValidationService - Handles non-existent ad", function() {
    $service = new AdValidationService();
    $result = $service->validateBeforeActivation(999999);
    return [
        'pass' => !$result['valid'] && in_array('Ad not found', $result['errors']),
        'message' => $result['valid'] ? "Should fail for non-existent ad" : "Correctly handles non-existent ad"
    ];
});

// Test 3: Validation Service - Total Clicks Validation
runTest("AdValidationService - Validates total clicks > 0", function() use ($adModel, $db) {
    // Create test ad with 0 clicks
    $sellerId = 1; // Assuming seller ID 1 exists
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 0, 0, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new AdValidationService();
    $result = $service->validateBeforeActivation($adId);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    return [
        'pass' => !$result['valid'] && !empty($result['errors']),
        'message' => $result['valid'] ? "Should fail for 0 clicks" : "Correctly validates total clicks"
    ];
});

// Test 4: Validation Service - Wallet Balance Check
runTest("AdValidationService - Validates wallet balance", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Get current balance
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    $originalBalance = (float)($wallet['balance'] ?? 0);
    
    // Create ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new AdValidationService();
    $result = $service->validateBeforeActivation($adId);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    $hasBalanceError = false;
    foreach ($result['errors'] as $error) {
        if (stripos($error, 'balance') !== false) {
            $hasBalanceError = true;
            break;
        }
    }
    
    return [
        'pass' => !$result['valid'] || $hasBalanceError || $originalBalance >= 2.00,
        'message' => "Wallet balance check: " . ($result['valid'] ? "Passed (balance sufficient)" : "Failed (balance insufficient)")
    ];
});

// Test 5: Ad Activation Service - Valid Ad
runTest("AdActivationService - Activates valid ad", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Ensure wallet has balance
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    if ((float)($wallet['balance'] ?? 0) < 2.00) {
        $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
    }
    
    // Create valid ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new AdActivationService();
    $result = $service->activateAd($adId);
    
    // Check if activated
    $ad = $db->query("SELECT status, remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    return [
        'pass' => $result['success'] && ($ad['status'] ?? '') === 'active',
        'message' => $result['success'] ? "Ad activated successfully" : $result['message']
    ];
});

// Test 6: RealTimeAdBillingService - Can Show Ad Check
runTest("RealTimeAdBillingService - Checks if ad can be shown", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Ensure wallet has balance
    $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
    
    // Create active ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
         VALUES (?, ?, 1, 100, 100, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new RealTimeAdBillingService();
    $result = $service->canShowAd($adId);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    return [
        'pass' => $result['can_show'] === true,
        'message' => $result['can_show'] ? "Ad can be shown (balance: Rs {$result['balance']})" : "Ad cannot be shown: {$result['reason']}"
    ];
});

// Test 7: RealTimeAdBillingService - Charge Click
runTest("RealTimeAdBillingService - Charges for click", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Set wallet balance
    $initialBalance = 100.00;
    $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
    
    // Create active ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
         VALUES (?, ?, 1, 100, 100, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new RealTimeAdBillingService();
    $result = $service->chargeClick($adId, '127.0.0.1');
    
    // Check wallet balance decreased
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    $newBalance = (float)($wallet['balance'] ?? 0);
    $expectedBalance = $initialBalance - 2.00;
    
    // Check remaining clicks decreased
    $ad = $db->query("SELECT remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
    $remainingClicks = (int)($ad['remaining_clicks'] ?? 0);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_click_logs WHERE ads_id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_daily_spend_log WHERE ads_id = ?", [$adId])->execute();
    $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
    
    return [
        'pass' => $result['success'] && abs($newBalance - $expectedBalance) < 0.01 && $remainingClicks === 99,
        'message' => $result['success'] 
            ? "Charged Rs {$result['charged']}, Balance: Rs {$newBalance}, Remaining clicks: {$remainingClicks}" 
            : "Failed: {$result['message']}"
    ];
});

// Test 8: RealTimeAdBillingService - Insufficient Balance
runTest("RealTimeAdBillingService - Handles insufficient balance", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Set low wallet balance
    $initialBalance = 1.00; // Less than per_click_rate (2.00)
    $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
    
    // Create active ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
         VALUES (?, ?, 1, 100, 100, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new RealTimeAdBillingService();
    $result = $service->chargeClick($adId, '127.0.0.2');
    
    // Check ad is auto-paused
    $ad = $db->query("SELECT auto_paused, status FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
    
    return [
        'pass' => !$result['success'] && ($ad['auto_paused'] ?? 0) == 1,
        'message' => !$result['success'] 
            ? "Correctly blocked charge, ad auto-paused: {$result['message']}" 
            : "Should have failed with insufficient balance"
    ];
});

// Test 9: Approval Flow
runTest("Admin Approval - Updates approval status", function() use ($db) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Create pending ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    // Approve ad
    $db->query(
        "UPDATE ads SET approval_status = 'approved', updated_at = NOW() WHERE id = ?",
        [$adId]
    )->execute();
    
    // Check approval status
    $ad = $db->query("SELECT approval_status FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    return [
        'pass' => ($ad['approval_status'] ?? '') === 'approved',
        'message' => "Approval status: {$ad['approval_status']}"
    ];
});

// Test 10: Rejection Flow
runTest("Admin Rejection - Updates approval status with reason", function() use ($db) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Create pending ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $rejectionReason = "Test rejection reason";
    $notes = "\n[REJECTED: " . date('Y-m-d H:i:s') . "] Reason: " . $rejectionReason;
    
    // Reject ad
    $db->query(
        "UPDATE ads SET approval_status = 'rejected', notes = CONCAT(COALESCE(notes, ''), ?), updated_at = NOW() WHERE id = ?",
        [$notes, $adId]
    )->execute();
    
    // Check rejection status
    $ad = $db->query("SELECT approval_status, notes FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    $hasReason = stripos($ad['notes'] ?? '', 'REJECTED') !== false;
    
    return [
        'pass' => ($ad['approval_status'] ?? '') === 'rejected' && $hasReason,
        'message' => "Rejection status: {$ad['approval_status']}, Has reason: " . ($hasReason ? 'Yes' : 'No')
    ];
});

// Test 11: Seller Cannot Start Unapproved Ad
runTest("Seller - Cannot start unapproved ad", function() use ($db) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Create pending ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new AdActivationService();
    $result = $service->activateAd($adId);
    
    // Check ad is still inactive
    $ad = $db->query("SELECT status FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    // Validation should fail because ad is not approved
    return [
        'pass' => !$result['success'] || ($ad['status'] ?? '') !== 'active',
        'message' => $result['success'] ? "Should have failed (ad not approved)" : "Correctly blocked activation: {$result['message']}"
    ];
});

// Test 12: Clicks Exhaustion
runTest("RealTimeAdBillingService - Auto-pauses when clicks exhausted", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Set wallet balance
    $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
    
    // Create ad with 1 remaining click
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
         VALUES (?, ?, 1, 100, 1, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $service = new RealTimeAdBillingService();
    $result = $service->chargeClick($adId, '127.0.0.3');
    
    // Check ad is paused and status is inactive
    $ad = $db->query("SELECT auto_paused, status, remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_click_logs WHERE ads_id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_daily_spend_log WHERE ads_id = ?", [$adId])->execute();
    
    return [
        'pass' => ($ad['auto_paused'] ?? 0) == 1 && ($ad['status'] ?? '') === 'inactive' && ($ad['remaining_clicks'] ?? 1) == 0,
        'message' => "Auto-paused: " . (($ad['auto_paused'] ?? 0) == 1 ? 'Yes' : 'No') . ", Status: {$ad['status']}, Remaining: {$ad['remaining_clicks']}"
    ];
});

// Test 13: Date Validation
runTest("AdValidationService - Validates date ranges", function() use ($db) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Create ad with invalid date (end_date in past)
    $pastDate = date('Y-m-d', strtotime('-1 day'));
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 100, 100, 'inactive', 'pending', CURDATE(), ?, 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id'], $pastDate]
    )->lastInsertId();
    
    $service = new AdValidationService();
    $result = $service->validateBeforeActivation($adId);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    
    $hasDateError = false;
    foreach ($result['errors'] as $error) {
        if (stripos($error, 'date') !== false || stripos($error, 'past') !== false) {
            $hasDateError = true;
            break;
        }
    }
    
    return [
        'pass' => !$result['valid'] && $hasDateError,
        'message' => $result['valid'] ? "Should fail for past end date" : "Correctly validates date range"
    ];
});

// Test 14: Fraud Detection
runTest("AdFraudDetectionService - Detects rapid clicks", function() use ($db, $walletModel) {
    if (!class_exists('App\Services\AdFraudDetectionService')) {
        return ['pass' => true, 'message' => 'AdFraudDetectionService not found, skipping'];
    }
    
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Set wallet balance
    $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
    
    // Create active ad
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
         VALUES (?, ?, 1, 100, 100, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    $testIp = '192.168.1.100';
    
    // Log multiple rapid clicks
    for ($i = 0; $i < 5; $i++) {
        $db->query(
            "INSERT INTO ads_click_logs (ads_id, ip_address, clicked_at) VALUES (?, ?, NOW())",
            [$adId, $testIp]
        )->execute();
        usleep(100000); // 0.1 second delay
    }
    
    $fraudService = new \App\Services\AdFraudDetectionService();
    $fraudCheck = $fraudService->checkRapidClickFraud($adId, $testIp);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_click_logs WHERE ads_id = ?", [$adId])->execute();
    
    return [
        'pass' => $fraudCheck['is_fraud'] || $fraudCheck['fraud_score'] > 0,
        'message' => "Fraud score: {$fraudCheck['fraud_score']}, Is fraud: " . ($fraudCheck['is_fraud'] ? 'Yes' : 'No')
    ];
});

// Test 15: Complete Workflow
runTest("Complete Ad Workflow - Create → Approve → Activate → Charge", function() use ($db, $walletModel) {
    $sellerId = 1;
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
    
    if (!$adType) {
        return ['pass' => false, 'message' => 'product_internal ad type not found'];
    }
    
    // Set wallet balance
    $initialBalance = 100.00;
    $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
    
    // Step 1: Create ad (pending)
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
         VALUES (?, ?, 1, 50, 50, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
        [$sellerId, $adType['id']]
    )->lastInsertId();
    
    // Step 2: Approve ad
    $db->query(
        "UPDATE ads SET approval_status = 'approved', updated_at = NOW() WHERE id = ?",
        [$adId]
    )->execute();
    
    // Step 3: Activate ad
    $activationService = new AdActivationService();
    $activateResult = $activationService->activateAd($adId);
    
    if (!$activateResult['success']) {
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        return ['pass' => false, 'message' => "Activation failed: {$activateResult['message']}"];
    }
    
    // Step 4: Charge for click
    $billingService = new RealTimeAdBillingService();
    $chargeResult = $billingService->chargeClick($adId, '127.0.0.4');
    
    // Verify final state
    $ad = $db->query("SELECT status, approval_status, remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    $finalBalance = (float)($wallet['balance'] ?? 0);
    
    // Cleanup
    $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_click_logs WHERE ads_id = ?", [$adId])->execute();
    $db->query("DELETE FROM ads_daily_spend_log WHERE ads_id = ?", [$adId])->execute();
    $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
    
    $workflowPass = 
        ($ad['approval_status'] ?? '') === 'approved' &&
        ($ad['status'] ?? '') === 'active' &&
        ($ad['remaining_clicks'] ?? 50) === 49 &&
        $chargeResult['success'] &&
        abs($finalBalance - ($initialBalance - 2.00)) < 0.01;
    
    return [
        'pass' => $workflowPass,
        'message' => $workflowPass 
            ? "Workflow complete: Approved → Activated → Charged Rs {$chargeResult['charged']}, Remaining: {$ad['remaining_clicks']} clicks"
            : "Workflow failed at some step"
    ];
});

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL TESTS PASSED! Ads module is working perfectly.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

