<?php

namespace App\Controllers\Notification;

use App\Core\Controller;
use App\Core\Database;
use Exception;

class NotificationSellerController extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Create payout notification for seller
     * 
     * @param int $sellerId Seller ID
     * @param int $orderId Order ID
     * @param float $amount Payout amount after deductions
     * @param array $deductions Array with tax, coupon, affiliate, delivery_fee
     * @param string $sellerName Seller name (optional)
     * @return bool
     */
    public function notifyPayoutReceived($sellerId, $orderId, $amount, $deductions = [], $sellerName = null)
    {
        try {
            // Get seller name if not provided
            if (empty($sellerName)) {
                $seller = $this->db->query(
                    "SELECT name, company_name FROM sellers WHERE id = ?",
                    [$sellerId]
                )->single();
                $sellerName = $seller['company_name'] ?? $seller['name'] ?? 'Seller';
            }

            // Get order invoice
            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();
            $orderInvoice = $order['invoice'] ?? '#' . $orderId;

            // Format amount
            $formattedAmount = 'रु ' . number_format($amount, 2);

            // Get tax rate for display
            $taxRate = $deductions['tax_rate'] ?? (new \App\Models\Setting())->get('tax_rate', 12);
            
            // Build message with deductions breakdown
            $message = "{$sellerName} received your order payout {$formattedAmount} after all deduction.\n\n";
            $message .= "Order: {$orderInvoice}\n\n";
            $message .= "Deductions Breakdown:\n";
            
            if (!empty($deductions['tax']) && $deductions['tax'] > 0) {
                $message .= "- Tax ({$taxRate}%): रु " . number_format($deductions['tax'], 2) . "\n";
            }
            
            if (!empty($deductions['coupon']) && $deductions['coupon'] > 0) {
                $message .= "- Coupon: रु " . number_format($deductions['coupon'], 2) . "\n";
            }
            
            if (!empty($deductions['affiliate']) && $deductions['affiliate'] > 0) {
                $message .= "- Affiliate earned: रु " . number_format($deductions['affiliate'], 2) . "\n";
            }
            
            if (!empty($deductions['delivery_fee']) && $deductions['delivery_fee'] > 0) {
                $message .= "- Delivery fee: रु " . number_format($deductions['delivery_fee'], 2) . "\n";
            }

            $message .= "\nNet Payout: {$formattedAmount}";

            // Create notification
            $this->createNotification(
                $sellerId,
                'payout_received',
                'Payout Received',
                $message,
                'seller/wallet',
                'fas fa-money-bill-wave'
            );

            return true;
        } catch (Exception $e) {
            error_log("NotificationSellerController: Error creating payout notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about order status change
     * 
     * @param int $orderId Order ID
     * @param string $oldStatus Previous order status
     * @param string $newStatus New order status
     * @return bool
     */
    public function notifyOrderStatusChange($orderId, $oldStatus, $newStatus)
    {
        try {
            // Get order details and seller IDs
            $order = $this->db->query(
                "SELECT invoice FROM orders WHERE id = ?",
                [$orderId]
            )->single();

            if (!$order) {
                return false;
            }

            $orderInvoice = $order['invoice'] ?? '#' . $orderId;

            // Get all sellers for this order
            $sellers = $this->db->query(
                "SELECT DISTINCT seller_id FROM order_items WHERE order_id = ? AND seller_id IS NOT NULL",
                [$orderId]
            )->all();

            if (empty($sellers)) {
                return false;
            }

            $messages = [
                'pending' => "Order {$orderInvoice} has been placed.",
                'confirmed' => "Order {$orderInvoice} has been confirmed.",
                'processing' => "Order {$orderInvoice} is now being processed.",
                'shipped' => "Order {$orderInvoice} has been shipped.",
                'in_transit' => "Order {$orderInvoice} is in transit.",
                'delivered' => "Order {$orderInvoice} has been delivered successfully.",
                'cancelled' => "Order {$orderInvoice} has been cancelled.",
                'returned' => "Order {$orderInvoice} has been returned."
            ];

            $titles = [
                'pending' => "Order Placed",
                'confirmed' => "Order Confirmed",
                'processing' => "Order Processing",
                'shipped' => "Order Shipped",
                'in_transit' => "Order In Transit",
                'delivered' => "Order Delivered",
                'cancelled' => "Order Cancelled",
                'returned' => "Order Returned"
            ];

            $icons = [
                'pending' => 'fas fa-shopping-cart',
                'confirmed' => 'fas fa-check-circle',
                'processing' => 'fas fa-cog',
                'shipped' => 'fas fa-shipping-fast',
                'in_transit' => 'fas fa-truck',
                'delivered' => 'fas fa-check-double',
                'cancelled' => 'fas fa-times-circle',
                'returned' => 'fas fa-undo'
            ];

            $message = $messages[$newStatus] ?? "Order {$orderInvoice} status has been updated to {$newStatus}.";
            $title = $titles[$newStatus] ?? "Order Status Updated";
            $icon = $icons[$newStatus] ?? 'fas fa-bell';

            // Notify all sellers
            foreach ($sellers as $seller) {
                $this->createNotification(
                    $seller['seller_id'],
                    'order_status_change',
                    $title,
                    $message,
                    'seller/orders/view/' . $orderId,
                    $icon
                );
            }

            return true;
        } catch (Exception $e) {
            error_log("NotificationSellerController: Error creating order status notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify seller about order cancellation
     * 
     * @param int $orderId Order ID
     * @return bool
     */
    public function notifyOrderCancelled($orderId)
    {
        return $this->notifyOrderStatusChange($orderId, '', 'cancelled');
    }

    /**
     * Create notification for seller
     * 
     * @param int $sellerId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param string $link
     * @param string $icon
     * @return bool
     */
    private function createNotification($sellerId, $type, $title, $message, $link = '', $icon = 'fas fa-bell')
    {
        try {
            $this->db->query(
                "INSERT INTO seller_notifications (seller_id, type, title, message, link, icon, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$sellerId, $type, $title, $message, $link, $icon]
            )->execute();

            return true;
        } catch (Exception $e) {
            error_log("NotificationSellerController: Error creating notification: " . $e->getMessage());
            return false;
        }
    }
}

