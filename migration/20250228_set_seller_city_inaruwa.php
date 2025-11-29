<?php
/**
 * Migration: Set all sellers' city to Inaruwa for testing
 *
 * Usage: php migration/20250228_set_seller_city_inaruwa.php
 */

require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    echo "=== Seller City Migration ===\n";
    echo "Updating sellers without city to 'Inaruwa'...\n";

    $db = Database::getInstance();
    $db->query("UPDATE sellers SET city = 'Inaruwa' WHERE city IS NULL OR TRIM(city) = ''")->execute();
    $affected = $db->rowCount();

    echo "✓ Updated {$affected} seller records.\n";
    echo "=================================\n";
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

