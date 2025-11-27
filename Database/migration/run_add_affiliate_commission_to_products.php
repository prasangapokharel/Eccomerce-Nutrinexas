<?php
/**
 * Migration: Add affiliate_commission to products table
 */

require_once __DIR__ . '/../../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

echo "=== Adding affiliate_commission to products table ===\n\n";

try {
    $db = Database::getInstance();
    
    // Check if column already exists
    $checkColumn = $db->query(
        "SELECT COUNT(*) as count 
         FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'products' 
         AND COLUMN_NAME = 'affiliate_commission'"
    )->single();
    
    if ($checkColumn['count'] > 0) {
        echo "✓ Column 'affiliate_commission' already exists\n";
    } else {
        // Add column
        $db->query(
            "ALTER TABLE `products` 
             ADD COLUMN `affiliate_commission` decimal(5,2) DEFAULT NULL 
             COMMENT 'Affiliate commission percentage for this product. If NULL, uses default commission_rate from settings' 
             AFTER `seller_commission`"
        )->execute();
        
        echo "✓ Added 'affiliate_commission' column to products table\n";
    }
    
    // Check if index exists
    $checkIndex = $db->query(
        "SELECT COUNT(*) as count 
         FROM INFORMATION_SCHEMA.STATISTICS 
         WHERE TABLE_SCHEMA = DATABASE() 
         AND TABLE_NAME = 'products' 
         AND INDEX_NAME = 'idx_affiliate_commission'"
    )->single();
    
    if ($checkIndex['count'] > 0) {
        echo "✓ Index 'idx_affiliate_commission' already exists\n";
    } else {
        // Add index
        $db->query(
            "CREATE INDEX `idx_affiliate_commission` ON `products` (`affiliate_commission`)"
        )->execute();
        
        echo "✓ Added index 'idx_affiliate_commission'\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}





