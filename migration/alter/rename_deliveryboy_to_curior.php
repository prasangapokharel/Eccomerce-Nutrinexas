<?php
/**
 * Migration: Rename deliveryboy table to curiors if exists
 * Also ensure orders table has curior_id column
 */

define('ROOT', dirname(dirname(__DIR__)));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

$db = Database::getInstance();

echo "=== Renaming deliveryboy to curior ===\n";

try {
    // Check if deliveryboy table exists and rename it
    $tables = $db->query("SHOW TABLES LIKE 'deliveryboy'")->all();
    if (!empty($tables)) {
        echo "Found deliveryboy table, renaming to curiors...\n";
        $db->query("RENAME TABLE deliveryboy TO curiors")->execute();
        echo "✅ Table renamed successfully\n";
    } else {
        echo "✅ deliveryboy table does not exist (already using curiors)\n";
    }
    
    // Check if orders table has deliveryboy_id column and rename it
    $columns = $db->query("SHOW COLUMNS FROM orders LIKE 'deliveryboy_id'")->all();
    if (!empty($columns)) {
        echo "Found deliveryboy_id column, renaming to curior_id...\n";
        $db->query("ALTER TABLE orders CHANGE deliveryboy_id curior_id INT UNSIGNED NULL")->execute();
        echo "✅ Column renamed successfully\n";
    } else {
        // Check if curior_id exists
        $curiorIdExists = $db->query("SHOW COLUMNS FROM orders LIKE 'curior_id'")->all();
        if (empty($curiorIdExists)) {
            echo "Adding curior_id column to orders table...\n";
            $db->query("ALTER TABLE orders ADD COLUMN curior_id INT UNSIGNED NULL AFTER status")->execute();
            echo "✅ Column added successfully\n";
        } else {
            echo "✅ curior_id column already exists\n";
        }
    }
    
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}









