<?php
/**
 * Test script: verifies referral earnings lifecycle
 * - Creates a referrer and a referred customer
 * - Inserts a test order
 * - Runs ReferralEarningService pending + paid logic
 * - Ensures balances update correctly
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

spl_autoload_register(function ($className) {
    $className = str_replace('\\', DS, $className);
    $file = ROOT . DS . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Database;
use App\Services\ReferralEarningService;
use App\Models\ReferralEarning;

echo "=== Referral Earnings Workflow Test ===\n\n";

$db = Database::getInstance();
$service = new ReferralEarningService();
$earningModel = new ReferralEarning();

$db->beginTransaction();

try {
    $suffix = uniqid();

    // Create referrer
    $referrerData = [
        'username' => "referrer_{$suffix}",
        'email' => "referrer_{$suffix}@test.com",
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'first_name' => 'Ref',
        'last_name' => 'Tester',
        'role' => 'customer',
        'status' => 'active',
        'referral_code' => 'REF' . strtoupper(substr($suffix, -5)),
        'referral_earnings' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->query("INSERT INTO users (username, email, password, first_name, last_name, role, status, referral_code, referral_earnings, created_at, updated_at)
                VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :referral_code, :referral_earnings, :created_at, :updated_at)")
        ->bind($referrerData)
        ->execute();
    $referrerId = (int)$db->lastInsertId();

    // Create referred customer
    $customerData = [
        'username' => "customer_{$suffix}",
        'email' => "customer_{$suffix}@test.com",
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'first_name' => 'Cust',
        'last_name' => 'Tester',
        'role' => 'customer',
        'status' => 'active',
        'referred_by' => $referrerId,
        'referral_code' => 'CUS' . strtoupper(substr($suffix, -5)),
        'referral_earnings' => 0,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->query("INSERT INTO users (username, email, password, first_name, last_name, role, status, referred_by, referral_code, referral_earnings, created_at, updated_at)
                VALUES (:username, :email, :password, :first_name, :last_name, :role, :status, :referred_by, :referral_code, :referral_earnings, :created_at, :updated_at)")
        ->bind($customerData)
        ->execute();
    $customerId = (int)$db->lastInsertId();

    // Create order for referred customer
    $orderInvoice = 'TEST' . strtoupper(substr($suffix, -6));
    $totalAmount = 1200.00;

    $orderData = [
        'invoice' => $orderInvoice,
        'user_id' => $customerId,
        'customer_name' => 'Cust Tester',
        'contact_no' => '9810000000',
        'address' => 'Test Address',
        'status' => 'processing',
        'payment_status' => 'pending',
        'total_amount' => $totalAmount,
        'delivery_fee' => 100.00,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $db->query("INSERT INTO orders (invoice, user_id, customer_name, contact_no, address, status, payment_status, total_amount, delivery_fee, created_at, updated_at)
                VALUES (:invoice, :user_id, :customer_name, :contact_no, :address, :status, :payment_status, :total_amount, :delivery_fee, :created_at, :updated_at)")
        ->bind($orderData)
        ->execute();
    $orderId = (int)$db->lastInsertId();

    echo "Created test order #{$orderId} (invoice {$orderInvoice}) for referred customer.\n";

    // Step 1: create pending earning (simulate order moved to processing/paid)
    $pendingResult = $service->createPendingReferralEarning($orderId);
    if (!$pendingResult) {
        throw new \RuntimeException('Failed to create pending referral earning.');
    }

    $pendingEarning = $earningModel->findByOrderId($orderId);
    if (!$pendingEarning || $pendingEarning['status'] !== 'pending') {
        throw new \RuntimeException('Pending earning not recorded.');
    }

    echo "Pending referral earning recorded (amount: Rs{$pendingEarning['amount']}).\n";

    // Step 2: mark order delivered and process earning
    $db->query("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE id = ?", [$orderId])->execute();
    $service->processReferralEarning($orderId);

    $paidEarning = $earningModel->findByOrderId($orderId);
    if (!$paidEarning || $paidEarning['status'] !== 'paid') {
        throw new \RuntimeException('Paid referral earning not recorded.');
    }

    $referrerBalance = $db->query("SELECT referral_earnings FROM users WHERE id = ?", [$referrerId])->single();
    $balance = (float)($referrerBalance['referral_earnings'] ?? 0);

    if (abs($balance - (float)$paidEarning['amount']) > 0.001) {
        throw new \RuntimeException('Referrer balance mismatch.');
    }

    echo "Referral earning paid successfully. Referrer balance: Rs{$balance}.\n";

    // Everything went fine
    $db->rollBack(); // Cleanup all inserted test data
    echo "Transaction rolled back (test data cleaned).\n";
    echo "=== Referral earnings workflow test PASSED ===\n";

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}

