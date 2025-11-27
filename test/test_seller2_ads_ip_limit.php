<?php
/**
 * Test Script: Delete all ads for seller 2, create 5 new ads, and test IP limit functionality
 */

require_once __DIR__ . '/../App/Config/config.php';

if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\Ad;
use App\Models\SellerWallet;
use App\Services\RealTimeAdBillingService;
use App\Services\SponsoredAdsService;

echo "=== Seller 2 Ads Setup and IP Limit Test ===\n\n";

$db = Database::getInstance();
$sellerId = 2;
$errors = [];
$passed = 0;
$failed = 0;

// Step 1: Verify seller 2 exists
echo "Step 1: Verifying seller 2 exists\n";
try {
    $seller = $db->query("SELECT id, name FROM sellers WHERE id = ?", [$sellerId])->single();
    if (!$seller) {
        throw new Exception("Seller 2 not found");
    }
    echo "✓ Seller found: {$seller['name']} (ID: {$seller['id']})\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 2: Ensure seller 2 has wallet with balance
echo "\nStep 2: Ensuring seller 2 has wallet with sufficient balance\n";
try {
    $walletModel = new SellerWallet();
    $wallet = $walletModel->getWalletBySellerId($sellerId);
    
    // Set balance to 1000 if less
    if ((float)($wallet['balance'] ?? 0) < 1000) {
        $db->query(
            "UPDATE seller_wallet SET balance = 1000.00 WHERE seller_id = ?",
            [$sellerId]
        )->execute();
        echo "✓ Wallet balance set to Rs 1000.00\n";
    } else {
        echo "✓ Wallet balance: Rs " . number_format($wallet['balance'], 2) . "\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 3: Delete all ads for seller 2
echo "\nStep 3: Deleting all ads for seller 2\n";
try {
    $existingAds = $db->query("SELECT id FROM ads WHERE seller_id = ?", [$sellerId])->all();
    $count = count($existingAds);
    
    if ($count > 0) {
        // Delete related data first
        $adIds = array_column($existingAds, 'id');
        $placeholders = implode(',', array_fill(0, count($adIds), '?'));
        
        // Delete click logs
        $db->query("DELETE FROM ads_click_logs WHERE ads_id IN ($placeholders)", $adIds)->execute();
        
        // Delete reach logs
        $db->query("DELETE FROM ads_reach_logs WHERE ads_id IN ($placeholders)", $adIds)->execute();
        
        // Delete daily spend logs
        $db->query("DELETE FROM ads_daily_spend_log WHERE ads_id IN ($placeholders)", $adIds)->execute();
        
        // Delete ads
        $db->query("DELETE FROM ads WHERE seller_id = ?", [$sellerId])->execute();
        
        echo "✓ Deleted {$count} ads and related data\n";
    } else {
        echo "✓ No ads to delete\n";
    }
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 4: Get required data for creating ads
echo "\nStep 4: Getting required data for creating ads\n";
try {
    // Get product_internal ad type
    $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal'")->single();
    if (!$adType) {
        throw new Exception("product_internal ad type not found");
    }
    $adTypeId = $adType['id'];
    
    // Get or create products for seller 2
    $products = $db->query("SELECT id FROM products WHERE seller_id = ? LIMIT 10", [$sellerId])->all();
    
    if (count($products) < 5) {
        // Create products if needed
        $pdo = $db->getPdo();
        for ($i = count($products); $i < 5; $i++) {
            $slug = 'test-product-' . time() . '-' . $sellerId . '-' . $i;
            $stmt = $pdo->prepare(
                "INSERT INTO products (seller_id, product_name, slug, description, price, status, category, created_at) 
                 VALUES (?, ?, ?, 'Test product for ad testing', 100.00, 'active', 'supplements', NOW())"
            );
            $productName = "Test Product " . ($i + 1);
            $stmt->execute([$sellerId, $productName, $slug]);
            $productId = $pdo->lastInsertId();
            $products[] = ['id' => $productId];
        }
        echo "✓ Created " . (5 - count($products)) . " test products\n";
    }
    
    // Get min_cpc_rate
    $setting = $db->query("SELECT setting_value FROM ad_settings WHERE setting_key = 'min_cpc_rate'")->single();
    $minCpcRate = $setting ? (float)$setting['setting_value'] : 2.50;
    
    echo "✓ Ad type ID: {$adTypeId}\n";
    echo "✓ Products available: " . count($products) . "\n";
    echo "✓ Min CPC rate: Rs {$minCpcRate}\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
    exit(1);
}

// Step 5: Create 5 ads for seller 2
echo "\nStep 5: Creating 5 ads for seller 2\n";
$createdAdIds = [];
try {
    $adModel = new Ad();
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+30 days'));
    $totalClicks = 100;
    
    for ($i = 0; $i < 5; $i++) {
        $adData = [
            'seller_id' => $sellerId,
            'ads_type_id' => $adTypeId,
            'product_id' => $products[$i]['id'],
            'banner_image' => null,
            'banner_link' => null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_days' => 30,
            'ads_cost_id' => null,
            'billing_type' => 'per_click',
            'daily_budget' => 0,
            'per_click_rate' => $minCpcRate,
            'per_impression_rate' => 0,
            'total_clicks' => $totalClicks,
            'remaining_clicks' => $totalClicks,
            'current_daily_spend' => 0,
            'current_day_spent' => 0,
            'last_spend_reset_date' => $startDate,
            'auto_paused' => 0,
            'status' => 'active',
            'notes' => "Test ad " . ($i + 1) . " for IP limit testing"
        ];
        
        $adId = $adModel->create($adData);
        if ($adId) {
            $createdAdIds[] = $adId;
            echo "✓ Created ad #{$adId} (Product: {$products[$i]['id']})\n";
        } else {
            throw new Exception("Failed to create ad " . ($i + 1));
        }
    }
    
    echo "✓ Successfully created 5 ads\n";
    $passed++;
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 6: Test IP limit functionality
echo "\nStep 6: Testing IP limit functionality\n";
try {
    $billingService = new RealTimeAdBillingService();
    $testIp = '192.168.1.100';
    
    // Clear today's click logs for test IP
    $today = date('Y-m-d');
    $db->query(
        "DELETE FROM ads_click_logs WHERE ip_address = ? AND DATE(clicked_at) = ?",
        [$testIp, $today]
    )->execute();
    
    echo "  Testing with IP: {$testIp}\n";
    echo "  ADS_IP_LIMIT setting: " . (defined('ADS_IP_LIMIT') ? constant('ADS_IP_LIMIT') : 'not defined') . "\n";
    
    $ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';
    
    if ($ipLimitEnabled) {
        echo "  IP limit is ENABLED - testing max 10 ads per IP\n";
        
        // Test clicking 12 ads (should allow 10, block 2)
        $chargedCount = 0;
        $blockedCount = 0;
        
        for ($i = 0; $i < 12; $i++) {
            $adId = $createdAdIds[$i % 5]; // Cycle through the 5 ads
            
            // Log click first (simulating real flow)
            $adModel = new Ad();
            $adModel->logClick($adId, $testIp);
            
            // Try to charge
            $result = $billingService->chargeClick($adId, $testIp);
            
            if ($result['success'] && $result['charged'] > 0) {
                $chargedCount++;
                echo "    Ad #{$adId}: Charged Rs {$result['charged']}\n";
            } else {
                $blockedCount++;
                echo "    Ad #{$adId}: Blocked - {$result['message']}\n";
            }
        }
        
        echo "  Results: {$chargedCount} charged, {$blockedCount} blocked\n";
        
        if ($chargedCount <= 10) {
            echo "✓ PASS: IP limit working correctly\n";
            $passed++;
        } else {
            echo "✗ FAIL: IP limit not working - {$chargedCount} ads charged (expected max 10)\n";
            $failed++;
        }
    } else {
        echo "  IP limit is DISABLED - all clicks should charge\n";
        
        // Test clicking 3 ads (all should charge)
        $chargedCount = 0;
        
        for ($i = 0; $i < 3; $i++) {
            $adId = $createdAdIds[$i];
            
            // Log click first
            $adModel = new Ad();
            $adModel->logClick($adId, $testIp);
            
            // Try to charge
            $result = $billingService->chargeClick($adId, $testIp);
            
            if ($result['success'] && $result['charged'] > 0) {
                $chargedCount++;
                echo "    Ad #{$adId}: Charged Rs {$result['charged']}\n";
            } else {
                echo "    Ad #{$adId}: Failed - {$result['message']}\n";
            }
        }
        
        if ($chargedCount == 3) {
            echo "✓ PASS: All clicks charged (IP limit disabled)\n";
            $passed++;
        } else {
            echo "✗ FAIL: Expected 3 charges, got {$chargedCount}\n";
            $failed++;
        }
    }
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Step 7: Verify ads are active and ready
echo "\nStep 7: Verifying ads status\n";
try {
    $ads = $db->query(
        "SELECT id, status, remaining_clicks, per_click_rate 
         FROM ads WHERE seller_id = ? ORDER BY id",
        [$sellerId]
    )->all();
    
    echo "  Found " . count($ads) . " ads:\n";
    foreach ($ads as $ad) {
        echo "    Ad #{$ad['id']}: Status={$ad['status']}, Clicks={$ad['remaining_clicks']}, Rate=Rs {$ad['per_click_rate']}\n";
    }
    
    $activeCount = count(array_filter($ads, fn($a) => $a['status'] === 'active'));
    if ($activeCount == 5) {
        echo "✓ PASS: All 5 ads are active\n";
        $passed++;
    } else {
        echo "✗ FAIL: Expected 5 active ads, found {$activeCount}\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: {$e->getMessage()}\n";
    $failed++;
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed == 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}





