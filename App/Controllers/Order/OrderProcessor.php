<?php
namespace App\Controllers\Order;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Core\Session;
use Exception;

class OrderProcessor extends Controller
{
    private $orderModel;
    private $orderItemModel;
    private $productModel;
    private $digitalProductModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = $this->model('Order');
        $this->orderItemModel = $this->model('OrderItem');
        $this->productModel = $this->model('Product');
        $this->digitalProductModel = new \App\Models\DigitalProduct();
    }
    
    /**
     * Process order delivery and create seller earnings
     * Handles both regular and digital products
     * 
     * @param int $orderId Order ID
     * @param bool $requirePost Whether to require POST method (default: true for HTTP requests)
     * @return bool
     */
    public function processDelivery($orderId, $requirePost = true)
    {
        if ($requirePost && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            error_log("OrderProcessor: Order #{$orderId} not found");
            return false;
        }
        
        // Get order items
        $orderItems = $this->orderItemModel->getByOrderId($orderId);
        
        $hasDigitalProducts = false;
        $hasNonDigitalProducts = false;
        
        foreach ($orderItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            
            if (!$product || empty($product['seller_id'])) {
                continue;
            }
            
            // Check if product is digital
            $isDigital = $this->isDigitalProduct($item['product_id']);
            
            if ($isDigital) {
                $hasDigitalProducts = true;
            } else {
                $hasNonDigitalProducts = true;
                // For regular products, seller earnings are handled by PayoutController
                // when order is marked as delivered
            }
        }
        
        // If order has only digital products, process payout via PayoutController
        if ($hasDigitalProducts && !$hasNonDigitalProducts) {
            $this->processDigitalProductPayout($orderId);
        }
        
        return true;
    }
    
    /**
     * Process seller payout for digital products using PayoutController
     */
    private function processDigitalProductPayout($orderId)
    {
        try {
            $payoutController = new \App\Controllers\Seller\Payout\PayoutController();
            $result = $payoutController->processSellerPayout($orderId);
            
            if ($result) {
                error_log("OrderProcessor: Digital product payout processed successfully for order #{$orderId}");
            } else {
                error_log("OrderProcessor: Failed to process digital product payout for order #{$orderId}");
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("OrderProcessor: Error processing digital product payout for order #{$orderId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if product is digital
     */
    private function isDigitalProduct($productId)
    {
        $digitalProduct = $this->digitalProductModel->getByProductId($productId);
        return $digitalProduct ? true : false;
    }
    
    
    /**
     * Process order cancellation and reverse earnings
     */
    public function processCancellation($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return false;
        }
        
        // Get order items and reverse earnings
        $orderItems = $this->orderItemModel->getByOrderId($orderId);
        
        foreach ($orderItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            
            if ($product && !empty($product['seller_id'])) {
                $this->reverseSellerEarning($order['id'], $product['id']);
            }
        }
        
        return true;
    }
    
    /**
     * Reverse seller earning for cancelled order
     */
    private function reverseSellerEarning($orderId, $productId)
    {
        $db = \App\Core\Database::getInstance();
        $sql = "UPDATE seller_earnings SET status = 'cancelled' WHERE order_id = ? AND product_id = ?";
        return $db->query($sql)->bind([$orderId, $productId])->execute();
    }
    
    /**
     * Process order return and adjust earnings
     */
    public function processReturn($orderId, $returnedItems)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return false;
        }
        
        foreach ($returnedItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            
            if ($product && !empty($product['seller_id'])) {
                $this->adjustSellerEarningForReturn($order['id'], $item, $product);
            }
        }
        
        return true;
    }
    
    /**
     * Adjust seller earning for returned items
     * Note: Seller earnings are managed through seller_wallet_transactions
     * This method is kept for compatibility but earnings are handled by PayoutController
     */
    private function adjustSellerEarningForReturn($orderId, $returnedItem, $product)
    {
        // Seller earnings adjustments are handled through seller_wallet_transactions
        // when order status changes or items are returned
        // This is managed by PayoutController and SellerBalanceService
        return true;
    }
}

