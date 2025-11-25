<?php
/**
 * Check Seller Tables and Create Missing Ones
 */

require_once __DIR__ . '/../App/Config/config.php';
require_once __DIR__ . '/../App/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance();

echo "=== Checking Seller Tables ===\n\n";

$requiredTables = [
    'seller_wallet',
    'seller_wallet_transactions',
    'seller_withdraw_requests',
    'seller_bank_accounts',
    'seller_support_tickets',
    'seller_ticket_replies',
    'seller_notifications',
    'seller_settings',
    'seller_reviews'
];

$existingTables = [];
$tables = $db->query("SHOW TABLES")->all();
foreach ($tables as $table) {
    $existingTables[] = array_values($table)[0];
}

echo "Existing tables: " . count($existingTables) . "\n\n";

$missingTables = [];
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        echo "✓ $table exists\n";
    } else {
        echo "✗ $table MISSING\n";
        $missingTables[] = $table;
    }
}

echo "\n=== Missing Tables: " . count($missingTables) . " ===\n";
foreach ($missingTables as $table) {
    echo "- $table\n";
}

echo "\n=== Checking seller_id in shared tables ===\n";
$sharedTables = ['products', 'orders', 'coupons'];
foreach ($sharedTables as $table) {
    if (in_array($table, $existingTables)) {
        $columns = $db->query("SHOW COLUMNS FROM `$table`")->all();
        $hasSellerId = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'seller_id') {
                $hasSellerId = true;
                break;
            }
        }
        if ($hasSellerId) {
            echo "✓ $table has seller_id column\n";
        } else {
            echo "✗ $table MISSING seller_id column\n";
        }
    }
}

