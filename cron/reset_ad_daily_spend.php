<?php
/**
 * Cron Job: Reset Ad Daily Spend
 * Run this script at midnight to reset daily spend and lock fresh daily budget
 * 
 * Usage: php cron/reset_ad_daily_spend.php
 * Or add to crontab: 0 0 * * * cd /path/to/Nutrinexus && php cron/reset_ad_daily_spend.php >> logs/cron_ad_reset.log 2>&1
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

echo "=== Ad Daily Spend Reset ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $service = new \App\Services\AdDailyResetService();
    
    // Reset daily spend and lock budget
    $resetResult = $service->resetDailySpendAndLockBudget();
    echo "Reset Results:\n";
    echo "  Processed: {$resetResult['processed']} ads\n";
    echo "  Errors: {$resetResult['errors']} ads\n";
    echo "  Total: {$resetResult['total']} ads\n\n";
    
    // Process expired ads
    $expiredResult = $service->processExpiredAds();
    echo "Expired Ads Results:\n";
    echo "  Processed: {$expiredResult['processed']} ads\n";
    echo "  Errors: {$expiredResult['errors']} ads\n";
    echo "  Total: {$expiredResult['total']} ads\n\n";
    
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "=== Done ===\n";
    
    exit(0);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Ad Daily Reset Cron Error: " . $e->getMessage());
    exit(1);
}

