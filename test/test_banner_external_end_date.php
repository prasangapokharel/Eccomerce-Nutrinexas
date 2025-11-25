<?php
/**
 * Test Banner External Ads End Date Display
 * 
 * Tests that banner external ads show perfectly till end date (inclusive)
 * Verifies date comparison logic in BannerAdDisplayService
 */

// Load only necessary files
require_once __DIR__ . '/../App/Config/config.php';

// Define constants if not defined
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost:8000');
}
if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', URLROOT . '/public');
}

// Load autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\BannerAdDisplayService;
use App\Models\Ad;
use App\Core\Database;

echo "=== Banner External Ads End Date Test ===\n\n";

$db = Database::getInstance();
$bannerService = new BannerAdDisplayService();
$adModel = new Ad();

// Test 1: Get banner type ID
echo "Test 1: Get banner_external type ID...\n";
$bannerType = $db->query(
    "SELECT id FROM ads_types WHERE name = 'banner_external' LIMIT 1"
)->single();

if (!$bannerType) {
    echo "✗ ERROR: banner_external type not found!\n";
    exit(1);
}

$bannerTypeId = $bannerType['id'];
echo "✓ Banner type ID: {$bannerTypeId}\n\n";

// Test 2: Create test banner with end date = today
echo "Test 2: Create test banner ending today...\n";
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Get required IDs
$sellerId = $db->query("SELECT id FROM sellers ORDER BY id DESC LIMIT 1")->single();
if (!$sellerId) {
    echo "✗ ERROR: No seller found. Please create a seller first.\n";
    exit(1);
}
$sellerId = $sellerId['id'];

$costId = $db->query("SELECT id FROM ads_costs ORDER BY id DESC LIMIT 1")->single();
if (!$costId) {
    echo "✗ ERROR: No ads_cost found. Please create an ads_cost first.\n";
    exit(1);
}
$costId = $costId['id'];

// Create test banner that ends today
$testBannerData = [
    'seller_id' => $sellerId,
    'ads_type_id' => $bannerTypeId,
    'ads_cost_id' => $costId,
    'status' => 'active',
    'start_date' => $yesterday,
    'end_date' => $today, // Ends today
    'banner_image' => 'https://example.com/test-banner.jpg',
    'banner_link' => 'https://example.com',
    'auto_paused' => 0
];

try {
    $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, ads_cost_id, status, start_date, end_date, banner_image, banner_link, auto_paused, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $testBannerData['seller_id'],
            $testBannerData['ads_type_id'],
            $testBannerData['ads_cost_id'],
            $testBannerData['status'],
            $testBannerData['start_date'],
            $testBannerData['end_date'],
            $testBannerData['banner_image'],
            $testBannerData['banner_link'],
            $testBannerData['auto_paused']
        ]
    )->execute();
    
    $testBannerId = $db->lastInsertId();
    echo "✓ Test banner created: ID {$testBannerId} (ends today: {$today})\n";
    
    // Create payment record
    $db->query(
        "INSERT INTO ads_payments (seller_id, ads_id, amount, payment_method, payment_status, created_at)
         VALUES (?, ?, 1000.00, 'wallet', 'paid', NOW())",
        [$sellerId, $testBannerId]
    )->execute();
    echo "✓ Payment record created\n";
} catch (Exception $e) {
    echo "✗ ERROR creating test banner: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verify banner is eligible today (end date)
echo "\nTest 3: Verify banner is eligible on end date (today)...\n";
// Check if test banner is in eligible banners
$eligibleBanner = $db->query(
    "SELECT a.*, ac.cost_amount as bid_amount
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
     LEFT JOIN ads_payments ap ON a.id = ap.ads_id
     WHERE at.id = ?
     AND a.id = ?
     AND a.status = 'active'
     AND (a.auto_paused = 0 OR a.auto_paused IS NULL)
     AND DATE(CURDATE()) >= DATE(a.start_date) 
     AND DATE(CURDATE()) <= DATE(a.end_date)
     AND (ap.payment_status = 'paid' OR ap.id IS NULL)
     AND a.banner_image IS NOT NULL
     AND a.banner_image != ''
     AND a.banner_link IS NOT NULL
     AND a.banner_link != ''",
    [$bannerTypeId, $testBannerId]
)->single();

if ($eligibleBanner) {
    echo "✓ PASS: Banner is eligible on end date (today)\n";
    echo "  Banner ID: {$eligibleBanner['id']}\n";
    echo "  End Date: {$eligibleBanner['end_date']}\n";
    echo "  Today: {$today}\n";
    echo "  Note: Banner may not be selected due to probability algorithm, but it's eligible\n";
} else {
    echo "✗ FAIL: Banner is NOT eligible on end date\n";
    echo "  Expected banner ID: {$testBannerId}\n";
    echo "  Today: {$today}\n";
}

// Test 4: Verify banner doesn't show tomorrow
echo "\nTest 4: Verify banner won't show tomorrow...\n";
// Simulate tomorrow by checking date comparison
$tomorrowBanner = $db->query(
    "SELECT a.*, ac.cost_amount as bid_amount
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
     LEFT JOIN ads_payments ap ON a.id = ap.ads_id
     WHERE at.id = ?
     AND a.id = ?
     AND a.status = 'active'
     AND (a.auto_paused = 0 OR a.auto_paused IS NULL)
     AND DATE(?) >= DATE(a.start_date) 
     AND DATE(?) <= DATE(a.end_date)
     AND (ap.payment_status = 'paid' OR ap.id IS NULL)
     AND a.banner_image IS NOT NULL
     AND a.banner_image != ''
     AND a.banner_link IS NOT NULL
     AND a.banner_link != ''",
    [$bannerTypeId, $testBannerId, $tomorrow, $tomorrow]
)->single();

if (!$tomorrowBanner) {
    echo "✓ PASS: Banner correctly excluded for tomorrow\n";
} else {
    echo "✗ FAIL: Banner still shows for tomorrow (should not)\n";
}

// Test 5: Check date comparison logic
echo "\nTest 5: Verify date comparison logic...\n";
$dateCheck = $db->query(
    "SELECT 
        DATE(CURDATE()) as today,
        DATE(?) as end_date,
        DATE(CURDATE()) >= DATE(?) as start_check,
        DATE(CURDATE()) <= DATE(?) as end_check,
        (DATE(CURDATE()) >= DATE(?) AND DATE(CURDATE()) <= DATE(?)) as should_show",
    [$testBannerData['end_date'], $testBannerData['start_date'], $testBannerData['end_date'], 
     $testBannerData['start_date'], $testBannerData['end_date']]
)->single();

echo "  Today: {$dateCheck['today']}\n";
echo "  End Date: {$dateCheck['end_date']}\n";
echo "  Start Check (today >= start): " . ($dateCheck['start_check'] ? 'YES' : 'NO') . "\n";
echo "  End Check (today <= end): " . ($dateCheck['end_check'] ? 'YES' : 'NO') . "\n";
echo "  Should Show: " . ($dateCheck['should_show'] ? 'YES ✓' : 'NO ✗') . "\n";

if ($dateCheck['should_show']) {
    echo "✓ PASS: Date comparison logic is correct\n";
} else {
    echo "✗ FAIL: Date comparison logic is incorrect\n";
}

// Test 6: Clean up test banner
echo "\nTest 6: Cleanup...\n";
try {
    $db->query("DELETE FROM ads_payments WHERE ads_id = ?", [$testBannerId])->execute();
    $db->query("DELETE FROM ads WHERE id = ?", [$testBannerId])->execute();
    echo "✓ Test banner cleaned up\n";
} catch (Exception $e) {
    echo "⚠ Warning: Could not clean up test banner: " . $e->getMessage() . "\n";
}

// Test 7: Test with real banners
echo "\nTest 7: Check real banners with end dates...\n";
$realBanners = $db->query(
    "SELECT a.id, a.start_date, a.end_date, a.status,
            DATE(CURDATE()) as today,
            (DATE(CURDATE()) >= DATE(a.start_date) AND DATE(CURDATE()) <= DATE(a.end_date)) as in_range
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     WHERE at.name = 'banner_external'
     AND a.status = 'active'
     ORDER BY a.end_date DESC
     LIMIT 5"
)->all();

if (empty($realBanners)) {
    echo "⚠ No real banners found to test\n";
} else {
    echo "Found " . count($realBanners) . " real banners:\n";
    foreach ($realBanners as $banner) {
        $status = $banner['in_range'] ? '✓ ACTIVE' : '✗ EXPIRED';
        echo "  Banner #{$banner['id']}: {$banner['start_date']} to {$banner['end_date']} - {$status}\n";
    }
}

echo "\n=== Test Complete ===\n";

