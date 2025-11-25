<?php
/**
 * Execute Migration - Direct Database Connection
 * Runs the migration SQL statements directly
 */

// Load database config
$dbConfig = require __DIR__ . '/../App/Config/database.php';

echo "=== Running Database Migrations ===\n\n";

try {
    // Create PDO connection directly
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
    
    echo "✓ Connected to database: {$dbConfig['dbname']}\n\n";
    
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
    
    $successCount = 0;
    $skipCount = 0;
    
    foreach ($migrations as $index => $migration) {
        echo ($index + 1) . ". {$migration['name']}...\n";
        
        // Check if column already exists
        try {
            $checkResult = $pdo->query($migration['check'])->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['cnt'] > 0) {
                echo "   ⚠ Column already exists, skipping...\n";
                $skipCount++;
            } else {
                try {
                    $pdo->exec($migration['sql']);
                    echo "   ✓ Successfully added column\n";
                    $successCount++;
                } catch (PDOException $e) {
                    // Check if error is about duplicate column
                    if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                        strpos($e->getMessage(), 'already exists') !== false ||
                        strpos($e->getMessage(), 'Duplicate') !== false) {
                        echo "   ⚠ Column already exists (detected via error)\n";
                        $skipCount++;
                    } else {
                        throw $e;
                    }
                }
            }
        } catch (PDOException $e) {
            echo "   ✗ ERROR: " . $e->getMessage() . "\n";
            throw $e;
        }
        echo "\n";
    }
    
    // Verify all columns exist
    echo "=== Verification ===\n";
    echo str_repeat("-", 80) . "\n";
    
    $requiredColumns = ['is_digital', 'colors', 'subtype'];
    $allExist = true;
    $existingColumns = [];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        try {
            $check = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = '$col'")->fetch(PDO::FETCH_ASSOC);
            
            if ($check['cnt'] > 0) {
                echo "✓ $col column exists\n";
                $existingColumns[] = $col;
            } else {
                echo "✗ $col column MISSING\n";
                $missingColumns[] = $col;
                $allExist = false;
            }
        } catch (PDOException $e) {
            echo "✗ Error checking $col: " . $e->getMessage() . "\n";
            $missingColumns[] = $col;
            $allExist = false;
        }
    }
    
    echo "\n";
    echo "=== Migration Summary ===\n";
    echo "Successfully added: $successCount columns\n";
    echo "Already existed: $skipCount columns\n";
    echo "Existing columns: " . implode(', ', $existingColumns) . "\n";
    
    if (!empty($missingColumns)) {
        echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
    }
    
    echo "\n";
    
    if ($allExist) {
        echo "=== ✓ Migration Complete - All Columns Added Successfully! ===\n";
        echo "\nAll required columns are now in the products table:\n";
        foreach ($existingColumns as $col) {
            echo "  ✓ $col\n";
        }
        echo "\nYou can now add products with all maximum columns!\n";
        echo "The system is ready to use all database columns.\n";
    } else {
        echo "=== ⚠ Migration Incomplete ===\n";
        echo "Some columns are still missing. Please check the errors above.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "\n✗ DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database credentials in App/Config/database.php\n";
    echo "2. Database server is running\n";
    echo "3. Database name is correct\n";
    exit(1);
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

