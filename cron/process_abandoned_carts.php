<?php
/**
 * Abandoned Cart Recovery Cron Job
 * Run this every hour to recover abandoned carts
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\AbandonedCartService;

$abandonedCartService = new AbandonedCartService();
$results = $abandonedCartService->processAbandonedCarts();

echo "Abandoned Cart Recovery Results:\n";
echo "Processed: {$results['processed']}\n";
echo "Emails Sent: {$results['emails_sent']}\n";
echo "Errors: {$results['errors']}\n";

?>


