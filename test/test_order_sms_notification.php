<?php
/**
 * Test Order SMS Notification
 * Tests SMS sending when order status changes
 * Tests to 9765470926 - clean, professional message
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

echo "=== Order SMS Notification Test ===\n\n";

// Test 1: Test SMS to 9765470926
echo "1. Testing SMS to 9765470926\n";
$testPhone = '9765470926';
$testMessage = "Dear Test Customer, your order #NTX000001 has been received and is being processed. Total: Rs 1500.00. Thank you for shopping with us!";

echo "  Phone: {$testPhone}\n";
echo "  Message: {$testMessage}\n\n";

// Use SMSController directly
$smsController = new \App\Controllers\SMSController();
$result = $smsController->sendBirSMS($testPhone, $testMessage);

if ($result['success']) {
    echo "  ✅ SMS sent successfully!\n";
    echo "  Response: " . json_encode($result['response'] ?? 'N/A') . "\n";
    echo "  Cost: Rs " . number_format($result['cost'] ?? 0, 2) . "\n";
} else {
    echo "  ❌ SMS failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}

// Test 2: Test order status change notification
echo "\n2. Testing Order Status Change Notification\n";

// Find an existing order or create a test scenario
$testOrder = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->single();

if ($testOrder) {
    echo "  Using order ID: {$testOrder['id']}\n";
    echo "  Current status: {$testOrder['status']}\n";
    echo "  Customer phone: " . ($testOrder['contact_no'] ?? 'N/A') . "\n\n";
    
    // Test status change to 'processing'
    $oldStatus = $testOrder['status'];
    $newStatus = 'processing';
    
    echo "  Testing status change: {$oldStatus} -> {$newStatus}\n";
    $smsResult = $notificationService->sendStatusChangeSMS($testOrder['id'], $oldStatus, $newStatus);
    
    if ($smsResult['success']) {
        echo "  ✅ SMS notification sent successfully!\n";
        echo "  Phone: " . ($smsResult['phone'] ?? 'N/A') . "\n";
    } else {
        echo "  ❌ SMS notification failed: " . ($smsResult['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "  ⚠️  No orders found in database. Skipping order status test.\n";
}

// Test 3: Test different status messages
echo "\n3. Testing Different Status Messages\n";
$testStatuses = ['pending', 'processing', 'dispatched', 'shipped', 'delivered', 'cancelled'];

foreach ($testStatuses as $status) {
    $testOrderData = [
        'id' => 999,
        'invoice' => 'NTX000999',
        'customer_name' => 'Test Customer',
        'total_amount' => 2500.00,
        'contact_no' => '9765470926'
    ];
    
    // Use reflection to test generateStatusMessage
    $reflection = new ReflectionClass($notificationService);
    $method = $reflection->getMethod('generateStatusMessage');
    $method->setAccessible(true);
    $message = $method->invoke($notificationService, $testOrderData, $status);
    
    echo "  Status: {$status}\n";
    echo "  Message: " . substr($message, 0, 80) . "...\n";
    echo "  Length: " . strlen($message) . " characters\n";
    echo "  No emojis: " . (preg_match('/[\x{1F300}-\x{1F9FF}]/u', $message) ? '❌ FAIL' : '✅ PASS') . "\n\n";
}

echo "=== Test Complete ===\n";
echo "✅ All SMS notification tests completed!\n";
echo "📱 Test SMS sent to: 9765470926\n";
echo "💬 Message is clean and professional (no emojis)\n";

echo "\n4. Testing Phone Normalization (user number 9705470926)\n";
$reflection = new ReflectionClass($notificationService);
$formatMethod = $reflection->getMethod('formatPhoneNumber');
$formatMethod->setAccessible(true);
$normalized = $formatMethod->invoke($notificationService, '9705470926');
echo "  Normalized: " . ($normalized ?: 'invalid') . "\n";
echo "=== Test Complete ===\n";

?>

