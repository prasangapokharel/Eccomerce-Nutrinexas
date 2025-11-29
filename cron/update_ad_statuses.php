<?php
/**
 * Cron Job: Update Ad Statuses
 * Run this script periodically (e.g., every hour) to update ad statuses based on dates
 * 
 * Usage: php cron/update_ad_statuses.php
 * Or add to crontab: 0 * * * * php /path/to/cron/update_ad_statuses.php
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
    $service = new \App\Services\AdStatusService();
    $result = $service->updateAllAdStatuses();
    
    if ($result) {
        echo "✅ Ad statuses updated successfully\n";
        
        // Show summary
        $scheduled = $service->getScheduledAds();
        $expired = $service->getExpiredAds();
        
        echo "Scheduled ads: " . count($scheduled) . "\n";
        echo "Expired ads: " . count($expired) . "\n";
        
        exit(0);
    } else {
        echo "❌ Failed to update ad statuses\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}










