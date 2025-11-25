<?php
/**
 * Run Migration: Add Order Balance Tracking
 * 
 * This script runs the migration to add delivered_at and balance_released_at columns
 * 
 * Usage: php cron/run_migration.php
 */

require_once __DIR__ . '/../App/Config/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "=== Running Migration: Add Order Balance Tracking ===\n";
    echo "Database: {$dbname}\n\n";
    
    // Read and execute migration SQL
    $sqlFile = __DIR__ . '/../Database/migration/alter/add_order_balance_tracking.sql';
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        throw new Exception("Migration file is empty or not found");
    }
    
    // Execute the migration
    $pdo->exec($sql);
    
    echo "✓ Migration executed successfully\n";
    echo "✓ Columns delivered_at and balance_released_at added to orders table\n";
    echo "✓ Index created for performance\n\n";
    
    // Verify the columns exist
    $stmt = $pdo->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'orders' 
        AND COLUMN_NAME IN ('delivered_at', 'balance_released_at')
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($columns) == 2) {
        echo "✓ Verified: Both columns exist in orders table\n";
    } else {
        echo "⚠ Warning: Some columns may be missing\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    
} catch (PDOException $e) {
    // Check if error is about column already existing (which is fine)
    if (strpos($e->getMessage(), 'already exists') !== false || 
        strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "✓ Migration completed: Columns may already exist (no action needed)\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

