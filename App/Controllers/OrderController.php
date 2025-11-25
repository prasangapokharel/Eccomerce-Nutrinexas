<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\ReferralEarning;
use App\Models\Transaction;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\ProductImage;
use App\Models\CancelLog;

/**
 * Order Controller
 * Handles order management
 */
class OrderController extends Controller
{
    private $productModel;
    private $productImageModel;
    private $orderModel;
    private $orderItemModel;
    private $userModel;
    private $referralEarningModel;
    private $transactionModel;
    private $notificationModel;
    private $settingModel;
    private $cancelLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->productModel = new Product();
        $this->userModel = new User();
        $this->referralEarningModel = new ReferralEarning();
        $this->transactionModel = new Transaction();
        $this->notificationModel = new Notification();
        $this->settingModel = new Setting();
        $this->productImageModel = new ProductImage();
        $this->cancelLogModel = new CancelLog();
    }

    /**
     * Display user's orders
     *
     * @return void
     */
    public function index()
    {
        $this->requireLogin();
        
        $userId = Session::get('user_id');
        $orders = $this->orderModel->getOrdersByUserId($userId);
        
        $this->view('orders/index', [
            'orders' => $orders,
            'title' => 'My Orders'
        ]);
    }

    /**
     * View specific order
     *
     * @param int $id
     * @return void
     */
    public function viewOrder($id = null)
    {
        $this->requireLogin();
        
        if (!$id) {
            $this->redirect('orders');
            return;
        }
        
        $userId = Session::get('user_id');
        $order = $this->orderModel->getUserOrder($userId, $id);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders');
            return;
        }
        
        $orderItems = $this->orderModel->getOrderItems($id);
        
        $this->view('orders/view', [
            'order' => $order,
            'orderItems' => $orderItems,
            'title' => 'Order Details'
        ]);
    }

    /**
     * Track order
     *
     * @return void
     */
    public function track()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $invoice = $this->post('invoice');
            
            if (empty($invoice)) {
                $this->setFlash('error', 'Invoice number is required');
                $this->redirect('orders/track');
                return;
            }
            
            $this->redirect('orders/track-result?invoice=' . urlencode($invoice));
        }
        
        $this->view('orders/track', [
            'title' => 'Track Order'
        ]);
    }

    /**
     * Track order result
     *
     * @return void
     */
    public function trackResult()
    {
        $invoice = $this->get('invoice') ?: $this->post('invoice');
        
        if (empty($invoice)) {
            $this->setFlash('error', 'Invoice number is required');
            $this->redirect('orders/track');
            return;
        }
        
        $order = $this->orderModel->getOrderByInvoice($invoice);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders/track');
            return;
        }
        
        $orderItems = $this->orderModel->getOrderItems($order['id']);
        
        $this->view('orders/track-result', [
            'order' => $order,
            'orderItems' => $orderItems,
            'title' => 'Track Order Result'
        ]);
    }

    /**
     * Cancel order
     *
     * @param int $id
     * @return void
     */
    public function cancel($id = null)
    {
        $this->requireLogin();
        
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
                return;
            }
            $this->redirect('orders');
            return;
        }
        
        $userId = Session::get('user_id');
        $order = $this->orderModel->getUserOrder($userId, $id);
        
        if (!$order) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders');
            return;
        }
        
        // Check if order can be cancelled
        $cancellableStatuses = ['pending', 'confirmed', 'processing', 'unpaid'];
        if (!in_array($order['status'], $cancellableStatuses)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'message' => 'This order cannot be cancelled']);
                return;
            }
            $this->setFlash('error', 'This order cannot be cancelled');
            $this->redirect('orders');
            return;
        }
        
        // Get cancellation reason
        $reason = trim($_POST['reason'] ?? '');
        if (empty($reason)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Cancellation reason is required']);
                return;
            }
            $this->setFlash('error', 'Cancellation reason is required');
            $this->redirect('orders');
            return;
        }
        
        try {
            // Start transaction
            $this->orderModel->beginTransaction();
            
            // Get seller_id from order (preferred) or from order items
            $sellerId = null;
            
            // First try to get seller_id directly from order
            if (!empty($order['seller_id'])) {
                $sellerId = (int)$order['seller_id'];
            } else {
                // Fallback: Get seller_id from order items (get the first seller_id from order items)
                $orderItems = $this->orderModel->getOrderItems($id);
                if (!empty($orderItems)) {
                    // Get seller_id from the first order item
                    $firstItem = $orderItems[0];
                    if (isset($firstItem['seller_id']) && !empty($firstItem['seller_id'])) {
                        $sellerId = (int)$firstItem['seller_id'];
                    } else {
                        // Fallback: get seller_id from product
                        $product = $this->productModel->find($firstItem['product_id']);
                        $sellerId = $product ? (int)($product['seller_id'] ?? null) : null;
                    }
                }
            }
            
            // Create cancel log entry with seller_id
            $cancelLogId = $this->cancelLogModel->create([
                'order_id' => $id,
                'seller_id' => $sellerId,
                'reason' => $reason,
                'status' => 'processing'
            ]);
            
            if (!$cancelLogId) {
                throw new \Exception('Failed to create cancel log');
            }
            
            // Restore product stock
            $orderItems = $this->orderModel->getOrderItems($id);
            foreach ($orderItems as $item) {
                $this->productModel->updateStock($item['product_id'], $item['quantity']);
            }
            
            // Update order status
            $result = $this->orderModel->update($id, [
                'status' => 'cancelled',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to cancel order');
            }
            
            // Commit transaction
            $this->orderModel->commit();
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => true, 'message' => 'Order cancellation request submitted successfully']);
                return;
            }
            
            $this->setFlash('success', 'Order cancellation request submitted successfully');
            
        } catch (\Exception $e) {
            // Rollback transaction
            $this->orderModel->rollback();
            error_log('Cancel order error: ' . $e->getMessage());
            
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Error cancelling order: ' . $e->getMessage()]);
                return;
            }
            
            $this->setFlash('error', 'Error cancelling order: ' . $e->getMessage());
        }
        
        $this->redirect('orders');
    }

    /**
     * JSON response helper
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * One-click reorder: copy items from a past order to the user's cart
     */
    public function reorder($id = null)
    {
        $this->requireLogin();

        if (!$id) {
            $this->redirect('orders');
            return;
        }

        $userId = Session::get('user_id');
        $order = $this->orderModel->getUserOrder($userId, $id);

        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders');
            return;
        }

        $orderItems = $this->orderModel->getOrderItems($id);
        if (empty($orderItems)) {
            $this->setFlash('error', 'No items to reorder');
            $this->redirect('orders');
            return;
        }

        $cartModel = new \App\Models\Cart();
        $productModel = $this->productModel;
        $added = 0;
        $skipped = [];

        foreach ($orderItems as $item) {
            $product = $productModel->find($item['product_id']);
            if (!$product) {
                $skipped[] = $item['product_id'];
                continue;
            }
            $qty = max(1, (int)$item['quantity']);
            if ($product['stock_quantity'] < $qty) {
                // Add up to available stock if any
                if ($product['stock_quantity'] < 1) {
                    $skipped[] = $product['id'];
                    continue;
                }
                $qty = (int)$product['stock_quantity'];
            }
            $price = $product['sale_price'] && $product['sale_price'] > 0 && $product['sale_price'] < $product['price']
                ? $product['sale_price']
                : $product['price'];
            $result = $cartModel->addItem([
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => $qty,
                'price' => $price,
            ]);
            if ($result) {
                $added++;
            } else {
                $skipped[] = $product['id'];
            }
        }

        if ($added > 0) {
            $msg = $added . ' item' . ($added > 1 ? 's' : '') . ' added to cart';
            if (!empty($skipped)) {
                $msg .= '. Some items were unavailable.';
            }
            $this->setFlash('success', $msg);
        } else {
            $this->setFlash('error', 'Could not add any items to cart');
        }

        $this->redirect('cart');
    }

    /**
     * Update order status (admin only)
     *
     * @param int $id
     * @return void
     */
    public function updateStatus($id = null)
    {
        $this->requireAdmin();
        
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/orders');
            return;
        }
        
        $status = $this->post('status');
        
        if (!in_array($status, ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'unpaid', 'paid'])) {
            $this->setFlash('error', 'Invalid status.');
            $this->redirect('admin/orders');
            return;
        }
        
        // Start transaction
        $this->orderModel->beginTransaction();
        
        try {
            // Get current order status synchronously
            $order = $this->orderModel->getOrderById($id);
            
            if (!$order) {
                throw new \Exception('Order not found.');
            }
            
            $oldStatus = $order['status'];
            
            // Update order status
            $result = $this->orderModel->update($id, [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to update order status.');
            }
            
            // If status changed to delivered, process referral earnings synchronously
            if ($status === 'delivered' && $oldStatus !== 'delivered') {
                $this->processReferralEarnings($id);
            }
            
            // If status changed to cancelled, cancel referral earnings synchronously
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->cancelReferralEarnings($id);
            }
            
            // Commit transaction
            $this->orderModel->commit();
            
            $this->setFlash('success', 'Order status updated successfully.');
            
        } catch (\Exception $e) {
            // Rollback transaction
            $this->orderModel->rollback();
            error_log('Update order status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating order status: ' . $e->getMessage());
        }
        
        $this->redirect('admin/orders');
    }

    /**
     * Process referral earnings for an order
     *
     * @param int $orderId
     * @return bool
     */
    public function processReferralEarnings($orderId)
    {
        // Use the new ReferralEarningService
        $referralService = new \App\Services\ReferralEarningService();
        return $referralService->processReferralEarning($orderId);
    }

    /**
     * Cancel referral earnings for a cancelled order
     *
     * @param int $orderId
     * @return bool
     */
    public function cancelReferralEarnings($orderId)
    {
        // Use the new ReferralEarningService
        $referralService = new \App\Services\ReferralEarningService();
        return $referralService->cancelReferralEarning($orderId);
    }

    /**
     * Reverse referral earnings for a cancelled order (legacy method - use cancelReferralEarnings instead)
     *
     * @param int $orderId
     * @return bool
     */
    public function reverseReferralEarnings($orderId)
    {
        // Use the new cancelReferralEarnings method
        return $this->cancelReferralEarnings($orderId);
    }

    /**
     * Delete order (admin only)
     *
     * @param int $id
     * @return void
     */
    public function deleteOrder($id = null)
    {
        $this->requireAdmin();
        
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request');
            $this->redirect('admin/orders');
            return;
        }
        
        try {
            // Get order details
            $order = $this->orderModel->getOrderById($id);
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('admin/orders');
                return;
            }
            
            // Check if order can be deleted (only pending orders)
            if ($order['status'] !== 'pending') {
                $this->setFlash('error', 'Only pending orders can be deleted');
                $this->redirect('admin/orders');
                return;
            }
            
            // Start transaction
            $this->orderModel->beginTransaction();
            
            try {
                // Delete order items first
                $this->orderItemModel->deleteByOrderId($id);
                
                // Delete the order
                $result = $this->orderModel->delete($id);
                
                if (!$result) {
                    throw new \Exception('Failed to delete order');
                }
                
                // Commit transaction
                $this->orderModel->commit();
                
                $this->setFlash('success', 'Order deleted successfully');
                
            } catch (\Exception $e) {
                // Rollback transaction
                $this->orderModel->rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            error_log('Delete order error: ' . $e->getMessage());
            $this->setFlash('error', 'Error deleting order: ' . $e->getMessage());
        }
        
        $this->redirect('admin/orders');
    }
}
