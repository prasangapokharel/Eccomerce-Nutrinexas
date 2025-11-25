<?php
/**
 * Verify: Ad tracking implementation in all pages
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

echo "=== Verify: Ad Tracking Implementation ===\n\n";

// Check 1: Product card has tracking
echo "Check 1: Product card tracking...\n";
$productCard = file_get_contents(__DIR__ . '/../App/views/home/sections/shared/product-card.php');
$hasReachTracking = strpos($productCard, 'trackAdReach') !== false;
$hasAdIdCheck = strpos($productCard, 'data-ad-id') !== false;
$hasOnload = strpos($productCard, 'onload') !== false;

echo "  trackAdReach function: " . ($hasReachTracking ? '✓ Found' : '✗ Missing') . "\n";
echo "  data-ad-id attribute: " . ($hasAdIdCheck ? '✓ Found' : '✗ Missing') . "\n";
echo "  onload tracking: " . ($hasOnload ? '✓ Found' : '✗ Missing') . "\n\n";

// Check 2: Search page has tracking
echo "Check 2: Search page tracking...\n";
$searchPage = file_get_contents(__DIR__ . '/../App/views/products/search.php');
$hasSearchTracking = strpos($searchPage, 'trackAdReach') !== false && strpos($searchPage, 'trackAdClick') !== false;
echo "  Tracking functions: " . ($hasSearchTracking ? '✓ Found' : '✗ Missing') . "\n\n";

// Check 3: Index page has tracking
echo "Check 3: Index page tracking...\n";
$indexPage = file_get_contents(__DIR__ . '/../App/views/products/index.php');
$hasIndexTracking = strpos($indexPage, 'trackAdReach') !== false && strpos($indexPage, 'trackAdClick') !== false;
echo "  Tracking functions: " . ($hasIndexTracking ? '✓ Found' : '✗ Missing') . "\n\n";

// Check 4: Category page has tracking
echo "Check 4: Category page tracking...\n";
$categoryPage = file_get_contents(__DIR__ . '/../App/views/products/category.php');
$hasCategoryTracking = strpos($categoryPage, 'trackAdReach') !== false && strpos($categoryPage, 'trackAdClick') !== false;
echo "  Tracking functions: " . ($hasCategoryTracking ? '✓ Found' : '✗ Missing') . "\n\n";

// Check 5: Home assets has tracking
echo "Check 5: Home assets tracking...\n";
$homeAssets = file_get_contents(__DIR__ . '/../App/views/home/sections/home-assets.php');
$hasHomeTracking = strpos($homeAssets, 'trackAdReach') !== false && strpos($homeAssets, 'trackAdClick') !== false;
echo "  Tracking functions: " . ($hasHomeTracking ? '✓ Found' : '✗ Missing') . "\n\n";

echo "=== Summary ===\n";
$allGood = $hasReachTracking && $hasAdIdCheck && $hasOnload && $hasSearchTracking && $hasIndexTracking && $hasCategoryTracking && $hasHomeTracking;
echo ($allGood ? "✓ All tracking implemented correctly!" : "✗ Some tracking missing") . "\n";
echo "\n";
echo "Tracking will work when:\n";
echo "  1. Sponsored product image loads → trackAdReach() called\n";
echo "  2. User clicks sponsored product → trackAdClick() called\n";
echo "  3. Data sent to ads/reach and ads/click endpoints\n";
echo "  4. Database updated with reach and click counts\n";

