<?php
require_once __DIR__ . '/../../App/Config/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if discount_percent column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM site_wide_sales LIKE 'discount_percent'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Make it nullable
        $pdo->exec("ALTER TABLE `site_wide_sales` MODIFY COLUMN `discount_percent` DECIMAL(5,2) DEFAULT NULL");
        echo "✓ Made discount_percent nullable\n";
    } else {
        echo "✓ discount_percent column does not exist\n";
    }
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

