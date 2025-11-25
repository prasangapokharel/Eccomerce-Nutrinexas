<?php
/**
 * Cron Job: Update Sale Statuses
 * Run this script periodically (e.g., every hour) to update sale statuses
 * 
 * Usage: php cron/update_sale_statuses.php
 * Or add to crontab: 0 * * * * php /path/to/cron/update_sale_statuses.php
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

try {
    $service = new \App\Services\SaleStatusService();
    $result = $service->updateAllSaleStatuses();
    
    if ($result) {
        echo "✅ Sale statuses updated successfully\n";
        exit(0);
    } else {
        echo "❌ Failed to update sale statuses\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

