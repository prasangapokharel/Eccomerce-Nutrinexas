<?php

namespace App\Controllers\Seller;

use App\Controllers\Order\OrderAssign;
use App\Core\Database;
use App\Models\Order;
use App\Models\OrderItem;
use App\Core\Cache;
use Exception;

class Orders extends BaseSellerController
{
    private $orderModel;
    private $orderItemModel;
    private $cache;
    protected $db;
    private OrderAssign $orderAssign;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->cache = new Cache();
        $this->db = Database::getInstance();
        $this->orderAssign = new OrderAssign();
    }

    /**
     * List all orders
     */
    public function index()
    {
        $status = $_GET['status'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $orders = $this->getOrders($status, $limit, $offset);
        $total = $this->getOrderCount($status);
        $totalPages = ceil($total / $limit);

        $this->view('seller/orders/index', [
            'title' => 'Orders',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'statusFilter' => $status
        ]);
    }

    /**
     * View order details
     */
    public function detail($id)
    {
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        // Get order items and filter by seller_id
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        // Verify that this order has items from this seller
        if (empty($orderItems)) {
            // Log unauthorized access attempt
            $securityLog = new \App\Services\SecurityLogService();
            $securityLog->logUnauthorizedAccess(
                'unauthorized_order_access',
                $this->sellerId,
                $id,
                'order',
                [
                    'order_exists' => true,
                    'order_seller_ids' => $this->getOrderSellerIds($id)
                ]
            );
            
            $this->setFlash('error', 'Order not found or no items belong to you');
            $this->redirect('seller/orders');
            return;
        }

        // Calculate seller's portion of the order
        $sellerSubtotal = 0;
        foreach ($orderItems as $item) {
            $sellerSubtotal += ($item['total'] ?? 0);
        }

        // Get total order subtotal to calculate proportions
        $db = \App\Core\Database::getInstance();
        $totalOrderSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ?",
            [$id]
        )->single()['subtotal'] ?? 0;

        // Calculate proportional amounts
        $proportion = $totalOrderSubtotal > 0 ? ($sellerSubtotal / $totalOrderSubtotal) : 0;
        
        $sellerDiscount = ($order['discount_amount'] ?? 0) * $proportion;
        $sellerTax = ($order['tax_amount'] ?? 0) * $proportion;
        $sellerDeliveryFee = ($order['delivery_fee'] ?? 0) * $proportion;
        $sellerTotal = $sellerSubtotal - $sellerDiscount + $sellerTax + $sellerDeliveryFee;

        // Add seller-specific calculations to order array
        $order['seller_subtotal'] = $sellerSubtotal;
        $order['seller_discount'] = $sellerDiscount;
        $order['seller_tax'] = $sellerTax;
        $order['seller_delivery_fee'] = $sellerDeliveryFee;
        $order['seller_total'] = $sellerTotal;

        $this->view('seller/orders/detail', [
            'title' => 'Order Details',
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }

    /**
     * Get all seller IDs associated with an order
     */
    private function getOrderSellerIds($orderId)
    {
        $db = \App\Core\Database::getInstance();
        $sellers = $db->query(
            "SELECT DISTINCT seller_id FROM order_items WHERE order_id = ? AND seller_id IS NOT NULL",
            [$orderId]
        )->all();
        return array_column($sellers, 'seller_id');
    }

    /**
     * Accept order
     */
    public function accept($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            
            if (!$order || empty($orderItems)) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }

            $result = $this->orderModel->updateStatus($id, 'confirmed');
            
            if ($result) {
                $this->setFlash('success', 'Order accepted successfully');
            } else {
                $this->setFlash('error', 'Failed to accept order');
            }
        } catch (Exception $e) {
            error_log('Accept order error: ' . $e->getMessage());
            $this->setFlash('error', 'Error accepting order');
        }

        $this->redirect('seller/orders/detail/' . $id);
    }

    /**
     * Reject order
     */
    public function reject($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            
            if (!$order || empty($orderItems)) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }

            $reason = trim($_POST['rejection_reason'] ?? 'Order rejected by seller');
            $result = $this->orderModel->updateStatus($id, 'cancelled');
            
            if ($result) {
                // Log rejection reason
                error_log("Order #{$id} rejected by seller #{$this->sellerId}: {$reason}");
                $this->setFlash('success', 'Order rejected successfully');
            } else {
                $this->setFlash('error', 'Failed to reject order');
            }
        } catch (Exception $e) {
            error_log('Reject order error: ' . $e->getMessage());
            $this->setFlash('error', 'Error rejecting order');
        }

        $this->redirect('seller/orders');
    }

    /**
     * Print invoice
     */
    public function printInvoice($id)
    {
        $order = $this->orderModel->find($id);
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        if (!$order || empty($orderItems)) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        // Redirect to receipt controller with seller context
        $this->redirect('orders/receipt/' . $id);
    }

    /**
     * Print shipping label
     */
    public function printShippingLabel($id)
    {
        $order = $this->orderModel->getOrderById($id);
        $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
        
        if (!$order || empty($orderItems)) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('seller/orders');
            return;
        }

        $shippingLabelController = new \App\Controllers\Billing\ShippingLabelController();
        $shippingLabelController->print($id);
    }

    /**
     * Bulk print shipping labels
     */
    public function bulkPrintLabels()
    {
        $orderIds = explode(',', $_GET['ids'] ?? '');
        $orderIds = array_filter(array_map('intval', $orderIds));
        
        if (empty($orderIds)) {
            $this->setFlash('error', 'No orders selected');
            $this->redirect('seller/orders');
            return;
        }

        $validOrderIds = [];
        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->find($orderId);
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($orderId, $this->sellerId);
            
            if ($order && !empty($orderItems)) {
                $validOrderIds[] = $orderId;
            }
        }

        if (empty($validOrderIds)) {
            $this->setFlash('error', 'No valid orders found');
            $this->redirect('seller/orders');
            return;
        }

        $shippingLabelController = new \App\Controllers\Billing\ShippingLabelController();
        $shippingLabelController->print($validOrderIds[0]);
    }


    /**
     * Update order status
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/orders');
            return;
        }

        try {
            $order = $this->orderModel->find($id);
            
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('seller/orders');
                return;
            }
            
            // Verify order has items from this seller
            $orderItems = $this->orderItemModel->getByOrderIdAndSellerId($id, $this->sellerId);
            if (empty($orderItems)) {
                $this->setFlash('error', 'Order not found or no items belong to you');
                $this->redirect('seller/orders');
                return;
            }

            $status = $_POST['status'] ?? '';
            // Seller can only set: New Order (pending), Processing/Packing (processing), Ready for Pickup (ready_for_pickup)
            $allowedStatuses = ['pending', 'processing', 'ready_for_pickup'];
            
            if (!in_array($status, $allowedStatuses)) {
                $this->setFlash('error', 'Invalid status. Sellers can only update to: New Order, Processing/Packing, or Ready for Pickup');
                $this->redirect('seller/orders/detail/' . $id);
                return;
            }

            $oldStatus = $order['status'];
            $result = $this->orderModel->updateStatus($id, $status);
            
            if ($result) {
                // Send SMS when order status changes
                if ($oldStatus !== $status) {
                    try {
                        $smsControllerClass = '\App\Controllers\Sms\SmsOrderController';
                        if (!class_exists($smsControllerClass)) {
                            $smsControllerPath = dirname(__DIR__) . '/Sms/SmsOrderController.php';
                            if (file_exists($smsControllerPath)) {
                                require_once $smsControllerPath;
                            } else {
                                throw new \RuntimeException('SmsOrderController file not found at ' . $smsControllerPath);
                            }
                        }
                        if (class_exists($smsControllerClass)) {
                            $smsOrderController = new $smsControllerClass();
                            $smsOrderController->sendOrderStatusSms($id, $oldStatus, $status);
                        } else {
                            throw new \RuntimeException('SmsOrderController class not available after requiring file.');
                        }
                    } catch (\Exception $e) {
                        error_log("Seller Orders: Error sending order status SMS: " . $e->getMessage());
                    }
                }
                
                // Clear order caches
                $this->cache->deletePattern('seller_orders_' . $this->sellerId . '_*');
                $this->cache->delete('seller_order_total_' . $id . '_' . $this->sellerId);
                
                // Auto-assign courier when seller marks order as "ready_for_pickup"
                if ($status === 'ready_for_pickup' && $oldStatus !== 'ready_for_pickup') {
                    try {
                        // Ensure order status is set to ready_for_pickup first
                        $this->orderModel->update($id, ['status' => 'ready_for_pickup']);
                        
                        $alreadyAssigned = !empty($order['curior_id']);
                        $assignedCourier = $this->orderAssign->assignForSeller($id, $this->sellerId);
                        
                        if ($assignedCourier && !empty($assignedCourier['id'])) {
                            // Verify assignment was successful
                            $updatedOrder = $this->orderModel->find($id);
                            if ($updatedOrder['curior_id'] == $assignedCourier['id'] && $updatedOrder['status'] === 'ready_for_pickup') {
                                if ($alreadyAssigned) {
                                    error_log("Seller Order Update: Order #{$id} already assigned to courier #{$assignedCourier['id']} ({$assignedCourier['name']}).");
                                } else {
                                    error_log("Seller Order Update: ✅ Auto-assigned courier #{$assignedCourier['id']} ({$assignedCourier['name']}, city: {$assignedCourier['city']}) to order #{$id} when status changed to ready_for_pickup.");
                                }
                            } else {
                                error_log("Seller Order Update: ⚠️ Assignment verification failed for order #{$id}. Courier ID: {$updatedOrder['curior_id']}, Status: {$updatedOrder['status']}");
                            }
                        } else {
                            error_log("Seller Order Update: ❌ No courier available for order #{$id} (ready_for_pickup). Seller city check needed.");
                        }
                    } catch (\Exception $e) {
                        error_log("Seller Order Update: Error auto-assigning courier to order #{$id}: " . $e->getMessage());
                        error_log("Seller Order Update: Stack trace: " . $e->getTraceAsString());
                    }
                }
                
                // Notify admin about seller's status update (optional)
                // The seller notification service can also be used here if needed
                $this->setFlash('success', 'Order status updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update order status');
            }
        } catch (Exception $e) {
            error_log('Update order status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating order status');
        }

        $this->redirect('seller/orders/detail/' . $id);
    }

    /**
     * Get orders with filter
     * Orders are filtered by seller_id in order_items (since orders can have products from multiple sellers)
     */
    private function getOrders($status, $limit, $offset)
    {
        $paymentFilter = $_GET['payment_type'] ?? '';
        $cacheKey = 'seller_orders_' . $this->sellerId . '_' . md5($status . $paymentFilter . $limit . $offset);
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $sql = "SELECT DISTINCT o.*, u.first_name, u.last_name, u.email as customer_email,
                       pm.name as payment_method_name
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE oi.seller_id = ?";
        
        $params = [$this->sellerId];
        
        if (!empty($status)) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        // Filter by payment type (COD/Prepaid)
        if ($paymentFilter === 'cod') {
            $sql .= " AND (pm.name LIKE '%COD%' OR pm.name LIKE '%Cash%' OR o.payment_method_id IS NULL)";
        } elseif ($paymentFilter === 'prepaid') {
            $sql .= " AND pm.name NOT LIKE '%COD%' AND pm.name NOT LIKE '%Cash%' AND o.payment_method_id IS NOT NULL";
        }
        
        $sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $db = \App\Core\Database::getInstance();
        $orders = $db->query($sql, $params)->all();
        
        // Calculate seller's portion for each order (cache individual calculations)
        foreach ($orders as &$order) {
            $totalCacheKey = 'seller_order_total_' . $order['id'] . '_' . $this->sellerId;
            $sellerTotal = $this->cache->remember($totalCacheKey, function() use ($order) {
                return $this->calculateSellerOrderTotal($order['id']);
            }, 300);
            $order['seller_total'] = $sellerTotal;
        }
        
        // Cache for 2 minutes (orders change frequently)
        $this->cache->set($cacheKey, $orders, 120);
        
        return $orders;
    }

    /**
     * Calculate seller's total for an order
     */
    private function calculateSellerOrderTotal($orderId)
    {
        $db = \App\Core\Database::getInstance();
        
        // Get seller's subtotal
        $sellerSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ? AND seller_id = ?",
            [$orderId, $this->sellerId]
        )->single()['subtotal'] ?? 0;

        // Get total order subtotal
        $totalOrderSubtotal = $db->query(
            "SELECT COALESCE(SUM(total), 0) as subtotal 
             FROM order_items 
             WHERE order_id = ?",
            [$orderId]
        )->single()['subtotal'] ?? 0;

        // Get order details
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return 0;
        }

        // Calculate proportional amounts
        $proportion = $totalOrderSubtotal > 0 ? ($sellerSubtotal / $totalOrderSubtotal) : 0;
        
        $sellerDiscount = ($order['discount_amount'] ?? 0) * $proportion;
        $sellerTax = ($order['tax_amount'] ?? 0) * $proportion;
        $sellerDeliveryFee = ($order['delivery_fee'] ?? 0) * $proportion;
        $sellerTotal = $sellerSubtotal - $sellerDiscount + $sellerTax + $sellerDeliveryFee;

        return $sellerTotal;
    }

    /**
     * Get order count with filter
     */
    private function getOrderCount($status)
    {
        $paymentFilter = $_GET['payment_type'] ?? '';
        
        $sql = "SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o
                INNER JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE oi.seller_id = ?";
        $params = [$this->sellerId];
        
        if (!empty($status)) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        // Filter by payment type (COD/Prepaid)
        if ($paymentFilter === 'cod') {
            $sql .= " AND (pm.name LIKE '%COD%' OR pm.name LIKE '%Cash%' OR o.payment_method_id IS NULL)";
        } elseif ($paymentFilter === 'prepaid') {
            $sql .= " AND pm.name NOT LIKE '%COD%' AND pm.name NOT LIKE '%Cash%' AND o.payment_method_id IS NOT NULL";
        }
        
        $db = \App\Core\Database::getInstance();
        $result = $db->query($sql, $params)->single();
        return $result['count'] ?? 0;
    }
}

