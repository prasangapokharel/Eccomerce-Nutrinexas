<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ReferralEarning;
use App\Models\User;
use App\Models\Setting;
use App\Helpers\EmailHelper;

class ReferralEarningService
{
    private $db;
    private $referralEarningModel;
    private $userModel;
    private $settingModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->referralEarningModel = new ReferralEarning();
        $this->userModel = new User();
        $this->settingModel = new Setting();
    }

    /**
     * Process referral earning when order is completed (delivered)
     * Only processes if buyer has referred_by set
     * Prevents double processing with logging
     * 
     * @param int $orderId
     * @return bool
     */
    public function processReferralEarning($orderId)
    {
        try {
            error_log("ReferralEarningService: Processing referral earning for order #{$orderId}");
            
            // Get order with user referral info - only if order is delivered
            $order = $this->db->query(
                "SELECT o.*, u.referred_by, u.first_name, u.last_name, u.email as customer_email
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 WHERE o.id = ? AND o.status = 'delivered'",
                [$orderId]
            )->single();

            if (!$order) {
                error_log("ReferralEarningService: Order #{$orderId} not found or not delivered");
                return false;
            }
            
            // Check if buyer is under a referral user
            if (empty($order['referred_by'])) {
                error_log("ReferralEarningService: Order #{$orderId} - Buyer has no referrer (referred_by is null)");
                return false;
            }
            
            error_log("ReferralEarningService: Order #{$orderId} - Buyer referred by user #{$order['referred_by']}");

            // Check if already processed (prevent double processing)
            $existing = $this->referralEarningModel->findByOrderId($orderId);
            if ($existing) {
                if ($existing['status'] === 'paid') {
                    error_log("ReferralEarningService: Order #{$orderId} - Earning already paid (ID: {$existing['id']})");
                    return false; // Already processed
                }
                
                if ($existing['status'] === 'cancelled') {
                    error_log("ReferralEarningService: Order #{$orderId} - Earning is cancelled, cannot process");
                    return false;
                }
                
                // Update pending to paid
                if ($existing['status'] === 'pending') {
                    error_log("ReferralEarningService: Order #{$orderId} - Updating pending earning to paid");
                    
                    $ok = $this->referralEarningModel->update($existing['id'], [
                        'status' => 'paid', 
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if ($ok) {
                        $this->updateUserReferralBalance($existing['user_id'], $existing['amount']);
                        $referrer = $this->userModel->find($existing['user_id']);
                        if ($referrer) {
                            $this->sendReferralNotification($referrer, $existing['amount'], $order);
                        }
                        error_log("ReferralEarningService: Order #{$orderId} - Earning paid successfully. Amount: Rs {$existing['amount']}");
                        return true;
                    } else {
                        error_log("ReferralEarningService: Order #{$orderId} - Failed to update earning status");
                        return false;
                    }
                }
            } else {
                // Create new paid earning directly
                error_log("ReferralEarningService: Order #{$orderId} - Creating new paid earning");
                return $this->createDirectPaidEarning($orderId, $order);
            }

            return false;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error processing referral earning: " . $e->getMessage());
            error_log("ReferralEarningService Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Create direct paid earning (when order is completed and no pending earning exists)
     * 
     * @param int $orderId
     * @param array $order Order data with referred_by
     * @return bool
     */
    private function createDirectPaidEarning($orderId, $order)
    {
        try {
            error_log("ReferralEarningService: Creating direct paid earning for order #{$orderId}");
            
            $referrer = $this->userModel->find($order['referred_by'] ?? null);
            if (!$referrer) {
                error_log("ReferralEarningService: Referrer not found for user #{$order['referred_by']}");
                return false;
            }

            // Calculate commission - returns array with amount and details
            $commissionData = $this->calculateOrderCommission($orderId);
            $amount = $commissionData['amount'];
            
            // If commission is 0, don't create earning
            if ($amount <= 0) {
                error_log("ReferralEarningService: Commission amount is 0 for order #{$orderId}, skipping earning creation");
                return false;
            }

            // Check if earning already exists (double-check)
            $existing = $this->referralEarningModel->findByOrderId($orderId);
            if ($existing) {
                error_log("ReferralEarningService: Earning already exists for order #{$orderId} (ID: {$existing['id']})");
                return false;
            }

            error_log("ReferralEarningService: Creating referral earning - User: #{$referrer['id']}, Order: #{$orderId}, Amount: Rs {$amount}");

            $earningId = $this->referralEarningModel->create([
                'user_id' => $referrer['id'],
                'order_id' => $orderId,
                'amount' => $amount,
                'status' => 'paid',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($earningId) {
                error_log("ReferralEarningService: Earning created (ID: {$earningId})");
                $this->updateUserReferralBalance($referrer['id'], $amount);
                $this->sendReferralNotification($referrer, $amount, $order);
                error_log("ReferralEarningService: Order #{$orderId} - Referral earning processed successfully. Amount: Rs {$amount}");
                return true;
            } else {
                error_log("ReferralEarningService: Failed to create earning record");
            }

            return false;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error creating direct paid earning: " . $e->getMessage());
            error_log("ReferralEarningService Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Calculate commission for an order based on product affiliate_commission
     * Uses product's affiliate_commission if set, otherwise uses default commission_rate from settings
     * Returns 0 if commission rate is 0 or invalid
     * 
     * Formula: referral_amount = (order_price * referral_percent) / 100
     * 
     * @param int $orderId
     * @return array ['amount' => float, 'details' => array] Returns amount and calculation details
     */
    private function calculateOrderCommission($orderId)
    {
        try {
            error_log("ReferralEarningService: Calculating commission for order #{$orderId}");
            
            // Get default commission rate from settings
            $defaultCommissionRate = (float)$this->settingModel->get('commission_rate', 10);
            
            // Validate default rate (0-50%)
            if ($defaultCommissionRate < 0 || $defaultCommissionRate > 50) {
                error_log("ReferralEarningService: Invalid default commission rate: {$defaultCommissionRate}%. Using 0%");
                $defaultCommissionRate = 0;
            }
            
            // Get order items with product affiliate_commission
            $orderItems = $this->db->query(
                "SELECT oi.*, p.affiliate_commission, p.product_name
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ?",
                [$orderId]
            )->all();
            
            if (empty($orderItems)) {
                error_log("ReferralEarningService: No order items found for order #{$orderId}");
                return ['amount' => 0, 'details' => []];
            }
            
            $totalCommission = 0;
            $details = [];
            
            foreach ($orderItems as $item) {
                $itemTotal = (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 0);
                
                if ($itemTotal <= 0) {
                    continue;
                }
                
                // Use product's affiliate_commission if set, otherwise use default
                $commissionRate = null;
                if (isset($item['affiliate_commission']) && $item['affiliate_commission'] !== null) {
                    $commissionRate = (float)$item['affiliate_commission'];
                } else {
                    $commissionRate = $defaultCommissionRate;
                }
                
                // Validate commission rate (0-50%)
                if ($commissionRate < 0 || $commissionRate > 50) {
                    error_log("ReferralEarningService: Invalid commission rate {$commissionRate}% for product #{$item['product_id']}. Using 0%");
                    $commissionRate = 0;
                }
                
                // If commission rate is 0, skip this item
                if ($commissionRate == 0) {
                    error_log("ReferralEarningService: Commission rate is 0% for product #{$item['product_id']}, skipping");
                    $details[] = [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'] ?? 'Unknown',
                        'item_total' => $itemTotal,
                        'commission_rate' => 0,
                        'commission_amount' => 0,
                        'reason' => 'Commission rate is 0%'
                    ];
                    continue;
                }
                
                // Calculate commission for this item: (X * P) / 100
                $itemCommission = ($itemTotal * $commissionRate) / 100;
                $totalCommission += $itemCommission;
                
                $details[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'] ?? 'Unknown',
                    'item_total' => $itemTotal,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => round($itemCommission, 2)
                ];
                
                error_log("ReferralEarningService: Product #{$item['product_id']} - Total: Rs {$itemTotal}, Rate: {$commissionRate}%, Commission: Rs " . round($itemCommission, 2));
            }
            
            $finalAmount = round($totalCommission, 2);
            error_log("ReferralEarningService: Total commission for order #{$orderId}: Rs {$finalAmount}");
            
            return [
                'amount' => $finalAmount,
                'details' => $details
            ];
        } catch (\Exception $e) {
            error_log("ReferralEarningService: Error calculating order commission: " . $e->getMessage());
            return ['amount' => 0, 'details' => []];
        }
    }

    public function cancelReferralEarning($orderId)
    {
        try {
            $existing = $this->referralEarningModel->findByOrderId($orderId);
            if (!$existing) return false;
            if ($existing['status'] === 'cancelled') return false;

            // Store status before update to check if we need to deduct balance
            $wasPaid = ($existing['status'] === 'paid');
            $amount = $existing['amount'] ?? 0;
            $userId = $existing['user_id'] ?? null;

            $ok = $this->referralEarningModel->update($existing['id'], ['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);
            
            // If it was paid, deduct the amount from user's balance
            if ($ok && $wasPaid && $userId && $amount > 0) {
                $this->updateUserReferralBalance($userId, -$amount);
            }

            return $ok;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error: " . $e->getMessage());
            return false;
        }
    }

    public function processWithdrawal($userId, $amount)
    {
        try {
            $this->db->beginTransaction();

            $available = $this->getAvailableBalance($userId);
            if ($available < $amount) throw new \Exception("Insufficient balance. Available: $available, Requested: $amount");

            $now = date('Y-m-d H:i:s');
            $this->db->query("INSERT INTO withdrawals (user_id, amount, status, payment_details, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)", [$userId, $amount, 'pending', json_encode([]), $now, $now])->execute();
            $withdrawalId = $this->db->lastInsertId();
            if (!$withdrawalId) throw new \Exception('Failed to create withdrawal record');

            $earningId = $this->referralEarningModel->create(['user_id' => $userId, 'order_id' => null, 'amount' => -abs($amount), 'status' => 'paid', 'created_at' => $now, 'updated_at' => $now]);
            if (!$earningId) throw new \Exception('Failed to create referral earning for withdrawal');

            $newBalance = $this->updateUserReferralBalance($userId, -abs($amount));
            if ($newBalance === false) throw new \Exception('Failed to update balance');

            $this->db->commit();
            return $withdrawalId;
        } catch (\Exception $e) {
            try { $this->db->rollBack(); } catch (\Exception $inner) {}
            error_log("ReferralEarningService Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create pending referral earning when order is placed
     * Only creates if buyer has referred_by set
     * Returns false if commission is 0
     * 
     * @param int $orderId
     * @return bool
     */
    public function createPendingReferralEarning($orderId)
    {
        try {
            error_log("ReferralEarningService: Creating pending referral earning for order #{$orderId}");
            
            $order = $this->db->query(
                "SELECT o.*, u.referred_by, u.first_name, u.last_name, u.email as customer_email 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.id = ?", 
                [$orderId]
            )->single();
            
            if (!$order) {
                error_log("ReferralEarningService: Order #{$orderId} not found");
                return false;
            }
            
            // Check if buyer is under a referral user
            if (empty($order['referred_by'])) {
                error_log("ReferralEarningService: Order #{$orderId} - Buyer has no referrer, skipping");
                return false;
            }
            
            // Check if earning already exists (prevent duplicates)
            $existing = $this->referralEarningModel->findByOrderId($orderId);
            if ($existing) {
                error_log("ReferralEarningService: Order #{$orderId} - Earning already exists (ID: {$existing['id']}, Status: {$existing['status']})");
                return false;
            }

            $referrer = $this->userModel->find($order['referred_by']);
            if (!$referrer) {
                error_log("ReferralEarningService: Referrer user #{$order['referred_by']} not found");
                return false;
            }

            // Calculate commission - returns array with amount and details
            $commissionData = $this->calculateOrderCommission($orderId);
            $amount = $commissionData['amount'];
            
            // If commission is 0, don't create earning
            if ($amount <= 0) {
                error_log("ReferralEarningService: Commission amount is 0 for order #{$orderId}, skipping pending earning");
                return false;
            }

            error_log("ReferralEarningService: Creating pending earning - User: #{$referrer['id']}, Order: #{$orderId}, Amount: Rs {$amount}");

            $earningId = $this->referralEarningModel->create([
                'user_id' => $referrer['id'], 
                'order_id' => $orderId, 
                'amount' => $amount, 
                'status' => 'pending', 
                'created_at' => date('Y-m-d H:i:s'), 
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($earningId) {
                error_log("ReferralEarningService: Pending earning created (ID: {$earningId}) for order #{$orderId}");
            } else {
                error_log("ReferralEarningService: Failed to create pending earning for order #{$orderId}");
            }
            
            return (bool)$earningId;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error creating pending earning: " . $e->getMessage());
            error_log("ReferralEarningService Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function getAvailableBalance($userId)
    {
        try {
            $res = $this->db->query("SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as balance FROM referral_earnings WHERE user_id = ?", [$userId])->single();
            return isset($res['balance']) ? (float)$res['balance'] : 0.0;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error: " . $e->getMessage());
            return 0.0;
        }
    }

    private function updateUserReferralBalance($userId, $amount)
    {
        try {
            $this->db->query("UPDATE users SET referral_earnings = GREATEST(COALESCE(referral_earnings, 0) + ?, 0), updated_at = ? WHERE id = ?", [$amount, date('Y-m-d H:i:s'), $userId])->execute();
            $row = $this->db->query("SELECT referral_earnings FROM users WHERE id = ?", [$userId])->single();
            return isset($row['referral_earnings']) ? (float)$row['referral_earnings'] : 0.0;
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error updating balance: " . $e->getMessage());
            return false;
        }
    }

    private function sendReferralNotification($referrer, $amount, $order)
    {
        try {
            // Skip email in test environment or if email is not configured
            if (defined('TEST_MODE') && TEST_MODE) {
                error_log("ReferralEarningService: Skipping email notification in test mode");
                return;
            }
            
            if (!empty($referrer['email'])) {
                EmailHelper::sendReferralIncomeNotification($referrer['email'], $referrer['first_name'] ?? 'User', $amount, $order['invoice'] ?? $order['id'], $order['total_amount'] ?? 0);
            }
        } catch (\Exception $e) {
            error_log("ReferralEarningService Error sending email: " . $e->getMessage());
        }
    }
}
