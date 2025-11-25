<?php
/**
 * Get Courier Login Credentials
 * Run: php scripts/get_courier_credentials.php
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();

// Check for existing couriers
$curiors = $db->query('SELECT id, name, email, phone, status FROM curiors ORDER BY id ASC LIMIT 10')->all();

if (empty($curiors)) {
    echo "===========================================\n";
    echo "NO COURIER ACCOUNTS FOUND\n";
    echo "===========================================\n\n";
    echo "Would you like to create a test courier account? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) === 'y') {
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
        
        echo "\n✅ Test courier created successfully!\n\n";
        echo "===========================================\n";
        echo "COURIER LOGIN CREDENTIALS\n";
        echo "===========================================\n\n";
        echo "Email: courier@test.com\n";
        echo "Password: courier123\n";
        echo "Courier ID: {$courierId}\n";
        echo "Name: Test Courier\n";
        echo "Status: active\n\n";
        echo "Login URL: " . BASE_URL . "/curior/login\n";
        echo "\n===========================================\n";
    } else {
        echo "\nNo courier account created.\n";
    }
} else {
    echo "===========================================\n";
    echo "COURIER ACCOUNTS\n";
    echo "===========================================\n\n";
    
    foreach ($curiors as $index => $curior) {
        echo "Courier #" . ($index + 1) . ":\n";
        echo "  ID: {$curior['id']}\n";
        echo "  Name: {$curior['name']}\n";
        echo "  Email: " . ($curior['email'] ?: 'N/A') . "\n";
        echo "  Phone: {$curior['phone']}\n";
        echo "  Status: {$curior['status']}\n";
        echo "\n";
    }
    
    echo "===========================================\n";
    echo "NOTE: To get password, check database or reset it.\n";
    echo "Login URL: " . BASE_URL . "/curior/login\n";
    echo "===========================================\n";
}

