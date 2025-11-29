<?php

namespace App\Controllers\Payment;

use App\Models\CODPayment;
use Exception;

/**
 * Cash on Delivery (COD) Payment Controller
 * Handles all Cash on Delivery payment operations
 */
class CODController extends PaymentBaseController
{
    private $codModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->codModel = new CODPayment();
    }
    
    /**
     * Initiate COD payment
     * For COD, we just confirm the order and mark payment as pending
     */
    public function initiate($orderId)
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        $traceId = $this->security->generateTraceId();
        
        try {
            // Get order details
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }
            
            // Check if COD payment method is enabled
            $codGateway = $this->gatewayModel->getBySlug('cod');
            if (!$codGateway || !($codGateway['is_active'] ?? false)) {
                $this->jsonResponse(['success' => false, 'message' => 'Cash on Delivery payment not available']);
                return;
            }
            
            // Create COD payment record
            $codPaymentId = $this->codModel->createPayment([
                'user_id' => $order['user_id'],
                'order_id' => $orderId,
                'amount' => $order['total_amount'],
                'status' => 'pending'
            ]);
            
            if (!$codPaymentId) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create payment record']);
                return;
            }
            
            // Update order status to confirmed (payment will be collected on delivery)
            $this->orderModel->update($orderId, [
                'payment_status' => 'pending',
                'status' => 'confirmed',
                'payment_method' => 'cod'
            ]);
            
            // Clear cart and send email
            $this->clearCartAndCoupon($order['user_id']);
            $this->sendPostPurchaseEmail($orderId);
            
            $this->logSecurityEvent($traceId, 'cod_payment_initiated', 'success', [
                'order_id' => $orderId
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Order confirmed. Payment will be collected on delivery.',
                'redirect' => URLROOT . '/checkout/success/' . $orderId
            ]);
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'cod_payment_error', 'error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Order confirmation failed']);
        }
    }
    
    /**
     * Confirm COD payment (when payment is collected on delivery)
     * This is typically called by admin/staff after collecting payment
     */
    public function confirm($orderId)
    {
        $traceId = $this->security->generateTraceId();
        
        try {
            // Get order details
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }
            
            // Update COD payment status
            $this->codModel->updateStatusByOrderId($orderId, 'completed');
            
            // Update order payment status
            $this->orderModel->update($orderId, [
                'payment_status' => 'paid',
                'status' => 'completed'
            ]);
            
            // Handle digital products
            $digitalController = new \App\Controllers\Product\DigitalProductController();
            $digitalController->processDigitalProductsAfterPayment($orderId);
            
            // Send payment confirmation SMS
            try {
                $smsController = new \App\Controllers\Sms\SmsOrderController();
                $smsController->sendPaymentConfirmationSms($orderId);
            } catch (\Exception $e) {
                error_log("COD: Error sending payment confirmation SMS: " . $e->getMessage());
            }
            
            $this->logSecurityEvent($traceId, 'cod_payment_confirmed', 'success', [
                'order_id' => $orderId
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Payment confirmed successfully'
                ]);
            } else {
                $this->setFlash('success', 'Payment confirmed successfully');
                $this->redirect('orders');
            }
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'cod_payment_confirm_error', 'error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment confirmation failed']);
            } else {
                $this->setFlash('error', 'Payment confirmation failed');
                $this->redirect('orders');
            }
        }
    }
    
    /**
     * Cancel COD payment (when order is cancelled before delivery)
     */
    public function cancel($orderId)
    {
        $traceId = $this->security->generateTraceId();
        
        try {
            // Get order details
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }
            
            // Update COD payment status
            $this->codModel->updateStatusByOrderId($orderId, 'cancelled');
            
            // Update order status
            $this->orderModel->update($orderId, [
                'payment_status' => 'cancelled',
                'status' => 'cancelled'
            ]);
            
            // Restore product stock
            $orderItems = $this->orderModel->getOrderItems($orderId);
            foreach ($orderItems as $item) {
                $this->productModel->updateStock($item['product_id'], $item['quantity']);
            }
            
            $this->logSecurityEvent($traceId, 'cod_payment_cancelled', 'success', [
                'order_id' => $orderId
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Payment cancelled successfully'
                ]);
            } else {
                $this->setFlash('success', 'Payment cancelled successfully');
                $this->redirect('orders');
            }
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'cod_payment_cancel_error', 'error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment cancellation failed']);
            } else {
                $this->setFlash('error', 'Payment cancellation failed');
                $this->redirect('orders');
            }
        }
    }
    
    /**
     * Get COD payment status
     */
    public function status($orderId)
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        try {
            $payment = $this->codModel->findByOrderId($orderId);
            if (!$payment) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment record not found']);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'created_at' => $payment['created_at']
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to get payment status']);
        }
    }
}





