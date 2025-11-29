<?php

namespace App\Controllers\Payment;

use App\Models\KhaltiPayment;
use App\Controllers\Security\SecurityController;
use Exception;

/**
 * Khalti Payment Controller
 * Handles all Khalti payment gateway operations
 */
class KhaltiController extends PaymentBaseController
{
    private $khaltiModel;
    private $securityController;
    
    public function __construct()
    {
        parent::__construct();
        $this->khaltiModel = new KhaltiPayment();
        $this->securityController = new SecurityController();
    }
    
    /**
     * Initiate Khalti payment
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
            
            // Get Khalti gateway configuration
            $khaltiGateway = $this->gatewayModel->getBySlug('khalti');
            if (!$khaltiGateway) {
                $this->jsonResponse(['success' => false, 'message' => 'Khalti payment not available']);
                return;
            }
            
            $parameters = $this->extractGatewayParameters($khaltiGateway);
            $isTestMode = (bool)($khaltiGateway['is_test_mode'] ?? false);
            $secretKey = $this->resolveKhaltiSecret($parameters, $isTestMode);
            
            if (!$secretKey) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error: Secret key not configured']);
                return;
            }
            
            // Calculate order totals - same logic as eSewa for consistency
            $orderItemModel = new \App\Models\OrderItem();
            $orderItems = $orderItemModel->getByOrderId($orderId);
            
            // Calculate subtotal from order items
            $subtotal = 0;
            foreach ($orderItems as $item) {
                $itemPrice = ($item['price'] ?? 0);
                $itemQuantity = ($item['quantity'] ?? 1);
                $subtotal += $itemPrice * $itemQuantity;
            }
            
            // Get stored discount and delivery fee
            $discountAmount = round($order['discount_amount'] ?? 0, 2);
            $deliveryFee = round($order['delivery_fee'] ?? 0, 2);
            $serviceCharge = round($order['service_charge'] ?? 0, 2);
            $taxRate = (new \App\Models\Setting())->get('tax_rate', 12);
            
            // Recalculate using same service as checkout and eSewa
            $totals = \App\Services\OrderCalculationService::calculateTotals(
                $subtotal,
                $discountAmount,
                $deliveryFee,
                $taxRate
            );
            
            // Use recalculated values for accuracy
            $taxAmount = round($totals['tax'], 2);
            $totalAmount = round($totals['total'], 2);
            
            // Security: Fraud detection and secure payment processing
            try {
                $securePayment = $this->securityController->processSecurePayment([
                    'order_id' => $orderId,
                    'amount' => $totalAmount,
                    'method' => 'khalti',
                    'items' => $orderItems
                ], $order['user_id']);
                
                $traceId = $securePayment['trace_id'];
            } catch (Exception $e) {
                error_log("Khalti security check failed: " . $e->getMessage());
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Payment security check failed. Please contact support.',
                    'timestamp' => time()
                ]);
                return;
            }
            
            // Prepare payment data
            $paymentData = [
                'return_url' => URLROOT . '/payment/khalti/success/' . $orderId,
                'website_url' => URLROOT,
                'amount' => (int)($totalAmount * 100), // Convert to paisa using recalculated total
                'purchase_order_id' => $order['invoice'],
                'purchase_order_name' => 'Order #' . $order['invoice'],
                'customer_info' => [
                    'name' => $order['customer_name'] ?? 'Customer',
                    'email' => $order['customer_email'] ?? '',
                    'phone' => $order['phone'] ?? ''
                ]
            ];
            
            // Create or update Khalti payment record with recalculated amount
            // Note: Payment record is created before API call, pidx will be updated after
            // Status must be 'pending' (not 'initiated') per database enum
            try {
                $khaltiPaymentId = $this->khaltiModel->createPayment([
                    'user_id' => $order['user_id'],
                    'order_id' => $orderId,
                    'amount' => $totalAmount, // Use recalculated total
                    'status' => 'pending' // Database enum: pending, completed, failed
                ]);
                
                if (!$khaltiPaymentId) {
                    error_log("Khalti payment creation failed for order: $orderId, amount: $totalAmount");
                    $this->logSecurityEvent($traceId, 'khalti_payment_creation_failed', 'error', [
                        'order_id' => $orderId,
                        'amount' => $totalAmount,
                        'user_id' => $order['user_id']
                    ]);
                    $this->jsonResponse([
                        'success' => false, 
                        'message' => 'Failed to create payment record. Please try again.',
                        'timestamp' => time()
                    ]);
                    return;
                }
            } catch (Exception $e) {
                error_log("Khalti payment creation exception: " . $e->getMessage());
                $this->logSecurityEvent($traceId, 'khalti_payment_creation_exception', 'error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Payment record creation error: ' . $e->getMessage(),
                    'timestamp' => time()
                ]);
                return;
            }
            
            // Initiate payment with Khalti SDK
            $khaltiResponse = $this->initiateKhaltiPaymentSDK($paymentData, $secretKey, $isTestMode);
            
            if ($khaltiResponse['success']) {
                // Update payment record with pidx
                $this->khaltiModel->updateStatusByOrderId($orderId, 'pending', $khaltiResponse['pidx']);
                
                $this->logSecurityEvent($traceId, 'khalti_payment_initiated', 'success', [
                    'order_id' => $orderId,
                    'pidx' => $khaltiResponse['pidx'],
                    'amount' => $totalAmount
                ]);
                
                $this->jsonResponse([
                    'success' => true,
                    'payment_url' => $khaltiResponse['payment_url'],
                    'pidx' => $khaltiResponse['pidx'],
                    'amount' => $totalAmount,
                    'breakdown' => [
                        'subtotal' => $subtotal,
                        'tax' => $taxAmount,
                        'delivery_fee' => $deliveryFee,
                        'total' => $totalAmount
                    ]
                ]);
            } else {
                $this->logSecurityEvent($traceId, 'khalti_payment_failed', 'error', [
                    'order_id' => $orderId,
                    'error' => $khaltiResponse['message']
                ]);
                
                $this->jsonResponse([
                    'success' => false,
                    'message' => $khaltiResponse['message']
                ]);
            }
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'khalti_payment_error', 'error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Payment initiation failed']);
        }
    }
    
    /**
     * Verify Khalti payment
     */
    public function verify()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        $traceId = $this->security->generateTraceId();
        
        try {
            $input = $this->getJsonInput();
            $pidx = $input['pidx'] ?? '';
            
            if (!$pidx) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment ID required']);
                return;
            }
            
            // Get Khalti gateway configuration
            $khaltiGateway = $this->gatewayModel->getBySlug('khalti');
            if (!$khaltiGateway) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error']);
                return;
            }
            
            $parameters = $this->extractGatewayParameters($khaltiGateway);
            $isTestMode = (bool)($khaltiGateway['is_test_mode'] ?? false);
            $secretKey = $this->resolveKhaltiSecret($parameters, $isTestMode);
            
            if (!$secretKey) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error']);
                return;
            }
            
            // Verify payment with Khalti API
            $verificationResponse = $this->verifyKhaltiPayment($pidx, $secretKey, $isTestMode);
            
            if ($verificationResponse['success']) {
                $paymentData = $verificationResponse['data'];
                
                // Update payment status
                $this->khaltiModel->updateStatusByOrderId($paymentData['order_id'], 'completed', $pidx);
                
                // Update order status
                $this->orderModel->update($paymentData['order_id'], [
                    'payment_status' => 'completed',
                    'status' => 'confirmed'
                ]);
                
                $this->logSecurityEvent($traceId, 'khalti_payment_verified', 'success', [
                    'order_id' => $paymentData['order_id'],
                    'pidx' => $pidx
                ]);
                
                // Handle digital products
                $digitalController = new \App\Controllers\Product\DigitalProductController();
                $digitalController->processDigitalProductsAfterPayment($paymentData['order_id']);
                
                $this->jsonResponse([
                    'success' => true,
                    'status' => 'completed',
                    'redirect' => URLROOT . '/checkout/success/' . $paymentData['order_id']
                ]);
            } else {
                $this->logSecurityEvent($traceId, 'khalti_payment_verification_failed', 'error', [
                    'pidx' => $pidx,
                    'error' => $verificationResponse['message']
                ]);
                
                $this->jsonResponse([
                    'success' => false,
                    'message' => $verificationResponse['message']
                ]);
            }
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'khalti_verification_error', 'error', [
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Payment verification failed']);
        }
    }
    
    /**
     * Khalti payment return handler
     */
    public function return($orderId)
    {
        try {
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('orders');
                return;
            }
            
            // Get pidx from query parameters (Khalti returns this)
            $pidx = $_GET['pidx'] ?? '';
            $status = $_GET['status'] ?? '';
            
            // If pidx is provided, verify the payment
            if (!empty($pidx)) {
                $gateway = $this->gatewayModel->getBySlug('khalti');
                if ($gateway) {
                    $parameters = $this->extractGatewayParameters($gateway);
                    $isTestMode = (bool)($gateway['is_test_mode'] ?? false);
                    $secretKey = $this->resolveKhaltiSecret($parameters, $isTestMode);
                    
                    if (!empty($secretKey)) {
                        $verificationResponse = $this->verifyKhaltiPayment($pidx, $secretKey, $isTestMode);
                        
                        if ($verificationResponse['success']) {
                            $paymentData = $verificationResponse['data'];
                            
                            // Check if payment is completed
                            $paymentStatus = $paymentData['status'] ?? '';
                            if ($paymentStatus === 'Completed' || $paymentStatus === 'completed') {
                                // Update payment status
                                $this->khaltiModel->updateStatusByOrderId($orderId, 'completed', $pidx);
                                
                                // Update order status
                                $this->orderModel->update($orderId, [
                                    'payment_status' => 'paid',
                                    'status' => 'confirmed'
                                ]);
                                
                                // Handle digital products
                                $digitalController = new \App\Controllers\Product\DigitalProductController();
                                $digitalController->processDigitalProductsAfterPayment($orderId);
                                
                                // Send payment confirmation SMS
                                try {
                                    $smsController = new \App\Controllers\Sms\SmsOrderController();
                                    $smsController->sendPaymentConfirmationSms($orderId);
                                } catch (\Exception $e) {
                                    error_log("Khalti: Error sending payment confirmation SMS: " . $e->getMessage());
                                }
                                
                                // Clear cart and send email
                                $this->clearCartAndCoupon($order['user_id']);
                                $this->sendPostPurchaseEmail($orderId);
                                
                                $this->setFlash('success', 'Payment completed successfully!');
                                $this->redirect('checkout/success/' . $orderId);
                                return;
                            }
                        }
                    }
                }
            }
            
            // If status is Completed from query params, handle it
            if ($status === 'Completed' || $status === 'completed') {
                $this->orderModel->update($orderId, [
                    'payment_status' => 'paid',
                    'status' => 'confirmed'
                ]);
                
                $this->khaltiModel->updateStatusByOrderId($orderId, 'completed', $pidx);
                
                // Handle digital products
                $digitalController = new \App\Controllers\Product\DigitalProductController();
                $digitalController->processDigitalProductsAfterPayment($orderId);
                
                // Send payment confirmation SMS
                try {
                    $smsController = new \App\Controllers\Sms\SmsOrderController();
                    $smsController->sendPaymentConfirmationSms($orderId);
                } catch (\Exception $e) {
                    error_log("Khalti: Error sending payment confirmation SMS: " . $e->getMessage());
                }
                
                // Clear cart
                $this->clearCartAndCoupon($order['user_id']);
                
                $this->setFlash('success', 'Payment completed successfully!');
                $this->redirect('checkout/success/' . $orderId);
                return;
            }
            
            // Otherwise, show payment page (payment might still be pending)
            $this->view('payment/khalti', [
                'order' => $order,
                'title' => 'Complete Payment'
            ]);
            
        } catch (Exception $e) {
            error_log('Khalti return handler error: ' . $e->getMessage());
            $this->setFlash('error', 'Payment verification failed');
            $this->redirect('orders');
        }
    }

    /**
     * Khalti payment failure handler
     */
    public function failure()
    {
        error_log("=== KHALTI FAILURE CALLBACK START ===");
        $pidx = $_GET['pidx'] ?? '';
        $status = $_GET['status'] ?? '';
        $purchaseOrderId = $_GET['purchase_order_id'] ?? '';
        $transactionId = $_GET['transaction_id'] ?? null;
        error_log("Khalti failure params: pidx=$pidx, status=$status, purchase_order_id=$purchaseOrderId");

        // Try to locate order by invoice from return params
        $order = null;
        if (!empty($purchaseOrderId)) {
            $order = $this->orderModel->findOneBy('invoice', $purchaseOrderId);
        }

        // If not found, try to find by last initiated payment matching pidx
        if (!$order && !empty($pidx)) {
            $payment = $this->khaltiModel->getByOrderId($_GET['order_id'] ?? 0);
            if ($payment && !empty($payment['order_id'])) {
                $order = $this->orderModel->find($payment['order_id']);
            }
        }

        if ($order) {
            try {
                // Update Khalti payment status to failed
                $this->khaltiModel->updateStatusByOrderId($order['id'], 'failed', $transactionId);

                // If user cancelled or gateway indicates non-completed, cancel pending orders
                $isCancelled = false;
                $normalized = strtolower($status);
                if (in_array($normalized, ['user canceled', 'user cancelled', 'cancelled', 'canceled', 'failed', 'expired'])) {
                    $isCancelled = true;
                }

                if ($isCancelled && ($order['status'] ?? '') === 'pending') {
                    // Restore product stock
                    $orderItems = $this->orderModel->getOrderItems($order['id']);
                    foreach ($orderItems as $item) {
                        $this->productModel->updateStock($item['product_id'], $item['quantity']);
                    }

                    // Update order status
                    $this->orderModel->update($order['id'], [
                        'status' => 'cancelled',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    error_log("Order {$order['id']} marked as cancelled and stock restored (Khalti failure)");
                }
            } catch (\Exception $e) {
                error_log('Error handling Khalti failure: ' . $e->getMessage());
            }
        } else {
            error_log('Khalti failure: order not found from provided parameters');
        }

        $this->setFlash('error', 'Payment was cancelled or failed');
        $this->redirect('checkout');
    }

    /**
     * Khalti webhook handler
     */
    public function webhook()
    {
        $traceId = $this->security->generateTraceId();
        
        try {
            $input = $this->getJsonInput();
            $pidx = $input['pidx'] ?? '';
            
            if (empty($pidx)) {
                http_response_code(400);
                echo 'Missing pidx';
                return;
            }
            
            // Get Khalti gateway configuration
            $khaltiGateway = $this->gatewayModel->getBySlug('khalti');
            if (!$khaltiGateway) {
                http_response_code(404);
                echo 'Gateway not configured';
                return;
            }
            
            $parameters = $this->extractGatewayParameters($khaltiGateway);
            $isTestMode = (bool)($khaltiGateway['is_test_mode'] ?? false);
            $secretKey = $this->resolveKhaltiSecret($parameters, $isTestMode);
            
            if (!$secretKey) {
                http_response_code(500);
                echo 'Configuration error';
                return;
            }
            
            // Verify payment
            $verificationResponse = $this->verifyKhaltiPayment($pidx, $secretKey, $isTestMode);
            
            if ($verificationResponse['success']) {
                $paymentData = $verificationResponse['data'];
                $orderId = $paymentData['order_id'] ?? null;
                
                if ($orderId) {
                    // Update payment and order status
                    $this->khaltiModel->updateStatusByOrderId($orderId, 'completed', $pidx);
                    $this->orderModel->update($orderId, [
                        'payment_status' => 'paid',
                        'status' => 'confirmed'
                    ]);
                    
                    $this->logSecurityEvent($traceId, 'khalti_webhook_success', 'success', [
                        'order_id' => $orderId,
                        'pidx' => $pidx
                    ]);
                }
                
                http_response_code(200);
                echo 'OK';
            } else {
                $this->logSecurityEvent($traceId, 'khalti_webhook_failed', 'error', [
                    'pidx' => $pidx,
                    'error' => $verificationResponse['message']
                ]);
                
                http_response_code(400);
                echo 'FAILED';
            }
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'khalti_webhook_error', 'error', [
                'error' => $e->getMessage()
            ]);
            
            http_response_code(500);
            echo 'ERROR';
        }
    }
    
    /**
     * Initiate Khalti payment with API (Direct cURL call per official documentation)
     * Reference: https://docs.khalti.com/khalti-epayment/
     */
    private function initiateKhaltiPaymentSDK($data, $secretKey, $isTestMode)
    {
        try {
            // Get API endpoint based on mode
            $apiUrl = $isTestMode 
                ? 'https://dev.khalti.com/api/v2/epayment/initiate/'
                : 'https://khalti.com/api/v2/epayment/initiate/';
            
            // Prepare payload
            $payload = json_encode([
                'return_url' => $data['return_url'],
                'website_url' => $data['website_url'],
                'amount' => $data['amount'],
                'purchase_order_id' => $data['purchase_order_id'],
                'purchase_order_name' => $data['purchase_order_name'],
                'customer_info' => $data['customer_info']
            ]);
            
            // Initialize cURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . $secretKey,
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            if ($curlError) {
                error_log('Khalti API cURL Error: ' . $curlError);
                return [
                    'success' => false,
                    'message' => 'Connection error: ' . $curlError
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 200 && isset($responseData['pidx'])) {
                return [
                    'success' => true,
                    'pidx' => $responseData['pidx'] ?? '',
                    'payment_url' => $responseData['payment_url'] ?? ''
                ];
            } else {
                $errorMessage = $responseData['detail'] ?? $responseData['error_key'] ?? 'Payment initiation failed';
                error_log('Khalti API Error: ' . json_encode($responseData));
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
            
        } catch (Exception $e) {
            error_log('Khalti payment initiation error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify Khalti payment with API (Direct cURL call per official documentation)
     * Reference: https://docs.khalti.com/khalti-epayment/
     */
    private function verifyKhaltiPayment($pidx, $secretKey, $isTestMode)
    {
        try {
            // Get API endpoint based on mode
            $apiUrl = $isTestMode 
                ? 'https://dev.khalti.com/api/v2/epayment/lookup/'
                : 'https://khalti.com/api/v2/epayment/lookup/';
            
            // Prepare payload
            $payload = json_encode(['pidx' => $pidx]);
            
            // Initialize cURL
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Key ' . $secretKey,
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            if ($curlError) {
                error_log('Khalti lookup cURL Error: ' . $curlError);
                return [
                    'success' => false,
                    'message' => 'Connection error: ' . $curlError
                ];
            }
            
            $responseData = json_decode($response, true);
            
            if ($httpCode === 200 && isset($responseData['pidx'])) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            } else {
                $errorMessage = $responseData['detail'] ?? $responseData['error_key'] ?? 'Payment verification failed';
                error_log('Khalti lookup API Error: ' . json_encode($responseData));
                return [
                    'success' => false,
                    'message' => $errorMessage
                ];
            }
            
        } catch (Exception $e) {
            error_log('Khalti payment verification error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Resolve proper Khalti secret based on mode
     */
    private function resolveKhaltiSecret(array $parameters, bool $isTestMode)
    {
        if ($isTestMode) {
            return $parameters['test_secret_key'] 
                ?? getenv('KHALTI_TEST_SECRET_KEY') 
                ?? $parameters['secret_key'] 
                ?? getenv('KHALTI_SECRET_KEY');
        }

        return $parameters['secret_key'] ?? getenv('KHALTI_SECRET_KEY');
    }
}

