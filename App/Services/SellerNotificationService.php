<?php

namespace App\Services;

use App\Core\Database;

class SellerNotificationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Notify seller about order status change
     */
    public function notifyOrderStatusChange($orderId, $oldStatus, $newStatus)
    {
        try {
            $order = $this->db->query(
                "SELECT o.*, p.seller_id 
                 FROM orders o
                 LEFT JOIN order_items oi ON o.id = oi.order_id
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE o.id = ? AND p.seller_id IS NOT NULL AND p.seller_id > 0
                 GROUP BY o.id",
                [$orderId]
            )->single();

            if (!$order || empty($order['seller_id'])) {
                return false;
            }

            $sellerId = $order['seller_id'];
            $orderNumber = $order['order_number'] ?? '#' . $order['id'];
            
            $messages = [
                'pending' => "New order {$orderNumber} has been placed",
                'confirmed' => "Order {$orderNumber} has been confirmed",
                'processing' => "Order {$orderNumber} is now being processed",
                'shipped' => "Order {$orderNumber} has been shipped",
                'delivered' => "Order {$orderNumber} has been delivered. Payment will be released after holding period.",
                'cancelled' => "Order {$orderNumber} has been cancelled",
                'paid' => "Order {$orderNumber} payment has been received"
            ];

            $titles = [
                'pending' => "New Order",
                'confirmed' => "Order Confirmed",
                'processing' => "Order Processing",
                'shipped' => "Order Shipped",
                'delivered' => "Order Delivered",
                'cancelled' => "Order Cancelled",
                'paid' => "Payment Received"
            ];

            $icons = [
                'pending' => 'fas fa-shopping-cart',
                'confirmed' => 'fas fa-check-circle',
                'processing' => 'fas fa-cog',
                'shipped' => 'fas fa-truck',
                'delivered' => 'fas fa-check-double',
                'cancelled' => 'fas fa-times-circle',
                'paid' => 'fas fa-money-bill-wave'
            ];

            $message = $messages[$newStatus] ?? "Order {$orderNumber} status changed to {$newStatus}";
            $title = $titles[$newStatus] ?? "Order Status Update";
            $icon = $icons[$newStatus] ?? 'fas fa-bell';

            $this->createNotification(
                $sellerId,
                'order_status_change',
                $title,
                $message,
                'seller/orders/detail/' . $orderId,
                $icon
            );

            return true;
        } catch (\Exception $e) {
            error_log('Seller notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about new order
     */
    public function notifyNewOrder($orderId)
    {
        try {
            // Get all sellers from order items
            $orderItems = $this->db->query(
                "SELECT DISTINCT oi.seller_id, o.invoice, o.id as order_id
                 FROM order_items oi
                 INNER JOIN orders o ON oi.order_id = o.id
                 WHERE oi.order_id = ? AND oi.seller_id IS NOT NULL AND oi.seller_id > 0",
                [$orderId]
            )->all();

            if (empty($orderItems)) {
                return false;
            }

            // Notify each seller
            foreach ($orderItems as $item) {
                $sellerId = $item['seller_id'];
                $invoice = $item['invoice'] ?? '#' . $orderId;
                
                $this->createNotification(
                    $sellerId,
                    'order_received',
                    'New Order Received',
                    "You have received a new order {$invoice}. Please process it as soon as possible.",
                    'seller/orders/detail/' . $orderId,
                    'fas fa-shopping-cart'
                );
            }

            return true;
        } catch (\Exception $e) {
            error_log('Notify new order error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about account approval
     */
    public function notifySellerApproval($sellerId, $sellerName = 'Seller')
    {
        try {
            $this->createNotification(
                $sellerId,
                'account_approved',
                'Account Approved',
                "Congratulations! Your seller account has been approved. You can now start selling on our platform.",
                'seller/dashboard',
                'fas fa-check-circle'
            );
            return true;
        } catch (\Exception $e) {
            error_log('Seller approval notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about order cancellation with fund info
     */
    public function notifyOrderCancelled($orderId, $sellerIds = [])
    {
        try {
            if (empty($sellerIds)) {
                // Get all sellers from order
                $sellers = $this->db->query(
                    "SELECT DISTINCT p.seller_id, o.invoice
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     JOIN orders o ON oi.order_id = o.id
                     WHERE oi.order_id = ? AND p.seller_id IS NOT NULL AND p.seller_id > 0",
                    [$orderId]
                )->all();
                
                foreach ($sellers as $seller) {
                    $sellerIds[] = $seller['seller_id'];
                }
            }
            
            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();
            
            $invoice = $order['invoice'] ?? '#' . $orderId;
            
            foreach ($sellerIds as $sellerId) {
                $this->createNotification(
                    $sellerId,
                    'order_cancelled',
                    'Order Cancelled',
                    "Order {$invoice} has been cancelled. No funds will be added to your balance for this order.",
                    'seller/orders/detail/' . $orderId,
                    'fas fa-times-circle'
                );
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Order cancellation notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about fund received (balance released)
     */
    public function notifyFundReceived($orderId, $amount, $sellerIds = [])
    {
        try {
            if (empty($sellerIds)) {
                // Get all sellers from order
                $sellers = $this->db->query(
                    "SELECT DISTINCT p.seller_id
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = ? AND p.seller_id IS NOT NULL AND p.seller_id > 0",
                    [$orderId]
                )->all();
                
                foreach ($sellers as $seller) {
                    $sellerIds[] = $seller['seller_id'];
                }
            }
            
            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();
            
            $invoice = $order['invoice'] ?? '#' . $orderId;
            $formattedAmount = 'रु ' . number_format($amount, 2);
            
            foreach ($sellerIds as $sellerId) {
                $this->createNotification(
                    $sellerId,
                    'fund_received',
                    'Fund Received',
                    "रु {$formattedAmount} has been added to your wallet for order {$invoice}. The holding period is complete.",
                    'seller/wallet',
                    'fas fa-money-bill-wave'
                );
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Fund received notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about withdrawal approval
     */
    public function notifyWithdrawalApproved($withdrawRequestId, $sellerId, $amount)
    {
        try {
            $formattedAmount = 'रु ' . number_format($amount, 2);
            
            $this->createNotification(
                $sellerId,
                'withdrawal_approved',
                'Withdrawal Approved',
                "Your withdrawal request of {$formattedAmount} has been approved. The amount will be transferred to your bank account.",
                'seller/withdraws',
                'fas fa-check-circle'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log('Withdrawal approval notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about ad approval
     */
    public function notifyAdApproved($sellerId, $adTitle = 'Your Ad')
    {
        try {
            $this->createNotification(
                $sellerId,
                'ad_approved',
                'Ad Approved',
                "Your ad '{$adTitle}' has been approved and is now live.",
                'seller/ads',
                'fas fa-check-circle'
            );
            
            return true;
        } catch (\Exception $e) {
            error_log('Ad approval notification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create notification for seller
     */
    private function createNotification($sellerId, $type, $title, $message, $link = '', $icon = 'fas fa-bell')
    {
        try {
            $this->db->query(
                "INSERT INTO seller_notifications (seller_id, type, title, message, link, icon) 
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$sellerId, $type, $title, $message, $link, $icon]
            )->execute();
        } catch (\Exception $e) {
            error_log('Create seller notification error: ' . $e->getMessage());
        }
    }
}

