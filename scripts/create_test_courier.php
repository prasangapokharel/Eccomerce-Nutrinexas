<?php
/**
 * Create Test Courier Account
 * Run: php scripts/create_test_courier.php
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();

// Check if test courier already exists
$existing = $db->query('SELECT id, name, email, phone, status FROM curiors WHERE email = ?', ['courier@test.com'])->single();

if ($existing) {
    echo "===========================================\n";
    echo "TEST COURIER ALREADY EXISTS\n";
    echo "===========================================\n\n";
    echo "Email: courier@test.com\n";
    echo "Password: courier123\n";
    echo "Courier ID: {$existing['id']}\n";
    echo "Name: {$existing['name']}\n";
    echo "Phone: {$existing['phone']}\n";
    echo "Status: {$existing['status']}\n\n";
    echo "Login URL: " . BASE_URL . "/curior/login\n";
    echo "\n===========================================\n";
} else {
    // Create test courier
    $hashedPassword = password_hash('courier123', PASSWORD_DEFAULT);
    $stmt = $db->query(
        "INSERT INTO curiors (name, email, phone, password, status, created_at, updated_at) 
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
        [
            'Test Courier',
            'courier@test.com',
            '+977 9800000001',
            $hashedPassword,
            'active'
        ]
    );
    $stmt->execute();
    $courierId = $db->lastInsertId();
    
    echo "===========================================\n";
    echo "TEST COURIER CREATED SUCCESSFULLY!\n";
    echo "===========================================\n\n";
    echo "Email: courier@test.com\n";
    echo "Password: courier123\n";
    echo "Courier ID: {$courierId}\n";
    echo "Name: Test Courier\n";
    echo "Phone: +977 9800000001\n";
    echo "Status: active\n\n";
    echo "Login URL: " . BASE_URL . "/curior/login\n";
    echo "\n===========================================\n";
}

