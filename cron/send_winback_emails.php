<?php
/**
 * Win-Back Email Automation Cron Job
 * Send win-back emails to inactive users (30+ days since last order)
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\EmailAutomationService;
use App\Models\User;
use App\Core\Database;

$db = Database::getInstance();
$emailService = new EmailAutomationService();
$userModel = new User();

// Get inactive users (30+ days since last order)
$sql = "SELECT DISTINCT u.id 
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'customer'
        AND (o.id IS NULL OR o.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY))
        AND u.email IS NOT NULL
        AND u.email != ''";
        
$inactiveUsers = $db->query($sql)->all();

$sent = 0;
$failed = 0;

foreach ($inactiveUsers as $userData) {
    try {
        if ($emailService->sendWinBackEmail($userData['id'])) {
            $sent++;
        } else {
            $failed++;
        }
    } catch (Exception $e) {
        error_log("Win-back email error for user {$userData['id']}: " . $e->getMessage());
        $failed++;
    }
}

echo "Win-Back Email Results:\n";
echo "Sent: {$sent}\n";
echo "Failed: {$failed}\n";
echo "Total: " . count($inactiveUsers) . "\n";

?>


