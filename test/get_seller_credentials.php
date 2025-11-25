<?php
/**
 * Get Seller Credentials
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();
$seller = $db->query('SELECT id, name, email, status, is_approved FROM sellers WHERE email = ?', ['test-seller@nutrinexus.com'])->single();

if ($seller) {
    echo "===========================================\n";
    echo "SELLER CREDENTIALS\n";
    echo "===========================================\n\n";
    echo "Email: {$seller['email']}\n";
    echo "Password: password123\n";
    echo "Seller ID: {$seller['id']}\n";
    echo "Name: {$seller['name']}\n";
    echo "Status: {$seller['status']}\n";
    echo "Approved: " . ($seller['is_approved'] ? 'Yes' : 'No') . "\n\n";
    echo "Login URL: http://192.168.1.125:8000/seller/login\n";
    echo "\n===========================================\n";
} else {
    echo "Seller not found\n";
}

