<?php
require_once __DIR__ . '/../App/Config/config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
    $file = __DIR__ . '/../' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Models\Setting;

try {
    $db = Database::getInstance();
    $settingModel = new Setting();
    
    echo "🔍 Checking maintenance_mode setting...\n";
    
    $existing = $settingModel->get('maintenance_mode');
    if ($existing === null) {
        $settingModel->set('maintenance_mode', 'false');
        echo "✅ Added maintenance_mode setting (default: false)\n";
    } else {
        echo "✅ maintenance_mode setting already exists\n";
    }
    
    echo "\n✅ Migration completed successfully.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

