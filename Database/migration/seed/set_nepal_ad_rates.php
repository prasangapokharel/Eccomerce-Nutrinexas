<?php
/**
 * Set Default Ad Rates for Nepal Market
 * Reasonable rates for Nepal e-commerce business
 */

define('ROOT', dirname(dirname(dirname(__DIR__))));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Setting Default Ad Rates for Nepal Market ===\n\n";

// Get ad types
$bannerType = $db->query("SELECT id FROM ads_types WHERE name = 'banner_external' LIMIT 1")->single();
$productType = $db->query("SELECT id FROM ads_types WHERE name = 'product_internal' LIMIT 1")->single();

if (!$bannerType || !$productType) {
    echo "ERROR: Ad types not found. Please run create_ads_tables.sql first.\n";
    exit(1);
}

$bannerTypeId = $bannerType['id'];
$productTypeId = $productType['id'];

// Check if costs already exist
$existingBanner = $db->query(
    "SELECT COUNT(*) as count FROM ads_costs WHERE ads_type_id = ?",
    [$bannerTypeId]
)->single();

$existingProduct = $db->query(
    "SELECT COUNT(*) as count FROM ads_costs WHERE ads_type_id = ?",
    [$productTypeId]
)->single();

// Banner Ad Rates (Fixed Cost - Nepal Market)
$bannerRates = [
    ['duration_days' => 7, 'cost_amount' => 600],
    ['duration_days' => 14, 'cost_amount' => 1100],
    ['duration_days' => 30, 'cost_amount' => 2000],
];

// Product Ad Rates (Fixed Cost - for reference, but product ads use real-time billing now)
$productRates = [
    ['duration_days' => 7, 'cost_amount' => 500],
    ['duration_days' => 14, 'cost_amount' => 900],
    ['duration_days' => 30, 'cost_amount' => 1600],
];

if ($existingBanner['count'] == 0) {
    echo "--- Creating Banner Ad Rates ---\n";
    foreach ($bannerRates as $rate) {
        $db->query(
            "INSERT INTO ads_costs (ads_type_id, duration_days, cost_amount) VALUES (?, ?, ?)",
            [$bannerTypeId, $rate['duration_days'], $rate['cost_amount']]
        )->execute();
        echo "✓ {$rate['duration_days']} days: Rs. {$rate['cost_amount']}\n";
    }
} else {
    echo "ℹ Banner ad rates already exist (skipping)\n";
}

if ($existingProduct['count'] == 0) {
    echo "\n--- Creating Product Ad Rates (Fixed Plans) ---\n";
    foreach ($productRates as $rate) {
        $db->query(
            "INSERT INTO ads_costs (ads_type_id, duration_days, cost_amount) VALUES (?, ?, ?)",
            [$productTypeId, $rate['duration_days'], $rate['cost_amount']]
        )->execute();
        echo "✓ {$rate['duration_days']} days: Rs. {$rate['cost_amount']}\n";
    }
} else {
    echo "ℹ Product ad rates already exist (skipping)\n";
}

echo "\n=== Summary ===\n";
echo "Banner Ad Rates (Fixed Cost):\n";
echo "  • 7 days: Rs. 600\n";
echo "  • 14 days: Rs. 1,100\n";
echo "  • 30 days: Rs. 2,000\n";
echo "\n";
echo "Product Ad Rates (Real-time Billing Recommended):\n";
echo "  • Daily Budget: Rs. 50-500/day\n";
echo "  • Per Click: Rs. 2-10/click\n";
echo "\n";
echo "These rates are competitive for Nepal e-commerce market.\n";
echo "Admin can adjust rates from Admin > Ads > Costs\n";
echo "\n";
echo "Setup completed!\n";

