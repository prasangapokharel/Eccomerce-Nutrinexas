<?php
/**
 * Migration: Create courier-related tables
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Creating courier tables ===\n";

try {
    // Create courier_locations table
    $tableExists = $db->query("SHOW TABLES LIKE 'courier_locations'")->single();
    if (!$tableExists) {
        echo "Creating courier_locations table...\n";
        $sql = "CREATE TABLE `courier_locations` (
            `id` int NOT NULL AUTO_INCREMENT,
            `curior_id` int NOT NULL,
            `order_id` int DEFAULT NULL,
            `latitude` decimal(10,8) NOT NULL,
            `longitude` decimal(11,8) NOT NULL,
            `address` text COLLATE utf8mb4_unicode_ci,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_curior_id` (`curior_id`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->query($sql)->execute();
        echo "✅ courier_locations table created\n";
    } else {
        echo "✅ courier_locations table already exists\n";
    }

    // Create courier_settlements table
    $tableExists = $db->query("SHOW TABLES LIKE 'courier_settlements'")->single();
    if (!$tableExists) {
        echo "Creating courier_settlements table...\n";
        $sql = "CREATE TABLE `courier_settlements` (
            `id` int NOT NULL AUTO_INCREMENT,
            `curior_id` int NOT NULL,
            `order_id` int NOT NULL,
            `cod_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
            `status` enum('pending','collected','settled') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
            `collected_at` timestamp NULL DEFAULT NULL,
            `settled_at` timestamp NULL DEFAULT NULL,
            `notes` text COLLATE utf8mb4_unicode_ci,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_curior_id` (`curior_id`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $db->query($sql)->execute();
        echo "✅ courier_settlements table created\n";
    } else {
        echo "✅ courier_settlements table already exists\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

