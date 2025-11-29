<?php
/**
 * Migration: Set all courier cities to Inaruwa (testing auto-assignment)
 *
 * Usage: php migration/20250228_set_curior_city_inaruwa.php
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
    echo "=== Courier City Migration ===\n";
    echo "Updating all couriers to city 'Inaruwa'...\n";

    $db = Database::getInstance();
    $db->query("UPDATE curiors SET city = 'Inaruwa'")->execute();
    $affected = $db->rowCount();

    echo "✓ Updated {$affected} courier records.\n";
    echo "✓ All couriers now set to operate from Inaruwa for testing.\n";
    echo "=================================\n";
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

