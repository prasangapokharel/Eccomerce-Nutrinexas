<?php

/**
 * Comprehensive Test for Referral Commission System
 * Tests the complete fix to ensure commissions are triggered on delivery
 */

require_once __DIR__ . '/../App/bootstrap.php';

use App\Models\Order;
use App\Models\User;
use App\Services\ReferralEarningService;
use App\Core\Database;

class ReferralSystemTest
{
    private $db;
    private $orderModel;
    private $userModel;
    private $referralService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->referralService = new ReferralEarningService();
    }

    public function runAllTests()
    {
        echo "=== REFERRAL COMMISSION SYSTEM TEST ===\n\n";
        
        $this->testDatabaseStructure();
        $this->testExistingReferralData();
        $this->testCommissionCalculation();
        $this->testControllerIntegration();
        $this->testMissingCommissions();
        
        echo "\n=== TEST SUMMARY ===\n";
        echo "All tests completed. Review results above.\n";
    }

    private function testDatabaseStructure()
    {
        echo "1. Testing Database Structure...\n";
        
        // Test users table for referred_by column
        $sql = "DESCRIBE users";
        $columns = $this->db->query($sql)->all();
        $hasReferredBy = false;
        $hasReferralEarnings = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'referred_by') {
                $hasReferredBy = true;
            }
            if ($column['Field'] === 'referral_earnings') {
                $hasReferralEarnings = true;
            }
        }
        
        echo $hasReferredBy ? "   ✓ users.referred_by column exists\n" : "   ✗ users.referred_by column missing\n";
        echo $hasReferralEarnings ? "   ✓ users.referral_earnings column exists\n" : "   ✗ users.referral_earnings column missing\n";
        
        // Test referral_earnings table
        $sql = "SHOW TABLES LIKE 'referral_earnings'";
        $table = $this->db->query($sql)->single();
        echo $table ? "   ✓ referral_earnings table exists\n" : "   ✗ referral_earnings table missing\n";
        
        if ($table) {
            $sql = "DESCRIBE referral_earnings";
            $columns = $this->db->query($sql)->all();
            echo "   ✓ referral_earnings columns: " . implode(', ', array_column($columns, 'Field')) . "\n";
        }
        
        echo "\n";
    }

    private function testExistingReferralData()
    {
        echo "2. Testing Existing Referral Data...\n";
        
        // Count users with referrals
        $sql = "SELECT COUNT(*) as count FROM users WHERE referred_by IS NOT NULL";
        $result = $this->db->query($sql)->single();
        echo "   Users with referrals: {$result['count']}\n";
        
        // Count referral earnings
        $sql = "SELECT COUNT(*) as count FROM referral_earnings";
        $result = $this->db->query($sql)->single();
        echo "   Existing referral earnings records: {$result['count']}\n";
        
        // Total earnings
        $sql = "SELECT SUM(amount) as total FROM referral_earnings";
        $result = $this->db->query($sql)->single();
        $total = $result['total'] ?? 0;
        echo "   Total referral earnings: \${$total}\n";
        
        echo "\n";
    }

    private function testCommissionCalculation()
    {
        echo "3. Testing Commission Calculation...\n";
        
        try {
            // Get commission rate
            $sql = "SELECT value FROM settings WHERE name = 'referral_commission_rate'";
            $result = $this->db->query($sql)->single();
            $rate = $result ? $result['value'] : '5.0';
            echo "   Commission rate: {$rate}%\n";
            
            // Test calculation
            $testAmount = 100;
            $expectedCommission = ($testAmount * $rate) / 100;
            echo "   Test: \${$testAmount} order should generate \${$expectedCommission} commission\n";
            
        } catch (\Exception $e) {
            echo "   ✗ Error testing commission calculation: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    private function testControllerIntegration()
    {
        echo "4. Testing Controller Integration...\n";
        
        $controllers = [
            'App\Controllers\CuriorController',
            'App\Controllers\StaffController', 
            'App\Controllers\Api\StaffApiController',
            'App\Controllers\Api\V1\StaffApiController',
            'App\Controllers\Api\CuriorApiController',
            'App\Controllers\Api\V1\CuriorApiController',
            'App\Controllers\Api\OrdersApiController'
        ];
        
        foreach ($controllers as $controller) {
            $file = str_replace(['App\\Controllers\\', '\\'], ['', '/'], $controller) . '.php';
            $fullPath = __DIR__ . '/../App/Controllers/' . $file;
            
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
                $hasReferralService = strpos($content, 'ReferralEarningService::processReferralEarning') !== false;
                echo $hasReferralService ? "   ✓ {$controller} has commission processing\n" : "   ✗ {$controller} missing commission processing\n";
            } else {
                echo "   ? {$controller} file not found\n";
            }
        }
        
        echo "\n";
    }

    private function testMissingCommissions()
    {
        echo "5. Testing for Missing Commissions...\n";
        
        // Find delivered orders from referred users without commission records
        $sql = "
            SELECT COUNT(*) as count
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            LEFT JOIN referral_earnings re ON o.id = re.order_id
            WHERE o.status = 'delivered'
              AND u.referred_by IS NOT NULL
              AND re.id IS NULL
        ";
        
        $result = $this->db->query($sql)->single();
        $missingCount = $result['count'];
        
        if ($missingCount > 0) {
            echo "   ⚠ Found {$missingCount} delivered orders missing commission records\n";
            echo "   Recommendation: Run the fix utility to process missing commissions\n";
            
            // Show sample missing orders
            $sql = "
                SELECT o.id, o.user_id, o.total_amount, o.created_at,
                       u.referred_by, referrer.email as referrer_email
                FROM orders o
                INNER JOIN users u ON o.user_id = u.id
                INNER JOIN users referrer ON u.referred_by = referrer.id
                LEFT JOIN referral_earnings re ON o.id = re.order_id
                WHERE o.status = 'delivered'
                  AND u.referred_by IS NOT NULL
                  AND re.id IS NULL
                LIMIT 5
            ";
            
            $samples = $this->db->query($sql)->all();
            if (!empty($samples)) {
                echo "   Sample missing commissions:\n";
                foreach ($samples as $order) {
                    echo "     Order {$order['id']}: \${$order['total_amount']} (Referrer: {$order['referrer_email']})\n";
                }
            }
        } else {
            echo "   ✓ No missing commission records found\n";
        }
        
        echo "\n";
    }

    public function testCommissionProcessing($orderId)
    {
        echo "6. Testing Commission Processing for Order {$orderId}...\n";
        
        try {
            // Get order details
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                echo "   ✗ Order {$orderId} not found\n";
                return;
            }
            
            // Get user details
            $user = $this->userModel->find($order['user_id']);
            if (!$user || !$user['referred_by']) {
                echo "   ✗ Order {$orderId} user has no referrer\n";
                return;
            }
            
            // Check if commission already exists
            $sql = "SELECT * FROM referral_earnings WHERE order_id = ?";
            $existing = $this->db->query($sql, [$orderId])->single();
            
            if ($existing) {
                echo "   ✓ Commission already exists for order {$orderId}\n";
                echo "     Amount: \${$existing['amount']}\n";
                echo "     Referrer ID: {$existing['referrer_id']}\n";
            } else {
                echo "   Processing commission for order {$orderId}...\n";
                $this->referralService->processReferralEarning($orderId);
                echo "   ✓ Commission processed successfully\n";
            }
            
        } catch (\Exception $e) {
            echo "   ✗ Error processing commission: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the test
$test = new ReferralSystemTest();
$test->runAllTests();

// If there are command line arguments, test specific order
if (isset($argv[1]) && is_numeric($argv[1])) {
    $test->testCommissionProcessing((int)$argv[1]);
}