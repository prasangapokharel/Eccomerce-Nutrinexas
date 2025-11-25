<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\Address;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Coupon;
use App\Models\User;
use KhaltiSDK\Khalti;
use KhaltiSDK\Exceptions\ApiException;
use KhaltiSDK\Exceptions\ValidationException;
use App\Models\KhaltiPayment;
use App\Models\DeliveryCharge;
use App\Models\Setting;
use App\Core\Database;
use App\Services\OrderCalculationService;
use Exception;

class CheckoutController extends Controller
{
    private $cartModel;
    private $productModel;
    private $productImageModel;
    private $orderModel;
    private $orderItemModel;
    private $couponModel;
    private $addressModel;
    private $gatewayModel;
    private $userModel;
    private $khaltiPaymentModel;
    private $deliveryModel;
    private $settingModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->couponModel = new Coupon();
        $this->addressModel = new Address();
        $this->gatewayModel = new PaymentGateway();
        $this->userModel = new User();
        $this->khaltiPaymentModel = new KhaltiPayment();
        $this->deliveryModel = new DeliveryCharge();
        $this->settingModel = new Setting();
    }

    /**
     * Set JSON response headers
     */
    private function setJsonHeader()
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
    }

    /**
     * Display checkout page
     */
    public function index()
    {
        // Handle both logged-in and guest users properly
        $cartMiddleware = new \App\Middleware\CartMiddleware();
        $isGuest = $cartMiddleware->isGuest();
        
        if ($isGuest) {
            // Get guest cart data from session
            $cartItems = $cartMiddleware->getCartData();
            $userId = 'guest_' . session_id();
        } else {
            // Get logged-in user cart data from database
            $userId = Session::get('user_id');
            $cartItems = $this->cartModel->getCartWithProducts($userId);
        }

        if (empty($cartItems)) {
            $this->setFlash('error', 'Your cart is empty');
            $this->redirect('cart');
            return;
        }

        $checkoutData = $this->prepareCheckoutData($userId, $cartItems);
        $this->view('checkout/index', array_merge($checkoutData, [
            'title' => 'Checkout'
        ]));
    }

    /**
     * Calculate delivery fee for a location
     */
    public function calculateDeliveryFee()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('checkout');
            return;
        }
        
        $location = $_POST['location'] ?? '';
        
        if (empty($location)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Location is required'
            ]);
            return;
        }
        
        $deliveryCharge = $this->deliveryModel->getChargeByLocation($location);
        $fee = $deliveryCharge ? $deliveryCharge['charge'] : 300; // Default fee
        
        $this->jsonResponse([
            'success' => true,
            'fee' => $fee,
            'location' => $deliveryCharge ? $deliveryCharge['location_name'] : $location
        ]);
    }

    /**
     * Get default address via AJAX
     */
    public function getDefaultAddress()
    {
        $this->setJsonHeader();
        
        $userId = Session::get('user_id');
        if (!$userId) {
            $addressFromCookies = $this->getAddressCookies();
            if ($addressFromCookies) {
                $this->jsonResponse(['success' => true, 'address' => $addressFromCookies]);
                return;
            }
            $this->jsonResponse(['success' => false, 'message' => 'User not logged in and no address in cookies']);
            return;
        }

        try {
            $defaultAddress = $this->addressModel->getDefaultAddress($userId);
            $this->jsonResponse([
                'success' => $defaultAddress ? true : false,
                'address' => $defaultAddress,
                'message' => $defaultAddress ? null : 'No default address found'
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error retrieving default address'
            ]);
        }
    }

    /**
     * Validate coupon - works for both guest and logged-in users
     */
    public function validateCoupon()
    {
        $this->setJsonHeader();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
                return;
            }

            $input = $this->getJsonInput();
            $code = trim($input['code'] ?? '');
            
            if (empty($code)) {
                $this->jsonResponse(['success' => false, 'message' => 'Coupon code is required']);
                return;
            }

            // Get cart items using CartMiddleware (works for guest and logged-in users)
            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $cartItems = $cartMiddleware->getCartData();
            
            if (empty($cartItems)) {
                $this->jsonResponse(['success' => false, 'message' => 'Cart is empty']);
                return;
            }

            // Calculate cart total
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $product = $this->productModel->find($item['product_id']);
                if ($product) {
                    $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                        ? $product['sale_price'] 
                        : $product['price'];
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                    $cartTotal += $currentPrice * $quantity;
                }
            }

            $userId = Session::get('user_id') ?? 0;
            $validation = $this->processCouponValidation($code, $userId, $cartItems, $cartTotal);
            $this->jsonResponse($validation);

        } catch (Exception $e) {
            error_log('Checkout coupon validation error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while validating the coupon: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove coupon - works for both guest and logged-in users
     */
    public function removeCoupon()
    {
        $this->setJsonHeader();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
                return;
            }

            if (isset($_SESSION['applied_coupon'])) {
                unset($_SESSION['applied_coupon']);
            }

            // Get cart items using CartMiddleware (works for guest and logged-in users)
            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $cartItems = $cartMiddleware->getCartData();
            
            // Calculate cart total
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $product = $this->productModel->find($item['product_id']);
                if ($product) {
                    $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                        ? $product['sale_price'] 
                        : $product['price'];
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                    $cartTotal += $currentPrice * $quantity;
                }
            }
            
            $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
            $tax = $cartTotal * $taxRate;
            $finalTotal = $cartTotal + $tax;
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Coupon removed successfully!',
                'final_amount' => $finalTotal
            ]);

        } catch (Exception $e) {
            error_log('Checkout coupon remove error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while removing the coupon: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process checkout - with security improvements
     */
    public function process()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('checkout');
            return;
        }

        try {
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('checkout');
                return;
            }
            
            // Rate limiting check
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $rateLimitKey = 'checkout_' . $ip;
            
            if (!\App\Helpers\SecurityHelper::checkRateLimit($rateLimitKey, 10, 3600)) {
                $remaining = \App\Helpers\SecurityHelper::getRateLimitRemaining($rateLimitKey, 3600);
                $this->setFlash('error', 'Too many checkout attempts. Please try again later.');
                $this->redirect('checkout');
                return;
            }
            
            $userId = $this->handleUserRegistration();
            
            // Use CartMiddleware to get cart data for both guest and logged-in users
            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $cartItems = $cartMiddleware->getCartData();

            if (empty($cartItems)) {
                $this->setFlash('error', 'Your cart is empty');
                $this->redirect('cart');
                return;
            }

            // Validate and sanitize input
            $errors = $this->validateCheckoutData();
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('checkout');
                return;
            }

            // Sanitize all POST data
            $sanitizedData = $this->sanitizeCheckoutInput($_POST);
            
            $orderData = $this->prepareOrderData($userId, $cartItems);
            $orderId = $this->orderModel->createOrder($orderData, $cartItems);
            
            if ($orderId) {
                // Reset rate limit on success (SecurityHelper uses 'rate_limit_' prefix)
                $sessionKey = 'rate_limit_' . $rateLimitKey;
                Session::remove($sessionKey);
                $this->handleSuccessfulOrder($orderId, $userId);
            } else {
                $this->setFlash('error', 'Failed to place order. Please try again.');
                $this->redirect('checkout');
            }
        } catch (Exception $e) {
            error_log('Checkout process error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while processing your order: ' . $e->getMessage());
            $this->redirect('checkout');
        }
    }
    
    /**
     * Sanitize checkout input data
     */
    private function sanitizeCheckoutInput($data)
    {
        $sanitized = [];
        
        $sanitized['recipient_name'] = \App\Helpers\SecurityHelper::sanitizeString($data['recipient_name'] ?? '');
        $sanitized['phone'] = \App\Helpers\SecurityHelper::sanitizePhone($data['phone'] ?? '');
        $sanitized['address_line1'] = \App\Helpers\SecurityHelper::sanitizeString($data['address_line1'] ?? '');
        $sanitized['city'] = \App\Helpers\SecurityHelper::sanitizeString($data['city'] ?? '');
        $sanitized['state'] = \App\Helpers\SecurityHelper::sanitizeString($data['state'] ?? '');
        $sanitized['order_notes'] = \App\Helpers\SecurityHelper::sanitizeString($data['order_notes'] ?? '', true);
        $sanitized['gateway_id'] = (int)($data['gateway_id'] ?? 0);
        $sanitized['transaction_id'] = \App\Helpers\SecurityHelper::sanitizeString($data['transaction_id'] ?? '');
        
        return $sanitized;
    }

    /**
     * Show Khalti payment page
     */
    public function khalti($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('checkout');
            return;
        }

        $this->view('checkout/khalti', [
            'order' => $order,
            'title' => 'Complete Payment - Khalti'
        ]);
    }

    /**
     * Show eSewa payment page
     */
    public function esewa($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('checkout');
            return;
        }

        $this->view('checkout/esewa', [
            'order' => $order,
            'title' => 'Complete Payment - eSewa'
        ]);
    }

    /**
     * Initiate Khalti payment (AJAX)
     */
    public function initiateKhalti($orderId)
    {
        $this->setJsonHeader();
        
        try {
            error_log("=== KHALTI PAYMENT INITIATION START ===");
            error_log("Order ID: $orderId");
            
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                error_log("ERROR: Order not found for ID: $orderId");
                throw new Exception('Order not found');
            }
            
            error_log("Order found: " . json_encode($order));

            $paymentData = $this->prepareKhaltiPaymentData($order);
            error_log("Payment data prepared: " . json_encode($paymentData));
            
            $gateway = $this->gatewayModel->findOneBy('slug', 'khalti');
            
            if (!$gateway) {
                error_log("ERROR: Khalti gateway not configured");
                throw new Exception('Khalti gateway not configured');
            }
            
            error_log("Gateway found: " . json_encode($gateway));
            
            $response = $this->initiateKhaltiPayment($paymentData, $gateway);
            error_log("Payment initiation response: " . json_encode($response));
            
            if ($response['success']) {
                $this->khaltiPaymentModel->createPayment([
                    'user_id' => $order['user_id'],
                    'order_id' => $orderId,
                    'amount' => $order['total_amount'],
                    'pidx' => $response['pidx'] ?? null,
                    'status' => 'pending'
                ]);
                error_log("Payment record created successfully");
            }
            
            error_log("=== KHALTI PAYMENT INITIATION SUCCESS ===");
            $this->jsonResponse($response);

        } catch (Exception $e) {
            error_log('=== KHALTI PAYMENT INITIATION ERROR ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('========================================');
            
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Initiate eSewa payment using official SDK (AJAX)
     */
    public function initiateEsewa($orderId)
    {
        $this->setJsonHeader();
        
        try {
            error_log("=== ESEWA PAYMENT INITIATION START ===");
            error_log("Order ID: $orderId");
            
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                error_log("ERROR: Order not found for ID: $orderId");
                throw new Exception('Order not found');
            }
            
            error_log("Order found: " . json_encode($order));

            $gateway = $this->gatewayModel->findOneBy('slug', 'esewa');
            
            if (!$gateway) {
                error_log("ERROR: eSewa gateway not configured");
                throw new Exception('eSewa gateway not configured');
            }
            
            error_log("Gateway found: " . json_encode($gateway));
            
            $gatewayParams = json_decode($gateway['parameters'], true);
            $isTestMode = $gateway['is_test_mode'] ?? false;
            
            error_log("Gateway params: " . json_encode($gatewayParams));
            error_log("Test mode: " . ($isTestMode ? 'Yes' : 'No'));
            
            // Use dynamic configuration based on test/live mode
            if ($isTestMode) {
                $merchantCode = $gatewayParams['test_merchant_id'] ?? $gatewayParams['merchant_id'] ?? null;
            } else {
                $merchantCode = $gatewayParams['live_merchant_id'] ?? $gatewayParams['merchant_id'] ?? null;
            }
            
            error_log("Merchant code: " . ($merchantCode ? 'SET' : 'NOT SET'));
            
            if (!$merchantCode) {
                error_log("ERROR: eSewa merchant code not configured");
                throw new Exception('eSewa merchant code not configured. Please check ' . ($isTestMode ? 'test' : 'live') . ' credentials.');
            }
            
            // Calculate tax amount (10% tax)
            $taxAmount = round($order['total_amount'] * 0.1, 2);
            $totalAmount = $order['total_amount'] + $taxAmount;
            
            // Generate unique transaction UUID (alphanumeric and hyphen only)
            $transactionUuid = 'ORDER-' . $orderId . '-' . time();
            
            // Get website URL from settings table
            $settingModel = new \App\Models\Setting();
            $websiteUrl = $settingModel->get('website_url', URLROOT);
            
            // Set success and failure URLs
            $successUrl = $websiteUrl . '/payment/esewa/success/' . $orderId;
            $failureUrl = $websiteUrl . '/payment/esewa/failure/' . $orderId;
            
            // Get secret key for signature generation
            // Use the epay_secret_key as per official documentation
            $secretKey = $isTestMode ? 
                ($gatewayParams['test_secret_key'] ?? '8gBm/:&EnhH.1/q') : 
                ($gatewayParams['live_secret_key'] ?? '');
            
            if (empty($secretKey)) {
                throw new Exception('eSewa secret key not configured for ' . ($isTestMode ? 'test' : 'live') . ' mode');
            }
            
            error_log("eSewa secret key: " . (empty($secretKey) ? 'NOT SET' : 'SET'));
            
            // Prepare payment data as per official documentation
            $paymentData = [
                'amount' => (string)$order['total_amount'],
                'tax_amount' => (string)$taxAmount,
                'total_amount' => (string)$totalAmount,
                'transaction_uuid' => $transactionUuid,
                'product_code' => $merchantCode,
                'product_service_charge' => '0',
                'product_delivery_charge' => '0',
                'success_url' => $successUrl,
                'failure_url' => $failureUrl,
                'signed_field_names' => 'total_amount,transaction_uuid,product_code'
            ];
            
            // Generate signature using HMAC SHA256 as per official documentation
            // Format: total_amount={amount},transaction_uuid={uuid},product_code={code}
            $signData = "total_amount={$paymentData['total_amount']},transaction_uuid={$paymentData['transaction_uuid']},product_code={$paymentData['product_code']}";
            $rawSign = hash_hmac('sha256', $signData, $secretKey, true);
            $signature = base64_encode($rawSign);
            $paymentData['signature'] = $signature;
            
            error_log("eSewa payment data: " . json_encode($paymentData));
            error_log("eSewa signature data: $signData");
            error_log("eSewa secret key: $secretKey");
            error_log("eSewa generated signature: $signature");
            
            // Get dynamic payment URL from database configuration
            if ($isTestMode) {
                $paymentUrl = $gatewayParams['test_payment_url'] ?? 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
            } else {
                $paymentUrl = $gatewayParams['live_payment_url'] ?? 'https://epay.esewa.com.np/api/epay/main/v2/form';
            }
            
            error_log("=== ESEWA PAYMENT INITIATION SUCCESS ===");
            $this->jsonResponse([
                'success' => true,
                'payment_data' => $paymentData,
                'payment_url' => $paymentUrl,
                'mode' => $isTestMode ? 'test' : 'live',
                'sdk_version' => 'remotemerge/esewa-php-sdk'
            ]);

        } catch (Exception $e) {
            error_log('=== ESEWA PAYMENT INITIATION ERROR ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('========================================');
            
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Verify Khalti payment (AJAX)
     */
    public function verifyKhalti()
    {
        try {
            $input = $this->getJsonInput();
            $pidx = $input['pidx'] ?? '';

            if (empty($pidx)) {
                throw new Exception('Payment ID is required');
            }

            $gateway = $this->gatewayModel->findOneBy('slug', 'khalti');
            if (!$gateway) {
                throw new Exception('Khalti gateway not configured');
            }
            
            $response = $this->verifyKhaltiPayment($pidx, $gateway);
            $this->jsonResponse($response);

        } catch (Exception $e) {
            error_log('Khalti payment verification error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle Khalti webhook/return URL
     */
    public function khaltiWebhook()
    {
        try {
            $pidx = $_GET['pidx'] ?? '';
            $status = $_GET['status'] ?? '';
            $purchaseOrderId = $_GET['purchase_order_id'] ?? '';
            
            if (!empty($purchaseOrderId)) {
                $order = $this->orderModel->findOneBy('invoice', $purchaseOrderId);
                if ($order) {
                    $this->handleKhaltiWebhookResponse($order, $status);
                    return;
                }
            }
            
            if (empty($pidx)) {
                $this->setFlash('error', 'Invalid payment ID');
                $this->redirect('checkout');
                return;
            }

            $gateway = $this->gatewayModel->findOneBy('slug', 'khalti');
            if (!$gateway) {
                $this->setFlash('error', 'Khalti gateway not configured');
                $this->redirect('checkout');
                return;
            }
            
            $response = $this->verifyKhaltiPayment($pidx, $gateway);
            if ($response['success'] && $response['status'] === 'completed') {
                $order = $this->orderModel->findOneBy('invoice', $response['purchase_order_id']);
                if ($order) {
                    $this->handleKhaltiWebhookResponse($order, 'Completed');
                }
            } else {
                $this->setFlash('error', 'Payment verification failed');
                $this->redirect('checkout');
            }

        } catch (Exception $e) {
            error_log('Khalti webhook error: ' . $e->getMessage());
            $this->setFlash('error', 'Payment verification failed');
            $this->redirect('checkout');
        }
    }

    /**
     * Success page for completed orders
     */
    public function success($orderId)
    {
        try {
            $order = $this->orderModel->getOrderWithItems($orderId);
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('orders');
                return;
            }

            // Check if user has access to this order
            $userId = Session::get('user_id');
            if ($userId && $order['user_id'] !== null && $order['user_id'] != $userId) {
                $this->setFlash('error', 'Access denied');
                $this->redirect('orders');
                return;
            }

            $this->view('checkout/success', [
                'data' => ['order' => $order],
                'title' => 'Order Confirmation - #' . $order['invoice']
            ]);

        } catch (Exception $e) {
            error_log('Checkout success page error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while loading the order confirmation page.');
            $this->redirect('orders');
        }
    }

    // Private helper methods

    /**
     * Prepare checkout data
     */
    private function prepareCheckoutData($userId, $cartItems)
    {
        $paymentGateways = $this->gatewayModel->getActiveGateways();
        $deliveryCharges = $this->deliveryModel->getAllCharges();
        
        $defaultAddress = null;
        if ($userId) {
            $defaultAddress = $this->addressModel->getDefaultAddress($userId);
        } else {
            $defaultAddress = $this->getAddressCookies();
        }

        // Calculate totals from cart items
        $total = 0;
        $enhancedItems = [];
        
        foreach ($cartItems as $item) {
            $product = $this->productModel->find($item['product_id']);
            if ($product) {
                // Get product image URL
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                    ? $product['sale_price'] 
                    : $product['price'];
                
                $subtotal = $currentPrice * $item['quantity'];
                $total += $subtotal;
                
                $enhancedItems[] = [
                    'id' => $item['id'] ?? $item['product_id'], // Use product_id for guest cart items
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                    'current_price' => $currentPrice
                ];
            }
        }
        
        $taxRate = $this->settingModel->get('tax_rate', 12) / 100; // Get tax rate from settings, default to 12%
        $tax = $total * $taxRate;
        $finalTotal = $total + $tax;

        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
        $couponDiscount = 0;
        if ($appliedCoupon) {
            $couponDiscount = $this->couponModel->calculateDiscount($appliedCoupon, $total);
        }

        return [
            'cartItems' => $enhancedItems,
            'total' => $total,
            'tax' => $tax,
            'finalTotal' => $finalTotal - $couponDiscount,
            'appliedCoupon' => $appliedCoupon,
            'couponDiscount' => $couponDiscount,
            'defaultAddress' => $defaultAddress,
            'paymentGateways' => $paymentGateways,
            'deliveryCharges' => $deliveryCharges,
            'userId' => $userId,
        ];
    }

    /**
     * Handle user registration during checkout
     */
    private function handleUserRegistration()
    {
        $userId = Session::get('user_id');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $createAccount = isset($_POST['create_account']) && $_POST['create_account'] === 'on';

        if (!$userId && $createAccount) {
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required to create an account.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (strlen($password) < 6) {
                throw new Exception('Password must be at least 6 characters long.');
            }

            if ($this->userModel->findUserByEmail($email)) {
                throw new Exception('An account with this email already exists. Please login or use a different email.');
            }

            $newUserId = $this->userModel->createUser([
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'username' => explode('@', $email)[0],
                'role' => 'customer',
                'status' => 'active'
            ]);

            if ($newUserId) {
                $userId = $newUserId;
                Session::set('user_id', $userId);
                Session::set('user_email', $email);
                $this->setFlash('success', 'Account created and order will be placed under your new account!');
            } else {
                throw new Exception('Failed to create account. Please try again.');
            }
        }

        // If no user ID (guest checkout), use session ID
        if (!$userId) {
            $userId = 'guest_' . session_id();
        }

        return $userId;
    }

    /**
     * Validate checkout data
     */
    private function validateCheckoutData()
    {
        $errors = [];
        // Make 'state' optional to avoid blocking checkout
        $requiredFields = ['recipient_name', 'phone', 'address_line1', 'city', 'gateway_id'];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        $gatewayId = (int)$_POST['gateway_id'];
        $gateway = $this->gatewayModel->getGatewayById($gatewayId);

        if (!$gateway) {
            $errors[] = 'Invalid payment method selected';
        } else {
            $errors = array_merge($errors, $this->validateGatewaySpecificFields($gateway));
        }

        return $errors;
    }

    /**
     * Validate gateway-specific fields
     */
    private function validateGatewaySpecificFields($gateway)
    {
        $errors = [];
        $gatewayParams = json_decode($gateway['parameters'], true) ?? [];

        if ($gateway['type'] === 'manual') {
            if (isset($gatewayParams['require_transaction_id']) && $gatewayParams['require_transaction_id']) {
                if (empty($_POST['transaction_id'])) {
                    $errors[] = 'Transaction ID is required for ' . $gateway['name'];
                }
            }

            if (isset($gatewayParams['require_screenshot']) && $gatewayParams['require_screenshot']) {
                if (empty($_FILES['payment_screenshot']['name'])) {
                    $errors[] = 'Payment screenshot is required for ' . $gateway['name'];
                }
            }
        }

        switch ($gateway['slug']) {
            case 'bank_transfer':
                if (empty($_POST['transaction_id'])) {
                    $errors[] = 'Transaction ID is required for bank transfer';
                }
                if (empty($_FILES['payment_screenshot']['name'])) {
                    $errors[] = 'Payment screenshot is required for bank transfer';
                }
                break;
        }

        return $errors;
    }

    /**
     * Prepare order data
     */
    private function prepareOrderData($userId, $cartItems)
    {
        // Calculate cart total using service (with product model for price lookup)
        $cartTotal = OrderCalculationService::calculateCartTotal($cartItems, $this->productModel);
        
        $subtotal = $cartTotal;
        
        $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
        $couponDiscount = 0;
        if ($appliedCoupon) {
            $couponDiscount = OrderCalculationService::applyCouponDiscount($cartTotal, $appliedCoupon);
        }
        
        $deliveryFee = $this->calculateDeliveryFeeFromForm();
        $taxRate = $this->settingModel->get('tax_rate', 13);
        
        // Use centralized calculation service
        $totals = OrderCalculationService::calculateTotals(
            $subtotal,
            $couponDiscount,
            $deliveryFee,
            $taxRate
        );
        
        $gatewayId = (int)$_POST['gateway_id'];
        $gateway = $this->gatewayModel->getGatewayById($gatewayId);
        $paymentMethodId = $this->getCorrectPaymentMethodId($gatewayId);
        
        $finalAmount = $totals['total'];
        $tax = $totals['tax'];

        return [
            'user_id' => is_numeric($userId) ? $userId : null,
            'total_amount' => $finalAmount,
            'tax_amount' => $tax,
            'discount_amount' => $couponDiscount,
            'delivery_fee' => $deliveryFee,
            'final_amount' => $finalAmount,
            'payment_method_id' => $paymentMethodId,
            'gateway_id' => $gatewayId,
            'payment_method' => $gateway['name'],
            'payment_status' => $this->determinePaymentStatus($gateway),
            'order_status' => 'pending',
            'recipient_name' => $_POST['recipient_name'],
            'phone' => $_POST['phone'],
            'address_line1' => $_POST['address_line1'],
            'city' => $_POST['city'],
            'state' => $_POST['state'] ?? '',
            'country' => 'Nepal',
            'order_notes' => $_POST['order_notes'] ?? '',
            'transaction_id' => $_POST['transaction_id'] ?? null,
            'payment_screenshot' => $this->handlePaymentScreenshot($gateway),
            'coupon_code' => $appliedCoupon ? $appliedCoupon['code'] : null
        ];
    }

    /**
     * Calculate delivery fee from form
     */
    private function calculateDeliveryFeeFromForm()
    {
        $selectedCity = $_POST['city'] ?? '';
        if (!empty($selectedCity)) {
            $deliveryCharge = $this->deliveryModel->getChargeByLocation($selectedCity);
            if ($deliveryCharge) {
                return $deliveryCharge['charge'];
            }
        }
        return 300; // Default fee
    }

    /**
     * Determine payment status based on gateway
     */
    private function determinePaymentStatus($gateway)
    {
        if ($gateway['type'] === 'manual') {
            return 'pending_verification';
        } elseif ($gateway['slug'] === 'cod') {
            return 'pending';
        } elseif ($gateway['type'] === 'digital') {
            return 'pending';
        }
        return 'pending';
    }

    /**
     * Handle payment screenshot upload
     */
    private function handlePaymentScreenshot($gateway)
    {
        if ($gateway['type'] === 'manual' && !empty($_FILES['payment_screenshot']['name'])) {
            $uploadDir = 'public/payments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = time() . '_' . $_FILES['payment_screenshot']['name'];
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $uploadPath)) {
                return $fileName;
            }
        }
        return null;
    }

    /**
     * Handle successful order creation
     */
    private function handleSuccessfulOrder($orderId, $userId)
    {
        // Save address to cookies for guest users
        if (!$userId || !is_numeric($userId)) {
            $this->setAddressCookies([
                'recipient_name' => $_POST['recipient_name'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'address_line1' => $_POST['address_line1'] ?? '',
                'address_line2' => $_POST['address_line2'] ?? '',
                'city' => $_POST['city'] ?? '',
                'state' => $_POST['state'] ?? '',
                'postal_code' => $_POST['postal_code'] ?? '',
            ]);
        }

        // Handle different gateway types
        $gatewayId = (int)$_POST['gateway_id'];
        $gateway = $this->gatewayModel->getGatewayById($gatewayId);
        
        if ($gateway['type'] === 'digital') {
            // For digital payments, don't clear cart yet - wait for payment completion
            $this->handleDigitalPayment($orderId, $gateway, $_POST['final_amount'] ?? 0);
        } else {
            // For COD and other non-digital payments, clear cart immediately
            // Handle both logged-in and guest users
            if (is_numeric($userId)) {
                $this->cartModel->clearCart($userId);
            } else {
                // Clear guest cart from session
                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $cartMiddleware->clearGuestCart();
            }
            
            if (isset($_SESSION['applied_coupon'])) {
                unset($_SESSION['applied_coupon']);
            }
            
            // Redirect immediately - emails sent asynchronously via shutdown function
            // Email notifications are handled in background to not block checkout
            register_shutdown_function(function() use ($orderId) {
                try {
                    $emailService = new \App\Services\EmailAutomationService();
                    $emailService->sendPostPurchaseEmail($orderId);
                } catch (Exception $e) {
                    error_log('Post-purchase email error: ' . $e->getMessage());
                }
            });
            
            $this->setFlash('success', 'Order placed successfully! Order ID: #' . $orderId);
            $this->redirect('checkout/success/' . $orderId);
        }
    }

    /**
     * Handle digital payment gateway processing
     */
    private function handleDigitalPayment($orderId, $gateway, $amount)
    {
        try {
            switch ($gateway['slug']) {
                case 'khalti':
                    $this->redirect('checkout/khalti/' . $orderId);
                    break;
                case 'esewa':
                    $this->redirect('checkout/esewa/' . $orderId);
                    break;
                case 'mypay':
                    $this->redirect('checkout/mypay/' . $orderId);
                    break;
                default:
                    $this->setFlash('info', 'Redirecting to payment gateway...');
                    $this->redirect('checkout/success/' . $orderId);
                    break;
            }
        } catch (Exception $e) {
            error_log('Digital payment processing error: ' . $e->getMessage());
            $this->setFlash('error', 'Payment processing failed. Please try again.');
            $this->redirect('checkout');
        }
    }

    /**
     * Process coupon validation - works for both guest and logged-in users
     */
    private function processCouponValidation($code, $userId, $cartItems, $cartTotal = null)
    {
        $coupon = $this->couponModel->getCouponByCode($code);
        if (!$coupon) {
            return ['success' => false, 'message' => 'Invalid coupon code'];
        }

        // Calculate cart total if not provided
        if ($cartTotal === null) {
            $cartTotal = 0;
            foreach ($cartItems as $item) {
                $product = $this->productModel->find($item['product_id']);
                if ($product) {
                    $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                        ? $product['sale_price'] 
                        : $product['price'];
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                    $cartTotal += $currentPrice * $quantity;
                }
            }
        }

        $productIds = $this->extractProductIds($cartItems);
        // For guest users, pass 0 as userId for validation
        $validationUserId = is_numeric($userId) ? $userId : 0;
        $validation = $this->couponModel->validateCoupon($code, $validationUserId, $cartTotal, $productIds);

        if ($validation['valid']) {
            $discount = $this->couponModel->calculateDiscount($validation['coupon'], $cartTotal);
            // Calculate tax on subtotal after discount
            $subtotalAfterDiscount = max(0, $cartTotal - $discount);
            $taxRate = $this->settingModel->get('tax_rate', 13) / 100;
            $tax = $subtotalAfterDiscount * $taxRate;
            $finalAmount = $subtotalAfterDiscount + $tax;

            $_SESSION['applied_coupon'] = $validation['coupon'];

            return [
                'success' => true,
                'coupon' => $validation['coupon'],
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'message' => 'Coupon applied successfully!'
            ];
        } else {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
    }

    /**
     * Extract product IDs from cart items
     */
    private function extractProductIds($cartItems)
    {
        $productIds = [];
        foreach ($cartItems as $item) {
            if (isset($item['product']['id'])) {
                $productIds[] = (int)$item['product']['id'];
            } elseif (isset($item['product_id'])) {
                $productIds[] = (int)$item['product_id'];
            }
        }
        return array_unique($productIds);
    }

    /**
     * Get correct payment method ID for gateway
     */
    private function getCorrectPaymentMethodId($gatewayId)
    {
        try {
            $sql = "SELECT id, name, gateway_id FROM payment_methods WHERE gateway_id = ? AND is_active = 1 LIMIT 1";
            $result = $this->gatewayModel->query($sql, [$gatewayId]);

            if (is_array($result) && !empty($result)) {
                return (int)$result[0]['id'];
            }

            $gateway = $this->gatewayModel->getGatewayById($gatewayId);
            if ($gateway) {
                $sql = "SELECT id, name FROM payment_methods WHERE
                                (LOWER(name) LIKE LOWER(?) OR LOWER(name) LIKE LOWER(?))
                                AND is_active = 1 LIMIT 1";
                $searchName1 = '%' . $gateway['name'] . '%';
                $searchName2 = '%' . $gateway['slug'] . '%';

                $result = $this->gatewayModel->query($sql, [$searchName1, $searchName2]);

                if (is_array($result) && !empty($result)) {
                    return (int)$result[0]['id'];
                }
            }

            return $gatewayId; // Fallback
        } catch (Exception $e) {
            error_log('Error in getCorrectPaymentMethodId: ' . $e->getMessage());
            return $gatewayId;
        }
    }

    /**
     * Prepare Khalti payment data
     */
    private function prepareKhaltiPaymentData($order)
    {
        // Get website URL from settings table
        $settingModel = new \App\Models\Setting();
        $websiteUrl = $settingModel->get('website_url', URLROOT);
        
        return [
            'return_url' => $websiteUrl . '/payment/khalti/success/' . $order['id'],
            'website_url' => $websiteUrl,
            'amount' => (int)($order['total_amount'] * 100), // Convert to paisa
            'purchase_order_id' => $order['invoice'],
            'purchase_order_name' => 'Order #' . $order['invoice'],
            'customer_info' => [
                'name' => $order['customer_name'] ?? 'Customer',
                'email' => $order['customer_email'] ?? '',
                'phone' => $order['customer_phone'] ?? '9800000001'
            ]
        ];
    }

    /**
     * Initiate Khalti payment
     */
    private function initiateKhaltiPayment($paymentData, $gateway)
    {
        $gatewayParams = json_decode($gateway['parameters'], true);
        $isTestMode = $gateway['is_test_mode'] ?? 0;
        
        // Use test secret key if in test mode, otherwise use live secret key
        $secretKey = $isTestMode 
            ? ($gatewayParams['test_secret_key'] ?? $gatewayParams['secret_key'] ?? '')
            : ($gatewayParams['secret_key'] ?? KHALTI_SECRET_KEY);
        
        if (empty($secretKey)) {
            throw new Exception('Khalti secret key not configured');
        }
        
        try {
            // Use correct API endpoints as per official documentation
            $apiUrl = $isTestMode ? 'https://dev.khalti.com/api/v2/epayment/initiate/' : 'https://khalti.com/api/v2/epayment/initiate/';
            
            error_log("Khalti API URL: $apiUrl");
            error_log("Secret Key: " . (empty($secretKey) ? 'NOT SET' : 'SET'));
            
            // Prepare request data as per official documentation
            $requestData = [
                'return_url' => $paymentData['return_url'],
                'website_url' => $paymentData['website_url'],
                'amount' => (string)$paymentData['amount'], // Convert to string as per docs
                'purchase_order_id' => $paymentData['purchase_order_id'],
                'purchase_order_name' => $paymentData['purchase_order_name'],
                'customer_info' => $paymentData['customer_info']
            ];
            
            error_log("Khalti request data: " . json_encode($requestData));
            
            // Make direct cURL request as per official documentation
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
                CURLOPT_POSTFIELDS => json_encode($requestData),
                CURLOPT_HTTPHEADER => [
                    'Authorization: key ' . $secretKey,
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            error_log("Khalti HTTP Code: $httpCode");
            error_log("Khalti Response: $response");
            
            if ($curlError) {
                throw new Exception('cURL Error: ' . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode . ' - ' . $response);
            }
            
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . $response);
            }
            
            if (isset($responseData['pidx']) && isset($responseData['payment_url'])) {
                return [
                    'success' => true,
                    'pidx' => $responseData['pidx'],
                    'payment_url' => $responseData['payment_url'],
                    'expires_at' => $responseData['expires_at'] ?? null,
                    'expires_in' => $responseData['expires_in'] ?? null
                ];
            } else {
                throw new Exception('Invalid response format: ' . $response);
            }
            
        } catch (Exception $e) {
            error_log('Khalti payment initiation error: ' . $e->getMessage());
            throw new Exception('Payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Verify Khalti payment
     */
    private function verifyKhaltiPayment($pidx, $gateway)
    {
        $gatewayParams = json_decode($gateway['parameters'], true);
        $isTestMode = $gateway['is_test_mode'] ?? 0;
        
        // Use test secret key if in test mode, otherwise use live secret key
        $secretKey = $isTestMode 
            ? ($gatewayParams['test_secret_key'] ?? $gatewayParams['secret_key'] ?? '')
            : ($gatewayParams['secret_key'] ?? KHALTI_SECRET_KEY);
        
        if (empty($secretKey)) {
            throw new Exception('Khalti secret key not configured');
        }
        
        $isTestMode = $gateway['is_test_mode'] ?? 0;
        
        try {
            // Use correct API endpoints as per official documentation
            $apiUrl = $isTestMode ? 'https://dev.khalti.com/api/v2/epayment/lookup/' : 'https://khalti.com/api/v2/epayment/lookup/';
            
            error_log("Khalti Lookup API URL: $apiUrl");
            error_log("Khalti Lookup pidx: $pidx");
            
            // Make direct cURL request for lookup
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
                CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: key ' . $secretKey,
                    'Content-Type: application/json',
                ],
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            error_log("Khalti Lookup HTTP Code: $httpCode");
            error_log("Khalti Lookup Response: $response");
            
            if ($curlError) {
                throw new Exception('cURL Error: ' . $curlError);
            }
            
            if ($httpCode !== 200) {
                throw new Exception('HTTP Error: ' . $httpCode . ' - ' . $response);
            }
            
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . $response);
            }
            
            if (isset($responseData['status'])) {
                $status = $responseData['status'];
                
                $order = $this->orderModel->findOneBy('invoice', $responseData['purchase_order_id']);
                
                if ($order) {
                    $this->khaltiPaymentModel->updateStatusByOrderId(
                        $order['id'], 
                        $status === 'Completed' ? 'completed' : 'pending',
                        $responseData['transaction_id'] ?? null
                    );

                    if ($status === 'Completed') {
                        $this->orderModel->update($order['id'], [
                            'status' => 'confirmed',
                            'payment_status' => 'paid'
                        ]);
                        
                        // Clear cart and applied coupon after successful payment
                        // Handle both logged-in and guest users
                        if (!empty($order['user_id']) && is_numeric($order['user_id'])) {
                            $this->cartModel->clearCart($order['user_id']);
                        } else {
                            // Clear guest cart
                            $cartMiddleware = new \App\Middleware\CartMiddleware();
                            $cartMiddleware->clearGuestCart();
                        }
                        if (isset($_SESSION['applied_coupon'])) {
                            unset($_SESSION['applied_coupon']);
                        }
                        
                        // Send post-purchase email automation asynchronously (non-blocking)
                        // Use shutdown function to send after response
                        register_shutdown_function(function() use ($order) {
                            try {
                                $emailService = new \App\Services\EmailAutomationService();
                                $emailService->sendPostPurchaseEmail($order['id']);
                            } catch (Exception $e) {
                                error_log('Post-purchase email error: ' . $e->getMessage());
                            }
                        });
                        
                        return [
                            'success' => true,
                            'status' => 'completed',
                            'redirect' => BASE_URL . '/checkout/success/' . $order['id']
                        ];
                    } else {
                        return [
                            'success' => true,
                            'status' => 'pending'
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'message' => 'Order not found'
                    ];
                }
            } else {
                throw new Exception('Invalid response from Khalti API');
            }
            
        } catch (ApiException $e) {
            throw new Exception('API Error: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Payment verification failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle Khalti webhook response
     */
    private function handleKhaltiWebhookResponse($order, $status)
    {
        if ($status === 'Completed') {
            $this->orderModel->update($order['id'], [
                'status' => 'confirmed',
                'payment_status' => 'paid'
            ]);
            
            $this->khaltiPaymentModel->updateStatusByOrderId(
                $order['id'], 
                'completed',
                $_GET['transaction_id'] ?? null
            );
            
            $this->setFlash('success', 'Payment successful!');
            $this->redirect('checkout/success/' . $order['id']);
        } else {
            $this->setFlash('warning', 'Payment is still pending');
            $this->redirect('checkout/success/' . $order['id']);
        }
    }


    /**
     * eSewa status check API
     */
    public function checkEsewaStatus()
    {
        $this->setJsonHeader();
        
        try {
            error_log("=== ESEWA STATUS CHECK START ===");
            
            $input = $this->getJsonInput();
            $productCode = $input['product_code'] ?? '';
            $totalAmount = $input['total_amount'] ?? '';
            $transactionUuid = $input['transaction_uuid'] ?? '';
            
            error_log("Status check params: " . json_encode($input));
            
            if (empty($productCode) || empty($totalAmount) || empty($transactionUuid)) {
                throw new Exception('Missing required parameters: product_code, total_amount, transaction_uuid');
            }
            
            $gateway = $this->gatewayModel->findOneBy('slug', 'esewa');
            if (!$gateway) {
                throw new Exception('eSewa gateway not configured');
            }
            
            $gatewayParams = json_decode($gateway['parameters'], true);
            $isTestMode = $gateway['is_test_mode'] ?? false;
            
            // Get dynamic status check URL from database configuration
            $statusUrl = $isTestMode ? 
                ($gatewayParams['test_status_url'] ?? 'https://rc.esewa.com.np/api/epay/transaction/status/') : 
                ($gatewayParams['live_status_url'] ?? 'https://epay.esewa.com.np/api/epay/transaction/status/');
            
            $statusUrl .= '?product_code=' . urlencode($productCode) . 
                         '&total_amount=' . urlencode($totalAmount) . 
                         '&transaction_uuid=' . urlencode($transactionUuid);
            
            error_log("eSewa status URL: $statusUrl");
            
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
            
            error_log("eSewa status HTTP Code: $httpCode");
            error_log("eSewa status Response: $response");
            
            if ($curlError) {
                throw new Exception('cURL Error: ' . $curlError);
            }
            
            $responseData = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response: ' . $response);
            }
            
            error_log("=== ESEWA STATUS CHECK SUCCESS ===");
            $this->jsonResponse([
                'success' => true,
                'status_data' => $responseData
            ]);
            
        } catch (Exception $e) {
            error_log('=== ESEWA STATUS CHECK ERROR ===');
            error_log('Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            error_log('========================================');
            
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get JSON input
     */
    private function getJsonInput()
    {
        $rawInput = file_get_contents('php://input');
        return json_decode($rawInput, true) ?? [];
    }

    /**
     * Set address details in cookies for guest users
     */
    private function setAddressCookies(array $addressData)
    {
        $expiry = time() + (86400 * 30); // 30 days
        $cookieOptions = [
            'expires' => $expiry,
            'path' => '/',
            'samesite' => 'Lax'
        ];
        
        setcookie('guest_recipient_name', $addressData['recipient_name'] ?? '', $cookieOptions);
        setcookie('guest_phone', $addressData['phone'] ?? '', $cookieOptions);
        setcookie('guest_address_line1', $addressData['address_line1'] ?? '', $cookieOptions);
        setcookie('guest_address_line2', $addressData['address_line2'] ?? '', $cookieOptions);
        setcookie('guest_city', $addressData['city'] ?? '', $cookieOptions);
        setcookie('guest_state', $addressData['state'] ?? '', $cookieOptions);
        setcookie('guest_postal_code', $addressData['postal_code'] ?? '', $cookieOptions);
    }

    /**
     * Get address details from cookies for guest users
     */
    private function getAddressCookies()
    {
        if (
            isset($_COOKIE['guest_recipient_name']) &&
            isset($_COOKIE['guest_phone']) &&
            isset($_COOKIE['guest_address_line1']) &&
            isset($_COOKIE['guest_city'])
        ) {
            return [
                'recipient_name' => $_COOKIE['guest_recipient_name'],
                'phone' => $_COOKIE['guest_phone'],
                'address_line1' => $_COOKIE['guest_address_line1'],
                'address_line2' => $_COOKIE['guest_address_line2'] ?? '',
                'city' => $_COOKIE['guest_city'],
                'state' => $_COOKIE['guest_state'] ?? '',
                'postal_code' => $_COOKIE['guest_postal_code'] ?? '',
            ];
        }
        return null;
    }

    /**
     * Get the URL for a product's image with proper fallback logic
     * 
     * @param array $product The product data
     * @param array|null $primaryImage The primary image data from product_images
     * @return string The image URL
     */
    private function getProductImageUrl($product, $primaryImage = null)
    {
        // 1. Check if product has direct image URL
        if (!empty($product['image'])) {
            return $product['image'];
        }
        
        // 2. Check for primary image from product_images table
        if ($primaryImage && !empty($primaryImage['image_url'])) {
            return $primaryImage['image_url'];
        }
        
        // 3. Check for any image from product_images table
        $images = $this->productImageModel->getByProductId($product['id']);
        if (!empty($images[0]['image_url'])) {
            return $images[0]['image_url'];
        }
        
        // 4. Fallback to default image
        return \App\Core\View::asset('images/products/default.jpg');
    }
}