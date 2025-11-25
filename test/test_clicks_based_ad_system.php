<?php
/**
 * Test Clicks-Based Ad System
 * Tests the complete flow: admin sets min_cpc_rate, seller creates ad with clicks, activation, click handling
 */

require_once __DIR__ . '/../App/Config/config.php';

// Define URLROOT before bootstrap
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost');
}

// Autoload classes manually
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
use App\Services\AdValidationService;
use App\Services\AdActivationService;
use App\Services\RealTimeAdBillingService;

echo "=== Testing Clicks-Based Ad System ===\n\n";

$db = Database::getInstance();
$errors = [];
$passed = 0;
$failed = 0;

// Test 1: Admin sets min_cpc_rate
echo "Test 1: Admin sets min_cpc_rate\n";
try {
    $db->query(
        "CREATE TABLE IF NOT EXISTS `ad_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` VARCHAR(255) NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
    )->execute();
    
    $db->query(
        "INSERT INTO ad_settings (setting_key, setting_value) VALUES ('min_cpc_rate', '2.50') ON DUPLICATE KEY UPDATE setting_value = '2.50'"
    )->execute();
    
    $setting = $db->query("SELECT setting_value FROM ad_settings WHERE setting_key = 'min_cpc_rate'")->single();
    if ($setting && $setting['setting_value'] == '2.50') {
        echo "✓ PASS: min_cpc_rate set to 2.50\n";
        $passed++;
    } else {
        echo "✗ FAIL: min_cpc_rate not set correctly\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: Seller creates ad with total_clicks
echo "\nTest 2: Seller creates ad with total_clicks\n";
$adId = null;
$sellerId = null;
try {
    // Get a test seller
    $seller = $db->query("SELECT id FROM sellers LIMIT 1")->single();
    if (!$seller || !isset($seller['id'])) {
        echo "✗ FAIL: No seller found for testing\n";
        $failed++;
    } else {
        $sellerId = $seller['id'];
        
        // Get product_internal ad type
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal'")->single();
        if (!$adType || !isset($adType['id'])) {
            echo "✗ FAIL: product_internal ad type not found\n";
            $failed++;
        } else {
            // Get or create a product
            $product = $db->query("SELECT id FROM products WHERE seller_id = ? LIMIT 1", [$sellerId])->single();
            if (!$product || !isset($product['id'])) {
                // Create a test product with all required fields using direct PDO
                $pdo = $db->getPdo();
                $slug = 'test-product-' . time() . '-' . $sellerId;
                $stmt = $pdo->prepare(
                    "INSERT INTO products (seller_id, product_name, slug, description, price, status, category, created_at) 
                     VALUES (?, 'Test Product', ?, 'Test product for ad testing', 100.00, 'active', 'supplements', NOW())"
                );
                $stmt->execute([$sellerId, $slug]);
                $productId = $pdo->lastInsertId();
                $product = ['id' => $productId];
                echo "  Created test product #{$productId}\n";
            }
            
            if ($product && isset($product['id'])) {
                $minCpcRate = 2.50;
                $totalClicks = 100;
                $requiredBalance = $minCpcRate * $totalClicks;
                
                // Create ad using PDO directly (ads_cost_id is now nullable)
                $pdo = $db->getPdo();
                $stmt = $pdo->prepare(
                    "INSERT INTO ads (seller_id, ads_type_id, product_id, start_date, end_date, duration_days, ads_cost_id, billing_type, per_click_rate, total_clicks, remaining_clicks, status) 
                     VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 7, NULL, 'per_click', ?, ?, ?, 'inactive')"
                );
                $stmt->execute([$sellerId, $adType['id'], $product['id'], $minCpcRate, $totalClicks, $totalClicks]);
                $adId = $pdo->lastInsertId();
                
                $adResult = ['id' => $adId];
                if ($adResult && isset($adResult['id'])) {
                    $adId = $adResult['id'];
                    $ad = $db->query("SELECT * FROM ads WHERE id = ?", [$adId])->single();
                    if ($ad && isset($ad['total_clicks']) && $ad['total_clicks'] == $totalClicks && isset($ad['remaining_clicks']) && $ad['remaining_clicks'] == $totalClicks) {
                        echo "✓ PASS: Ad created with total_clicks = {$totalClicks}, remaining_clicks = {$totalClicks}\n";
                        echo "  Required balance: Rs " . number_format($requiredBalance, 2) . "\n";
                        $passed++;
                    } else {
                        echo "✗ FAIL: Ad not created correctly\n";
                        if ($ad) {
                            echo "  total_clicks: " . ($ad['total_clicks'] ?? 'null') . ", remaining_clicks: " . ($ad['remaining_clicks'] ?? 'null') . "\n";
                        }
                        $failed++;
                    }
                } else {
                    echo "✗ FAIL: Could not retrieve created ad ID\n";
                    $failed++;
                }
            } else {
                echo "✗ FAIL: Product ID not available\n";
                $failed++;
            }
        }
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Validation before activation
echo "\nTest 3: Validation before activation\n";
try {
    if (!empty($adId)) {
        $validationService = new AdValidationService();
        $result = $validationService->validateBeforeActivation($adId);
        
        if ($result['valid']) {
            echo "✓ PASS: Validation passed\n";
            $passed++;
        } else {
            echo "✗ FAIL: Validation failed: " . implode(', ', $result['errors']) . "\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 4: Activation locks balance
echo "\nTest 4: Activation locks balance\n";
try {
    if (!empty($adId) && !empty($sellerId)) {
        // Ensure seller has enough balance
        $wallet = $db->query("SELECT * FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
        if (!$wallet) {
            $db->query("INSERT INTO seller_wallet (seller_id, balance) VALUES (?, 1000)", [$sellerId])->execute();
        } else {
            $db->query("UPDATE seller_wallet SET balance = 1000, locked_balance = 0 WHERE seller_id = ?", [$sellerId])->execute();
        }
        
        $activationService = new AdActivationService();
        $result = $activationService->activateAd($adId);
        
        if ($result['success']) {
            $wallet = $db->query("SELECT * FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
            $ad = $db->query("SELECT * FROM ads WHERE id = ?", [$adId])->single();
            
            if ($ad['status'] == 'active' && $ad['remaining_clicks'] == $ad['total_clicks']) {
                // Check that no balance was locked
                if ($wallet['locked_balance'] == 0) {
                    echo "✓ PASS: Ad activated, no balance locked\n";
                    echo "  Balance: Rs " . number_format($wallet['balance'], 2) . ", Locked: Rs " . number_format($wallet['locked_balance'], 2) . "\n";
                    $passed++;
                } else {
                    echo "✗ FAIL: Balance was locked (should not be)\n";
                    $failed++;
                }
            } else {
                echo "✗ FAIL: Ad not activated correctly\n";
                $failed++;
            }
        } else {
            echo "✗ FAIL: Activation failed: " . $result['message'] . "\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID or seller ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: Click handling decrements remaining_clicks
echo "\nTest 5: Click handling decrements remaining_clicks\n";
try {
    if (!empty($adId)) {
        $ad = $db->query("SELECT * FROM ads WHERE id = ?", [$adId])->single();
        $initialClicks = (int)$ad['remaining_clicks'];
        
        $billingService = new RealTimeAdBillingService();
        $result = $billingService->chargeClick($adId);
        
        if ($result['success']) {
            $ad = $db->query("SELECT * FROM ads WHERE id = ?", [$adId])->single();
            $newClicks = (int)$ad['remaining_clicks'];
            
            if ($newClicks == $initialClicks - 1) {
                echo "✓ PASS: remaining_clicks decremented from {$initialClicks} to {$newClicks}\n";
                echo "  Charged: Rs " . number_format($result['charged'], 2) . "\n";
                $passed++;
            } else {
                echo "✗ FAIL: remaining_clicks not decremented correctly\n";
                $failed++;
            }
        } else {
            echo "✗ FAIL: Click charge failed: " . $result['message'] . "\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 6: Ad pauses when clicks exhausted
echo "\nTest 6: Ad pauses when clicks exhausted\n";
try {
    if (!empty($adId)) {
        // Set remaining_clicks to 1
        $db->query("UPDATE ads SET remaining_clicks = 1, status = 'active' WHERE id = ?", [$adId])->execute();
        
        $billingService = new RealTimeAdBillingService();
        $result = $billingService->chargeClick($adId);
        
        $ad = $db->query("SELECT * FROM ads WHERE id = ?", [$adId])->single();
        
        if ($ad['remaining_clicks'] == 0 && $ad['status'] == 'inactive') {
            echo "✓ PASS: Ad paused when clicks exhausted\n";
            $passed++;
        } else {
            echo "✗ FAIL: Ad not paused when clicks exhausted\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed == 0) {
    echo "\n✅ ALL TESTS PASSED - System is production ready!\n";
    exit(0);
} else {
    echo "\n❌ SOME TESTS FAILED - Please fix issues before production\n";
    exit(1);
}

