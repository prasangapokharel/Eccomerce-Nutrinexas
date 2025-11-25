<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Database;
use App\Models\GatewayTransaction;
use App\Models\PaymentGateway;
use App\Models\KhaltiPayment;
use App\Models\EsewaPayment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use App\Core\Security;
use App\Services\Payments\OmnipayGatewayManager;
use Exception;
use KhaltiSDK\Khalti;
use KhaltiSDK\Exceptions\ApiException;
use KhaltiSDK\Exceptions\ValidationException;

class PaymentController extends Controller
{
    private $gatewayModel;
    private $khaltiModel;
    private $esewaModel;
    private $orderModel;
    private $productModel;
    private $cartModel;
    private $gatewayTransactionModel;
    private $omnipayManager;
    private $security;
    private $db;
    
    public function __construct()
    {
        parent::__construct();
        $this->gatewayModel = new PaymentGateway();
        $this->khaltiModel = new KhaltiPayment();
        $this->esewaModel = new EsewaPayment();
        $this->orderModel = new Order();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
        $this->gatewayTransactionModel = new GatewayTransaction();
        $this->security = Security::getInstance();
        $this->db = Database::getInstance();
        $this->omnipayManager = new OmnipayGatewayManager($this->gatewayModel, $this->gatewayTransactionModel);
        
        // Set security headers
        $this->security->setSecurityHeaders();
    }

    private function isOmnipayGateway(?array $gateway): bool
    {
        if (!$gateway) {
            return false;
        }

        if (!empty($gateway['type']) && strtolower($gateway['type']) === 'omnipay') {
            return true;
        }

        $parameters = json_decode($gateway['parameters'] ?? '[]', true);
        return isset($parameters['driver']);
    }

    private function finalizeOmnipaySuccess(array $order, ?string $reference, string $gatewaySlug, string $traceId): void
    {
        $this->orderModel->update($order['id'], [
            'payment_status' => 'paid',
            'status' => 'confirmed'
        ]);

        if (!empty($order['user_id'])) {
            $this->cartModel->clearCart($order['user_id']);
        }

        if (isset($_SESSION['applied_coupon'])) {
            unset($_SESSION['applied_coupon']);
        }

        $this->logSecurityEvent($traceId, 'omnipay_payment_completed', 'success', [
            'order_id' => $order['id'],
            'gateway' => $gatewaySlug,
            'reference' => $reference
        ]);
    }
    
    /**
     * Initiate Khalti payment
     */
    public function initiateKhalti($orderId)
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
            
            // Prepare payment data
            $paymentData = [
                'return_url' => URLROOT . '/payment/khalti/success/' . $orderId,
                'website_url' => URLROOT,
                'amount' => (int)($order['total_amount'] * 100), // Convert to paisa
                'purchase_order_id' => $order['invoice'],
                'purchase_order_name' => 'Order #' . $order['invoice'],
                'customer_info' => [
                    'name' => $order['customer_name'] ?? 'Customer',
                    'email' => $order['customer_email'] ?? '',
                    'phone' => $order['phone'] ?? ''
                ]
            ];
            
            // Create Khalti payment record
            $khaltiPaymentId = $this->khaltiModel->createPayment([
                'user_id' => $order['user_id'],
                'order_id' => $orderId,
                'amount' => $order['total_amount'],
                'status' => 'initiated'
            ]);
            
            if (!$khaltiPaymentId) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create payment record']);
                return;
            }
            
            // Initiate payment with Khalti SDK
            $khaltiResponse = $this->initiateKhaltiPaymentSDK($paymentData, $secretKey, $isTestMode);
            
            if ($khaltiResponse['success']) {
                // Update payment record with pidx
                $this->khaltiModel->updateStatusByOrderId($orderId, 'pending', $khaltiResponse['pidx']);
                
                $this->logSecurityEvent($traceId, 'khalti_payment_initiated', 'success', [
                    'order_id' => $orderId,
                    'pidx' => $khaltiResponse['pidx']
                ]);
                
                $this->jsonResponse([
                    'success' => true,
                    'payment_url' => $khaltiResponse['payment_url'],
                    'pidx' => $khaltiResponse['pidx']
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
    public function verifyKhalti()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        $traceId = $this->security->generateTraceId();
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
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
    public function khaltiReturn($orderId)
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
                                
                                // Clear cart
                                if (!empty($order['user_id']) && is_numeric($order['user_id'])) {
                                    $this->cartModel->clearCart($order['user_id']);
                                }
                                if (isset($_SESSION['applied_coupon'])) {
                                    unset($_SESSION['applied_coupon']);
                                }
                                
                                // Send post-purchase email
                                try {
                                    $emailService = new \App\Services\EmailAutomationService();
                                    $emailService->sendPostPurchaseEmail($orderId);
                                } catch (Exception $e) {
                                    error_log('Post-purchase email error: ' . $e->getMessage());
                                }
                                
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
                
                $this->khaltiPaymentModel->updateStatusByOrderId($orderId, 'completed', $pidx);
                
                // Clear cart
                if (!empty($order['user_id']) && is_numeric($order['user_id'])) {
                    $this->cartModel->clearCart($order['user_id']);
                }
                if (isset($_SESSION['applied_coupon'])) {
                    unset($_SESSION['applied_coupon']);
                }
                
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
     * Initiate a payment using an Omnipay gateway
     */
    public function initiateOmnipay($slug, $orderId)
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }

        $traceId = $this->security->generateTraceId();

        try {
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }

            $gateway = $this->gatewayModel->getBySlug($slug);
            if (!$this->isOmnipayGateway($gateway)) {
                $this->jsonResponse(['success' => false, 'message' => 'Gateway is not configured for Omnipay']);
                return;
            }

            $session = $this->omnipayManager->initiatePurchase($gateway, [
                'order_id' => $orderId,
                'amount' => $order['total_amount'],
                'transactionId' => $order['invoice'] ?? $orderId,
                'description' => 'Order #' . ($order['invoice'] ?? $orderId),
                'returnUrl' => URLROOT . "/payment/omnipay/return/{$slug}/{$orderId}",
                'cancelUrl' => URLROOT . "/payment/omnipay/cancel/{$slug}/{$orderId}",
                'metadata' => [
                    'order_id' => $orderId,
                    'user_id' => $order['user_id'],
                    'invoice' => $order['invoice'] ?? null
                ]
            ]);

            if ($session->isRedirect()) {
                $this->logSecurityEvent($traceId, 'omnipay_payment_redirect', 'success', [
                    'order_id' => $orderId,
                    'gateway' => $slug,
                    'reference' => $session->getTransactionReference()
                ]);

                $this->jsonResponse([
                    'success' => true,
                    'redirect' => [
                        'url' => $session->getRedirectUrl(),
                        'method' => $session->getRedirectMethod(),
                        'data' => $session->getRedirectData()
                    ],
                    'transaction_reference' => $session->getTransactionReference()
                ]);
                return;
            }

            if ($session->isSuccessful()) {
                $this->finalizeOmnipaySuccess($order, $session->getTransactionReference(), $slug, $traceId);
                $this->jsonResponse([
                    'success' => true,
                    'status' => 'completed',
                    'redirect' => URLROOT . '/checkout/success/' . $orderId
                ]);
                return;
            }

            $this->jsonResponse([
                'success' => false,
                'message' => $session->getMessage() ?? 'Unable to initiate payment'
            ]);
        } catch (\Throwable $e) {
            $this->logSecurityEvent($traceId, 'omnipay_payment_error', 'error', [
                'order_id' => $orderId,
                'gateway' => $slug,
                'error' => $e->getMessage()
            ]);

            $this->jsonResponse(['success' => false, 'message' => 'Payment initiation failed.']);
        }
    }

    /**
     * Complete Omnipay payment (return URL)
     */
    public function completeOmnipay($slug, $orderId)
    {
        $traceId = $this->security->generateTraceId();
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('checkout');
            return;
        }

        $gateway = $this->gatewayModel->getBySlug($slug);
        if (!$this->isOmnipayGateway($gateway)) {
            $this->setFlash('error', 'Gateway not available');
            $this->redirect('checkout');
            return;
        }

        try {
            $response = $this->omnipayManager->completePurchase($gateway, $_REQUEST);
            if ($response->isSuccessful()) {
                $reference = $response->getTransactionReference();
                $this->finalizeOmnipaySuccess($order, $reference, $slug, $traceId);
                $this->setFlash('success', 'Payment completed successfully!');
                $this->redirect('checkout/success/' . $orderId);
                return;
            }

            $this->logSecurityEvent($traceId, 'omnipay_payment_failed', 'error', [
                'order_id' => $orderId,
                'gateway' => $slug,
                'message' => $response->getMessage()
            ]);

            $this->setFlash('error', $response->getMessage() ?? 'Payment could not be completed');
            $this->redirect('checkout');
        } catch (\Throwable $e) {
            $this->logSecurityEvent($traceId, 'omnipay_payment_error', 'error', [
                'order_id' => $orderId,
                'gateway' => $slug,
                'error' => $e->getMessage()
            ]);

            $this->setFlash('error', 'Payment verification failed');
            $this->redirect('checkout');
        }
    }

    /**
     * Handle Omnipay cancellation callback
     */
    public function cancelOmnipay($slug, $orderId)
    {
        $traceId = $this->security->generateTraceId();
        $this->logSecurityEvent($traceId, 'omnipay_payment_cancelled', 'info', [
            'order_id' => $orderId,
            'gateway' => $slug,
            'query' => $_GET
        ]);

        $this->setFlash('error', 'Payment was cancelled');
        $this->redirect('checkout');
    }

    /**
     * Handle Omnipay webhooks (optional)
     */
    public function webhookOmnipay($slug)
    {
        $traceId = $this->security->generateTraceId();
        $gateway = $this->gatewayModel->getBySlug($slug);
        if (!$this->isOmnipayGateway($gateway)) {
            http_response_code(404);
            echo 'Gateway not configured';
            return;
        }

        $payload = $_POST;
        if (empty($payload)) {
            $body = file_get_contents('php://input');
            $decoded = json_decode($body, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        try {
            $response = $this->omnipayManager->completePurchase($gateway, $payload);
            if ($response->isSuccessful()) {
                http_response_code(200);
                echo 'OK';
                return;
            }

            $this->logSecurityEvent($traceId, 'omnipay_webhook_failed', 'error', [
                'gateway' => $slug,
                'payload' => $payload,
                'message' => $response->getMessage()
            ]);

            http_response_code(400);
            echo 'FAILED';
        } catch (\Throwable $e) {
            $this->logSecurityEvent($traceId, 'omnipay_webhook_error', 'error', [
                'gateway' => $slug,
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo 'ERROR';
        }
    }
    
    /**
     * Initiate eSewa payment
     */
    public function initiateEsewa($orderId)
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
            
            // Get eSewa gateway configuration
            $esewaGateway = $this->gatewayModel->getBySlug('esewa');
            if (!$esewaGateway) {
                $this->jsonResponse(['success' => false, 'message' => 'eSewa payment not available']);
                return;
            }
            
            $parameters = json_decode($esewaGateway['parameters'], true);
            $merchantId = $parameters['merchant_id'] ?? getenv('ESEWA_MERCHANT_ID');
            $secretKey = $parameters['secret_key'] ?? getenv('ESEWA_SECRET_KEY');
            $isTestMode = $esewaGateway['is_test_mode'] ?? false;
            
            if (!$merchantId || !$secretKey) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error']);
                return;
            }
            
            // Prepare eSewa payment data
            $paymentData = [
                'amt' => $order['total_amount'],
                'psc' => 0,
                'pdc' => 0,
                'txAmt' => 0,
                'tAmt' => $order['total_amount'],
                'pid' => $order['invoice'],
                'scd' => $merchantId,
                'su' => ASSETS_URL . '/payment/esewa/success/' . $orderId,
                'fu' => ASSETS_URL . '/payment/esewa/failure/' . $orderId
            ];
            
            // Generate signature
            $signature = $this->generateEsewaSignature($paymentData, $secretKey);
            $paymentData['signature'] = $signature;
            
            $this->logSecurityEvent($traceId, 'esewa_payment_initiated', 'success', [
                'order_id' => $orderId
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'payment_data' => $paymentData,
                'payment_url' => $isTestMode ? 'https://uat.esewa.com.np/epay/main' : 'https://esewa.com.np/epay/main'
            ]);
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'esewa_payment_error', 'error', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Payment initiation failed']);
        }
    }
    
    /**
     * Initiate Khalti payment with API
     */
    private function initiateKhaltiPaymentSDK($data, $secretKey, $isTestMode)
    {
        try {
            // Initialize Khalti SDK
            $khalti = new Khalti([
                'environment' => $isTestMode ? 'sandbox' : 'live',
                'secretKey' => $secretKey,
                'enableLogging' => true
            ]);
            
            // Initiate payment using SDK
            $response = $khalti->ePayment()->initiate([
                'return_url' => $data['return_url'],
                'website_url' => $data['website_url'],
                'amount' => $data['amount'],
                'purchase_order_id' => $data['purchase_order_id'],
                'purchase_order_name' => $data['purchase_order_name'],
                'customer_info' => $data['customer_info']
            ]);
            
            return [
                'success' => true,
                'pidx' => $response['pidx'] ?? '',
                'payment_url' => $response['payment_url'] ?? ''
            ];
            
        } catch (ValidationException $e) {
            return [
                'success' => false,
                'message' => 'Validation Error: ' . $e->getMessage()
            ];
        } catch (ApiException $e) {
            return [
                'success' => false,
                'message' => 'API Error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment initiation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verify Khalti payment with API
     */
    private function verifyKhaltiPayment($pidx, $secretKey, $isTestMode)
    {
        try {
            // Initialize Khalti SDK
            $khalti = new Khalti([
                'environment' => $isTestMode ? 'sandbox' : 'live',
                'secretKey' => $secretKey,
                'enableLogging' => true
            ]);
            
            // Verify payment using SDK
            $result = $khalti->ePayment()->verify($pidx);
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (ApiException $e) {
            return [
                'success' => false,
                'message' => 'API Error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Normalize gateway parameter payload
     */
    private function extractGatewayParameters($gateway)
    {
        $parameters = $gateway['parameters'] ?? [];
        if (is_string($parameters)) {
            $decoded = json_decode($parameters, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parameters = $decoded;
            }
        }
        return is_array($parameters) ? $parameters : [];
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
    
    /**
     * Generate eSewa signature
     */
    private function generateEsewaSignature($data, $secretKey)
    {
        $message = $data['amt'] . ',' . $data['pdc'] . ',' . $data['psc'] . ',' . $data['txAmt'] . ',' . $data['tAmt'] . ',' . $data['pid'] . ',' . $data['scd'] . ',' . $data['su'] . ',' . $data['fu'];
        return hash_hmac('sha256', $message, $secretKey);
    }
    
    /**
     * eSewa payment success handler
     */
    public function esewaSuccess($orderId)
    {
        error_log("=== ESEWA SUCCESS CALLBACK START ===");
        error_log("Order ID: $orderId");
        error_log("POST Data: " . json_encode($_POST));
        error_log("GET Data: " . json_encode($_GET));
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            error_log("Order not found: $orderId");
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders');
            return;
        }
        
        // Save payment data to database first - handle both GET and POST data
        $responseData = !empty($_POST) ? $_POST : $_GET;
        
        // eSewa sends data as base64 encoded JSON in 'data' parameter
        $esewaData = [];
        if (isset($responseData['data'])) {
            $decodedData = base64_decode($responseData['data']);
            $esewaData = json_decode($decodedData, true);
            error_log("eSewa decoded data: " . json_encode($esewaData));
        }
        
        // Extract transaction details from eSewa response
        $transactionId = $esewaData['transaction_uuid'] ?? $responseData['transaction_uuid'] ?? null;
        $referenceId = $esewaData['transaction_code'] ?? $responseData['refId'] ?? $responseData['ref_id'] ?? null;
        $paymentStatus = $esewaData['status'] ?? 'pending';
        
        $paymentData = [
            'order_id' => $orderId,
            'user_id' => $order['user_id'],
            'amount' => $order['total_amount'],
            'transaction_id' => $transactionId,
            'reference_id' => $referenceId,
            'status' => $paymentStatus === 'COMPLETE' ? 'completed' : 'pending',
            'response_data' => json_encode($responseData)
        ];
        
        $paymentId = $this->esewaModel->createPayment($paymentData);
        error_log("Payment record created with ID: $paymentId");
        
        // Verify eSewa payment - use decoded eSewa data if available
        $verificationData = !empty($esewaData) ? $esewaData : $responseData;
        if ($this->verifyEsewaPayment($verificationData)) {
            // Update payment record
            $this->esewaModel->updatePayment($paymentId, [
                'status' => 'completed',
                'transaction_id' => $transactionId,
                'reference_id' => $referenceId
            ]);
            
            // Update order status
            $this->orderModel->update($orderId, [
                'payment_status' => 'paid',
                'status' => 'confirmed'
            ]);
            
            // Clear cart and applied coupon after successful payment
            $this->cartModel->clearCart($order['user_id']);
            if (isset($_SESSION['applied_coupon'])) {
                unset($_SESSION['applied_coupon']);
            }
            
            error_log("=== ESEWA SUCCESS CALLBACK SUCCESS ===");
            $this->setFlash('success', 'Payment completed successfully!');
            $this->redirect('checkout/success/' . $orderId);
        } else {
            // Update payment record as failed
            $this->esewaModel->updatePayment($paymentId, [
                'status' => 'failed'
            ]);
            
            error_log("=== ESEWA SUCCESS CALLBACK FAILED ===");
            $this->setFlash('error', 'Payment verification failed');
            $this->redirect('checkout');
        }
    }
    
    /**
     * eSewa payment failure handler
     */
    public function esewaFailure($orderId)
    {
        error_log("=== ESEWA FAILURE CALLBACK START ===");
        error_log("Order ID: $orderId");
        error_log("POST Data: " . json_encode($_POST));
        error_log("GET Data: " . json_encode($_GET));
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        
        $order = $this->orderModel->find($orderId);
        if ($order) {
            // Save failed payment data to database - handle both GET and POST data
            $responseData = !empty($_POST) ? $_POST : $_GET;
            
            // eSewa sends data as base64 encoded JSON in 'data' parameter
            $esewaData = [];
            if (isset($responseData['data'])) {
                $decodedData = base64_decode($responseData['data']);
                $esewaData = json_decode($decodedData, true);
                error_log("eSewa failure decoded data: " . json_encode($esewaData));
            }
            
            // Extract transaction details from eSewa response
            $transactionId = $esewaData['transaction_uuid'] ?? $responseData['transaction_uuid'] ?? null;
            $referenceId = $esewaData['transaction_code'] ?? $responseData['refId'] ?? $responseData['ref_id'] ?? null;
            
            $paymentData = [
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'amount' => $order['total_amount'],
                'transaction_id' => $transactionId,
                'reference_id' => $referenceId,
                'status' => 'failed',
                'response_data' => json_encode($responseData)
            ];
            
            $this->esewaModel->createPayment($paymentData);
            error_log("Failed payment record created");

            // If order is still pending, mark cancelled and restore stock
            if (($order['status'] ?? '') === 'pending') {
                try {
                    // Restore product stock
                    $orderItems = $this->orderModel->getOrderItems($orderId);
                    foreach ($orderItems as $item) {
                        $this->productModel->updateStock($item['product_id'], $item['quantity']);
                    }

                    // Update order status
                    $this->orderModel->update($orderId, [
                        'status' => 'cancelled',
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                    error_log("Order {$orderId} marked as cancelled and stock restored (eSewa failure)");
                } catch (\Exception $e) {
                    error_log('Error cancelling order on eSewa failure: ' . $e->getMessage());
                }
            }
        }
        
        error_log("=== ESEWA FAILURE CALLBACK END ===");
        $this->setFlash('error', 'Payment was cancelled or failed');
        $this->redirect('checkout');
    }

    /**
     * Khalti payment failure handler
     */
    public function khaltiFailure()
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
     * Verify eSewa payment using official SDK
     */
    private function verifyEsewaPayment($data)
    {
        try {
        // Get eSewa gateway configuration
        $esewaGateway = $this->gatewayModel->getBySlug('esewa');
        if (!$esewaGateway) {
            return false;
        }
        
        $parameters = json_decode($esewaGateway['parameters'], true);
            $isTestMode = $esewaGateway['is_test_mode'] ?? false;
            
            // Use dynamic configuration based on test/live mode
            if ($isTestMode) {
                $merchantCode = $parameters['test_merchant_id'] ?? $parameters['merchant_id'] ?? null;
            } else {
                $merchantCode = $parameters['live_merchant_id'] ?? $parameters['merchant_id'] ?? null;
            }
            
            if (!$merchantCode) {
                return false;
            }
            
            // Extract verification parameters from the response
            $refId = $data['transaction_code'] ?? $data['refId'] ?? $data['ref_id'] ?? null;
            $oid = $data['transaction_uuid'] ?? $data['oid'] ?? null;
            $tAmt = $data['total_amount'] ?? $data['tAmt'] ?? null;
            
            error_log('eSewa verification parameters: refId=' . $refId . ', oid=' . $oid . ', tAmt=' . $tAmt);
            
            // If eSewa data shows COMPLETE status, verify signature before considering it verified
            if (isset($data['status']) && $data['status'] === 'COMPLETE') {
                // Verify signature if present
                if (isset($data['signature']) && isset($data['signed_field_names'])) {
                    $isSignatureValid = $this->verifyEsewaSignature($data, $parameters, $isTestMode);
                    if ($isSignatureValid) {
                        error_log('eSewa payment marked as COMPLETE with valid signature - verification successful');
                        return true;
                    } else {
                        error_log('eSewa payment marked as COMPLETE but signature verification failed');
                        return false;
                    }
                } else {
                    error_log('eSewa payment marked as COMPLETE but no signature data for verification');
                    // For now, accept it but log the missing signature
                    return true;
                }
            }
            
            if (!$refId || !$oid || !$tAmt) {
                error_log('eSewa verification: Missing required parameters');
                error_log('Available data keys: ' . implode(', ', array_keys($data)));
                return false;
            }
            
            // Use direct API call for verification (no SDK needed)
            $isTestMode = $esewaGateway['is_test_mode'] ?? false;
            $gatewayParams = json_decode($esewaGateway['parameters'], true);
            
            // Get dynamic URLs from database
            $statusUrl = $isTestMode ? 
                ($gatewayParams['test_status_url'] ?? 'https://rc.esewa.com.np/api/epay/transaction/status/') : 
                ($gatewayParams['live_status_url'] ?? 'https://epay.esewa.com.np/api/epay/transaction/status/');
            
            $statusUrl .= '?product_code=' . urlencode($merchantCode) . 
                         '&total_amount=' . urlencode($tAmt) . 
                         '&transaction_uuid=' . urlencode($oid);
            
            error_log('eSewa status check URL: ' . $statusUrl);
            
            // Make cURL request to eSewa status API
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $statusUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            error_log('eSewa status HTTP Code: ' . $httpCode);
            error_log('eSewa status Response: ' . $response);
            
            if ($curlError) {
                error_log('eSewa status cURL Error: ' . $curlError);
                return false;
            }
            
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('eSewa status Invalid JSON response: ' . $response);
                return false;
            }
            
            // Check payment status according to eSewa documentation
            $status = $responseData['status'] ?? '';
            $isSuccessful = false;
            
            switch ($status) {
                case 'COMPLETE':
                    $isSuccessful = true;
                    error_log('eSewa payment status: COMPLETE - Payment successful');
                    break;
                case 'PENDING':
                    error_log('eSewa payment status: PENDING - Payment initiated but not completed');
                    break;
                case 'FULL_REFUND':
                    error_log('eSewa payment status: FULL_REFUND - Payment refunded');
                    break;
                case 'PARTIAL_REFUND':
                    error_log('eSewa payment status: PARTIAL_REFUND - Partial refund');
                    break;
                case 'AMBIGUOUS':
                    error_log('eSewa payment status: AMBIGUOUS - Payment in halt state');
                    break;
                case 'NOT_FOUND':
                    error_log('eSewa payment status: NOT_FOUND - Session expired');
                    break;
                case 'CANCELED':
                    error_log('eSewa payment status: CANCELED - Payment canceled');
                    break;
                default:
                    error_log('eSewa payment status: UNKNOWN - ' . $status);
                    break;
            }
            
            error_log('eSewa verification result: ' . ($isSuccessful ? 'SUCCESS' : 'FAILED'));
            error_log('eSewa verification data: ' . json_encode($data));
            error_log('eSewa status response: ' . json_encode($responseData));
            
            return $isSuccessful;
            
        } catch (Exception $e) {
            error_log('eSewa verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify eSewa signature
     */
    private function verifyEsewaSignature($data, $parameters, $isTestMode)
    {
        try {
            // Get secret key
            $secretKey = $isTestMode ? 
                ($parameters['test_secret_key'] ?? '8gBm/:&EnhH.1/q') : 
                ($parameters['live_secret_key'] ?? '');
            
            if (empty($secretKey)) {
                error_log('eSewa signature verification: No secret key available');
                return false;
            }
            
            // Get signed field names
            $signedFieldNames = $data['signed_field_names'] ?? '';
            if (empty($signedFieldNames)) {
                error_log('eSewa signature verification: No signed field names');
                return false;
            }
            
            // Create signature data string
            $fields = explode(',', $signedFieldNames);
            $signData = '';
            foreach ($fields as $field) {
                $field = trim($field);
                if (isset($data[$field])) {
                    if (!empty($signData)) {
                        $signData .= ',';
                    }
                    $signData .= $field . '=' . $data[$field];
                }
            }
            
            error_log('eSewa signature data: ' . $signData);
            
            // Generate expected signature
            $expectedSignature = base64_encode(hash_hmac('sha256', $signData, $secretKey, true));
            $receivedSignature = $data['signature'] ?? '';
            
            error_log('eSewa expected signature: ' . $expectedSignature);
            error_log('eSewa received signature: ' . $receivedSignature);
            
            // Compare signatures
            $isValid = hash_equals($expectedSignature, $receivedSignature);
            error_log('eSewa signature verification result: ' . ($isValid ? 'VALID' : 'INVALID'));
            
            return $isValid;
            
        } catch (Exception $e) {
            error_log('eSewa signature verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * AJAX endpoint to check eSewa payment status
     */
    public function checkEsewaPaymentStatus()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        
        $orderId = $_POST['order_id'] ?? null;
        if (!$orderId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order ID required']);
            return;
        }
        
        try {
            // Get payment record from database
            $payment = $this->esewaModel->findByOrderId($orderId);
            if (!$payment) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment record not found']);
                return;
            }
            
            // If already completed, return success
            if ($payment['status'] === 'completed') {
                $this->jsonResponse([
                    'success' => true, 
                    'status' => 'completed',
                    'message' => 'Payment already completed'
                ]);
                return;
            }
            
            // Check status with eSewa API
            $gateway = $this->gatewayModel->getBySlug('esewa');
            if (!$gateway) {
                $this->jsonResponse(['success' => false, 'message' => 'eSewa gateway not configured']);
                return;
            }
            
            $gatewayParams = json_decode($gateway['parameters'], true);
            $isTestMode = $gateway['is_test_mode'] ?? false;
            $merchantCode = $isTestMode ? 
                ($gatewayParams['test_merchant_id'] ?? 'EPAYTEST') : 
                ($gatewayParams['live_merchant_id'] ?? '');
            
            $statusUrl = $isTestMode ? 
                'https://uat.esewa.com.np/api/epay/transaction/status/' : 
                'https://epay.esewa.com.np/api/epay/transaction/status/';
            
            $statusUrl .= '?product_code=' . urlencode($merchantCode) . 
                         '&total_amount=' . urlencode($payment['amount']) . 
                         '&transaction_uuid=' . urlencode($payment['transaction_id']);
            
            // Make cURL request to eSewa status API
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $statusUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            if ($curlError) {
                $this->jsonResponse(['success' => false, 'message' => 'Status check failed: ' . $curlError]);
                return;
            }
            
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid response from eSewa']);
                return;
            }
            
            // Update payment status based on response
            $newStatus = 'pending';
            if (isset($responseData['status'])) {
                switch ($responseData['status']) {
                    case 'COMPLETE':
                    case 'SUCCESS':
                        $newStatus = 'completed';
                        break;
                    case 'FAILED':
                    case 'CANCELLED':
                        $newStatus = 'failed';
                        break;
                    default:
                        $newStatus = 'pending';
                }
            }
            
            // Update payment record
            $this->esewaModel->updatePayment($payment['id'], [
                'status' => $newStatus,
                'response_data' => json_encode($responseData)
            ]);
            
            // If completed, update order status
            if ($newStatus === 'completed') {
                $this->orderModel->update($orderId, [
                    'payment_status' => 'completed',
                    'status' => 'confirmed'
                ]);
            }
            
            $this->jsonResponse([
                'success' => true,
                'status' => $newStatus,
                'esewa_response' => $responseData,
                'message' => 'Status updated successfully'
            ]);
            
        } catch (Exception $e) {
            error_log('eSewa status check error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Status check failed']);
        }
    }

    /**
     * Check eSewa transaction status
     */
    public function checkEsewaStatus()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        try {
            $input = $this->getJsonInput();
            $transactionUuid = $input['transaction_uuid'] ?? '';
            $totalAmount = $input['total_amount'] ?? 0;
            
            if (empty($transactionUuid) || empty($totalAmount)) {
                $this->jsonResponse(['success' => false, 'message' => 'Missing required parameters']);
                return;
            }
            
            // Get eSewa gateway configuration
            $esewaGateway = $this->gatewayModel->getBySlug('esewa');
            if (!$esewaGateway) {
                $this->jsonResponse(['success' => false, 'message' => 'eSewa gateway not configured']);
                return;
            }
            
            $parameters = json_decode($esewaGateway['parameters'], true);
            $isTestMode = $esewaGateway['is_test_mode'] ?? false;
            
            // Use dynamic configuration based on test/live mode
            if ($isTestMode) {
                $productCode = $parameters['test_merchant_id'] ?? $parameters['merchant_id'] ?? 'EPAYTEST';
                $statusUrl = $parameters['test_status_url'] ?? 'https://rc.esewa.com.np/api/epay/transaction/status/';
            } else {
                $productCode = $parameters['live_merchant_id'] ?? $parameters['merchant_id'] ?? '';
                $statusUrl = $parameters['live_status_url'] ?? 'https://epay.esewa.com.np/api/epay/transaction/status/';
            }
            
            // Build the complete status check URL
            $statusUrl = $statusUrl . "?product_code={$productCode}&total_amount={$totalAmount}&transaction_uuid={$transactionUuid}";
            
            $response = file_get_contents($statusUrl);
            $statusData = json_decode($response, true);
            
            if ($statusData && isset($statusData['status'])) {
                $this->jsonResponse([
                    'success' => true,
                    'status' => $statusData['status'],
                    'ref_id' => $statusData['ref_id'] ?? null,
                    'data' => $statusData
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Unable to check transaction status']);
            }
            
        } catch (Exception $e) {
            error_log('eSewa status check error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Status check failed']);
        }
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($traceId, $action, $status, $data = [])
    {
        $this->db->query(
            "INSERT INTO payment_security_logs (trace_id, user_id, action, status, ip_address, user_agent, request_data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $traceId,
                Session::get('user_id'),
                $action,
                $status,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($data)
            ]
        );
    }
}
