<?php
/**
 * Test Admin Order Status SMS Notification
 * Tests SMS sending when admin changes order status
 * Tests to 9765470926 only
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\OrderNotificationService;
use App\Models\Order;
use App\Core\Database;

$db = Database::getInstance();
$orderModel = new Order();
$notificationService = new OrderNotificationService();

echo "=== Admin Order Status SMS Test ===\n";
echo "Test Phone Number: 9765470926\n\n";

// Check SMS configuration
echo "1. Checking SMS Configuration\n";
if (!defined('SMS_STATUS')) {
    echo "  ❌ SMS_STATUS not defined\n";
    exit(1);
}

$smsStatus = SMS_STATUS ?? 'disable';
if ($smsStatus !== 'enable') {
    echo "  ⚠️  SMS_STATUS is '{$smsStatus}' - SMS will not be sent\n";
    echo "  Set SMS_STATUS=enable in .env.development or .env.production to enable\n\n";
} else {
    echo "  ✅ SMS_STATUS is 'enable' - SMS will be sent\n\n";
}

// Test 1: Find or create test order with phone 9765470926
echo "2. Finding/Creating Test Order\n";
$testOrder = $db->query("SELECT * FROM orders WHERE contact_no LIKE '%9765470926%' OR contact_no LIKE '%9765470926' ORDER BY id DESC LIMIT 1")->single();

if (!$testOrder) {
    // Try to find any order and update its phone
    $testOrder = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->single();
    if ($testOrder) {
        echo "  Found order #{$testOrder['id']}, updating phone to 9765470926\n";
        $db->query("UPDATE orders SET contact_no = '9765470926' WHERE id = ?", [$testOrder['id']])->execute();
        $testOrder = $orderModel->getOrderById($testOrder['id']);
    }
}

if (!$testOrder) {
    echo "  ❌ No orders found in database. Please create an order first.\n";
    exit(1);
}

echo "  ✅ Using order ID: {$testOrder['id']}\n";
echo "  Current status: {$testOrder['status']}\n";
echo "  Phone number: " . ($testOrder['contact_no'] ?? $testOrder['user_phone'] ?? 'N/A') . "\n\n";

// Test 2: Test SMS sending for different status changes
echo "3. Testing SMS for Status Changes\n\n";

$testStatuses = [
    'processing' => 'Processing',
    'shipped' => 'Shipped',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled'
];

foreach ($testStatuses as $newStatus => $statusLabel) {
    $oldStatus = $testOrder['status'];
    
    if ($oldStatus === $newStatus) {
        echo "  Status: {$statusLabel} (skipping - already {$oldStatus})\n";
        continue;
    }
    
    echo "  Testing: {$oldStatus} -> {$newStatus}\n";
    echo "  Phone: 9765470926\n";
    
    $smsResult = $notificationService->sendStatusChangeSMS($testOrder['id'], $oldStatus, $newStatus);
    
    if ($smsResult['success']) {
        echo "  ✅ SMS sent successfully!\n";
        echo "  Phone: " . ($smsResult['phone'] ?? 'N/A') . "\n";
        echo "  Message: " . ($smsResult['message'] ?? 'N/A') . "\n";
    } else {
        echo "  ❌ SMS failed: " . ($smsResult['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";
    
    // Small delay between tests
    sleep(2);
}

// Test 3: Test direct SMSController call
echo "4. Testing Direct SMSController Call\n";
$testMessage = "Test SMS from Admin Order Status Test - Order #{$testOrder['id']} status update test. This is a test message.";
$smsController = new \App\Controllers\SMSController();
$result = $smsController->sendBirSMS('9765470926', $testMessage);

if ($result['success']) {
    echo "  ✅ Direct SMS sent successfully!\n";
    echo "  Response: " . json_encode($result['response'] ?? 'N/A') . "\n";
    echo "  Cost: Rs " . number_format($result['cost'] ?? 0, 2) . "\n";
} else {
    echo "  ❌ Direct SMS failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== Test Complete ===\n";
echo "✅ All tests completed!\n";
echo "📱 Test SMS sent to: 9765470926\n";
echo "💬 Check the phone for SMS messages\n";

?>

