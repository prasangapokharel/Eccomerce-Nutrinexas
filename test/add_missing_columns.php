<?php
/**
 * Add missing columns to products table
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define minimal constants
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost:8000');
}

use App\Core\Database;

$db = Database::getInstance();

try {
    // Check if is_digital column exists
    $columns = $db->query("SHOW COLUMNS FROM products LIKE 'is_digital'")->all();
    if (empty($columns)) {
        $db->query("ALTER TABLE products ADD COLUMN is_digital TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether product is digital' AFTER is_featured")->execute();
        echo "✓ Added is_digital column\n";
    } else {
        echo "✓ is_digital column already exists\n";
    }
    
    // Check if colors column exists
    $columns = $db->query("SHOW COLUMNS FROM products LIKE 'colors'")->all();
    if (empty($columns)) {
        $db->query("ALTER TABLE products ADD COLUMN colors TEXT NULL COMMENT 'Available colors for product (JSON array)' AFTER product_type")->execute();
        echo "✓ Added colors column\n";
    } else {
        echo "✓ colors column already exists\n";
    }
    
    echo "\nAll columns are ready!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

