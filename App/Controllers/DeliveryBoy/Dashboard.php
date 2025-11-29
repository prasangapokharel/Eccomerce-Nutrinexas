<?php

namespace App\Controllers\DeliveryBoy;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use Exception;

class Dashboard extends BaseDeliveryBoyController
{
    private $db;
    private $deliveryBoyId;
    private $city;
    private $sellerId;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->deliveryBoyId = Session::get('delivery_boy_id');
        $this->city = Session::get('delivery_boy_city');
        $this->sellerId = Session::get('delivery_boy_seller_id');
    }

    /**
     * Delivery boy dashboard
     */
    public function index()
    {
        // Get orders for this seller in the delivery boy's city
        $orders = $this->db->query(
            "SELECT DISTINCT o.*, 
                    u.first_name, u.last_name, u.email as customer_email,
                    o.customer_name as order_customer_name,
                    pm.name as payment_method_name,
                    COUNT(DISTINCT oi.id) as item_count,
                    SUM(oi.total) as order_total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
             WHERE oi.seller_id = ?
             AND o.shipping_city = ?
             AND o.status IN ('confirmed', 'ready_for_pickup', 'picked_up', 'in_transit')
             AND o.status != 'delivered'
             AND o.status != 'cancelled'
             GROUP BY o.id
             ORDER BY o.created_at DESC
             LIMIT 50",
            [$this->sellerId, $this->city]
        )->all();
        
        // Get statistics
        $stats = $this->db->query(
            "SELECT 
                COUNT(DISTINCT CASE WHEN o.status != 'delivered' AND o.status != 'cancelled' THEN o.id END) as pending_deliveries,
                COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_count,
                COUNT(DISTINCT o.id) as total_orders
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             WHERE oi.seller_id = ? AND o.shipping_city = ?",
            [$this->sellerId, $this->city]
        )->single();
        
        $this->view('curior/deliveryboy/index', [
            'title' => 'Delivery Dashboard',
            'orders' => $orders,
            'stats' => $stats ?? [],
            'city' => $this->city
        ]);
    }

    /**
     * Mark order as delivered
     */
    public function deliver($orderId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('deliveryboy/dashboard');
            return;
        }
        
        try {
            // Verify order belongs to seller and city
            $order = $this->db->query(
                "SELECT o.* FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE o.id = ? AND oi.seller_id = ? AND o.shipping_city = ?",
                [$orderId, $this->sellerId, $this->city]
            )->single();
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('deliveryboy/dashboard');
                return;
            }
            
            // Update order status and set delivered_at timestamp
            $this->db->query(
                "UPDATE orders SET status = 'delivered', delivered_at = NOW(), updated_at = NOW() WHERE id = ?",
                [$orderId]
            )->execute();
            
            // Process seller payout when order is marked as delivered
            try {
                $payoutController = new \App\Controllers\Seller\Payout\PayoutController();
                $payoutController->processSellerPayout($orderId);
                error_log("DeliveryBoy: Seller payout processed for order #{$orderId}");
            } catch (\Exception $e) {
                error_log('DeliveryBoy: Error processing seller payout: ' . $e->getMessage());
            }
            
            // Process referral earnings
            try {
                $referralService = new \App\Services\ReferralEarningService();
                $referralService->processReferralEarning($orderId);
            } catch (\Exception $e) {
                error_log('DeliveryBoy: Error processing referral earning: ' . $e->getMessage());
            }
            
            $this->setFlash('success', 'Order marked as delivered');
        } catch (Exception $e) {
            error_log('Deliver order error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update order');
        }
        
        $this->redirect('deliveryboy/dashboard');
    }

    /**
     * View order details
     */
    public function viewOrder($orderId)
    {
        // Verify order belongs to seller and city
        $order = $this->db->query(
            "SELECT DISTINCT o.*, 
                    u.first_name, u.last_name, u.email as customer_email,
                    o.customer_name as order_customer_name,
                    pm.name as payment_method_name,
                    COUNT(DISTINCT oi.id) as item_count,
                    SUM(oi.total) as order_total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
             WHERE o.id = ? AND oi.seller_id = ? AND o.shipping_city = ?
             GROUP BY o.id",
            [$orderId, $this->sellerId, $this->city]
        )->single();
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('deliveryboy/dashboard');
            return;
        }
        
        // Get order items
        $items = $this->db->query(
            "SELECT oi.*, p.name as product_name, p.image_url as product_image
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ? AND oi.seller_id = ?
             ORDER BY oi.id",
            [$orderId, $this->sellerId]
        )->all();
        
        $order['items'] = $items;
        
        // Get seller info
        $sellerInfo = $this->db->query(
            "SELECT id, name, company_name, address, city, phone, email
             FROM sellers
             WHERE id = ?",
            [$this->sellerId]
        )->single();
        
        $this->view('curior/deliveryboy/order/view', [
            'title' => 'Order Details',
            'order' => $order,
            'sellerInfo' => $sellerInfo ?? [],
            'city' => $this->city
        ]);
    }

    /**
     * Get pickup orders (ready_for_pickup)
     */
    public function pickup()
    {
        $orders = $this->db->query(
            "SELECT DISTINCT o.*, 
                    u.first_name, u.last_name, u.email as customer_email,
                    o.customer_name as order_customer_name,
                    pm.name as payment_method_name,
                    COUNT(DISTINCT oi.id) as item_count,
                    SUM(oi.total) as order_total
             FROM orders o
             INNER JOIN order_items oi ON o.id = oi.order_id
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
             WHERE oi.seller_id = ?
             AND o.shipping_city = ?
             AND o.status = 'ready_for_pickup'
             GROUP BY o.id
             ORDER BY o.created_at DESC
             LIMIT 50",
            [$this->sellerId, $this->city]
        )->all();
        
        $this->view('curior/deliveryboy/pickup', [
            'title' => 'Pickup Orders',
            'orders' => $orders ?? [],
            'city' => $this->city
        ]);
    }
}

