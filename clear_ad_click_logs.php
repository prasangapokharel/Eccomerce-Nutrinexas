<?php
require_once __DIR__ . '/App/Config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== Clearing Ad Click Logs for Development ===\n\n";
    
    // Check if ads_click_logs table exists
    $tableExists = $pdo->query(
        "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ads_click_logs'"
    )->fetch(PDO::FETCH_ASSOC);
    
    if ($tableExists['count'] > 0) {
        // Count records before deletion
        $countBefore = $pdo->query("SELECT COUNT(*) as count FROM ads_click_logs")->fetch(PDO::FETCH_ASSOC);
        echo "Records before: {$countBefore['count']}\n";
        
        // Delete all click logs
        $pdo->exec("DELETE FROM ads_click_logs");
        
        // Count records after deletion
        $countAfter = $pdo->query("SELECT COUNT(*) as count FROM ads_click_logs")->fetch(PDO::FETCH_ASSOC);
        echo "Records after: {$countAfter['count']}\n";
        
        echo "\n✅ All ad click logs cleared successfully!\n";
        echo "You can now test clicks without IP restrictions.\n";
    } else {
        echo "⚠ Table 'ads_click_logs' does not exist (no logs to clear)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}




