<?php
/**
 * Migration: Add 'ready_for_pickup' status to orders table
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
    
    echo "🔍 Checking orders table status column...\n";
    
    // Get current status column definition
    $columnInfo = $db->query("SHOW COLUMNS FROM orders WHERE Field = 'status'")->single();
    
    if (!$columnInfo) {
        echo "❌ Status column not found in orders table\n";
        exit(1);
    }
    
    $currentType = $columnInfo['Type'];
    echo "Current status column type: {$currentType}\n";
    
    // Check if it's an ENUM
    if (stripos($currentType, 'enum') !== false) {
        echo "📝 Status column is ENUM, adding 'ready_for_pickup'...\n";
        
        // Extract current enum values
        preg_match("/enum\((.*?)\)/i", $currentType, $matches);
        if (isset($matches[1])) {
            $enumValues = $matches[1];
            
            // Check if ready_for_pickup already exists
            if (stripos($enumValues, 'ready_for_pickup') !== false) {
                echo "✅ 'ready_for_pickup' already exists in ENUM\n";
            } else {
                // Add ready_for_pickup to the enum
                $newEnum = $enumValues . ",'ready_for_pickup'";
                $sql = "ALTER TABLE orders MODIFY COLUMN status ENUM({$newEnum})";
                
                $db->query($sql)->execute();
                echo "✅ Added 'ready_for_pickup' to status ENUM\n";
            }
        }
    } else if (stripos($currentType, 'varchar') !== false) {
        echo "📝 Status column is VARCHAR, no changes needed (ready_for_pickup will work)\n";
    } else {
        echo "⚠️  Status column type is {$currentType}, may need manual update\n";
    }
    
    echo "✅ Migration completed successfully.\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

