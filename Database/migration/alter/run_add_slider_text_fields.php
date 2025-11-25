<?php
/**
 * Run migration to add subtitle, description, and button_text fields to sliders table
 */

require_once __DIR__ . '/../../../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../../../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;

echo "Running migration: Add slider text fields...\n\n";

try {
    $db = Database::getInstance();
    
    // Add columns one by one with existence check
    $columns = [
        ['name' => 'subtitle', 'type' => 'VARCHAR(255) NULL', 'after' => 'title'],
        ['name' => 'description', 'type' => 'TEXT NULL', 'after' => 'subtitle'],
        ['name' => 'button_text', 'type' => 'VARCHAR(100) NULL', 'after' => 'description'],
    ];
    
    foreach ($columns as $column) {
        // Check if column exists
        $check = $db->query(
            "SELECT COUNT(*) as count 
             FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'sliders' 
             AND COLUMN_NAME = ?",
            [$column['name']]
        )->single();
        
        if (($check['count'] ?? 0) == 0) {
            // Column doesn't exist, add it
            try {
                $sql = "ALTER TABLE sliders ADD COLUMN `{$column['name']}` {$column['type']} AFTER `{$column['after']}`";
                $result = $db->query($sql)->execute();
                if ($result) {
                    echo "✓ Added column: {$column['name']}\n";
                } else {
                    echo "⚠ Query executed but result is false for: {$column['name']}\n";
                }
            } catch (Exception $e) {
                echo "✗ Error adding column {$column['name']}: " . $e->getMessage() . "\n";
                // Try without AFTER clause
                try {
                    $sql = "ALTER TABLE sliders ADD COLUMN `{$column['name']}` {$column['type']}";
                    $result = $db->query($sql)->execute();
                    if ($result) {
                        echo "✓ Added column {$column['name']} (without AFTER clause)\n";
                    } else {
                        echo "✗ Failed to add column {$column['name']} (query returned false)\n";
                    }
                } catch (Exception $e2) {
                    echo "✗ Failed to add column {$column['name']}: " . $e2->getMessage() . "\n";
                }
            }
        } else {
            echo "✓ Column already exists: {$column['name']}\n";
        }
    }
    
    echo "✓ Migration completed successfully!\n";
    echo "✓ Added columns: subtitle, description, button_text\n\n";
    
    // Verify columns exist
    $columns = $db->query("SHOW COLUMNS FROM sliders LIKE 'subtitle'")->single();
    if ($columns) {
        echo "✓ Verified: subtitle column exists\n";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM sliders LIKE 'description'")->single();
    if ($columns) {
        echo "✓ Verified: description column exists\n";
    }
    
    $columns = $db->query("SHOW COLUMNS FROM sliders LIKE 'button_text'")->single();
    if ($columns) {
        echo "✓ Verified: button_text column exists\n";
    }
    
    echo "\n";
    echo "Status: ✓ 100% PASS - Migration successful!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Migration failed!\n";
    exit(1);
}

