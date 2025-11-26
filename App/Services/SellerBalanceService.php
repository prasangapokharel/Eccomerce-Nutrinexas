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

            // Check 5: No active return/refund - do not release balance if return is active
            // Check if order_returns table exists
            $tableExists = $this->db->query("SHOW TABLES LIKE 'order_returns'")->single();
            if ($tableExists) {
                $returnStatus = $this->db->query(
                    "SELECT status FROM order_returns WHERE order_id = ? AND status IN ('return_requested', 'return_picked_up', 'return_in_transit', 'processing') LIMIT 1",
                    [$orderId]
                )->single();
                
                if ($returnStatus) {
                    error_log("SellerBalanceService: Order #{$orderId} has active return (status: {$returnStatus['status']}), skipping balance release");
                    return ['success' => false, 'message' => 'Active return/refund - balance cannot be released'];
                }
            }
            
            // Check for refund status
            if (!empty($order['refund_status']) && in_array($order['refund_status'], ['processing', 'requested', 'approved'])) {
                error_log("SellerBalanceService: Order #{$orderId} has active refund (status: {$order['refund_status']}), skipping balance release");
                return ['success' => false, 'message' => 'Active refund - balance cannot be released'];
            }

            // Check 6: Wait period - bypass for courier deliveries (immediate release)
            // Check if order was delivered by courier (has curior_id)
            $hasCourier = !empty($order['curior_id']);
            
            if ($hasCourier) {
                // Courier delivery - bypass wait period for immediate release
                error_log("SellerBalanceService: Order #{$orderId} - Courier delivery detected (courier_id: {$order['curior_id']}), bypassing wait period for immediate balance release");
            } else {
                // No courier - apply wait period
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
                // Commission set to 0% for now (as per requirement)
                $commissionRate = 0.00;
                $commissionAmount = 0;
                
                // Calculate item ratio for proportional deductions
                $itemRatio = 0;
                if (!empty($order['total_amount']) && $order['total_amount'] > 0) {
                    $itemRatio = $itemTotal / $order['total_amount'];
                }
                
                // Tax calculation (if applicable) - seller doesn't pay tax, it's already in price
                $taxAmount = 0;
                
                // Delivery fee - ALWAYS deduct (seller should NOT receive delivery fee)
                $deliveryFeeDeduction = 0;
                if (!empty($order['delivery_fee']) && $order['delivery_fee'] > 0 && $itemRatio > 0) {
                    $deliveryFeeDeduction = $order['delivery_fee'] * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Delivery fee deduction: Rs " . round($deliveryFeeDeduction, 2));
                }
                
                // Cancellation fee (if any) - deduct proportionally
                $cancellationFeeDeduction = 0;
                if (!empty($order['cancellation_fee']) && $order['cancellation_fee'] > 0 && $itemRatio > 0) {
                    $cancellationFeeDeduction = $order['cancellation_fee'] * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Cancellation fee deduction: Rs " . round($cancellationFeeDeduction, 2));
                }
                
                // Return fee (if any) - deduct proportionally
                $returnFeeDeduction = 0;
                if (!empty($order['return_fee']) && $order['return_fee'] > 0 && $itemRatio > 0) {
                    $returnFeeDeduction = $order['return_fee'] * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Return fee deduction: Rs " . round($returnFeeDeduction, 2));
                }
                
                // Compound deduct (if any) - deduct proportionally
                $compoundDeduct = 0;
                if (!empty($order['compound_deduct']) && $order['compound_deduct'] > 0 && $itemRatio > 0) {
                    $compoundDeduct = $order['compound_deduct'] * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Compound deduct: Rs " . round($compoundDeduct, 2));
                }

                // Calculate referral deduction proportionally for this item
                $referralDeduction = 0;
                if ($referralAmount > 0 && $itemRatio > 0) {
                    $referralDeduction = $referralAmount * $itemRatio;
                    error_log("SellerBalanceService: Product #{$productId} - Item total: Rs {$itemTotal}, Referral deduction: Rs " . round($referralDeduction, 2));
                }

                // Net amount to seller: product_total - cancellation_fee - return_fee - compound_deduct - delivery_fee - commission - referral
                $sellerAmount = $itemTotal 
                    - $cancellationFeeDeduction 
                    - $returnFeeDeduction 
                    - $compoundDeduct 
                    - $deliveryFeeDeduction 
                    - $commissionAmount 
                    - $referralDeduction;
                
                error_log("SellerBalanceService: Product #{$productId} - Calculation: Rs {$itemTotal} - cancellation({$cancellationFeeDeduction}) - return({$returnFeeDeduction}) - compound({$compoundDeduct}) - delivery({$deliveryFeeDeduction}) - commission({$commissionAmount}) - referral({$referralDeduction}) = Rs " . round($sellerAmount, 2));

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

                // Create detailed description with breakdown
                $descriptionParts = ["Order #{$order['invoice']} - {$item['product_name']} (Product #{$productId}, Qty: {$item['quantity']})"];
                $descriptionParts[] = "Product Total: Rs " . number_format($itemTotal, 2);
                
                if ($deliveryFeeDeduction > 0) {
                    $descriptionParts[] = "Delivery Fee: -Rs " . number_format($deliveryFeeDeduction, 2);
                }
                if ($cancellationFeeDeduction > 0) {
                    $descriptionParts[] = "Cancellation Fee: -Rs " . number_format($cancellationFeeDeduction, 2);
                }
                if ($returnFeeDeduction > 0) {
                    $descriptionParts[] = "Return Fee: -Rs " . number_format($returnFeeDeduction, 2);
                }
                if ($compoundDeduct > 0) {
                    $descriptionParts[] = "Compound Deduct: -Rs " . number_format($compoundDeduct, 2);
                }
                if ($commissionAmount > 0) {
                    $descriptionParts[] = "Commission ({$commissionRate}%): -Rs " . number_format($commissionAmount, 2);
                }
                if ($referralDeduction > 0) {
                    $descriptionParts[] = "Referral: -Rs " . number_format($referralDeduction, 2);
                }
                $descriptionParts[] = "Net Payout: Rs " . number_format($sellerAmount, 2);
                
                $description = implode(' | ', $descriptionParts);
                
                // Create wallet transaction
                $this->db->query(
                    "INSERT INTO seller_wallet_transactions 
                     (seller_id, type, amount, description, order_id, balance_after, status, created_at) 
                     VALUES (?, 'credit', ?, ?, ?, ?, 'completed', NOW())",
                    [
                        $sellerId,
                        $sellerAmount,
                        $description,
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

