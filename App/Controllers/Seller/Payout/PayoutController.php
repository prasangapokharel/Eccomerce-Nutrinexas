<?php

namespace App\Controllers\Seller\Payout;

use App\Core\Controller;
use App\Tools\SellerAmountTool;
use App\Models\Order;
use App\Models\SellerWallet;
use App\Models\Setting;
use App\Core\Database;
use App\Controllers\Notification\NotificationSellerController;

class PayoutController extends Controller
{
    private $tool;
    private $orderModel;
    private $walletModel;
    private $settingModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->tool = new SellerAmountTool();
        $this->orderModel = new Order();
        $this->walletModel = new SellerWallet();
        $this->settingModel = new Setting();
        $this->db = Database::getInstance();
    }

    public function processSellerPayout($orderId)
    {
        $order = $this->orderModel->getOrderById($orderId);
        
        if (!$order) {
            error_log("PayoutController: Order #{$orderId} not found");
            return false;
        }
        
        if ($order['status'] !== 'delivered') {
            error_log("PayoutController: Order #{$orderId} status is '{$order['status']}', not 'delivered'");
            return false;
        }

        $sellerIds = $this->getOrderSellerIds($orderId);
        
        if (empty($sellerIds)) {
            error_log("PayoutController: No sellers found for order #{$orderId}");
            return false;
        }

        $processed = 0;
        
        foreach ($sellerIds as $sellerId) {
            if ($this->hasAlreadyProcessed($orderId, $sellerId)) {
                error_log("PayoutController: Payout already processed for seller #{$sellerId}, order #{$orderId}");
                continue;
            }

            $payoutData = $this->calculateSellerAmount($order, $sellerId);
            $sellerAmount = $payoutData['amount'];
            $deductions = $payoutData['deductions'];
            
            if ($sellerAmount > 0) {
                $result = $this->updateSellerBalance($sellerId, $sellerAmount, $orderId, $deductions);
                if ($result) {
                    $processed++;
                } else {
                    error_log("PayoutController: Failed to update balance for seller #{$sellerId}, order #{$orderId}");
                }
            } else {
                error_log("PayoutController: Calculated amount is 0 for seller #{$sellerId}, order #{$orderId}");
            }
        }

        return $processed > 0;
    }

    private function getOrderSellerIds($orderId)
    {
        $sellers = $this->db->query(
            "SELECT DISTINCT seller_id FROM order_items WHERE order_id = ? AND seller_id IS NOT NULL",
            [$orderId]
        )->all();
        
        return array_column($sellers, 'seller_id');
    }

    private function calculateSellerAmount($order, $sellerId)
    {
        $sellerItems = $this->db->query(
            "SELECT SUM(total) as seller_subtotal FROM order_items WHERE order_id = ? AND seller_id = ?",
            [$order['id'], $sellerId]
        )->single();

        $sellerSubtotal = $sellerItems['seller_subtotal'] ?? 0;
        
        if ($sellerSubtotal <= 0) {
            error_log("PayoutController: Seller subtotal is 0 for seller #{$sellerId}, order #{$order['id']}");
            return ['amount' => 0, 'deductions' => []];
        }

        $totalSubtotal = $this->getOrderSubtotal($order);
        
        if ($totalSubtotal <= 0) {
            error_log("PayoutController: Total subtotal is 0 for order #{$order['id']}, checking order_items directly");
            
            $allItems = $this->db->query(
                "SELECT COALESCE(SUM(total), 0) as total FROM order_items WHERE order_id = ?",
                [$order['id']]
            )->single();
            
            $totalSubtotal = $allItems['total'] ?? 0;
            
            if ($totalSubtotal <= 0) {
                error_log("PayoutController: Order items total is also 0, using seller subtotal only for order #{$order['id']}");
                $totalSubtotal = $sellerSubtotal;
            }
        }
        
        $proportion = $totalSubtotal > 0 ? ($sellerSubtotal / $totalSubtotal) : 1;

        $delivery = ($order['delivery_fee'] ?? 0) * $proportion;
        $tax = ($order['tax_amount'] ?? 0) * $proportion;
        
        // Calculate coupon deduction - only deduct if coupon belongs to this seller
        $coupon = 0;
        if (!empty($order['coupon_code']) && !empty($order['discount_amount'])) {
            $couponData = $this->db->query(
                "SELECT seller_id FROM coupons WHERE code = ? LIMIT 1",
                [$order['coupon_code']]
            )->single();
            
            // Only deduct coupon if it belongs to this seller (seller bears the discount cost)
            if (!empty($couponData['seller_id']) && $couponData['seller_id'] == $sellerId) {
                $coupon = ($order['discount_amount'] ?? 0) * $proportion;
                error_log("PayoutController: Coupon belongs to seller #{$sellerId}, deducting Rs {$coupon}");
            } else {
                error_log("PayoutController: Coupon does not belong to seller #{$sellerId}, not deducting");
            }
        }
        
        $affPercent = $order['affiliate_percent'] ?? 0;
        $hasReferral = !empty($order['is_referral']) || !empty($order['referral_code']);
        
        // Calculate affiliate deduction
        $affiliate = 0;
        if ($hasReferral && $affPercent > 0) {
            $affiliate = ($sellerSubtotal * $affPercent) / 100;
        }

        $amount = $this->tool->getSellerAmount(
            $sellerSubtotal,
            $delivery,
            $tax,
            $coupon,
            $affPercent,
            $hasReferral
        );
        
        // Get tax rate for notification display
        $taxRate = $this->settingModel->get('tax_rate', 12);
        
        // Log calculation for debugging
        error_log("PayoutController: Calculation for seller #{$sellerId}, order #{$order['id']}:");
        error_log("  - Seller Subtotal: Rs {$sellerSubtotal}");
        error_log("  - Delivery Fee: Rs {$delivery}");
        error_log("  - Tax: Rs {$tax}");
        error_log("  - Coupon: Rs {$coupon}");
        error_log("  - Affiliate: Rs {$affiliate}");
        error_log("  - Final Amount: Rs {$amount}");
        
        // Return amount and deductions for notification
        return [
            'amount' => $amount,
            'deductions' => [
                'tax' => $tax,
                'tax_rate' => $taxRate,
                'coupon' => $coupon,
                'affiliate' => $affiliate,
                'delivery_fee' => $delivery
            ]
        ];
    }

    private function updateSellerBalance($sellerId, $amount, $orderId, $deductions = [])
    {
        try {
            if ($this->db->inTransaction()) {
                error_log("PayoutController: Transaction already active, skipping beginTransaction for seller #{$sellerId}, order #{$orderId}");
            } else {
                $this->db->beginTransaction();
            }

            $wallet = $this->walletModel->getWalletBySellerId($sellerId);
            if (!$wallet) {
                error_log("PayoutController: Wallet not found for seller #{$sellerId}");
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            $oldBalance = $wallet['balance'] ?? $wallet['available_balance'] ?? 0;
            $oldEarnings = $wallet['total_earnings'] ?? $wallet['earnings'] ?? 0;

            $updateResult = $this->walletModel->updateBalance($sellerId, $amount, 'credit');

            if (!$updateResult) {
                error_log("PayoutController: Failed to update wallet for seller #{$sellerId}");
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            $walletAfter = $this->walletModel->getWalletBySellerId($sellerId);
            $newBalance = $walletAfter['balance'] ?? $walletAfter['available_balance'] ?? ($oldBalance + $amount);

            $transactionResult = $this->walletModel->addTransaction([
                'seller_id' => $sellerId,
                'order_id' => $orderId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'description' => "Order #{$orderId} payout - रु " . number_format($amount, 2),
                'status' => 'completed'
            ]);

            if (!$transactionResult) {
                error_log("PayoutController: Failed to create transaction for seller #{$sellerId}, order #{$orderId}");
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            // Send notification to seller
            $notificationController = new NotificationSellerController();
            $notificationController->notifyPayoutReceived($sellerId, $orderId, $amount, $deductions);

            // Send SMS to seller
            try {
                $smsControllerPath = __DIR__ . '/../../Sms/SmsSellerFund.php';
                if (file_exists($smsControllerPath)) {
                    require_once $smsControllerPath;
                }
                $smsController = new \App\Controllers\Sms\SmsSellerFund();
                $smsController->sendPayoutSms($sellerId, $orderId, $amount, $deductions);
            } catch (\Exception $e) {
                error_log("PayoutController: Error sending payout SMS: " . $e->getMessage());
            }

            $this->db->commit();
            
            error_log("PayoutController: Successfully updated balance for seller #{$sellerId}: रु {$oldBalance} -> रु {$newBalance} (+रु {$amount})");
            
            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("PayoutController: Error updating seller balance for seller #{$sellerId}, order #{$orderId}: " . $e->getMessage());
            error_log("PayoutController: Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    private function hasAlreadyProcessed($orderId, $sellerId)
    {
        $existing = $this->db->query(
            "SELECT id FROM seller_wallet_transactions WHERE seller_id = ? AND order_id = ? AND type = 'credit'",
            [$sellerId, $orderId]
        )->single();

        return !empty($existing);
    }

    private function getOrderSubtotal($order)
    {
        if (!empty($order['subtotal']) && $order['subtotal'] > 0) {
            return $order['subtotal'];
        }

        $result = $this->db->query(
            "SELECT COALESCE(SUM(COALESCE(total, price * quantity, 0)), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ?",
            [$order['id']]
        )->single();

        $subtotal = $result['subtotal'] ?? 0;
        
        if ($subtotal <= 0 && !empty($order['total_amount']) && $order['total_amount'] > 0) {
            error_log("PayoutController: Subtotal is 0, using total_amount as fallback for order #{$order['id']}");
            return $order['total_amount'];
        }

        return $subtotal;
    }
}

