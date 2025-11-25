<?php
/**
 * Migration: Create order_activities table for tracking order status changes
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

echo "=== Creating order_activities table ===\n";

try {
    $tableExists = $db->query("SHOW TABLES LIKE 'order_activities'")->single();
    
    if ($tableExists) {
        echo "✅ order_activities table already exists\n";
    } else {
        echo "Creating order_activities table...\n";
        
        $sql = "CREATE TABLE `order_activities` (
            `id` int NOT NULL AUTO_INCREMENT,
            `order_id` int NOT NULL,
            `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
            `data` text COLLATE utf8mb4_unicode_ci,
            `created_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_action` (`action`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql)->execute();
        echo "✅ order_activities table created successfully\n";
    }
    
    $deliveredAtExists = $db->query("SHOW COLUMNS FROM orders LIKE 'delivered_at'")->single();
    if (!$deliveredAtExists) {
        echo "Adding delivered_at column to orders table...\n";
        $db->query("ALTER TABLE orders ADD COLUMN delivered_at TIMESTAMP NULL AFTER updated_at")->execute();
        echo "✅ delivered_at column added to orders table\n";
    } else {
        echo "✅ delivered_at column already exists in orders table\n";
    }
    
    $statuses = ['picked_up', 'in_transit', 'return_requested', 'return_picked_up', 'return_in_transit', 'returned'];
    $currentStatuses = $db->query("SHOW COLUMNS FROM orders WHERE Field = 'status'")->single();
    
    if ($currentStatuses) {
        $type = $currentStatuses['Type'];
        foreach ($statuses as $status) {
            if (stripos($type, $status) === false) {
                echo "Note: Status '{$status}' may need to be added to orders.status enum\n";
            }
        }
    }
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

