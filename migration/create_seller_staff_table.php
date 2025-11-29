<?php
/**
 * Migration: Create seller_staff table if not exists
 * This table stores delivery boys and other staff members for sellers
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

echo "=== Creating seller_staff table ===\n";

try {
    $tableExists = $db->query("SHOW TABLES LIKE 'seller_staff'")->single();
    
    if ($tableExists) {
        echo "✅ seller_staff table already exists\n";
        
        // Check and add missing columns
        $columns = $db->query("SHOW COLUMNS FROM seller_staff")->all();
        $columnNames = array_column($columns, 'Field');
        
        if (!in_array('role', $columnNames)) {
            echo "Adding 'role' column...\n";
            $db->query("ALTER TABLE seller_staff ADD COLUMN role VARCHAR(50) DEFAULT 'delivery_boy' AFTER password")->execute();
            echo "✅ 'role' column added\n";
        } else {
            echo "✅ 'role' column already exists\n";
        }
        
        if (!in_array('city', $columnNames)) {
            echo "Adding 'city' column...\n";
            $db->query("ALTER TABLE seller_staff ADD COLUMN city VARCHAR(100) NOT NULL DEFAULT '' AFTER password")->execute();
            echo "✅ 'city' column added\n";
        } else {
            echo "✅ 'city' column already exists\n";
        }
    } else {
        echo "Creating seller_staff table...\n";
        
        $sql = "CREATE TABLE `seller_staff` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `seller_id` INT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `phone` VARCHAR(20) DEFAULT NULL,
            `password` VARCHAR(255) NOT NULL,
            `city` VARCHAR(100) NOT NULL,
            `role` VARCHAR(50) DEFAULT 'delivery_boy',
            `status` VARCHAR(20) DEFAULT 'active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_seller_id` (`seller_id`),
            INDEX `idx_email` (`email`),
            INDEX `idx_city` (`city`),
            INDEX `idx_status` (`status`),
            FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql)->execute();
        echo "✅ seller_staff table created successfully\n";
    }
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

