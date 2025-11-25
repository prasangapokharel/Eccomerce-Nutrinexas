<?php
/**
 * Comprehensive Referral Commission Test
 * Tests the complete referral commission flow to identify issues
 */

require_once __DIR__ . '/../App/bootstrap.php';

class ReferralCommissionTest
{
    private $db;
    private $userModel;
    private $orderModel;
    private $referralEarningModel;
    private $referralService;
    
    public function __construct()
    {
        $this->db = \App\Core\Database::getInstance();
        $this->userModel = new \App\Models\User();
        $this->orderModel = new \App\Models\Order();
        $this->referralEarningModel = new \App\Models\ReferralEarning();
        $this->referralService = new \App\Services\ReferralEarningService();
    }
    
    public function runAllTests()
    {
        echo "=== REFERRAL COMMISSION SYSTEM TEST ===\n\n";
        
        $this->testDatabaseStructure();
        $this->testExistingReferralData();
        $this->testCommissionCalculation();
        $this->testOrderDeliveryFlow();
        $this->testMissingCommissions();
        
        echo "\n=== TEST COMPLETED ===\n";
    }
    
    private function testDatabaseStructure()
    {
        echo "1. Testing Database Structure...\n";
        
        // Check if referral_earnings table exists
        try {
            $result = $this->db->query("DESCRIBE referral_earnings")->all();
            echo "   ✓ referral_earnings table exists\n";
            echo "   Columns: " . implode(', ', array_column($result, 'Field')) . "\n";
        } catch (Exception $e) {
            echo "   ✗ referral_earnings table missing: " . $e->getMessage() . "\n";
        }
        
        // Check users table for referral fields
        try {
            $result = $this->db->query("DESCRIBE users")->all();
            $fields = array_column($result, 'Field');
            
            if (in_array('referred_by', $fields)) {
                echo "   ✓ users.referred_by field exists\n";
            } else {
                echo "   ✗ users.referred_by field missing\n";
            }
            
            if (in_array('referral_earnings', $fields)) {
                echo "   ✓ users.referral_earnings field exists\n";
            } else {
                echo "   ✗ users.referral_earnings field missing\n";
            }
        } catch (Exception $e) {
            echo "   ✗ Error checking users table: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testExistingReferralData()
    {
        echo "2. Testing Existing Referral Data...\n";
        
        // Count users with referrers
        try {
            $referredUsers = $this->db->query("SELECT COUNT(*) as count FROM users WHERE referred_by IS NOT NULL")->single();
            echo "   Users with referrers: " . $referredUsers['count'] . "\n";
            
            // Count existing referral earnings
            $earnings = $this->db->query("SELECT COUNT(*) as count FROM referral_earnings")->single();
            echo "   Existing referral earnings records: " . $earnings['count'] . "\n";
            
            // Count delivered orders from referred users
            $deliveredOrders = $this->db->query("
                SELECT COUNT(*) as count 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE u.referred_by IS NOT NULL AND o.status = 'delivered'
            ")->single();
            echo "   Delivered orders from referred users: " . $deliveredOrders['count'] . "\n";
            
            // Count orders missing commission
            $missingCommissions = $this->db->query("
                SELECT COUNT(*) as count 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN referral_earnings re ON o.id = re.order_id 
                WHERE u.referred_by IS NOT NULL 
                AND o.status = 'delivered' 
                AND re.id IS NULL
            ")->single();
            echo "   Orders missing commission: " . $missingCommissions['count'] . "\n";
            
        } catch (Exception $e) {
            echo "   ✗ Error checking referral data: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testCommissionCalculation()
    {
        echo "3. Testing Commission Calculation...\n";
        
        try {
            // Get commission rate from settings
            $commissionRate = $this->db->query("SELECT value FROM settings WHERE `key` = 'commission_rate'")->single();
            $rate = $commissionRate ? $commissionRate['value'] : 5;
            echo "   Commission rate: {$rate}%\n";
            
            // Test calculation
            $testAmount = 1000;
            $expectedCommission = ($testAmount * $rate) / 100;
            echo "   Test: Order amount ₹{$testAmount} should give ₹{$expectedCommission} commission\n";
            
        } catch (Exception $e) {
            echo "   ✗ Error testing commission calculation: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testOrderDeliveryFlow()
    {
        echo "4. Testing Order Delivery Flow...\n";
        
        try {
            // Find a delivered order from a referred user
            $testOrder = $this->db->query("
                SELECT o.*, u.referred_by, u.first_name, u.last_name
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE u.referred_by IS NOT NULL AND o.status = 'delivered'
                LIMIT 1
            ")->single();
            
            if ($testOrder) {
                echo "   Found test order: #{$testOrder['id']} (₹{$testOrder['total_amount']})\n";
                echo "   Customer: {$testOrder['first_name']} {$testOrder['last_name']}\n";
                echo "   Referrer ID: {$testOrder['referred_by']}\n";
                
                // Check if commission exists
                $existingCommission = $this->db->query("
                    SELECT * FROM referral_earnings WHERE order_id = ?
                ", [$testOrder['id']])->single();
                
                if ($existingCommission) {
                    echo "   ✓ Commission exists: ₹{$existingCommission['amount']}\n";
                } else {
                    echo "   ✗ Commission missing for this order\n";
                    
                    // Test the referral service
                    echo "   Testing ReferralEarningService...\n";
                    $result = $this->referralService->processReferralEarning($testOrder['id']);
                    
                    if ($result) {
                        echo "   ✓ ReferralEarningService processed successfully\n";
                    } else {
                        echo "   ✗ ReferralEarningService failed\n";
                    }
                }
            } else {
                echo "   No delivered orders from referred users found\n";
            }
            
        } catch (Exception $e) {
            echo "   ✗ Error testing order delivery flow: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function testMissingCommissions()
    {
        echo "5. Finding Missing Commissions...\n";
        
        try {
            $missingCommissions = $this->db->query("
                SELECT 
                    o.id as order_id,
                    o.total_amount,
                    o.status,
                    o.created_at as order_date,
                    u.id as customer_id,
                    CONCAT(u.first_name, ' ', u.last_name) as customer_name,
                    u.referred_by as referrer_id,
                    r.first_name as referrer_name
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                JOIN users r ON u.referred_by = r.id
                LEFT JOIN referral_earnings re ON o.id = re.order_id 
                WHERE u.referred_by IS NOT NULL 
                AND o.status = 'delivered' 
                AND re.id IS NULL
                ORDER BY o.created_at DESC
                LIMIT 10
            ")->all();
            
            if (count($missingCommissions) > 0) {
                echo "   Found " . count($missingCommissions) . " orders missing commissions:\n";
                
                foreach ($missingCommissions as $order) {
                    $commissionAmount = $order['total_amount'] * 0.05; // Assuming 5%
                    echo "   - Order #{$order['order_id']}: ₹{$order['total_amount']} → ₹{$commissionAmount} commission\n";
                    echo "     Customer: {$order['customer_name']} → Referrer: {$order['referrer_name']}\n";
                }
            } else {
                echo "   ✓ No missing commissions found\n";
            }
            
        } catch (Exception $e) {
            echo "   ✗ Error finding missing commissions: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
}

// Run the test
$test = new ReferralCommissionTest();
$test->runAllTests();