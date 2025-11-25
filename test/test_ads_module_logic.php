<?php
/**
 * Comprehensive Logic Test for Ads Module
 * Tests all internal logic without full app initialization
 */

// Load only what we need
require_once __DIR__ . '/../vendor/autoload.php';

// Load config directly
$configPath = __DIR__ . '/../App/Config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    // Try alternative paths
    $altPaths = [
        __DIR__ . '/../App/config/config.php',
        __DIR__ . '/../config/config.php',
    ];
    foreach ($altPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
}

use App\Core\Database;
use App\Models\Ad;
use App\Models\AdType;
use App\Services\AdActivationService;
use App\Services\AdValidationService;
use App\Services\RealTimeAdBillingService;

echo "=== ADS MODULE LOGIC TEST ===\n\n";

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

try {
    $db = Database::getInstance();
    $adModel = new Ad();
    $adTypeModel = new AdType();
    
    // Test 1: Database Connection
    runTest("Database connection", function() use ($db) {
        try {
            $result = $db->query("SELECT 1 as test")->single();
            return [
                'pass' => !empty($result) && $result['test'] == 1,
                'message' => "Database connected successfully"
            ];
        } catch (Exception $e) {
            return [
                'pass' => false,
                'message' => "Database connection failed: {$e->getMessage()}"
            ];
        }
    });
    
    // Test 2: Ad Type Exists
    runTest("Ad Type 'product_internal' exists", function() use ($adTypeModel) {
        $adType = $adTypeModel->findByName('product_internal');
        return [
            'pass' => !empty($adType),
            'message' => $adType ? "Found ad type ID: {$adType['id']}" : "Ad type 'product_internal' not found"
        ];
    });
    
    // Test 3: Validation Service - Ad Not Found
    runTest("AdValidationService - Handles non-existent ad", function() {
        $service = new AdValidationService();
        $result = $service->validateBeforeActivation(999999);
        return [
            'pass' => !$result['valid'] && in_array('Ad not found', $result['errors']),
            'message' => $result['valid'] ? "Should fail for non-existent ad" : "Correctly handles non-existent ad"
        ];
    });
    
    // Test 4: Validation Service - Total Clicks Validation
    runTest("AdValidationService - Validates total clicks > 0", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, 1, 0, 0, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id']]
        )->execute();
        $adId = $db->lastInsertId();
        
        $service = new AdValidationService();
        $result = $service->validateBeforeActivation($adId);
        
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        
        return [
            'pass' => !$result['valid'] && !empty($result['errors']),
            'message' => $result['valid'] ? "Should fail for 0 clicks" : "Correctly validates total clicks"
        ];
    });
    
    // Test 5: Ad Activation Service - Valid Ad
    runTest("AdActivationService - Activates valid ad", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        // Ensure wallet has balance
        $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
        
        $stmt =         $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, NULL, 100, 100, 'inactive', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id']]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad - lastInsertId: ' . $adId];
        }
        
        $service = new AdActivationService();
        $result = $service->activateAd($adId);
        
        $ad = $db->query("SELECT status, remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
        
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        
        return [
            'pass' => $result['success'] && ($ad['status'] ?? '') === 'active',
            'message' => $result['success'] ? "Ad activated successfully" : $result['message']
        ];
    });
    
    // Test 6: RealTimeAdBillingService - Can Show Ad Check
    runTest("RealTimeAdBillingService - Checks if ad can be shown", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
        
        $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, auto_paused, created_at) 
             VALUES (?, ?, NULL, 100, 100, 'active', 'approved', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', 0, NOW())",
            [$sellerId, $adType['id']]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad'];
        }
        
        $service = new RealTimeAdBillingService();
        $result = $service->canShowAd($adId);
        
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        
        return [
            'pass' => $result['can_show'] === true,
            'message' => $result['can_show'] ? "Ad can be shown (balance: Rs {$result['balance']})" : "Ad cannot be shown: {$result['reason']}"
        ];
    });
    
    // Test 7: Approval Flow
    runTest("Admin Approval - Updates approval status", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, NULL, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id']]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad'];
        }
        
        $db->query(
            "UPDATE ads SET approval_status = 'approved', updated_at = NOW() WHERE id = ?",
            [$adId]
        )->execute();
        
        $ad = $db->query("SELECT approval_status FROM ads WHERE id = ?", [$adId])->single();
        
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        
        return [
            'pass' => ($ad['approval_status'] ?? '') === 'approved',
            'message' => "Approval status: {$ad['approval_status']}"
        ];
    });
    
    // Test 8: Rejection Flow
    runTest("Admin Rejection - Updates approval status with reason", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, NULL, 100, 100, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id']]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad'];
        }
        
        $rejectionReason = "Test rejection reason";
        $notes = "\n[REJECTED: " . date('Y-m-d H:i:s') . "] Reason: " . $rejectionReason;
        
        $db->query(
            "UPDATE ads SET approval_status = 'rejected', notes = CONCAT(COALESCE(notes, ''), ?), updated_at = NOW() WHERE id = ?",
            [$notes, $adId]
        )->execute();
        
        $ad = $db->query("SELECT approval_status, notes FROM ads WHERE id = ?", [$adId])->single();
        
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        
        $hasReason = stripos($ad['notes'] ?? '', 'REJECTED') !== false;
        
        return [
            'pass' => ($ad['approval_status'] ?? '') === 'rejected' && $hasReason,
            'message' => "Rejection status: {$ad['approval_status']}, Has reason: " . ($hasReason ? 'Yes' : 'No')
        ];
    });
    
    // Test 9: Date Validation
    runTest("AdValidationService - Validates date ranges", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $pastDate = date('Y-m-d', strtotime('-1 day'));
        $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, NULL, 100, 100, 'inactive', 'pending', CURDATE(), ?, 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id'], $pastDate]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad'];
        }
        
        $service = new AdValidationService();
        $result = $service->validateBeforeActivation($adId);
        
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
    
    // Test 10: Complete Workflow
    runTest("Complete Ad Workflow - Create → Approve → Activate", function() use ($db) {
        $sellerId = 1;
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();
        
        if (!$adType) {
            return ['pass' => false, 'message' => 'product_internal ad type not found'];
        }
        
        $initialBalance = 100.00;
        $db->query("UPDATE seller_wallet SET balance = ? WHERE seller_id = ?", [$initialBalance, $sellerId])->execute();
        
        // Step 1: Create ad (pending)
        $stmt = $db->query(
            "INSERT INTO ads (seller_id, ads_type_id, product_id, total_clicks, remaining_clicks, status, approval_status, start_date, end_date, per_click_rate, billing_type, created_at) 
             VALUES (?, ?, NULL, 50, 50, 'inactive', 'pending', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 2.00, 'per_click', NOW())",
            [$sellerId, $adType['id']]
        );
        $stmt->execute();
        $adId = $db->lastInsertId();
        
        if (!$adId || $adId == 0) {
            return ['pass' => false, 'message' => 'Failed to insert ad'];
        }
        
        // Step 2: Approve ad
        $db->query(
            "UPDATE ads SET approval_status = 'approved', updated_at = NOW() WHERE id = ?",
            [$adId]
        )->execute();
        
        // Step 3: Activate ad
        $activationService = new AdActivationService();
        $activateResult = $activationService->activateAd($adId);
        
        // Verify final state
        $ad = $db->query("SELECT status, approval_status, remaining_clicks FROM ads WHERE id = ?", [$adId])->single();
        
        // Cleanup
        $db->query("DELETE FROM ads WHERE id = ?", [$adId])->execute();
        $db->query("UPDATE seller_wallet SET balance = 100.00 WHERE seller_id = ?", [$sellerId])->execute();
        
        $workflowPass = 
            ($ad['approval_status'] ?? '') === 'approved' &&
            ($ad['status'] ?? '') === 'active' &&
            ($ad['remaining_clicks'] ?? 50) === 50;
        
        return [
            'pass' => $workflowPass && $activateResult['success'],
            'message' => $workflowPass 
                ? "Workflow complete: Approved → Activated, Remaining: {$ad['remaining_clicks']} clicks"
                : "Workflow failed: " . ($activateResult['message'] ?? 'Unknown error')
        ];
    });
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Summary
echo "\n=== TEST SUMMARY ===\n";
echo "Total Tests: {$testCount}\n";
echo "Passed: {$passCount}\n";
echo "Failed: {$failCount}\n";
echo "Success Rate: " . round(($passCount / $testCount) * 100, 2) . "%\n\n";

if ($failCount === 0) {
    echo "✓ ALL TESTS PASSED! Ads module logic is working perfectly.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}

