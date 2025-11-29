<?php

namespace App\Controllers\Payment;

use App\Models\EsewaPayment;
use App\Controllers\Security\SecurityController;
use Exception;

/**
 * eSewa Payment Controller
 * Handles all eSewa payment gateway operations
 */
class EsewaController extends PaymentBaseController
{
    private $esewaModel;
    private $securityController;
    
    public function __construct()
    {
        parent::__construct();
        $this->esewaModel = new EsewaPayment();
        $this->securityController = new SecurityController();
    }
    
    /**
     * Initiate eSewa payment
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
            
            // Get eSewa gateway configuration
            $esewaGateway = $this->gatewayModel->getBySlug('esewa');
            if (!$esewaGateway) {
                $this->jsonResponse(['success' => false, 'message' => 'eSewa payment not available']);
                return;
            }
            
            $parameters = $this->extractGatewayParameters($esewaGateway);
            $isTestMode = (bool)($esewaGateway['is_test_mode'] ?? false);
            $merchantId = $this->resolveEsewaMerchantId($parameters, $isTestMode);
            $secretKey = $this->resolveEsewaSecretKey($parameters, $isTestMode);
            
            if (!$merchantId || !$secretKey) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error']);
                return;
            }
            
            // Calculate order totals
            $orderItemModel = new \App\Models\OrderItem();
            $orderItems = $orderItemModel->getByOrderId($orderId);
            
            $subtotal = 0;
            foreach ($orderItems as $item) {
                $itemPrice = ($item['price'] ?? 0);
                $itemQuantity = ($item['quantity'] ?? 1);
                $subtotal += $itemPrice * $itemQuantity;
            }
            
            $discountAmount = round($order['discount_amount'] ?? 0, 2);
            $deliveryFee = round($order['delivery_fee'] ?? 0, 2);
            $serviceCharge = round($order['service_charge'] ?? 0, 2);
            $taxRate = (new \App\Models\Setting())->get('tax_rate', 12);
            
            $totals = \App\Services\OrderCalculationService::calculateTotals(
                $subtotal,
                $discountAmount,
                $deliveryFee,
                $taxRate
            );
            
            $taxAmount = round($totals['tax'], 2);
            $totalAmount = round($totals['total'], 2);
            $esewaSubtotal = max(0, round($totalAmount - $taxAmount - $serviceCharge - $deliveryFee, 2));
            
            // Security: Fraud detection and secure payment processing
            try {
                $securePayment = $this->securityController->processSecurePayment([
                    'order_id' => $orderId,
                    'amount' => $totalAmount,
                    'method' => 'esewa',
                    'items' => $orderItems
                ], $order['user_id']);
                
                $traceId = $securePayment['trace_id'];
            } catch (Exception $e) {
                error_log("eSewa security check failed: " . $e->getMessage());
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Payment security check failed. Please contact support.',
                    'timestamp' => time()
                ]);
                return;
            }
            
            // Generate unique transaction UUID
            $transactionUuid = 'ORDER-' . $orderId . '-' . time();
            
            // Prepare eSewa payment data
            $paymentData = [
                'amount' => number_format($esewaSubtotal, 2, '.', ''),
                'tax_amount' => number_format($taxAmount, 2, '.', ''),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'transaction_uuid' => $transactionUuid,
                'product_code' => $merchantId,
                'product_service_charge' => number_format($serviceCharge, 2, '.', ''),
                'product_delivery_charge' => number_format($deliveryFee, 2, '.', ''),
                'success_url' => URLROOT . '/payment/esewa/success/' . $orderId,
                'failure_url' => URLROOT . '/payment/esewa/failure/' . $orderId,
                'signed_field_names' => 'total_amount,transaction_uuid,product_code'
            ];
            
            // Generate signature using HMAC SHA256
            $signData = "total_amount={$paymentData['total_amount']},transaction_uuid={$paymentData['transaction_uuid']},product_code={$paymentData['product_code']}";
            $rawSign = hash_hmac('sha256', $signData, $secretKey, true);
            $signature = base64_encode($rawSign);
            $paymentData['signature'] = $signature;
            
            // Create eSewa payment record
            $esewaPaymentId = $this->esewaModel->createPayment([
                'order_id' => $orderId,
                'user_id' => $order['user_id'],
                'amount' => $totalAmount,
                'transaction_id' => $transactionUuid,
                'status' => 'initiated'
            ]);
            
            if (!$esewaPaymentId) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to create payment record']);
                return;
            }
            
            $this->logSecurityEvent($traceId, 'esewa_payment_initiated', 'success', [
                'order_id' => $orderId,
                'transaction_uuid' => $transactionUuid
            ]);
            
            // Get payment URL from configuration
            $paymentUrlKey = $isTestMode ? 'test_payment_url' : 'live_payment_url';
            $defaultUrl = $isTestMode 
                ? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'
                : 'https://epay.esewa.com.np/api/epay/main/v2/form';
            $paymentUrl = $parameters[$paymentUrlKey] ?? $defaultUrl;
            
            $this->jsonResponse([
                'success' => true,
                'payment_data' => $paymentData,
                'payment_url' => $paymentUrl,
                'mode' => $isTestMode ? 'test' : 'live'
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
     * eSewa payment success handler
     */
    public function success($orderId)
    {
        error_log("=== ESEWA SUCCESS CALLBACK START ===");
        error_log("Order ID: $orderId");
        error_log("POST Data: " . json_encode($_POST));
        error_log("GET Data: " . json_encode($_GET));
        
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            error_log("Order not found: $orderId");
            $this->setFlash('error', 'Order not found');
            $this->redirect('orders');
            return;
        }
        
        // Save payment data to database - handle both GET and POST data
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
        
        // Verify eSewa payment
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
            
            // Handle digital products
            $digitalController = new \App\Controllers\Product\DigitalProductController();
            $digitalController->processDigitalProductsAfterPayment($orderId);
            
            // Send payment confirmation SMS
            try {
                $smsController = new \App\Controllers\Sms\SmsOrderController();
                $smsController->sendPaymentConfirmationSms($orderId);
            } catch (\Exception $e) {
                error_log("Esewa: Error sending payment confirmation SMS: " . $e->getMessage());
            }
            
            // Clear cart and send email
            $this->clearCartAndCoupon($order['user_id']);
            $this->sendPostPurchaseEmail($orderId);
            
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
    public function failure($orderId)
    {
        error_log("=== ESEWA FAILURE CALLBACK START ===");
        error_log("Order ID: $orderId");
        error_log("POST Data: " . json_encode($_POST));
        error_log("GET Data: " . json_encode($_GET));
        
        $order = $this->orderModel->find($orderId);
        if ($order) {
            // Save failed payment data to database
            $responseData = !empty($_POST) ? $_POST : $_GET;
            
            $esewaData = [];
            if (isset($responseData['data'])) {
                $decodedData = base64_decode($responseData['data']);
                $esewaData = json_decode($decodedData, true);
                error_log("eSewa failure decoded data: " . json_encode($esewaData));
            }
            
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
     * Verify eSewa payment
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
            
            $parameters = $this->extractGatewayParameters($esewaGateway);
            $isTestMode = (bool)($esewaGateway['is_test_mode'] ?? false);
            $merchantCode = $this->resolveEsewaMerchantId($parameters, $isTestMode);
            
            if (!$merchantCode) {
                $this->jsonResponse(['success' => false, 'message' => 'Payment configuration error']);
                return;
            }
            
            // Check status with eSewa API
            $statusUrl = $this->getEsewaStatusUrl($parameters, $isTestMode);
            $statusUrl .= '?product_code=' . urlencode($merchantCode) . 
                         '&total_amount=' . urlencode($totalAmount) . 
                         '&transaction_uuid=' . urlencode($transactionUuid);
            
            $response = file_get_contents($statusUrl);
            $statusData = json_decode($response, true);
            
            if ($statusData && isset($statusData['status'])) {
                $isSuccessful = ($statusData['status'] === 'COMPLETE');
                
                if ($isSuccessful) {
                    // Find order by transaction UUID
                    $payment = $this->esewaModel->findByTransactionId($transactionUuid);
                    if ($payment && $payment['order_id']) {
                        // Update payment and order status
                        $this->esewaModel->updatePayment($payment['id'], [
                            'status' => 'completed',
                            'reference_id' => $statusData['ref_id'] ?? null
                        ]);
                        
                        $this->orderModel->update($payment['order_id'], [
                            'payment_status' => 'paid',
                            'status' => 'confirmed'
                        ]);
                        
                        $this->logSecurityEvent($traceId, 'esewa_payment_verified', 'success', [
                            'order_id' => $payment['order_id'],
                            'transaction_uuid' => $transactionUuid
                        ]);
                    }
                }
                
                $this->jsonResponse([
                    'success' => $isSuccessful,
                    'status' => $statusData['status'],
                    'ref_id' => $statusData['ref_id'] ?? null,
                    'data' => $statusData
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Unable to check transaction status']);
            }
            
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'esewa_verification_error', 'error', [
                'error' => $e->getMessage()
            ]);
            
            $this->jsonResponse(['success' => false, 'message' => 'Payment verification failed']);
        }
    }
    
    /**
     * Check eSewa payment status (AJAX endpoint)
     */
    public function checkStatus()
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
            
            $gatewayParams = $this->extractGatewayParameters($gateway);
            $isTestMode = (bool)($gateway['is_test_mode'] ?? false);
            $merchantCode = $this->resolveEsewaMerchantId($gatewayParams, $isTestMode);
            
            $statusUrl = $this->getEsewaStatusUrl($gatewayParams, $isTestMode);
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
     * Verify eSewa payment using official API
     */
    private function verifyEsewaPayment($data)
    {
        try {
            // Get eSewa gateway configuration
            $esewaGateway = $this->gatewayModel->getBySlug('esewa');
            if (!$esewaGateway) {
                return false;
            }
            
            $parameters = $this->extractGatewayParameters($esewaGateway);
            $isTestMode = (bool)($esewaGateway['is_test_mode'] ?? false);
            $merchantCode = $this->resolveEsewaMerchantId($parameters, $isTestMode);
            
            if (!$merchantCode) {
                return false;
            }
            
            // Extract verification parameters from the response
            $refId = $data['transaction_code'] ?? $data['refId'] ?? $data['ref_id'] ?? null;
            $oid = $data['transaction_uuid'] ?? $data['oid'] ?? null;
            $tAmt = $data['total_amount'] ?? $data['tAmt'] ?? null;
            
            error_log('eSewa verification parameters: refId=' . $refId . ', oid=' . $oid . ', tAmt=' . $tAmt);
            
            // If eSewa data shows COMPLETE status, verify signature
            if (isset($data['status']) && $data['status'] === 'COMPLETE') {
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
                    return true;
                }
            }
            
            if (!$refId || !$oid || !$tAmt) {
                error_log('eSewa verification: Missing required parameters');
                return false;
            }
            
            // Use direct API call for verification
            $statusUrl = $this->getEsewaStatusUrl($parameters, $isTestMode);
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
            $isSuccessful = ($status === 'COMPLETE');
            
            error_log('eSewa verification result: ' . ($isSuccessful ? 'SUCCESS' : 'FAILED'));
            
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
            $secretKey = $this->resolveEsewaSecretKey($parameters, $isTestMode);
            
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
     * Resolve proper eSewa merchant ID based on mode
     */
    private function resolveEsewaMerchantId(array $parameters, bool $isTestMode)
    {
        if ($isTestMode) {
            return $parameters['test_merchant_id'] 
                ?? getenv('ESEWA_TEST_MERCHANT_ID') 
                ?? $parameters['merchant_id'] 
                ?? getenv('ESEWA_MERCHANT_ID');
        }

        return $parameters['live_merchant_id'] 
            ?? $parameters['merchant_id'] 
            ?? getenv('ESEWA_MERCHANT_ID');
    }
    
    /**
     * Resolve proper eSewa secret key based on mode
     */
    private function resolveEsewaSecretKey(array $parameters, bool $isTestMode)
    {
        if ($isTestMode) {
            return $parameters['test_secret_key'] 
                ?? getenv('ESEWA_TEST_SECRET_KEY') 
                ?? $parameters['secret_key'] 
                ?? getenv('ESEWA_SECRET_KEY');
        }

        return $parameters['live_secret_key'] 
            ?? $parameters['secret_key'] 
            ?? getenv('ESEWA_SECRET_KEY');
    }
    
    /**
     * Get eSewa status URL based on mode
     */
    private function getEsewaStatusUrl(array $parameters, bool $isTestMode)
    {
        if ($isTestMode) {
            return $parameters['test_status_url'] 
                ?? 'https://rc.esewa.com.np/api/epay/transaction/status/';
        }

        return $parameters['live_status_url'] 
            ?? 'https://epay.esewa.com.np/api/epay/transaction/status/';
    }
    
    /**
     * eSewa webhook handler
     */
    public function webhook()
    {
        $traceId = $this->security->generateTraceId();
        
        try {
            $input = $this->getJsonInput();
            $transactionUuid = $input['transaction_uuid'] ?? $input['oid'] ?? '';
            
            if (empty($transactionUuid)) {
                http_response_code(400);
                echo 'Missing transaction_uuid';
                return;
            }
            
            // Get eSewa gateway configuration
            $esewaGateway = $this->gatewayModel->getBySlug('esewa');
            if (!$esewaGateway) {
                http_response_code(404);
                echo 'Gateway not configured';
                return;
            }
            
            $parameters = $this->extractGatewayParameters($esewaGateway);
            $isTestMode = (bool)($esewaGateway['is_test_mode'] ?? false);
            
            // Find payment by transaction UUID
            $payment = $this->esewaModel->findByTransactionId($transactionUuid);
            if (!$payment) {
                http_response_code(404);
                echo 'Payment not found';
                return;
            }
            
            // Verify payment
            $verificationResponse = $this->verifyEsewaPayment($input);
            
            if ($verificationResponse) {
                // Update payment and order status
                $this->esewaModel->updatePayment($payment['id'], [
                    'status' => 'completed',
                    'reference_id' => $input['transaction_code'] ?? $input['refId'] ?? null
                ]);
                
                $this->orderModel->update($payment['order_id'], [
                    'payment_status' => 'paid',
                    'status' => 'confirmed'
                ]);
                
                $this->logSecurityEvent($traceId, 'esewa_webhook_success', 'success', [
                    'order_id' => $payment['order_id'],
                    'transaction_uuid' => $transactionUuid
                ]);
                
                http_response_code(200);
                echo 'OK';
            } else {
                $this->logSecurityEvent($traceId, 'esewa_webhook_failed', 'error', [
                    'transaction_uuid' => $transactionUuid
                ]);
                
                http_response_code(400);
                echo 'FAILED';
            }
        } catch (Exception $e) {
            $this->logSecurityEvent($traceId, 'esewa_webhook_error', 'error', [
                'error' => $e->getMessage()
            ]);
            
            http_response_code(500);
            echo 'ERROR';
        }
    }
}

