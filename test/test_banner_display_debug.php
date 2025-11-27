<?php
/**
 * Debug Banner Display Issue
 * Check why banner ads are not showing
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
use App\Services\BannerAdDisplayService;
use App\Core\Database;

$db = Database::getInstance();
$adModel = new Ad();
$bannerService = new BannerAdDisplayService();

echo "=== Banner Display Debug ===\n\n";

// Check 1: Get banner_external ad type
$bannerType = $db->query(
    "SELECT id, name FROM ads_types WHERE name = 'banner_external' LIMIT 1"
)->single();

if (!$bannerType) {
    echo "✗ ERROR: banner_external ad type not found!\n";
    exit(1);
}

echo "✓ Banner ad type found: ID {$bannerType['id']}, Name: {$bannerType['name']}\n\n";

// Check 2: Check all banner ads
$allBannerAds = $db->query(
    "SELECT a.id, a.status, a.start_date, a.end_date, a.banner_image, a.banner_link,
            ap.payment_status, ac.cost_amount
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     LEFT JOIN ads_payments ap ON a.id = ap.ads_id
     LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
     WHERE at.name = 'banner_external'
     ORDER BY a.id DESC
     LIMIT 10"
)->all();

echo "--- All Banner Ads (Last 10) ---\n";
if (empty($allBannerAds)) {
    echo "✗ ERROR: No banner ads found in database!\n";
} else {
    foreach ($allBannerAds as $ad) {
        echo "Ad #{$ad['id']}:\n";
        echo "  Status: {$ad['status']}\n";
        echo "  Payment Status: " . ($ad['payment_status'] ?? 'N/A') . "\n";
        echo "  Date Range: {$ad['start_date']} to {$ad['end_date']}\n";
        echo "  Banner Image: " . ($ad['banner_image'] ? 'SET' : 'MISSING') . "\n";
        echo "  Banner Link: " . ($ad['banner_link'] ? 'SET' : 'MISSING') . "\n";
        echo "  Cost: Rs " . ($ad['cost_amount'] ?? '0') . "\n";
        
        $today = date('Y-m-d');
        $isDateValid = ($ad['start_date'] <= $today && $ad['end_date'] >= $today);
        $isActive = ($ad['status'] === 'active');
        $isPaid = ($ad['payment_status'] === 'paid');
        $hasImage = !empty($ad['banner_image']);
        $hasLink = !empty($ad['banner_link']);
        
        $canDisplay = $isActive && $isPaid && $isDateValid && $hasImage && $hasLink;
        
        echo "  Can Display: " . ($canDisplay ? 'YES ✓' : 'NO ✗') . "\n";
        if (!$canDisplay) {
            $issues = [];
            if (!$isActive) $issues[] = "Status is not 'active'";
            if (!$isPaid) $issues[] = "Payment not paid";
            if (!$isDateValid) $issues[] = "Date range invalid";
            if (!$hasImage) $issues[] = "Missing banner_image";
            if (!$hasLink) $issues[] = "Missing banner_link";
            echo "  Issues: " . implode(', ', $issues) . "\n";
        }
        echo "\n";
    }
}

// Check 3: Test getHomepageBanner
echo "--- Testing getHomepageBanner() ---\n";
$homepageBanner = $bannerService->getHomepageBanner();

if ($homepageBanner) {
    echo "✓ Homepage banner found:\n";
    echo "  Ad ID: {$homepageBanner['id']}\n";
    echo "  Banner Image: {$homepageBanner['banner_image']}\n";
    echo "  Banner Link: {$homepageBanner['banner_link']}\n";
    echo "  Bid Amount: Rs " . ($homepageBanner['bid_amount'] ?? '0') . "\n";
} else {
    echo "✗ ERROR: getHomepageBanner() returned NULL\n";
    echo "  This means no eligible banners found for homepage display\n";
}

echo "\n";

// Check 4: Test getActiveBannerAds
echo "--- Testing getActiveBannerAds() ---\n";
$activeBanners = $adModel->getActiveBannerAds(10);

if (!empty($activeBanners)) {
    echo "✓ Found " . count($activeBanners) . " active banner ads:\n";
    foreach ($activeBanners as $banner) {
        echo "  Ad #{$banner['id']}: {$banner['banner_image']}\n";
    }
} else {
    echo "✗ ERROR: getActiveBannerAds() returned empty array\n";
}

echo "\n";

// Check 5: Query eligible banners directly
echo "--- Direct Query Check ---\n";
$today = date('Y-m-d');
$eligibleBanners = $db->query(
    "SELECT a.*, ac.cost_amount as bid_amount
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
     INNER JOIN ads_payments ap ON a.id = ap.ads_id
     WHERE at.id = ?
     AND a.status = 'active'
     AND a.status != 'suspended'
     AND CURDATE() BETWEEN a.start_date AND a.end_date
     AND ap.payment_status = 'paid'
     AND a.banner_image IS NOT NULL
     AND a.banner_image != ''
     AND a.banner_link IS NOT NULL
     AND a.banner_link != ''
     ORDER BY ac.cost_amount DESC
     LIMIT 3",
    [$bannerType['id']]
)->all();

if (!empty($eligibleBanners)) {
    echo "✓ Found " . count($eligibleBanners) . " eligible banners:\n";
    foreach ($eligibleBanners as $banner) {
        echo "  Ad #{$banner['id']}: {$banner['banner_image']}\n";
    }
} else {
    echo "✗ ERROR: No eligible banners found with direct query\n";
    echo "  This means banners are missing one or more requirements:\n";
    echo "  - status = 'active' AND not 'suspended'\n";
    echo "  - payment_status = 'paid'\n";
    echo "  - Date range valid (today between start_date and end_date)\n";
    echo "  - banner_image is not NULL and not empty\n";
    echo "  - banner_link is not NULL and not empty\n";
}

echo "\n";
echo "=== Debug Complete ===\n";





