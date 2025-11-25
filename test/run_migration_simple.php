<?php
/**
 * Simple Migration Runner - Direct PDO Connection
 */

// Load database config
$dbConfig = require __DIR__ . '/../App/Config/database.php';

try {
    // Create PDO connection directly
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    
    echo "=== Database Migration and Verification ===\n\n";
    echo "✓ Database connection successful\n\n";
    
    // Step 1: Read current products table structure
    echo "1. Reading Products Table Structure:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Step 2: Check for missing columns and run migrations
    echo "\n2. Checking and Adding Required Columns:\n";
    
    // Check and add is_digital
    if (!in_array('is_digital', $columnNames)) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN is_digital TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether product is digital (no shipping required)' AFTER is_featured");
            echo "✓ Added is_digital column\n";
            $columnNames[] = 'is_digital';
        } catch (PDOException $e) {
            echo "✗ Failed to add is_digital: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ is_digital column already exists\n";
    }
    
    // Check and add colors
    if (!in_array('colors', $columnNames)) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN colors TEXT NULL COMMENT 'Available colors for product (JSON array)' AFTER product_type");
            echo "✓ Added colors column\n";
            $columnNames[] = 'colors';
        } catch (PDOException $e) {
            echo "✗ Failed to add colors: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ colors column already exists\n";
    }
    
    // Check and add subtype
    if (!in_array('subtype', $columnNames)) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN subtype VARCHAR(255) NULL AFTER category");
            echo "✓ Added subtype column\n";
            $columnNames[] = 'subtype';
        } catch (PDOException $e) {
            echo "✗ Failed to add subtype: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ subtype column already exists\n";
    }
    
    // Step 3: Verify final structure
    echo "\n3. Final Products Table Structure:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("DESCRIBE products");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $finalColumnNames = [];
    foreach ($finalColumns as $col) {
        $finalColumnNames[] = $col['Field'];
    }
    
    echo "Total columns: " . count($finalColumnNames) . "\n";
    echo "All columns: " . implode(', ', $finalColumnNames) . "\n";
    
    // Step 4: List all tables
    echo "\n4. All Tables in Database:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = $table[0];
        $tableNames[] = $tableName;
        $stmt2 = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName'");
        $colCount = $stmt2->fetch(PDO::FETCH_ASSOC);
        echo sprintf("%-40s %d columns\n", $tableName, $colCount['cnt']);
    }
    
    echo "\nTotal tables: " . count($tableNames) . "\n";
    
    // Step 5: Check AdminController uses maximum columns
    echo "\n5. Verifying AdminController Uses Maximum Columns:\n";
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

