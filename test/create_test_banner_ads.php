<?php
/**
 * Create Test Banner Ads for 23 Companies
 * External banner ads with different bid amounts
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

use App\Config\BannerSlotConfig;
use App\Core\Database;
use App\Models\Ad;
use App\Models\AdType;
use App\Models\AdCost;

echo "=== Create Test Banner Ads (23 Companies) ===\n\n";

$db = Database::getInstance();
$adModel = new Ad();
$adTypeModel = new AdType();
$adCostModel = new AdCost();

// Get banner_external ad type
$bannerType = $adTypeModel->findByName('banner_external');
if (!$bannerType) {
    echo "✗ banner_external ad type not found\n";
    exit;
}

// Get or create ad costs for different bid amounts
$bidAmounts = [
    500, 750, 1000, 1250, 1500, 1750, 2000, 2250, 2500, 2750, 3000,
    3500, 4000, 4500, 5000, 5500, 6000, 6500, 7000, 7500, 8000, 8500, 9000
];

$slotKeys = array_keys(BannerSlotConfig::getSlots());
$slotCount = count($slotKeys);

if ($slotCount === 0) {
    echo "✗ No banner slots configured. Aborting.\n";
    exit;
}

$adCostIds = [];
foreach ($bidAmounts as $amount) {
    // Find or create ad cost
    $cost = $db->query(
        "SELECT id FROM ads_costs WHERE ads_type_id = ? AND cost_amount = ? LIMIT 1",
        [$bannerType['id'], $amount]
    )->single();
    
    if (!$cost) {
        $db->query(
            "INSERT INTO ads_costs (ads_type_id, duration_days, cost_amount) VALUES (?, 30, ?)",
            [$bannerType['id'], $amount]
        )->execute();
        $costId = $db->lastInsertId();
    } else {
        $costId = $cost['id'];
    }
    $adCostIds[] = $costId;
}

// Get test seller (or create one)
$testSeller = $db->query("SELECT id FROM sellers LIMIT 1")->single();
if (!$testSeller) {
    echo "✗ No seller found. Please create a seller first.\n";
    exit;
}
$sellerId = $testSeller['id'];

// Create 23 banner ads with different bid amounts
$companies = [
    'NutriTech', 'HealthPlus', 'FitLife', 'VitaMax', 'PowerUp', 'EnergyBoost', 'MuscleGain',
    'ProteinPro', 'WellnessCo', 'FitZone', 'HealthHub', 'NutriMax', 'Vitality', 'StrengthPlus',
    'FitnessFirst', 'HealthCore', 'NutriFit', 'PowerFit', 'VitaLife', 'EnergyMax', 'FitPro',
    'HealthMax', 'NutriPower'
];

$startDate = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+30 days'));

$created = 0;
foreach ($companies as $index => $company) {
    $bidAmount = $bidAmounts[$index];
    $costId = $adCostIds[$index];
    $slotKey = $slotKeys[$index % $slotCount];
    $slotMeta = BannerSlotConfig::getSlot($slotKey);
    $tier = $slotMeta['tier'] ?? 'tier3';
    
    // Check if ad already exists
    $existing = $db->query(
        "SELECT id FROM ads WHERE seller_id = ? AND ads_type_id = ? AND banner_link LIKE ? LIMIT 1",
        [$sellerId, $bannerType['id'], "%{$company}%"]
    )->single();
    
    if ($existing) {
        $db->query(
            "UPDATE ads SET slot_key = ?, tier = ?, updated_at = NOW() WHERE id = ?",
            [$slotKey, $tier, $existing['id']]
        )->execute();
        echo "  ↺ Updated slot for {$company} to {$slotKey} ({$tier})\n";
        continue;
    }
    
    // Create banner ad
    $bannerImage = "https://via.placeholder.com/800x300/0A3167/FFFFFF?text={$company}+Banner+Ad";
    $bannerLink = "https://example.com/{$company}";
    
    $adId = $db->query(
        "INSERT INTO ads (seller_id, ads_type_id, banner_image, banner_link, start_date, end_date, ads_cost_id, slot_key, tier, status, notes, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())",
        [
            $sellerId,
            $bannerType['id'],
            $bannerImage,
            $bannerLink,
            $startDate,
            $endDate,
            $costId,
            $slotKey,
            $tier,
            "Test banner ad for {$company} - Bid: रु {$bidAmount}"
        ]
    )->execute();
    
    if ($adId) {
        $created++;
        echo "  ✓ Created ad for {$company} (Bid: रु {$bidAmount})\n";
    }
}

echo "\n=== Summary ===\n";
echo "Created {$created} new banner ads\n";
echo "Total banner ads: " . count($companies) . "\n";
echo "\n";
echo "Bid amounts range: रु " . min($bidAmounts) . " to रु " . max($bidAmounts) . "\n";
echo "Display time range: " . round((min($bidAmounts) / 100) * 60) . "s to " . round((max($bidAmounts) / 100) * 60) . "s\n";
echo "\n";
echo "✓ Banner ads ready for testing!\n";
echo "✓ Higher bid = longer display time\n";
echo "✓ Ads will rotate on home page below categories\n";

