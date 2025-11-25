<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerEarning;
use App\Core\Session;

class OrderProcessor extends Controller
{
    private $orderModel;
    private $orderItemModel;
    private $productModel;
    private $sellerEarningModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = $this->model('Order');
        $this->orderItemModel = $this->model('OrderItem');
        $this->productModel = $this->model('Product');
        $this->sellerEarningModel = $this->model('SellerEarning');
    }
    
    /**
     * Process order delivery and create seller earnings
     */
    public function processDelivery($orderId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return false;
        }
        
        // Get order items
        $orderItems = $this->orderItemModel->getByOrderId($orderId);
        
        foreach ($orderItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            
            // Check if product has a seller
            if ($product && !empty($product['seller_id'])) {
                $this->createSellerEarning($order, $item, $product);
            }
        }
        
        return true;
    }
    
    /**
     * Create seller earning record
     */
    private function createSellerEarning($order, $orderItem, $product)
    {
        $commissionRate = $product['seller_commission'] ?? 10.00;
        $totalAmount = $orderItem['price'] * $orderItem['quantity'];
        $commissionAmount = ($totalAmount * $commissionRate) / 100;
        $sellerAmount = $totalAmount - $commissionAmount;
        
        $earningData = [
            'seller_id' => $product['seller_id'],
            'order_id' => $order['id'],
            'product_id' => $product['id'],
            'quantity' => $orderItem['quantity'],
            'unit_price' => $orderItem['price'],
            'total_amount' => $totalAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'seller_amount' => $sellerAmount
        ];
        
        return $this->sellerEarningModel->create($earningData);
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
        $sql = "UPDATE seller_earnings SET status = 'cancelled' WHERE order_id = ? AND product_id = ?";
        return $this->db->query($sql)->bind([$orderId, $productId])->execute();
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
     */
    private function adjustSellerEarningForReturn($orderId, $returnedItem, $product)
    {
        $commissionRate = $product['seller_commission'] ?? 10.00;
        $returnedAmount = $returnedItem['price'] * $returnedItem['quantity'];
        $commissionAmount = ($returnedAmount * $commissionRate) / 100;
        $sellerAmount = $returnedAmount - $commissionAmount;
        
        // Create negative earning record for return
        $earningData = [
            'seller_id' => $product['seller_id'],
            'order_id' => $orderId,
            'product_id' => $product['id'],
            'quantity' => $returnedItem['quantity'],
            'unit_price' => $returnedItem['price'],
            'total_amount' => -$returnedAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => -$commissionAmount,
            'seller_amount' => -$sellerAmount
        ];
        
        return $this->sellerEarningModel->create($earningData);
    }
}

