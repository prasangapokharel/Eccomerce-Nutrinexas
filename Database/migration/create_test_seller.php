<?php
/**
 * Create test seller account
 * Run: php Database/migration/create_test_seller.php
 */

require_once __DIR__ . '/../../App/Config/config.php';

$host = DB_HOST;
$dbname = DB_NAME;
$username = DB_USER;
$password = DB_PASS;

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if test seller already exists
    $stmt = $pdo->prepare("SELECT id FROM sellers WHERE email = ?");
    $stmt->execute(['seller@test.com']);
    $existing = $stmt->fetch();

    if ($existing) {
        echo "Test seller already exists with ID: " . $existing['id'] . "\n";
        echo "Email: seller@test.com\n";
        echo "Password: test123\n";
    } else {
        // Create test seller
        $hashedPassword = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO sellers (name, email, password, phone, company_name, status, commission_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Test Seller',
            'seller@test.com',
            $hashedPassword,
            '+977 9800000000',
            'Test Company',
            'active',
            10.00
        ]);

        $sellerId = $pdo->lastInsertId();
        echo "Test seller created successfully!\n";
        echo "ID: {$sellerId}\n";
        echo "Email: seller@test.com\n";
        echo "Password: test123\n";
        echo "\nYou can now login at: " . BASE_URL . "/seller/login\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}


