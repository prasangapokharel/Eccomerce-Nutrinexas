<?php

namespace App\Controllers\Coupon;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Cart;
use App\Core\Database;
use App\Helpers\CouponCheck;
use Exception;

class CouponController extends Controller
{
    private $db;
    private $couponModel;
    private $productModel;
    private $cartModel;
    private $couponCheck;
    
    public function __construct()
    {
        parent::__construct();
        $this->couponModel = new Coupon();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
        $this->db = Database::getInstance();
        $this->couponCheck = new CouponCheck();
        
        // Set CORS headers for AJAX requests
        $this->setCorsHeaders();
    }
    
    private function setCorsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Extract product IDs from cart items - FIXED
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
     * Validate coupon via AJAX - FIXED: Proper product ID extraction
     */
    public function validate()
    {
        header('Content-Type: application/json');
        
        try {
            error_log('=== COUPON VALIDATION START ===');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            // Get input data
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            
            error_log('Raw input: ' . $rawInput);
            error_log('Decoded input: ' . json_encode($input));
            
            $code = trim($input['code'] ?? '');
            $userId = Session::get('user_id') ?? 0;
            
            error_log('Coupon code: ' . $code);
            error_log('User ID: ' . $userId);
            
            if (empty($code)) {
                error_log('Empty coupon code provided');
                echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
                return;
            }
            
            // Get cart data for validation
            $cartData = $this->cartModel->getCartWithProducts($this->productModel);
            error_log('Cart data structure: ' . json_encode(array_keys($cartData)));
            error_log('Cart items count: ' . count($cartData['items'] ?? []));
            
            if (empty($cartData['items'])) {
                error_log('Cart is empty');
                echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                return;
            }
            
            $orderAmount = $cartData['total'];
            
            // FIXED: Proper product ID extraction
            $productIds = $this->extractProductIds($cartData['items']);
            
            error_log('Order amount: ' . $orderAmount);
            error_log('Product IDs: ' . json_encode($productIds));
            
            // Validate coupon exists first
            $coupon = $this->couponModel->getCouponByCode($code);
            if (!$coupon) {
                error_log('Coupon not found: ' . $code);
                echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
                return;
            }
            
            error_log('Coupon found: ' . json_encode($coupon));
            
            // Check seller matching for all products in cart
            if (!empty($coupon['seller_id'])) {
                $allProductsMatch = true;
                $mismatchProducts = [];
                
                foreach ($cartData['items'] as $item) {
                    $product = $item['product'] ?? null;
                    if (!$product) {
                        continue;
                    }
                    
                    $productSellerId = $product['seller_id'] ?? null;
                    
                    // Validate coupon for this product using CouponCheck
                    if (!$this->couponCheck->isValid($coupon, $product)) {
                        $allProductsMatch = false;
                        $mismatchProducts[] = $product['name'] ?? 'Product';
                        error_log("Coupon seller mismatch: Coupon seller_id={$coupon['seller_id']}, Product seller_id={$productSellerId}");
                    }
                }
                
                if (!$allProductsMatch) {
                    $productList = implode(', ', array_slice($mismatchProducts, 0, 3));
                    if (count($mismatchProducts) > 3) {
                        $productList .= ' and more';
                    }
                    error_log('Coupon validation failed: Seller mismatch');
                    echo json_encode([
                        'success' => false,
                        'message' => "This coupon is only valid for products from the same seller. Products: {$productList} do not match."
                    ]);
                    return;
                }
            }
            
            // Validate coupon using model validation
            $validation = $this->couponModel->validateCoupon($code, $userId, $orderAmount, $productIds);
            error_log('Validation result: ' . json_encode($validation));
            
            if ($validation['valid']) {
                $discount = $this->couponModel->calculateDiscount($validation['coupon'], $orderAmount);
                $finalAmount = ($cartData['final_total'] - $discount);
                
                error_log('Discount calculated: ' . $discount);
                error_log('Final amount: ' . $finalAmount);
                
                // Store coupon in session
                $_SESSION['applied_coupon'] = $validation['coupon'];
                error_log('Coupon stored in session');
                
                echo json_encode([
                    'success' => true,
                    'coupon' => $validation['coupon'],
                    'discount' => $discount,
                    'final_amount' => $finalAmount,
                    'message' => 'Coupon applied successfully!'
                ]);
            } else {
                error_log('Coupon validation failed: ' . $validation['message']);
                echo json_encode([
                    'success' => false,
                    'message' => $validation['message']
                ]);
            }
            
            error_log('=== COUPON VALIDATION END ===');
            
        } catch (Exception $e) {
            error_log('Coupon validation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while validating the coupon: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Apply coupon to session - FIXED
     */
    public function apply()
    {
        header('Content-Type: application/json');
        
        try {
            error_log('=== COUPON APPLY START ===');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log('Invalid request method for apply: ' . $_SERVER['REQUEST_METHOD']);
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            $code = trim($input['code'] ?? '');
            $userId = Session::get('user_id') ?? 0;
            
            error_log('Apply coupon code: ' . $code);
            error_log('Apply user ID: ' . $userId);
            
            if (empty($code)) {
                error_log('Empty coupon code for apply');
                echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
                return;
            }
            
            // Get cart data
            $cartData = $this->cartModel->getCartWithProducts($this->productModel);
            
            if (empty($cartData['items'])) {
                error_log('Cart is empty for apply');
                echo json_encode(['success' => false, 'message' => 'Cart is empty']);
                return;
            }
            
            $orderAmount = $cartData['total'];
            
            // FIXED: Proper product ID extraction
            $productIds = $this->extractProductIds($cartData['items']);
            
            // Get coupon
            $coupon = $this->couponModel->getCouponByCode($code);
            if (!$coupon) {
                echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
                return;
            }
            
            // Check seller matching for all products in cart
            if (!empty($coupon['seller_id'])) {
                $allProductsMatch = true;
                $mismatchProducts = [];
                
                foreach ($cartData['items'] as $item) {
                    $product = $item['product'] ?? null;
                    if (!$product) {
                        continue;
                    }
                    
                    if (!$this->couponCheck->isValid($coupon, $product)) {
                        $allProductsMatch = false;
                        $mismatchProducts[] = $product['name'] ?? 'Product';
                    }
                }
                
                if (!$allProductsMatch) {
                    $productList = implode(', ', array_slice($mismatchProducts, 0, 3));
                    if (count($mismatchProducts) > 3) {
                        $productList .= ' and more';
                    }
                    echo json_encode([
                        'success' => false,
                        'message' => "This coupon is only valid for products from the same seller. Products: {$productList} do not match."
                    ]);
                    return;
                }
            }
            
            // Validate and apply coupon
            $validation = $this->couponModel->validateCoupon($code, $userId, $orderAmount, $productIds);
            
            if ($validation['valid']) {
                $_SESSION['applied_coupon'] = $validation['coupon'];
                $discount = $this->couponModel->calculateDiscount($validation['coupon'], $orderAmount);
                
                error_log('Coupon applied successfully with discount: ' . $discount);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Coupon applied successfully!',
                    'discount' => $discount,
                    'final_amount' => ($cartData['final_total'] - $discount)
                ]);
            } else {
                error_log('Coupon apply validation failed: ' . $validation['message']);
                echo json_encode([
                    'success' => false,
                    'message' => $validation['message']
                ]);
            }
            
            error_log('=== COUPON APPLY END ===');
            
        } catch (Exception $e) {
            error_log('Coupon apply error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while applying the coupon: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Remove applied coupon from session - Enhanced
     */
    public function remove()
    {
        header('Content-Type: application/json');
        
        try {
            error_log('=== COUPON REMOVE START ===');
            
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                error_log('Invalid request method for remove: ' . $_SERVER['REQUEST_METHOD']);
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            // Remove coupon from session
            if (isset($_SESSION['applied_coupon'])) {
                error_log('Removing coupon: ' . $_SESSION['applied_coupon']['code']);
                unset($_SESSION['applied_coupon']);
            } else {
                error_log('No coupon to remove from session');
            }
            
            // Get updated cart data
            $cartData = $this->cartModel->getCartWithProducts($this->productModel);
            
            echo json_encode([
                'success' => true,
                'message' => 'Coupon removed successfully!',
                'final_amount' => $cartData['final_total']
            ]);
            
            error_log('=== COUPON REMOVE END ===');
            
        } catch (Exception $e) {
            error_log('Coupon remove error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while removing the coupon: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get coupon details by code - For debugging
     */
    public function getCouponDetails()
    {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $code = trim($input['code'] ?? '');
            
            if (empty($code)) {
                echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
                return;
            }
            
            $coupon = $this->couponModel->getCouponByCode($code);
            
            if ($coupon) {
                // Remove sensitive data
                unset($coupon['created_at'], $coupon['updated_at']);
                
                echo json_encode([
                    'success' => true,
                    'coupon' => $coupon
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Coupon not found'
                ]);
            }
            
        } catch (Exception $e) {
            error_log('Get coupon details error: ' . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while fetching coupon details.'
            ]);
        }
    }
    
    /**
     * Debug coupon system - For troubleshooting
     */
    public function debug()
    {
        header('Content-Type: application/json');
        
        try {
            $userId = Session::get('user_id') ?? 0;
            $cartData = $this->cartModel->getCartWithProducts($this->productModel);
            $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
            
            // FIXED: Proper product ID extraction for debug
            $productIds = [];
            if (!empty($cartData['items'])) {
                $productIds = $this->extractProductIds($cartData['items']);
            }
            
            $debugInfo = [
                'user_id' => $userId,
                'cart_total' => $cartData['total'] ?? 0,
                'cart_final_total' => $cartData['final_total'] ?? 0,
                'cart_items_count' => count($cartData['items'] ?? []),
                'product_ids' => $productIds,
                'applied_coupon' => $appliedCoupon ? $appliedCoupon['code'] : null,
                'session_id' => session_id(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            error_log('Coupon debug info: ' . json_encode($debugInfo));
            
            echo json_encode([
                'success' => true,
                'debug_info' => $debugInfo
            ]);
            
        } catch (Exception $e) {
            error_log('Coupon debug error: ' . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Display public coupons page
     */
    public function index()
    {
        try {
            // Initialize performance cache
            if (!class_exists('App\Helpers\PerformanceCache')) {
                require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
            }
            \App\Helpers\PerformanceCache::init();
            
            // Check cache
            $cacheKey = 'coupons_public';
            $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
            if ($cachedData) {
                $this->view('home/coupon', $cachedData);
                return;
            }
            
            // Get all public coupons
            $coupons = $this->couponModel->getPublicCoupons();
            
            $viewData = [
                'coupons' => $coupons,
                'title' => 'Available Coupons'
            ];
            
            // Cache for 30 minutes
            \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $viewData, 1800);
            
            $this->view('home/coupon', $viewData);
        } catch (Exception $e) {
            $environment = $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: 'production';
            if ($environment === 'development' || $environment === 'dev') {
                error_log('Error loading public coupons: ' . $e->getMessage());
            }
            $this->view('home/coupon', [
                'coupons' => [],
                'title' => 'Available Coupons'
            ]);
        }
    }

    // Admin methods below (keeping existing functionality)
    
    public function adminIndex()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        try {
            $coupons = $this->couponModel->getAllCoupons($limit, $offset);
            $totalCoupons = $this->couponModel->getTotalCoupons();
            $totalPages = ceil($totalCoupons / $limit);
            
            $this->view('admin/coupons/index', [
                'coupons' => $coupons,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCoupons' => $totalCoupons,
                'title' => 'Manage Coupons'
            ]);
        } catch (Exception $e) {
            error_log('Coupon index error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load coupons');
            $this->redirect('admin');
        }
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $errors = [];
                $data = $_POST;
                
                // Enhanced validation
                $errors = $this->validateCouponData($data);
                
                if (empty($errors)) {
                    // Process applicable products
                    if (!empty($data['applicable_products']) && is_array($data['applicable_products'])) {
                        $productIds = array_filter(array_map('intval', $data['applicable_products']));
                        $data['applicable_products'] = json_encode($productIds);
                    } else {
                        $data['applicable_products'] = null;
                    }
                    
                    if ($this->couponModel->createCoupon($data)) {
                        $this->setFlash('success', 'Coupon created successfully');
                        $this->redirect('admin/coupons');
                    } else {
                        $errors['general'] = 'Failed to create coupon';
                    }
                }
                
                $this->view('admin/coupons/create', [
                    'errors' => $errors,
                    'data' => $data,
                    'products' => $this->productModel->all(),
                    'title' => 'Create Coupon'
                ]);
            } catch (Exception $e) {
                error_log('Coupon create error: ' . $e->getMessage());
                $this->setFlash('error', 'An error occurred while creating the coupon');
                $this->redirect('admin/coupons');
            }
        } else {
            $this->view('admin/coupons/create', [
                'products' => $this->productModel->all(),
                'title' => 'Create Coupon'
            ]);
        }
    }
    
    private function validateCouponData($data, $excludeId = null)
    {
        $errors = [];
        
        if (empty($data['code'])) {
            $errors['code'] = 'Coupon code is required';
        } elseif (strlen($data['code']) < 3) {
            $errors['code'] = 'Coupon code must be at least 3 characters';
        } else {
            $existingCoupon = $this->couponModel->getCouponByCode($data['code']);
            if ($existingCoupon && (!$excludeId || $existingCoupon['id'] != $excludeId)) {
                $errors['code'] = 'Coupon code already exists';
            }
        }
        
        if (empty($data['discount_type']) || !in_array($data['discount_type'], ['percentage', 'fixed'])) {
            $errors['discount_type'] = 'Valid discount type is required';
        }
        
        if (empty($data['discount_value']) || $data['discount_value'] <= 0) {
            $errors['discount_value'] = 'Discount value must be greater than 0';
        }
        
        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            $errors['discount_value'] = 'Percentage discount cannot exceed 100%';
        }
        
        if (!empty($data['expires_at']) && strtotime($data['expires_at']) <= time()) {
            $errors['expires_at'] = 'Expiry date must be in the future';
        }
        
        if (!empty($data['min_order_amount']) && $data['min_order_amount'] < 0) {
            $errors['min_order_amount'] = 'Minimum order amount cannot be negative';
        }
        
        if (!empty($data['usage_limit_per_user']) && $data['usage_limit_per_user'] < 1) {
            $errors['usage_limit_per_user'] = 'Usage limit per user must be at least 1';
        }
        
        if (!empty($data['usage_limit_global']) && $data['usage_limit_global'] < 1) {
            $errors['usage_limit_global'] = 'Global usage limit must be at least 1';
        }
        
        return $errors;
    }
    
    public function edit($id)
    {
        try {
            $coupon = $this->couponModel->getCouponById($id);
            
            if (!$coupon) {
                $this->setFlash('error', 'Coupon not found');
                $this->redirect('admin/coupons');
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $errors = $this->validateCouponData($_POST, $id);
                
                if (empty($errors)) {
                    $data = $_POST;
                    
                    // Process applicable products
                    if (!empty($data['applicable_products']) && is_array($data['applicable_products'])) {
                        $productIds = array_filter(array_map('intval', $data['applicable_products']));
                        $data['applicable_products'] = json_encode($productIds);
                    } else {
                        $data['applicable_products'] = null;
                    }
                    
                    if ($this->couponModel->updateCoupon($id, $data)) {
                        $this->setFlash('success', 'Coupon updated successfully');
                        $this->redirect('admin/coupons');
                    } else {
                        $errors['general'] = 'Failed to update coupon';
                    }
                }
                
                $this->view('admin/coupons/edit', [
                    'coupon' => $coupon,
                    'errors' => $errors,
                    'data' => $_POST,
                    'products' => $this->productModel->all(),
                    'title' => 'Edit Coupon'
                ]);
            } else {
                // Prepare data for form
                $coupon['applicable_products_array'] = [];
                if ($coupon['applicable_products']) {
                    $coupon['applicable_products_array'] = json_decode($coupon['applicable_products'], true) ?: [];
                }
                
                $this->view('admin/coupons/edit', [
                    'coupon' => $coupon,
                    'products' => $this->productModel->all(),
                    'title' => 'Edit Coupon'
                ]);
            }
        } catch (Exception $e) {
            error_log('Coupon edit error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while editing the coupon');
            $this->redirect('admin/coupons');
        }
    }
    
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $coupon = $this->couponModel->getCouponById($id);
                
                if (!$coupon) {
                    $this->jsonResponse(['success' => false, 'message' => 'Coupon not found'], 404);
                    return;
                }
                
                if ($this->couponModel->deleteCoupon($id)) {
                    $this->jsonResponse(['success' => true, 'message' => 'Coupon deleted successfully']);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to delete coupon'], 500);
                }
            } catch (Exception $e) {
                error_log('Coupon delete error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while deleting the coupon'], 500);
            }
        }
    }

    /**
     * Toggle coupon status (active/inactive)
     */
    public function toggleCoupon($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $coupon = $this->couponModel->getCouponById($id);
                
                if (!$coupon) {
                    $this->jsonResponse(['success' => false, 'message' => 'Coupon not found'], 404);
                    return;
                }
                
                $newStatus = $coupon['status'] === 'active' ? 'inactive' : 'active';
                
                if ($this->couponModel->updateCoupon($id, ['status' => $newStatus])) {
                    $this->jsonResponse(['success' => true, 'message' => 'Coupon status updated successfully', 'new_status' => $newStatus]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update coupon status'], 500);
                }
            } catch (Exception $e) {
                error_log('Coupon toggle error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while updating the coupon'], 500);
            }
        }
    }

    /**
     * Toggle coupon visibility (public/private)
     */
    public function toggleVisibility($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $coupon = $this->couponModel->getCouponById($id);
                
                if (!$coupon) {
                    $this->jsonResponse(['success' => false, 'message' => 'Coupon not found'], 404);
                    return;
                }
                
                $newVisibility = $coupon['visibility'] === 'public' ? 'private' : 'public';
                
                if ($this->couponModel->updateCoupon($id, ['visibility' => $newVisibility])) {
                    $this->jsonResponse(['success' => true, 'message' => 'Coupon visibility updated successfully', 'new_visibility' => $newVisibility]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update coupon visibility'], 500);
                }
            } catch (Exception $e) {
                error_log('Coupon visibility toggle error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while updating the coupon'], 500);
            }
        }
    }
    
    public function toggle($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $coupon = $this->couponModel->getCouponById($id);
                
                if (!$coupon) {
                    $this->jsonResponse(['success' => false, 'message' => 'Coupon not found'], 404);
                    return;
                }
                
                $newStatus = $coupon['is_active'] ? 0 : 1;
                
                if ($this->couponModel->updateCoupon($id, ['is_active' => $newStatus])) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Coupon status updated successfully',
                        'new_status' => $newStatus
                    ]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => 'Failed to update coupon status'], 500);
                }
            } catch (Exception $e) {
                error_log('Coupon toggle error: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'An error occurred while updating coupon status'], 500);
            }
        }
    }
    
    public function stats($id)
    {
        try {
            $stats = $this->couponModel->getCouponStats($id);
            
            if (!$stats) {
                $this->setFlash('error', 'Coupon not found');
                $this->redirect('admin/coupons');
                return;
            }
            
            $this->view('admin/coupons/stats', [
                'stats' => $stats,
                'title' => 'Coupon Statistics'
            ]);
        } catch (Exception $e) {
            error_log('Coupon stats error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load coupon statistics');
            $this->redirect('admin/coupons');
        }
    }
    
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}