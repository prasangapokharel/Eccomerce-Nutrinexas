<?php
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

echo "=== Testing Simplified Ad System (No Balance Locking) ===\n\n";

$db = \App\Core\Database::getInstance();
$passed = 0;
$failed = 0;

// Test 1: Seller creates ad - no balance locking required
echo "Test 1: Seller creates ad (no balance locking)\n";
try {
    $seller = $db->query("SELECT id FROM sellers LIMIT 1")->single();
    if (!$seller || !isset($seller['id'])) {
        echo "✗ FAIL: No seller found\n";
        $failed++;
    } else {
        $sellerId = $seller['id'];
        
        // Ensure seller has wallet with balance
        $wallet = $db->query("SELECT * FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
        if (!$wallet) {
            $db->query("INSERT INTO seller_wallet (seller_id, balance, locked_balance) VALUES (?, 1000.00, 0.00)", [$sellerId])->execute();
        } else {
            // Set balance to 1000, locked to 0
            $db->query("UPDATE seller_wallet SET balance = 1000.00, locked_balance = 0.00 WHERE seller_id = ?", [$sellerId])->execute();
        }
        
        // Get ad type
        $adType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal'")->single();
        if (!$adType || !isset($adType['id'])) {
            echo "✗ FAIL: product_internal ad type not found\n";
            $failed++;
        } else {
            // Get or create product
            $product = $db->query("SELECT id FROM products WHERE seller_id = ? LIMIT 1", [$sellerId])->single();
            if (!$product || !isset($product['id'])) {
                $pdo = $db->getPdo();
                $slug = 'test-product-' . time() . '-' . $sellerId;
                $stmt = $pdo->prepare(
                    "INSERT INTO products (seller_id, product_name, slug, description, price, status, category, created_at) 
                     VALUES (?, 'Test Product', ?, 'Test product for ad testing', 100.00, 'active', 'supplements', NOW())"
                );
                $stmt->execute([$sellerId, $slug]);
                $productId = $pdo->lastInsertId();
                $product = ['id' => $productId];
            }
            
            if ($product && isset($product['id'])) {
                $minCpcRate = 2.50;
                $totalClicks = 100;
                
                // Create ad
                $pdo = $db->getPdo();
                $stmt = $pdo->prepare(
                    "INSERT INTO ads (seller_id, ads_type_id, product_id, start_date, end_date, duration_days, ads_cost_id, billing_type, per_click_rate, total_clicks, remaining_clicks, status) 
                     VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 7, NULL, 'per_click', ?, ?, ?, 'inactive')"
                );
                $stmt->execute([$sellerId, $adType['id'], $product['id'], $minCpcRate, $totalClicks, $totalClicks]);
                $adId = $pdo->lastInsertId();
                
                // Check wallet - should still have full balance (no locking)
                $wallet = $db->query("SELECT balance, locked_balance FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
                if ($wallet && $wallet['balance'] == 1000.00 && $wallet['locked_balance'] == 0.00) {
                    echo "✓ PASS: Ad created, wallet balance unchanged (no locking)\n";
                    echo "  Balance: Rs " . number_format($wallet['balance'], 2) . ", Locked: Rs " . number_format($wallet['locked_balance'], 2) . "\n";
                    $passed++;
                } else {
                    echo "✗ FAIL: Wallet balance was changed during ad creation\n";
                    $failed++;
                }
            }
        }
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 2: Activate ad - no balance locking
echo "\nTest 2: Activate ad (no balance locking)\n";
try {
    if (!empty($adId)) {
        $activationService = new \App\Services\AdActivationService();
        $result = $activationService->activateAd($adId);
        
        if ($result['success']) {
            // Check wallet - should still have full balance
            $wallet = $db->query("SELECT balance, locked_balance FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
            if ($wallet && $wallet['balance'] == 1000.00 && $wallet['locked_balance'] == 0.00) {
                echo "✓ PASS: Ad activated, no balance locked\n";
                echo "  Balance: Rs " . number_format($wallet['balance'], 2) . ", Locked: Rs " . number_format($wallet['locked_balance'], 2) . "\n";
                $passed++;
            } else {
                echo "✗ FAIL: Balance was locked during activation\n";
                $failed++;
            }
        } else {
            echo "✗ FAIL: Activation failed: " . $result['message'] . "\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Charge on click - direct wallet deduction
echo "\nTest 3: Charge on click (direct wallet deduction)\n";
try {
    if (!empty($adId)) {
        $billingService = new \App\Services\RealTimeAdBillingService();
        $result = $billingService->chargeClick($adId);
        
        if ($result['success']) {
            // Check wallet - should be reduced by CPC rate
            $wallet = $db->query("SELECT balance FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
            $expectedBalance = 1000.00 - 2.50; // 997.50
            
            if (abs($wallet['balance'] - $expectedBalance) < 0.01) {
                echo "✓ PASS: Click charged directly from wallet\n";
                echo "  Charged: Rs " . number_format($result['charged'], 2) . "\n";
                echo "  New Balance: Rs " . number_format($wallet['balance'], 2) . "\n";
                $passed++;
            } else {
                echo "✗ FAIL: Wallet balance incorrect. Expected: Rs " . number_format($expectedBalance, 2) . ", Got: Rs " . number_format($wallet['balance'], 2) . "\n";
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

// Test 4: Multiple clicks - wallet decreases each time
echo "\nTest 4: Multiple clicks charge correctly\n";
try {
    if (!empty($adId)) {
        $billingService = new \App\Services\RealTimeAdBillingService();
        
        $walletBefore = $db->query("SELECT balance FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
        $balanceBefore = (float)$walletBefore['balance'];
        
        // Charge 5 clicks
        for ($i = 0; $i < 5; $i++) {
            $result = $billingService->chargeClick($adId);
            if (!$result['success']) {
                break;
            }
        }
        
        $walletAfter = $db->query("SELECT balance FROM seller_wallet WHERE seller_id = ?", [$sellerId])->single();
        $balanceAfter = (float)$walletAfter['balance'];
        $expectedDeduction = 5 * 2.50; // 12.50
        
        if (abs(($balanceBefore - $balanceAfter) - $expectedDeduction) < 0.01) {
            echo "✓ PASS: Multiple clicks charged correctly\n";
            echo "  Deducted: Rs " . number_format($balanceBefore - $balanceAfter, 2) . " for 5 clicks\n";
            $passed++;
        } else {
            echo "✗ FAIL: Incorrect deduction. Expected: Rs " . number_format($expectedDeduction, 2) . ", Got: Rs " . number_format($balanceBefore - $balanceAfter, 2) . "\n";
            $failed++;
        }
    } else {
        echo "⚠ SKIP: No ad ID available\n";
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: Insufficient balance - ad auto-pauses
echo "\nTest 5: Insufficient balance auto-pauses ad\n";
try {
    if (!empty($adId)) {
        // Set wallet balance to less than one click
        $db->query("UPDATE seller_wallet SET balance = 1.00 WHERE seller_id = ?", [$sellerId])->execute();
        
        $billingService = new \App\Services\RealTimeAdBillingService();
        $result = $billingService->chargeClick($adId);
        
        // Check if ad was auto-paused
        $ad = $db->query("SELECT status, auto_paused FROM ads WHERE id = ?", [$adId])->single();
        
        if ($ad && ($ad['auto_paused'] == 1 || $ad['status'] === 'inactive')) {
            echo "✓ PASS: Ad auto-paused when balance insufficient\n";
            $passed++;
        } else {
            echo "✗ FAIL: Ad was not auto-paused\n";
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
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n\n";

if ($failed == 0) {
    echo "✅ ALL TESTS PASSED - Simplified system working correctly!\n";
} else {
    echo "❌ SOME TESTS FAILED - Please fix issues\n";
}

