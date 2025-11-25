<?php

namespace App\Utilities;

use App\Models\Order;
use App\Models\User;
use App\Services\ReferralEarningService;
use App\Core\Database;

/**
 * Utility to fix missing referral commissions for delivered orders
 * This utility identifies orders that were delivered but didn't trigger commission processing
 */
class ReferralCommissionFixer
{
    private $orderModel;
    private $userModel;
    private $referralService;
    private $db;

    public function __construct()
    {
        $this->orderModel = new Order();
        $this->userModel = new User();
        $this->referralService = new ReferralEarningService();
        $this->db = Database::getInstance();
    }

    /**
     * Find delivered orders that should have triggered commissions but didn't
     */
    public function findMissingCommissions()
    {
        $sql = "
            SELECT DISTINCT o.id, o.user_id, o.total_amount, o.created_at, o.updated_at,
                   u.referred_by, referrer.email as referrer_email
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            INNER JOIN users referrer ON u.referred_by = referrer.id
            LEFT JOIN referral_earnings re ON o.id = re.order_id
            WHERE o.status = 'delivered'
              AND u.referred_by IS NOT NULL
              AND re.id IS NULL
            ORDER BY o.updated_at DESC
        ";
        
        return $this->db->query($sql)->all();
    }

    /**
     * Process missing commissions for specific orders
     */
    public function processMissingCommissions($orderIds = null)
    {
        $processed = 0;
        $failed = 0;
        $errors = [];

        if ($orderIds) {
            // Process specific orders
            foreach ($orderIds as $orderId) {
                try {
                    $this->referralService->processReferralEarning($orderId);
                    $processed++;
                    echo "✓ Processed commission for order ID: {$orderId}\n";
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Order {$orderId}: " . $e->getMessage();
                    echo "✗ Failed to process order ID: {$orderId} - " . $e->getMessage() . "\n";
                }
            }
        } else {
            // Process all missing commissions
            $missingOrders = $this->findMissingCommissions();
            
            echo "Found " . count($missingOrders) . " orders with missing commissions\n\n";
            
            foreach ($missingOrders as $order) {
                try {
                    $this->referralService->processReferralEarning($order['id']);
                    $processed++;
                    echo "✓ Processed commission for order ID: {$order['id']} (User: {$order['user_id']}, Referrer: {$order['referrer_email']})\n";
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Order {$order['id']}: " . $e->getMessage();
                    echo "✗ Failed to process order ID: {$order['id']} - " . $e->getMessage() . "\n";
                }
            }
        }

        echo "\n=== SUMMARY ===\n";
        echo "Processed: {$processed}\n";
        echo "Failed: {$failed}\n";
        
        if (!empty($errors)) {
            echo "\nErrors:\n";
            foreach ($errors as $error) {
                echo "- {$error}\n";
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors
        ];
    }

    /**
     * Generate a report of missing commissions
     */
    public function generateReport()
    {
        $missingOrders = $this->findMissingCommissions();
        
        echo "=== REFERRAL COMMISSION MISSING REPORT ===\n\n";
        echo "Total orders with missing commissions: " . count($missingOrders) . "\n\n";
        
        if (empty($missingOrders)) {
            echo "✓ No missing commissions found!\n";
            return;
        }

        $totalMissedAmount = 0;
        
        foreach ($missingOrders as $order) {
            // Calculate what the commission should have been
            $commissionRate = $this->getCommissionRate();
            $commissionAmount = ($order['total_amount'] * $commissionRate) / 100;
            $totalMissedAmount += $commissionAmount;
            
            echo "Order ID: {$order['id']}\n";
            echo "  Customer: User ID {$order['user_id']}\n";
            echo "  Referrer: {$order['referrer_email']}\n";
            echo "  Order Amount: \${$order['total_amount']}\n";
            echo "  Missing Commission: \${$commissionAmount}\n";
            echo "  Order Date: {$order['created_at']}\n";
            echo "  Delivered Date: {$order['updated_at']}\n";
            echo "  ---\n";
        }
        
        echo "\nTotal missed commission amount: \${$totalMissedAmount}\n";
    }

    /**
     * Get commission rate from settings
     */
    private function getCommissionRate()
    {
        $sql = "SELECT value FROM settings WHERE name = 'referral_commission_rate'";
        $result = $this->db->query($sql)->single();
        return $result ? (float)$result['value'] : 5.0; // Default 5%
    }

    /**
     * Validate referral system integrity
     */
    public function validateSystem()
    {
        echo "=== REFERRAL SYSTEM VALIDATION ===\n\n";
        
        // Check if ReferralEarningService exists and is working
        try {
            $testService = new ReferralEarningService();
            echo "✓ ReferralEarningService is accessible\n";
        } catch (\Exception $e) {
            echo "✗ ReferralEarningService error: " . $e->getMessage() . "\n";
            return false;
        }
        
        // Check database tables
        $tables = ['orders', 'users', 'referral_earnings', 'settings'];
        foreach ($tables as $table) {
            $sql = "SHOW TABLES LIKE '{$table}'";
            $result = $this->db->query($sql)->single();
            if ($result) {
                echo "✓ Table '{$table}' exists\n";
            } else {
                echo "✗ Table '{$table}' missing\n";
                return false;
            }
        }
        
        // Check commission rate setting
        $rate = $this->getCommissionRate();
        echo "✓ Commission rate: {$rate}%\n";
        
        // Check for users with referrals
        $sql = "SELECT COUNT(*) as count FROM users WHERE referred_by IS NOT NULL";
        $result = $this->db->query($sql)->single();
        echo "✓ Users with referrals: {$result['count']}\n";
        
        // Check for delivered orders from referred users
        $sql = "
            SELECT COUNT(*) as count 
            FROM orders o 
            INNER JOIN users u ON o.user_id = u.id 
            WHERE o.status = 'delivered' AND u.referred_by IS NOT NULL
        ";
        $result = $this->db->query($sql)->single();
        echo "✓ Delivered orders from referred users: {$result['count']}\n";
        
        echo "\n✓ System validation complete\n";
        return true;
    }
}