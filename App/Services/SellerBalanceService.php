<?php

namespace App\Services;

use App\Core\Database;
use App\Models\SellerWallet;
use App\Models\Order;
use App\Models\OrderItem;
use Exception;

/**
 * Seller Balance Service
 * Handles seller balance release after order delivery with wait period
 */
class SellerBalanceService
{
    private $db;
    private $walletModel;
    private $orderModel;
    private $waitPeriodHours = 24; // 24 hours wait period after delivery

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->walletModel = new SellerWallet();
        $this->orderModel = new Order();
    }

    /**
     * Process seller balance release for delivered orders
     * Called when order status changes to 'delivered' or via cron job
     */
    public function processBalanceRelease($orderId)
    {
        try {
            $order = $this->orderModel->getOrderById($orderId);
            
            if (!$order) {
                error_log("SellerBalanceService: Order #{$orderId} not found");
                return ['success' => false, 'message' => 'Order not found'];
            }

            // Check 1: Order must be delivered
            if ($order['status'] !== 'delivered') {
                return ['success' => false, 'message' => 'Order is not delivered'];
            }

            // Check 2: Already released (prevent double release)
            if (!empty($order['balance_released_at'])) {
                error_log("SellerBalanceService: Balance already released for order #{$orderId}");
                return ['success' => false, 'message' => 'Balance already released'];
            }

            // Check 3: Cancelled orders never add balance
            if ($order['status'] === 'cancelled') {
                error_log("SellerBalanceService: Order #{$orderId} is cancelled, skipping balance release");
                return ['success' => false, 'message' => 'Cancelled orders do not add balance'];
            }

            // Check 4: COD orders - only release after cash collected
            $paymentMethod = $this->getPaymentMethod($order['payment_method_id']);
            if ($paymentMethod === 'cod' || $paymentMethod === 'cash_on_delivery') {
                // For COD, check if payment is marked as paid (cash collected)
                if ($order['payment_status'] !== 'paid') {
                    error_log("SellerBalanceService: COD order #{$orderId} - cash not collected yet");
                    return ['success' => false, 'message' => 'COD order: Cash must be collected first'];
                }
            }

            // Check 5: Wait period - ensure 24 hours have passed since delivery
            $deliveredAt = $order['delivered_at'] ?? null;
            if (!$deliveredAt) {
                // If delivered_at is not set, use updated_at when status changed to delivered
                $deliveredAt = $order['updated_at'];
            }

            $deliveredTimestamp = strtotime($deliveredAt);
            $waitPeriodEnd = $deliveredTimestamp + ($this->waitPeriodHours * 3600);
            $currentTime = time();

            if ($currentTime < $waitPeriodEnd) {
                $remainingHours = ceil(($waitPeriodEnd - $currentTime) / 3600);
                error_log("SellerBalanceService: Order #{$orderId} - Wait period not complete. {$remainingHours} hours remaining");
                return [
                    'success' => false, 
                    'message' => "Wait period not complete. {$remainingHours} hours remaining",
                    'wait_period_remaining' => $remainingHours
                ];
            }

            // All checks passed - Release balance
            return $this->releaseBalance($order);
            
        } catch (Exception $e) {
            error_log("SellerBalanceService Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error processing balance release: ' . $e->getMessage()];
        }
    }

    /**
     * Release balance to seller wallet
     */
    private function releaseBalance($order)
    {
        try {
            $this->db->beginTransaction();

            // Get order items with seller information
            $orderItems = $this->db->query(
                "SELECT oi.*, p.seller_id, p.product_name, s.commission_rate 
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 LEFT JOIN sellers s ON p.seller_id = s.id
                 WHERE oi.order_id = ?",
                [$order['id']]
            )->all();
            
            $totalReleased = 0;
            $releasedItems = [];

            // Get referral amount for this order (if buyer has referrer)
            $referralAmount = 0;
            $orderUser = $this->db->query(
                "SELECT u.referred_by FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?",
                [$order['id']]
            )->single();
            
            if (!empty($orderUser['referred_by'])) {
                // Calculate referral commission for this order
                $referralEarning = $this->db->query(
                    "SELECT amount FROM referral_earnings WHERE order_id = ? AND status IN ('paid', 'pending')",
                    [$order['id']]
                )->single();
                
                if ($referralEarning) {
                    $referralAmount = (float)$referralEarning['amount'];
                    error_log("SellerBalanceService: Order #{$order['id']} - Referral amount to deduct: Rs {$referralAmount}");
                }
            }

            foreach ($orderItems as $item) {
                // Skip if product has no seller
                if (empty($item['seller_id'])) {
                    continue;
                }

                $sellerId = $item['seller_id'];
                $productId = $item['product_id'];
                
                // Calculate seller earnings
                $itemTotal = $item['price'] * $item['quantity'];
                $commissionRate = $item['commission_rate'] ?? 10.00; // Default 10%
                $commissionAmount = ($itemTotal * $commissionRate) / 100;
                
                // Tax calculation (if applicable)
                $taxAmount = 0;
                if (!empty($order['tax_amount']) && $order['tax_amount'] > 0) {
                    // Distribute tax proportionally
                    $taxRatio = $itemTotal / $order['total_amount'];
                    $taxAmount = $order['tax_amount'] * $taxRatio;
                }

                // Shipping - seller pays if configured
                $shippingDeduction = 0;
                // Note: Add logic here if seller pays shipping

                // Calculate referral deduction proportionally for this item
                $referralDeduction = 0;
                if ($referralAmount > 0 && $order['total_amount'] > 0) {
                    $itemRatio = $itemTotal / $order['total_amount'];
                    $referralDeduction = $referralAmount * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Item total: Rs {$itemTotal}, Referral deduction: Rs " . round($referralDeduction, 2));
                }

                // Net amount to seller: X - commission - tax - shipping - referral_amount
                $sellerAmount = $itemTotal - $commissionAmount - $taxAmount - $shippingDeduction - $referralDeduction;

                // Ensure non-negative
                $sellerAmount = max(0, $sellerAmount);

                // Check if already released for this order-seller combination
                $existingRelease = $this->db->query(
                    "SELECT id FROM seller_wallet_transactions 
                     WHERE seller_id = ? AND order_id = ? AND type = 'credit' AND description LIKE ?",
                    [$sellerId, $order['id'], "%Product #{$productId}%"]
                )->single();

                if ($existingRelease) {
                    error_log("SellerBalanceService: Balance already released for seller #{$sellerId}, order #{$order['id']}, product #{$productId}");
                    continue; // Skip this item
                }

                // Update seller wallet
                $wallet = $this->walletModel->getWalletBySellerId($sellerId);
                $newBalance = $wallet['balance'] + $sellerAmount;
                $newTotalEarnings = $wallet['total_earnings'] + $sellerAmount;

                $this->db->query(
                    "UPDATE seller_wallet 
                     SET balance = ?, total_earnings = ?, updated_at = NOW() 
                     WHERE seller_id = ?",
                    [$newBalance, $newTotalEarnings, $sellerId]
                )->execute();

                // Create wallet transaction
                $this->db->query(
                    "INSERT INTO seller_wallet_transactions 
                     (seller_id, type, amount, description, order_id, balance_after, status, created_at) 
                     VALUES (?, 'credit', ?, ?, ?, ?, 'completed', NOW())",
                    [
                        $sellerId,
                        $sellerAmount,
                        "Order #{$order['invoice']} - {$item['product_name']} (Product #{$productId}, Qty: {$item['quantity']})",
                        $order['id'],
                        $newBalance
                    ]
                )->execute();

                $totalReleased += $sellerAmount;
                $releasedItems[] = [
                    'seller_id' => $sellerId,
                    'product_id' => $productId,
                    'amount' => $sellerAmount
                ];
            }

            // Mark order as balance released
            $this->db->query(
                "UPDATE orders 
                 SET balance_released_at = NOW(), updated_at = NOW() 
                 WHERE id = ?",
                [$order['id']]
            )->execute();

            $this->db->commit();

            error_log("SellerBalanceService: Successfully released रु {$totalReleased} for order #{$order['id']}");
            
            // Notify sellers about fund received
            try {
                $notificationService = new \App\Services\SellerNotificationService();
                $sellerIds = array_unique(array_column($releasedItems, 'seller_id'));
                foreach ($sellerIds as $sellerId) {
                    // Calculate amount for this seller
                    $sellerAmount = array_sum(array_column(
                        array_filter($releasedItems, function($item) use ($sellerId) {
                            return $item['seller_id'] == $sellerId;
                        }),
                        'amount'
                    ));
                    $notificationService->notifyFundReceived($order['id'], $sellerAmount, [$sellerId]);
                }
            } catch (\Exception $e) {
                error_log("SellerBalanceService: Notification error: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'message' => 'Balance released successfully',
                'total_released' => $totalReleased,
                'items' => $releasedItems
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("SellerBalanceService: Error releasing balance: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle order return - reduce seller balance if already released
     */
    public function handleReturn($orderId, $returnedItems)
    {
        try {
            $order = $this->orderModel->getOrderById($orderId);
            
            if (!$order || empty($order['balance_released_at'])) {
                // Balance not released yet, nothing to reverse
                return ['success' => true, 'message' => 'Balance not released, no reversal needed'];
            }

            $this->db->beginTransaction();

            $totalReversed = 0;

            foreach ($returnedItems as $returnedItem) {
                $productId = $returnedItem['product_id'] ?? null;
                $quantity = $returnedItem['quantity'] ?? 0;

                if (!$productId || $quantity <= 0) {
                    continue;
                }

                // Get the original transaction (match by order_id and description containing product_id)
                $transaction = $this->db->query(
                    "SELECT * FROM seller_wallet_transactions 
                     WHERE order_id = ? AND type = 'credit' AND status = 'completed' AND description LIKE ?
                     ORDER BY id DESC LIMIT 1",
                    [$orderId, "%Product #{$productId}%"]
                )->single();

                if (!$transaction) {
                    continue; // No transaction found for this item
                }

                $sellerId = $transaction['seller_id'];
                $originalAmount = $transaction['amount'];
                
                // Calculate reversal amount (proportional if partial return)
                $orderItem = $this->db->query(
                    "SELECT * FROM order_items WHERE order_id = ? AND product_id = ?",
                    [$orderId, $productId]
                )->single();

                if (!$orderItem) {
                    continue;
                }

                $reversalAmount = ($originalAmount / $orderItem['quantity']) * $quantity;

                // Update wallet
                $wallet = $this->walletModel->getWalletBySellerId($sellerId);
                $newBalance = max(0, $wallet['balance'] - $reversalAmount);

                $this->db->query(
                    "UPDATE seller_wallet 
                     SET balance = ?, updated_at = NOW() 
                     WHERE seller_id = ?",
                    [$newBalance, $sellerId]
                )->execute();

                // Create reversal transaction
                $this->db->query(
                    "INSERT INTO seller_wallet_transactions 
                     (seller_id, type, amount, description, order_id, balance_after, status, created_at) 
                     VALUES (?, 'debit', ?, ?, ?, ?, 'completed', NOW())",
                    [
                        $sellerId,
                        $reversalAmount,
                        "Return - Order #{$order['invoice']} - Product #{$productId}, Qty: {$quantity}",
                        $order['id'],
                        $newBalance
                    ]
                )->execute();

                $totalReversed += $reversalAmount;
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Balance reversed for returned items',
                'total_reversed' => $totalReversed
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("SellerBalanceService: Error handling return: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error handling return: ' . $e->getMessage()];
        }
    }

    /**
     * Get payment method name
     */
    private function getPaymentMethod($paymentMethodId)
    {
        if (!$paymentMethodId) {
            return null;
        }

        $method = $this->db->query(
            "SELECT LOWER(name) as name FROM payment_methods WHERE id = ?",
            [$paymentMethodId]
        )->single();

        return $method['name'] ?? null;
    }

    /**
     * Check and release balances for orders that passed wait period
     * Can be called via cron job
     */
    public function processPendingReleases()
    {
        try {
            $orders = $this->db->query(
                "SELECT * FROM orders 
                 WHERE status = 'delivered' 
                 AND balance_released_at IS NULL 
                 AND delivered_at IS NOT NULL
                 AND DATE_ADD(delivered_at, INTERVAL ? HOUR) <= NOW()",
                [$this->waitPeriodHours]
            )->all();

            $processed = 0;
            $errors = 0;

            foreach ($orders as $order) {
                $result = $this->processBalanceRelease($order['id']);
                if ($result['success']) {
                    $processed++;
                } else {
                    $errors++;
                    error_log("SellerBalanceService: Failed to process order #{$order['id']}: " . ($result['message'] ?? 'Unknown error'));
                }
            }

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'total' => count($orders)
            ];

        } catch (Exception $e) {
            error_log("SellerBalanceService: Error processing pending releases: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

