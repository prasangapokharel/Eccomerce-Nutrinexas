<?php
/**
 * Migration: Add 'in_transit' status to orders.status ENUM
 */

require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

try {
    $db = Database::getInstance();
    
    echo "🔍 Adding 'in_transit' to orders.status ENUM...\n";
    
    $columnInfo = $db->query("SHOW COLUMNS FROM orders WHERE Field = 'status'")->single();
    $currentType = $columnInfo['Type'];
    
    if (stripos($currentType, 'enum') !== false) {
        preg_match("/enum\((.*?)\)/i", $currentType, $matches);
        if (isset($matches[1])) {
            $enumValues = $matches[1];
            
            // Check if in_transit exists
            if (stripos($enumValues, 'in_transit') === false) {
                $newEnum = $enumValues . ",'in_transit'";
                $sql = "ALTER TABLE orders MODIFY COLUMN status ENUM({$newEnum}) DEFAULT 'pending'";
                $db->query($sql)->execute();
                echo "✅ Added 'in_transit' to status ENUM\n";
            } else {
                echo "✅ 'in_transit' already exists in status ENUM\n";
            }
        }
    } else {
        echo "⚠️  Status column is not an ENUM type\n";
    }
    
    echo "\n✅ Migration completed successfully.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

