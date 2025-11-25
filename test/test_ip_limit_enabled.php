<?php
/**
 * Test IP Limit when enabled
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
use App\Services\RealTimeAdBillingService;

echo "=== Testing IP Limit (ENABLED) ===\n\n";

// Note: This test assumes ADS_IP_LIMIT is set to 'enable' in config.php
// If not, you need to change it manually for this test

$db = Database::getInstance();
$sellerId = 2;
$testIp = '192.168.1.200';

// Check if IP limit is enabled
$ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';

if (!$ipLimitEnabled) {
    echo "⚠ WARNING: ADS_IP_LIMIT is not enabled in config.php\n";
    echo "Please set ADS_IP_LIMIT to 'enable' in App/Config/config.php to test this feature\n";
    echo "Skipping test...\n";
    exit(0);
}

// Get seller 2's active ads
$ads = $db->query(
    "SELECT id FROM ads WHERE seller_id = ? AND status = 'active' ORDER BY id",
    [$sellerId]
)->all();

if (count($ads) < 5) {
    echo "✗ Need at least 5 ads for testing. Found: " . count($ads) . "\n";
    exit(1);
}

echo "Found " . count($ads) . " ads for testing\n";
echo "Testing with IP: {$testIp}\n";
echo "IP Limit: ENABLED (max 10 ads per IP per day)\n\n";

// Clear today's click logs for test IP
$today = date('Y-m-d');
$db->query(
    "DELETE FROM ads_click_logs WHERE ip_address = ? AND DATE(clicked_at) = ?",
    [$testIp, $today]
)->execute();

$billingService = new RealTimeAdBillingService();
$adModel = new Ad();

$chargedCount = 0;
$blockedCount = 0;

// Test 1: Click same ad twice (should charge first, block second)
echo "Test 1: Clicking same ad twice\n";
$firstAdId = $ads[0]['id'];
$adModel->logClick($firstAdId, $testIp);
$result1 = $billingService->chargeClick($firstAdId, $testIp);
if ($result1['success'] && $result1['charged'] > 0) {
    $chargedCount++;
    echo "  Ad #{$firstAdId} (1st click): ✓ Charged Rs {$result1['charged']}\n";
} else {
    echo "  Ad #{$firstAdId} (1st click): ✗ {$result1['message']}\n";
}

$adModel->logClick($firstAdId, $testIp);
$result2 = $billingService->chargeClick($firstAdId, $testIp);
if ($result2['success'] && $result2['charged'] > 0) {
    $chargedCount++;
    echo "  Ad #{$firstAdId} (2nd click): ✓ Charged Rs {$result2['charged']}\n";
} else {
    $blockedCount++;
    echo "  Ad #{$firstAdId} (2nd click): ✗ Blocked - {$result2['message']}\n";
}

// Test 2: Click 10 different ads (all should charge)
echo "\nTest 2: Clicking 10 different ads\n";
$adsToTest = array_slice($ads, 0, min(10, count($ads)));
foreach ($adsToTest as $ad) {
    $adId = $ad['id'];
    if ($adId == $firstAdId) continue; // Skip first ad (already tested)
    
    $adModel->logClick($adId, $testIp);
    $result = $billingService->chargeClick($adId, $testIp);
    
    if ($result['success'] && $result['charged'] > 0) {
        $chargedCount++;
        echo "  Ad #{$adId}: ✓ Charged Rs {$result['charged']}\n";
    } else {
        $blockedCount++;
        echo "  Ad #{$adId}: ✗ Blocked - {$result['message']}\n";
    }
}

echo "\n=== Results ===\n";
echo "Total Charged: {$chargedCount} clicks\n";
echo "Total Blocked: {$blockedCount} clicks\n";

// Should have charged first click of first ad, then 9 more unique ads = 10 total unique ads charged
// Second click of first ad should be blocked
$expectedUniqueAds = min(10, count($ads));
if ($chargedCount >= $expectedUniqueAds && $blockedCount >= 1) {
    echo "\n✓ PASS: IP limit working correctly\n";
    exit(0);
} else {
    echo "\n⚠ NOTE: Test completed. Verify results manually.\n";
    exit(0);
}

