<?php
/**
 * Migration: Add password reset fields to curiors table
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

echo "=== Adding curior password reset fields ===\n";

try {
    $resetTokenExists = $db->query("SHOW COLUMNS FROM curiors LIKE 'reset_token'")->single();
    if (!$resetTokenExists) {
        echo "Adding reset_token column...\n";
        $db->query("ALTER TABLE curiors ADD COLUMN reset_token VARCHAR(128) NULL AFTER password")->execute();
        echo "✅ reset_token column added\n";
    } else {
        echo "✅ reset_token column already exists\n";
    }

    $resetExpiresExists = $db->query("SHOW COLUMNS FROM curiors LIKE 'reset_token_expires_at'")->single();
    if (!$resetExpiresExists) {
        echo "Adding reset_token_expires_at column...\n";
        $db->query("ALTER TABLE curiors ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token")->execute();
        echo "✅ reset_token_expires_at column added\n";
    } else {
        echo "✅ reset_token_expires_at column already exists\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}


