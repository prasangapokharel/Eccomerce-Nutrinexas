<?php
namespace App\Controllers\Api;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;

class OrdersApiController extends BaseApiController
{
    private $orderModel;
    private $cartModel;
    private $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    /**
     * Get user orders
     * GET /api/orders
     */
    public function index()
    {
        $this->requirePermission('read');
        
        $userId = $this->currentUser['user_id'];
        $pagination = $this->getPaginationParams();
        
        $orders = $this->orderModel->getUserOrders($userId, $pagination['limit'], $pagination['offset']);
        $total = $this->orderModel->getUserOrdersCount($userId);
        
        $formattedOrders = array_map([$this, 'formatOrder'], $orders);
        
        $this->jsonResponse($this->formatPaginatedResponse($formattedOrders, $total, $pagination));
    }
    
    /**
     * Get single order
     * GET /api/orders/{id}
     */
    public function show($id)
    {
        $this->requirePermission('read');
        
        $userId = $this->currentUser['user_id'];
        $order = $this->orderModel->getUserOrder($userId, $id);
        
        if (!$order) {
            $this->jsonResponse(['error' => 'Order not found'], 404);
        }
        
        $this->jsonResponse(['data' => $this->formatOrder($order, true)]);
    }
    
    /**
     * Create new order
     * POST /api/orders
     */
    public function create()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['shipping_address', 'payment_method']);
        
        $userId = $this->currentUser['user_id'];
        
        // Get cart items
        $cartItems = $this->cartModel->getUserCart($userId);
        
        if (empty($cartItems)) {
            $this->jsonResponse(['error' => 'Cart is empty'], 400);
        }
        
        // Validate stock for all items
        foreach ($cartItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            if (!$product || $product['stock_quantity'] < $item['quantity']) {
                $this->jsonResponse([
                    'error' => 'Insufficient stock for product: ' . $item['product_name']
                ], 400);
            }
        }
        
        // Calculate totals
        $subtotal = array_sum(array_column($cartItems, 'subtotal'));
        // Calculate tax using dynamic rate from settings
        $settingModel = new \App\Models\Setting();
        $taxRate = $settingModel->get('tax_rate', 13) / 100; // Convert percentage to decimal
        $tax = $subtotal * $taxRate;
        $shipping = 100; // Fixed shipping cost
        $total = $subtotal + $tax + $shipping;
        
        // Create order
        $orderData = [
            'user_id' => $userId,
            'order_number' => 'ORD-' . time() . '-' . rand(1000, 9999),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'payment_method' => $data['payment_method'],
            'payment_status' => 'pending',
            'shipping_address' => json_encode($data['shipping_address']),
            'billing_address' => json_encode($data['billing_address'] ?? $data['shipping_address']),
            'notes' => $data['notes'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $orderId = $this->orderModel->create($orderData);
        
        if (!$orderId) {
            $this->jsonResponse(['error' => 'Failed to create order'], 500);
        }
        
        // Add order items
        foreach ($cartItems as $item) {
            $this->orderModel->addOrderItem($orderId, [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal']
            ]);
            
            // NOTE: Stock should NOT be reduced here - it should only be reduced when order is confirmed/paid
            // Stock reduction happens in AdminController::updateOrderStatus when status changes to 'paid'/'processing'/'confirmed'
        }
        
        // Clear cart
        $this->cartModel->clearCart($userId);
        
        // Get created order
        $order = $this->orderModel->getUserOrder($userId, $orderId);
        
        $this->jsonResponse([
            'message' => 'Order created successfully',
            'data' => $this->formatOrder($order, true)
        ], 201);
    }
    
    /**
     * Update order status
     * PUT /api/orders/{id}/status
     */
    public function updateStatus($id)
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['status']);
        
        $userId = $this->currentUser['user_id'];
        $order = $this->orderModel->getUserOrder($userId, $id);
        
        if (!$order) {
            $this->jsonResponse(['error' => 'Order not found'], 404);
        }
        
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($data['status'], $allowedStatuses)) {
            $this->jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        $result = $this->orderModel->update($id, [
            'status' => $data['status'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Process referral earnings if order is marked as delivered
            if ($data['status'] === 'delivered') {
                try {
                    $referralService = new \App\Services\ReferralEarningService();
                    $referralService->processReferralEarning($id);
                } catch (\Exception $e) {
                    error_log('OrdersApiController: Error processing referral earning: ' . $e->getMessage());
                }
            }
            
            $updatedOrder = $this->orderModel->getUserOrder($userId, $id);
            $this->jsonResponse([
                'message' => 'Order status updated successfully',
                'data' => $this->formatOrder($updatedOrder)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update order status'], 500);
        }
    }
    
    /**
     * Cancel order
     * PUT /api/orders/{id}/cancel
     */
    public function cancel($id)
    {
        $this->requirePermission('write');
        
        $userId = $this->currentUser['user_id'];
        $order = $this->orderModel->getUserOrder($userId, $id);
        
        if (!$order) {
            $this->jsonResponse(['error' => 'Order not found'], 404);
        }
        
        if (in_array($order['status'], ['delivered', 'cancelled'])) {
            $this->jsonResponse(['error' => 'Cannot cancel this order'], 400);
        }
        
        // Restore stock
        $orderItems = $this->orderModel->getOrderItems($id);
        foreach ($orderItems as $item) {
            $this->productModel->updateStock($item['product_id'], $item['quantity']);
        }
        
        $result = $this->orderModel->update($id, [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $this->jsonResponse(['message' => 'Order cancelled successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to cancel order'], 500);
        }
    }
    
    /**
     * Format order for API response
     */
    private function formatOrder($order, $includeItems = false)
    {
        $formatted = [
            'id' => $order['id'],
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'subtotal' => (float)$order['subtotal'],
            'tax' => (float)$order['tax'],
            'shipping' => (float)$order['shipping'],
            'total' => (float)$order['total'],
            'payment_method' => $order['payment_method'],
            'payment_status' => $order['payment_status'],
            'shipping_address' => json_decode($order['shipping_address'], true),
            'billing_address' => json_decode($order['billing_address'], true),
            'notes' => $order['notes'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at']
        ];
        
        if ($includeItems) {
            $orderItems = $this->orderModel->getOrderItems($order['id']);
            $formatted['items'] = array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'price' => (float)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'subtotal' => (float)$item['subtotal']
                ];
            }, $orderItems);
        }
        
        return $formatted;
    }
}















