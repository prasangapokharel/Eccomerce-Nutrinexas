<?php
/**
 * Migration: Add Digital Product and Color Variant Support
 * - Adds is_digital field to products table
 * - Creates product_variants table for colors and sizes
 * - Adds colors JSON field to products table
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Adding Digital Product and Color Variant Support ===\n\n";

try {
    // Check if transaction is supported
    $hasTransaction = false;
    try {
        $db->beginTransaction();
        $hasTransaction = true;
    } catch (Exception $e) {
        // Transaction might not be supported or already active
    }
    
    // 1. Add is_digital field to products table
    echo "1. Adding is_digital field to products table...\n";
    try {
        $db->query("ALTER TABLE products ADD COLUMN is_digital TINYINT(1) DEFAULT 0 COMMENT '1 if product is digital (no shipping required)'")->execute();
        echo "   ✅ Added is_digital field\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️  is_digital field already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Add colors JSON field to products table
    echo "2. Adding colors field to products table...\n";
    try {
        $db->query("ALTER TABLE products ADD COLUMN colors JSON DEFAULT NULL COMMENT 'Available colors as JSON array'")->execute();
        echo "   ✅ Added colors field\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ️  colors field already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Create product_variants table
    echo "3. Creating product_variants table...\n";
    try {
        $db->query("
            CREATE TABLE IF NOT EXISTS product_variants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                variant_type ENUM('color', 'size', 'other') DEFAULT 'color',
                variant_name VARCHAR(100) NOT NULL COMMENT 'Color name, size, etc.',
                variant_value VARCHAR(255) NOT NULL COMMENT 'Hex color code, size value, etc.',
                price_adjustment DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Price difference for this variant',
                stock_quantity INT DEFAULT 0,
                sku VARCHAR(100) DEFAULT NULL,
                image VARCHAR(255) DEFAULT NULL COMMENT 'Variant-specific image',
                is_default TINYINT(1) DEFAULT 0,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_id (product_id),
                INDEX idx_variant_type (variant_type),
                INDEX idx_status (status),
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ")->execute();
        echo "   ✅ Created product_variants table\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "   ℹ️  product_variants table already exists\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Update product_type_main enum to include Digital
    echo "4. Updating product_type_main enum...\n";
    try {
        // Check current enum values
        $result = $db->query("SHOW COLUMNS FROM products WHERE Field = 'product_type_main'")->single();
        if ($result) {
            $enumValues = $result['Type'];
            if (strpos($enumValues, 'Digital') === false) {
                $db->query("ALTER TABLE products MODIFY COLUMN product_type_main ENUM('Accessories','Supplement','Vitamins','Digital') DEFAULT NULL")->execute();
                echo "   ✅ Updated product_type_main enum to include Digital\n";
            } else {
                echo "   ℹ️  product_type_main already includes Digital\n";
            }
        }
    } catch (Exception $e) {
        echo "   ⚠️  Could not update product_type_main: " . $e->getMessage() . "\n";
    }
    
    if ($hasTransaction) {
        try {
            $db->commit();
        } catch (Exception $e) {
            // Transaction might already be committed
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "\nSummary:\n";
    echo "  - is_digital field added to products table\n";
    echo "  - colors JSON field added to products table\n";
    echo "  - product_variants table created for color/size variants\n";
    echo "  - product_type_main enum updated to include Digital\n";
    
} catch (Exception $e) {
    try {
        $db->rollBack();
    } catch (Exception $rollbackException) {
        // Transaction might already be committed
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Migration rolled back (if applicable).\n";
    exit(1);
}

?>

