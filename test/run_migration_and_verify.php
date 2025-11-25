<?php
/**
 * Run Migration and Verify Database Schema
 * This script reads the database, runs migrations, and verifies all columns
 */

// Load autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

// Define minimal constants
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost:8000');
}

// Load database config
require_once __DIR__ . '/../App/Config/database.php';

// Now we can use Database class
use App\Core\Database;

echo "=== Database Migration and Verification ===\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✓ Database connection successful\n\n";
    
    // Step 1: Read current products table structure
    echo "1. Reading Products Table Structure:\n";
    echo str_repeat("-", 80) . "\n";
    $columns = $db->query("DESCRIBE products")->all();
    
    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        printf("%-30s %-25s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    
    echo "\nTotal columns: " . count($columnNames) . "\n";
    
    // Step 2: Check for missing columns
    echo "\n2. Checking for Required Columns:\n";
    $requiredColumns = ['is_digital', 'colors', 'subtype'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columnNames)) {
            echo "✓ $col exists\n";
        } else {
            echo "✗ $col MISSING - will be added\n";
            $missingColumns[] = $col;
        }
    }
    
    // Step 3: Run migrations for missing columns
    if (!empty($missingColumns)) {
        echo "\n3. Running Migrations:\n";
        
        // Add is_digital if missing
        if (in_array('is_digital', $missingColumns)) {
            try {
                $db->query("ALTER TABLE products ADD COLUMN is_digital TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether product is digital (no shipping required)' AFTER is_featured")->execute();
                echo "✓ Added is_digital column\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    throw $e;
                }
                echo "✓ is_digital column already exists\n";
            }
        }
        
        // Add colors if missing
        if (in_array('colors', $missingColumns)) {
            try {
                $db->query("ALTER TABLE products ADD COLUMN colors TEXT NULL COMMENT 'Available colors for product (JSON array)' AFTER product_type")->execute();
                echo "✓ Added colors column\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    throw $e;
                }
                echo "✓ colors column already exists\n";
            }
        }
        
        // Add subtype if missing
        if (in_array('subtype', $missingColumns)) {
            try {
                $db->query("ALTER TABLE products ADD COLUMN subtype VARCHAR(255) NULL AFTER category")->execute();
                echo "✓ Added subtype column\n";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    throw $e;
                }
                echo "✓ subtype column already exists\n";
            }
        }
    } else {
        echo "\n3. All required columns exist - no migration needed\n";
    }
    
    // Step 4: Verify final structure
    echo "\n4. Final Products Table Structure:\n";
    echo str_repeat("-", 80) . "\n";
    $finalColumns = $db->query("DESCRIBE products")->all();
    
    $finalColumnNames = [];
    foreach ($finalColumns as $col) {
        $finalColumnNames[] = $col['Field'];
    }
    
    echo "Total columns: " . count($finalColumnNames) . "\n";
    echo "All columns: " . implode(', ', $finalColumnNames) . "\n";
    
    // Step 5: List all tables
    echo "\n5. All Tables in Database:\n";
    echo str_repeat("-", 80) . "\n";
    $tables = $db->query("SHOW TABLES")->all();
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $tableNames[] = $tableName;
        $columnCount = $db->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$tableName])->single();
        echo sprintf("%-40s %d columns\n", $tableName, $columnCount['cnt']);
    }
    
    echo "\nTotal tables: " . count($tableNames) . "\n";
    
    // Step 6: Check AdminController uses maximum columns
    echo "\n6. Verifying AdminController Uses Maximum Columns:\n";
    echo str_repeat("-", 80) . "\n";
    $controllerFile = __DIR__ . '/../App/Controllers/AdminController.php';
    $controllerContent = file_get_contents($controllerFile);
    
    // All available columns from schema
    $allColumns = [
        'product_name', 'slug', 'description', 'short_description', 'price', 'sale_price',
        'stock_quantity', 'category', 'subtype', 'weight', 'serving', 'is_featured',
        'is_digital', 'product_type_main', 'product_type', 'colors', 'flavor', 'material',
        'ingredients', 'size_available', 'status', 'commission_rate', 'seller_commission',
        'meta_title', 'meta_description', 'tags', 'cost_price', 'compare_price',
        'optimal_weight', 'serving_size', 'capsule', 'is_scheduled', 'scheduled_date',
        'scheduled_duration', 'scheduled_message', 'seller_id'
    ];
    
    $usedColumns = [];
    $unusedColumns = [];
    
    foreach ($allColumns as $col) {
        if (in_array($col, $finalColumnNames)) {
            $used = strpos($controllerContent, "'$col'") !== false || 
                    strpos($controllerContent, "\"$col\"") !== false ||
                    strpos($controllerContent, "['$col']") !== false ||
                    strpos($controllerContent, '[$col]') !== false;
            
            if ($used) {
                $usedColumns[] = $col;
                echo "✓ $col (used)\n";
            } else {
                $unusedColumns[] = $col;
                echo "⚠ $col (exists but not used in addProduct)\n";
            }
        }
    }
    
    echo "\nUsed columns: " . count($usedColumns) . " / " . count($allColumns) . "\n";
    if (!empty($unusedColumns)) {
        echo "Unused columns: " . implode(', ', $unusedColumns) . "\n";
    }
    
    echo "\n=== Migration and Verification Complete ===\n";
    echo "✓ All migrations passed\n";
    echo "✓ Database structure verified\n";
    echo "✓ Ready to use all maximum columns\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

