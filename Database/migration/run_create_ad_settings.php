<?php
/**
 * Create ad_settings table
 */

define('ROOT', dirname(dirname(__DIR__)));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Creating ad_settings table ===\n\n";

try {
    // Create table
    $db->query(
        "CREATE TABLE IF NOT EXISTS `ad_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` VARCHAR(255) NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
    )->execute();
    
    echo "✓ Table created successfully\n\n";
    
    // Insert default values
    $defaults = [
        'per_click_min' => '2',
        'per_click_recommended' => '5',
        'per_click_premium' => '10',
        'daily_budget_min' => '50',
        'daily_budget_recommended' => '100',
        'daily_budget_premium' => '500'
    ];
    
    echo "--- Inserting default values ---\n";
    foreach ($defaults as $key => $value) {
        $db->query(
            "INSERT INTO `ad_settings` (`setting_key`, `setting_value`) 
             VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)",
            [$key, $value]
        )->execute();
        echo "✓ {$key} = {$value}\n";
    }
    
    echo "\n=== Migration completed successfully! ===\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

