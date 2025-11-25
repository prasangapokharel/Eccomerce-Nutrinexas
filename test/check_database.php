<?php
/**
 * Database Connection and Table Check Script
 * Run this to verify database connection and check if optimization indexes are needed
 */

require_once __DIR__ . '/../App/Config/config.php';

try {
    echo "=== Database Connection Test ===\n\n";
    
    $db = \App\Core\Database::getInstance();
    echo "✅ Database connection successful\n\n";
    
    // Get all tables
    echo "=== Checking Tables ===\n";
    $tables = $db->query("SHOW TABLES")->all();
    $tableNames = [];
    foreach ($tables as $table) {
        $tableNames[] = array_values($table)[0];
    }
    
    echo "Found " . count($tableNames) . " tables:\n";
    foreach ($tableNames as $table) {
        echo "  - $table\n";
    }
    
    // Check for essential tables
    echo "\n=== Essential Tables Check ===\n";
    $essentialTables = ['products', 'orders', 'users', 'cart', 'wishlist', 'order_items', 'reviews', 'blog_posts'];
    $missingTables = [];
    
    foreach ($essentialTables as $essential) {
        if (in_array($essential, $tableNames)) {
            echo "✅ $essential - exists\n";
        } else {
            echo "❌ $essential - missing\n";
            $missingTables[] = $essential;
        }
    }
    
    // Check for indexes
    echo "\n=== Index Check ===\n";
    $indexesToCheck = [
        'orders' => ['user_id', 'status', 'created_at'],
        'products' => ['category', 'status', 'is_featured'],
        'cart' => ['user_id', 'product_id'],
        'wishlist' => ['user_id', 'product_id'],
        'users' => ['email', 'phone', 'referral_code']
    ];
    
    $missingIndexes = [];
    foreach ($indexesToCheck as $table => $columns) {
        if (!in_array($table, $tableNames)) {
            continue;
        }
        
        $indexes = $db->query("SHOW INDEXES FROM `$table`")->all();
        $indexNames = array_column($indexes, 'Key_name');
        
        foreach ($columns as $column) {
            $indexName = "idx_{$table}_{$column}";
            $hasIndex = false;
            
            // Check if index exists (could be single column or composite)
            foreach ($indexes as $index) {
                if ($index['Column_name'] === $column) {
                    $hasIndex = true;
                    break;
                }
            }
            
            if ($hasIndex) {
                echo "✅ $table.$column - indexed\n";
            } else {
                echo "⚠️  $table.$column - no index\n";
                $missingIndexes[] = [
                    'table' => $table,
                    'column' => $column,
                    'index_name' => $indexName
                ];
            }
        }
    }
    
    // Summary
    echo "\n=== Summary ===\n";
    if (empty($missingTables) && empty($missingIndexes)) {
        echo "✅ All essential tables exist\n";
        echo "✅ All recommended indexes exist\n";
        echo "\nDatabase is optimized!\n";
    } else {
        if (!empty($missingTables)) {
            echo "⚠️  Missing tables: " . implode(', ', $missingTables) . "\n";
        }
        if (!empty($missingIndexes)) {
            echo "⚠️  Missing indexes: " . count($missingIndexes) . " indexes recommended\n";
            echo "\nRun Database/optimization_indexes.sql to add missing indexes\n";
        }
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

