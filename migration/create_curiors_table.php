<?php
/**
 * Migration: Create curiors table if not exists
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

echo "=== Creating curiors table ===\n";

try {
    $tableExists = $db->query("SHOW TABLES LIKE 'curiors'")->single();
    
    if ($tableExists) {
        echo "✅ curiors table already exists\n";
    } else {
        echo "Creating curiors table...\n";
        
        $sql = "CREATE TABLE `curiors` (
            `id` int NOT NULL AUTO_INCREMENT,
            `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
            `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
            `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `address` text COLLATE utf8mb4_unicode_ci,
            `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `phone_unique` (`phone`),
            UNIQUE KEY `email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql)->execute();
        echo "✅ curiors table created successfully\n";
    }
    
    $curiorIdExists = $db->query("SHOW COLUMNS FROM orders LIKE 'curior_id'")->single();
    if (!$curiorIdExists) {
        echo "Adding curior_id column to orders table...\n";
        $db->query("ALTER TABLE orders ADD COLUMN curior_id INT UNSIGNED NULL AFTER status")->execute();
        echo "✅ curior_id column added to orders table\n";
    } else {
        echo "✅ curior_id column already exists in orders table\n";
    }
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

