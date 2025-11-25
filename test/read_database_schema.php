<?php
/**
 * Read Database Schema and Verify Products Table
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define minimal constants
if (!defined('URLROOT')) {
    define('URLROOT', 'http://localhost:8000');
}

// Load database config
require_once __DIR__ . '/../App/Config/database.php';

use App\Core\Database;

echo "=== Reading Database Schema ===\n\n";

try {
    $db = Database::getInstance();
    
    // Get all columns from products table
    echo "1. Products Table Columns:\n";
    echo str_repeat("-", 80) . "\n";
    $columns = $db->query("DESCRIBE products")->all();
    
    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        printf("%-30s %-20s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    
    echo "\nTotal columns: " . count($columnNames) . "\n";
    echo "\nColumn list: " . implode(', ', $columnNames) . "\n";
    
    // Check for is_digital column
    echo "\n2. Checking for is_digital column:\n";
    $hasIsDigital = in_array('is_digital', $columnNames);
    echo $hasIsDigital ? "✓ is_digital column exists\n" : "✗ is_digital column MISSING\n";
    
    // Check for colors column
    echo "\n3. Checking for colors column:\n";
    $hasColors = in_array('colors', $columnNames);
    echo $hasColors ? "✓ colors column exists\n" : "✗ colors column MISSING\n";
    
    // Get all tables in database
    echo "\n4. All Tables in Database:\n";
    echo str_repeat("-", 80) . "\n";
    $tables = $db->query("SHOW TABLES")->all();
    $tableNames = [];
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $tableNames[] = $tableName;
        echo "- $tableName\n";
    }
    
    echo "\nTotal tables: " . count($tableNames) . "\n";
    
    // Check AdminController uses all columns
    echo "\n5. Checking AdminController field usage:\n";
    $controllerFile = __DIR__ . '/../App/Controllers/AdminController.php';
    $controllerContent = file_get_contents($controllerFile);
    
    $importantColumns = ['product_name', 'slug', 'description', 'short_description', 'price', 'sale_price', 
                        'stock_quantity', 'category', 'subtype', 'weight', 'serving', 'is_featured',
                        'is_digital', 'product_type_main', 'product_type', 'colors', 'flavor', 'material',
                        'ingredients', 'size_available', 'status', 'commission_rate'];
    
    echo "Checking if important columns are used:\n";
    foreach ($importantColumns as $col) {
        if (in_array($col, $columnNames)) {
            $used = strpos($controllerContent, "'$col'") !== false || strpos($controllerContent, "\"$col\"") !== false;
            echo $used ? "✓ $col (used)\n" : "⚠ $col (exists but not used in addProduct)\n";
        } else {
            echo "✗ $col (missing from database)\n";
        }
    }
    
    echo "\n=== Schema Reading Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

