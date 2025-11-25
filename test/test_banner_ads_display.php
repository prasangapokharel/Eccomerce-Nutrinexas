<?php
/**
 * Test Banner Ads Display
 * Verify banner ads are showing correctly
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\BannerAdDisplayService;
use App\Core\Database;

$db = Database::getInstance();
$bannerService = new BannerAdDisplayService();

echo "=== Banner Ads Display Test ===\n\n";

// Check for banner ads
echo "--- Checking for banner ads ---\n";
$banners = $db->query(
    "SELECT a.*, at.name as ad_type_name, ap.payment_status 
     FROM ads a
     INNER JOIN ads_types at ON a.ads_type_id = at.id
     LEFT JOIN ads_payments ap ON a.id = ap.ads_id
     WHERE at.name = 'banner_external' 
     AND a.status = 'active'
     LIMIT 5"
)->all();

echo "Found " . count($banners) . " banner ads:\n";
foreach ($banners as $banner) {
    echo "  Ad #{$banner['id']}: Status={$banner['status']}, Payment={$banner['payment_status']}, Image=" . (!empty($banner['banner_image']) ? 'Yes' : 'No') . "\n";
}

// Test getHomepageBanner
echo "\n--- Testing getHomepageBanner() ---\n";
$homepageBanner = $bannerService->getHomepageBanner();
if ($homepageBanner) {
    echo "✓ Homepage banner found:\n";
    echo "  ID: {$homepageBanner['id']}\n";
    echo "  Image: {$homepageBanner['banner_image']}\n";
    echo "  Link: {$homepageBanner['banner_link']}\n";
} else {
    echo "✗ No homepage banner found\n";
}

// Test getSearchBanner
echo "\n--- Testing getSearchBanner() ---\n";
$searchBanner = $bannerService->getSearchBanner();
if ($searchBanner) {
    echo "✓ Search banner found:\n";
    echo "  ID: {$searchBanner['id']}\n";
    echo "  Image: {$searchBanner['banner_image']}\n";
} else {
    echo "✗ No search banner found\n";
}

echo "\n=== Test Summary ===\n";
echo "Banner ads should display if:\n";
echo "  1. Status = 'active'\n";
echo "  2. Auto_paused = 0 or NULL\n";
echo "  3. Current date is between start_date and end_date\n";
echo "  4. Payment status = 'paid' (or no payment record)\n";
echo "  5. Has banner_image and banner_link\n";
echo "\nTest completed!\n";

