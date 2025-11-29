<?php
/**
 * Verify Product Views and Social Tables
 * Quick verification script to check if tables exist and have correct structure
 */

require_once __DIR__ . '/../../App/Config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== Product Tables Verification ===\n\n";
    
    // Check products_views
    $stmt = $pdo->query("SHOW TABLES LIKE 'products_views'");
    if ($stmt->fetch()) {
        echo "✓ products_views table exists\n";
        $stmt = $pdo->query("DESCRIBE products_views");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns: " . count($cols) . "\n";
        foreach ($cols as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "✗ products_views table NOT FOUND\n";
    }
    
    echo "\n";
    
    // Check products_social
    $stmt = $pdo->query("SHOW TABLES LIKE 'products_social'");
    if ($stmt->fetch()) {
        echo "✓ products_social table exists\n";
        $stmt = $pdo->query("DESCRIBE products_social");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  Columns: " . count($cols) . "\n";
        foreach ($cols as $col) {
            echo "    - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "✗ products_social table NOT FOUND\n";
    }
    
    echo "\n=== Verification Complete ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}




