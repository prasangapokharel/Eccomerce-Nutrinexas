<?php
/**
 * Run Migration Now - Execute SQL migrations
 * This script will run the migration SQL statements
 */

// Load bootstrap to get database connection
require_once __DIR__ . '/../App/bootstrap.php';

use App\Core\Database;

echo "=== Running Database Migrations ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✓ Database connection successful\n\n";
    
    // Migration SQL statements
    $migrations = [
        [
            'name' => 'Add is_digital column',
            'sql' => "ALTER TABLE products ADD COLUMN is_digital TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether product is digital (no shipping required)' AFTER is_featured",
            'check' => "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'is_digital'"
        ],
        [
            'name' => 'Add colors column',
            'sql' => "ALTER TABLE products ADD COLUMN colors TEXT NULL COMMENT 'Available colors for product (JSON array)' AFTER product_type",
            'check' => "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'colors'"
        ],
        [
            'name' => 'Add subtype column',
            'sql' => "ALTER TABLE products ADD COLUMN subtype VARCHAR(255) NULL AFTER category",
            'check' => "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'subtype'"
        ]
    ];
    
    foreach ($migrations as $migration) {
        echo "Running: {$migration['name']}...\n";
        
        // Check if column already exists
        $checkResult = $pdo->query($migration['check'])->fetch(PDO::FETCH_ASSOC);
        
        if ($checkResult['cnt'] > 0) {
            echo "  ⚠ Column already exists, skipping...\n";
        } else {
            try {
                $pdo->exec($migration['sql']);
                echo "  ✓ Successfully added column\n";
            } catch (PDOException $e) {
                // Check if error is about duplicate column
                if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false) {
                    echo "  ⚠ Column already exists (detected via error)\n";
                } else {
                    throw $e;
                }
            }
        }
        echo "\n";
    }
    
    // Verify all columns exist
    echo "Verifying migrations...\n";
    echo str_repeat("-", 80) . "\n";
    
    $requiredColumns = ['is_digital', 'colors', 'subtype'];
    $allExist = true;
    
    foreach ($requiredColumns as $col) {
        $check = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = '$col'")->fetch(PDO::FETCH_ASSOC);
        
        if ($check['cnt'] > 0) {
            echo "✓ $col column exists\n";
        } else {
            echo "✗ $col column MISSING\n";
            $allExist = false;
        }
    }
    
    echo "\n";
    
    if ($allExist) {
        echo "=== Migration Complete - All Columns Added Successfully! ===\n";
        echo "\nAll required columns are now in the products table:\n";
        echo "  ✓ is_digital\n";
        echo "  ✓ colors\n";
        echo "  ✓ subtype\n";
        echo "\nYou can now add products with all maximum columns!\n";
    } else {
        echo "=== Migration Incomplete ===\n";
        echo "Some columns are still missing. Please check the errors above.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

