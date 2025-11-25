<?php
/**
 * Migration: Add banner slot & tier support to ads/ads_costs tables
 */

define('ROOT', dirname(dirname(__DIR__)));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Config\BannerSlotConfig;
use App\Core\Database;

$db = Database::getInstance();

echo "=== Adding banner slot + tier columns ===\n";

try {
    // Add slot_key column to ads table
    $slotColumn = $db->query("SHOW COLUMNS FROM ads LIKE 'slot_key'")->single();
    if (!$slotColumn) {
        echo "- Adding ads.slot_key column...\n";
        $db->query("ALTER TABLE ads ADD COLUMN slot_key VARCHAR(50) NOT NULL DEFAULT 'slot_home_offer_box' AFTER ads_cost_id")->execute();
    } else {
        echo "✔ ads.slot_key already exists\n";
    }

    // Add tier column to ads table
    $tierColumn = $db->query("SHOW COLUMNS FROM ads LIKE 'tier'")->single();
    if (!$tierColumn) {
        echo "- Adding ads.tier column...\n";
        $db->query("ALTER TABLE ads ADD COLUMN tier ENUM('tier1','tier2','tier3') NOT NULL DEFAULT 'tier3' AFTER slot_key")->execute();
    } else {
        echo "✔ ads.tier already exists\n";
    }

    // Ensure ads rows have defaults
    $db->query("UPDATE ads SET slot_key = IFNULL(slot_key, 'slot_home_offer_box'), tier = IFNULL(tier, 'tier3')")->execute();

    // Add tier column to ads_costs
    $costTierColumn = $db->query("SHOW COLUMNS FROM ads_costs LIKE 'tier'")->single();
    if (!$costTierColumn) {
        echo "- Adding ads_costs.tier column...\n";
        $db->query("ALTER TABLE ads_costs ADD COLUMN tier ENUM('tier1','tier2','tier3') NULL AFTER cost_amount")->execute();
    } else {
        echo "✔ ads_costs.tier already exists\n";
    }

    // Upsert tier pricing for banner_external type
    $bannerType = $db->query("SELECT id FROM ads_types WHERE name = 'banner_external' LIMIT 1")->single();
    if ($bannerType) {
        $typeId = (int) $bannerType['id'];
        foreach (BannerSlotConfig::TIERS as $tier => $meta) {
            $existing = $db->query(
                "SELECT id FROM ads_costs WHERE ads_type_id = ? AND tier = ? LIMIT 1",
                [$typeId, $tier]
            )->single();

            if ($existing) {
                echo "- Updating ads_costs tier {$tier} (ID {$existing['id']})...\n";
                $db->query(
                    "UPDATE ads_costs SET duration_days = ?, cost_amount = ?, tier = ?, updated_at = NOW() WHERE id = ?",
                    [$meta['duration_days'], $meta['price'], $tier, $existing['id']]
                )->execute();
            } else {
                echo "- Creating ads_costs tier {$tier}...\n";
                $db->query(
                    "INSERT INTO ads_costs (ads_type_id, duration_days, cost_amount, tier, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                    [$typeId, $meta['duration_days'], $meta['price'], $tier]
                )->execute();
            }
        }
    } else {
        echo "⚠ banner_external ad type not found. Skipping cost seeding.\n";
    }

    echo "Migration completed.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}


