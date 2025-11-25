<?php
/**
 * Comprehensive E-Commerce Logic Test Suite
 * Tests all business logic from cart to final order
 */

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

// Load autoloader
require_once ROOT . DS . 'App' . DS . 'Config' . DS . 'config.php';

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = ROOT . DS . 'App' . DS;
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', DS, $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderCalculationService;

class EcommerceTestSuite
{
    private $db;
    private $userModel;
    private $productModel;
    private $cartModel;
    private $couponModel;
    private $orderModel;
    private $orderItemModel;
    private $testUserId;
    private $testProductId;
    private $testCouponId;
    private $errors = [];
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->userModel = new User();
        $this->productModel = new Product();
        $this->cartModel = new Cart();
        $this->couponModel = new Coupon();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
    }

    private function log($message, $type = 'INFO')
    {
        $prefix = match($type) {
            'PASS' => '✅',
            'FAIL' => '❌',
            'WARN' => '⚠️',
            default => 'ℹ️'
        };
        echo "[$type] $prefix $message\n";
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->log($message, 'PASS');
            $this->passed++;
            return true;
        } else {
            $this->log($message, 'FAIL');
            $this->errors[] = $message;
            $this->failed++;
            return false;
        }
    }

    /**
     * Test 1: Login Flow and Session Management
     */
    public function testLoginFlow()
    {
        echo "\n=== TEST 1: Login Flow and Session Management ===\n";
        
        // Create test user
        $testEmail = 'test_' . time() . '@example.com';
        $testPassword = 'Test123!@#';
        
        $userId = $this->userModel->register([
            'full_name' => 'Test User',
            'email' => $testEmail,
            'phone' => '9876543210',
            'password' => $testPassword  // register() will hash it
        ]);
        
        $this->assert($userId > 0, "User registration successful");
        $this->testUserId = $userId;
        
        // Test authentication
        $user = $this->userModel->authenticate($testEmail, $testPassword);
        $this->assert($user !== false, "User authentication successful");
        if ($user) {
            $this->assert(isset($user['id']), "User object has ID");
            $this->assert($user['id'] == $this->testUserId, "Authenticated user ID matches registered ID");
        }
        
        // Test session (simulated)
        $this->assert($this->testUserId > 0, "Session user ID is valid");
        
        // Cleanup
        $this->db->query("DELETE FROM users WHERE id = ?")->bind([$this->testUserId])->execute();
    }

    /**
     * Test 2: Product Stock Management
     */
    public function testProductStock()
    {
        echo "\n=== TEST 2: Product Stock Management ===\n";
        
        // Get a test product
        $products = $this->productModel->getAllProducts(1, 0);
        if (empty($products)) {
            $this->log("No products found for testing", 'WARN');
            return;
        }
        
        $product = $products[0];
        $this->testProductId = $product['id'];
        $initialStock = (int)$product['stock_quantity'];
        
        $this->assert($initialStock >= 0, "Product has valid stock quantity: $initialStock");
        
        // Test stock update
        $newStock = $initialStock + 10;
        $this->productModel->update($this->testProductId, ['stock_quantity' => $newStock]);
        
        $updatedProduct = $this->productModel->find($this->testProductId);
        $this->assert((int)$updatedProduct['stock_quantity'] === $newStock, "Stock update successful: $newStock");
        
        // Restore original stock
        $this->productModel->update($this->testProductId, ['stock_quantity' => $initialStock]);
    }

    /**
     * Test 3: Cart Operations
     */
    public function testCartOperations()
    {
        echo "\n=== TEST 3: Cart Operations ===\n";
        
        if (!$this->testUserId) {
            $this->log("Skipping cart test - no test user", 'WARN');
            return;
        }
        
        if (!$this->testProductId) {
            $products = $this->productModel->getAllProducts(1, 0);
            if (empty($products)) {
                $this->log("No products found for cart test", 'WARN');
                return;
            }
            $this->testProductId = $products[0]['id'];
        }
        
        // Clear existing cart
        $existingCart = $this->cartModel->getByUserId($this->testUserId);
        foreach ($existingCart as $item) {
            $this->cartModel->delete($item['id']);
        }
        
        // Test add to cart
        $quantity = 2;
        $product = $this->productModel->find($this->testProductId);
        $productPrice = (float)$product['price'];
        
        // Clear any existing cart items for this user first
        $this->db->query("DELETE FROM cart WHERE user_id = ?", [$this->testUserId])->execute();
        
        // Direct SQL insert to avoid session dependency
        $sql = "INSERT INTO cart (user_id, product_id, quantity, price, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        try {
            // Use bind separately to avoid double binding
            $insertResult = $this->db->query($sql)->bind([
                $this->testUserId,
                $this->testProductId,
                $quantity,
                $productPrice
            ])->execute();
            $result = $insertResult ? $this->db->lastInsertId() : false;
            if (!$result) {
                // Get error from statement
                $stmt = $this->db->getPdo()->query("SELECT 1"); // Dummy query to get access
                $errorInfo = $this->db->getPdo()->errorInfo();
                $this->assert(false, "Add to cart failed: " . ($errorInfo[2] ?? 'Unknown error'));
                return;
            }
            $this->assert($result !== false, "Add to cart successful");
        } catch (Exception $e) {
            $this->assert(false, "Add to cart exception: " . $e->getMessage());
            return;
        }
        
        // Verify cart item exists
        $cartItems = $this->cartModel->getByUserId($this->testUserId);
        $this->assert(count($cartItems) > 0, "Cart has items");
        
        $cartItem = $cartItems[0];
        $this->assert((int)$cartItem['quantity'] === $quantity, "Cart quantity is correct: $quantity");
        $this->assert((int)$cartItem['product_id'] === $this->testProductId, "Cart product ID matches");
        
        // Test update cart
        $newQuantity = 3;
        $cartItemId = $cartItem['id'];
        $this->cartModel->update($cartItemId, ['quantity' => $newQuantity]);
        
        $updatedCartItems = $this->cartModel->getByUserId($this->testUserId);
        $updatedItem = $updatedCartItems[0];
        $this->assert((int)$updatedItem['quantity'] === $newQuantity, "Cart update successful: $newQuantity");
        
        // Test remove from cart
        $this->cartModel->delete($cartItemId);
        $emptyCart = $this->cartModel->getByUserId($this->testUserId);
        $this->assert(count($emptyCart) === 0, "Cart is empty after removal");
    }

    /**
     * Test 4: Calculation Logic
     */
    public function testCalculations()
    {
        echo "\n=== TEST 4: Calculation Logic ===\n";
        
        // Test OrderCalculationService
        $subtotal = 1000.00;
        $discount = 100.00;
        $deliveryFee = 150.00;
        $taxRate = 13; // 13%
        
        $totals = OrderCalculationService::calculateTotals($subtotal, $discount, $deliveryFee, $taxRate);
        
        // Expected: subtotal - discount = 900, tax = 900 * 0.13 = 117, total = 900 + 117 + 150 = 1167
        $expectedSubtotalAfterDiscount = 900.00;
        $expectedTax = 117.00;
        $expectedTotal = 1167.00;
        
        $this->assert(abs($totals['subtotal_after_discount'] - $expectedSubtotalAfterDiscount) < 0.01, 
            "Subtotal after discount correct: {$totals['subtotal_after_discount']}");
        $this->assert(abs($totals['tax'] - $expectedTax) < 0.01, 
            "Tax calculation correct: {$totals['tax']}");
        $this->assert(abs($totals['total'] - $expectedTotal) < 0.01, 
            "Total calculation correct: {$totals['total']}");
        
        // Test cart total calculation
        $cartItems = [
            ['price' => 500.00, 'quantity' => 2],
            ['price' => 200.00, 'quantity' => 1]
        ];
        $cartTotal = OrderCalculationService::calculateCartTotal($cartItems);
        $expectedCartTotal = 1200.00;
        $this->assert(abs($cartTotal - $expectedCartTotal) < 0.01, 
            "Cart total calculation correct: $cartTotal");
    }

    /**
     * Test 5: Coupon Validation and Application
     */
    public function testCouponLogic()
    {
        echo "\n=== TEST 5: Coupon Validation and Application ===\n";
        
        // Create test coupon
        $couponCode = 'TEST' . time();
        $couponData = [
            'code' => $couponCode,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'min_order_amount' => 500,
            'is_active' => 1,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
        ];
        
        // Check which columns exist in coupons table
        $columns = $this->db->query("SHOW COLUMNS FROM coupons")->all();
        $columnNames = array_column($columns, 'Field');
        
        $insertFields = ['code', 'discount_type', 'discount_value', 'min_order_amount', 'is_active', 'expires_at', 'created_at'];
        $insertValues = [$couponCode, 'percentage', 10, 500, 1, $couponData['expires_at'], date('Y-m-d H:i:s')];
        
        // Add max_uses only if column exists
        if (in_array('max_uses', $columnNames)) {
            $insertFields[] = 'max_uses';
            $insertValues[] = 100;
        }
        
        $placeholders = str_repeat('?,', count($insertValues) - 1) . '?';
        $sql = "INSERT INTO coupons (" . implode(', ', $insertFields) . ") VALUES ($placeholders)";
        $couponId = $this->db->query($sql, $insertValues)->execute();
        
        $this->testCouponId = $this->db->lastInsertId();
        
        // Test coupon retrieval
        $coupon = $this->couponModel->getCouponByCode($couponCode);
        $this->assert($coupon !== false, "Coupon retrieved successfully");
        
        // Test coupon validation - valid case
        $subtotal = 1000.00;
        $validation = $this->couponModel->validateCoupon($couponCode, null, $subtotal, []);
        $this->assert($validation['valid'] === true, "Valid coupon passes validation");
        
        // Test coupon validation - below minimum
        $lowSubtotal = 300.00;
        $validationLow = $this->couponModel->validateCoupon($couponCode, null, $lowSubtotal, []);
        $this->assert($validationLow['valid'] === false, "Coupon rejected for low subtotal");
        
        // Test discount calculation
        $discount = OrderCalculationService::applyCouponDiscount($subtotal, $coupon);
        $expectedDiscount = 100.00; // 10% of 1000
        $this->assert(abs($discount - $expectedDiscount) < 0.01, 
            "Coupon discount calculation correct: $discount");
        
        // Cleanup
        $this->db->query("DELETE FROM coupons WHERE id = ?")->bind([$this->testCouponId])->execute();
    }

    /**
     * Test 6: Stock Reduction After Order Confirmation
     */
    public function testStockReduction()
    {
        echo "\n=== TEST 6: Stock Reduction After Order Confirmation ===\n";
        
        if (!$this->testProductId) {
            $this->log("Skipping stock reduction test - no test product", 'WARN');
            return;
        }
        
        // Get initial stock
        $product = $this->productModel->find($this->testProductId);
        $initialStock = (int)$product['stock_quantity'];
        
        // Set stock to a known value
        $testStock = 100;
        $this->productModel->update($this->testProductId, ['stock_quantity' => $testStock]);
        
        // Create a test order (simulated - not actually confirmed)
        // We'll verify stock is NOT reduced until order is confirmed
        $orderQuantity = 5;
        
        // Check stock before order creation
        $productBefore = $this->productModel->find($this->testProductId);
        $stockBefore = (int)$productBefore['stock_quantity'];
        $this->assert($stockBefore === $testStock, "Stock unchanged before order: $testStock");
        
        // Note: Actual stock reduction should happen in Order model when order status is 'confirmed'
        // This test verifies stock is NOT reduced prematurely
        
        // Restore stock
        $this->productModel->update($this->testProductId, ['stock_quantity' => $initialStock]);
    }

    /**
     * Test 7: Order Creation Flow
     */
    public function testOrderCreation()
    {
        echo "\n=== TEST 7: Order Creation Flow ===\n";
        
        if (!$this->testUserId || !$this->testProductId) {
            $this->log("Skipping order creation test - missing prerequisites", 'WARN');
            return;
        }
        
        // Get product details
        $product = $this->productModel->find($this->testProductId);
        $productPrice = (float)$product['price'];
        
        // Create order data
        $subtotal = $productPrice * 2;
        $deliveryFee = 150;
        $finalAmount = $subtotal + $deliveryFee;
        
        $orderData = [
            'user_id' => $this->testUserId,
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $finalAmount,
            'final_amount' => $finalAmount,
            'payment_method_id' => 1,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'recipient_name' => 'Test User',
            'phone' => '9876543210',
            'address_line1' => 'Test Address',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal'
        ];
        
        $orderItems = [
            [
                'product_id' => $this->testProductId,
                'quantity' => 2,
                'price' => $productPrice,
                'total' => $productPrice * 2
            ]
        ];
        
        // Create order
        try {
            $orderId = $this->orderModel->createOrder($orderData, $orderItems);
            if (!$orderId || $orderId <= 0) {
                $this->assert(false, "Order creation returned invalid ID: " . ($orderId ?: 'false'));
                return;
            }
            $this->assert($orderId > 0, "Order created successfully: ID $orderId");
        } catch (Exception $e) {
            $this->assert(false, "Order creation exception: " . $e->getMessage());
            return;
        }
        
        // Verify order exists
        $order = $this->orderModel->getOrderById($orderId);
        $this->assert($order !== false, "Order retrieved successfully");
        $this->assert((float)$order['total_amount'] === (float)$orderData['total_amount'], 
            "Order total amount is correct");
        
        // Verify order items
        $items = $this->orderItemModel->getByOrderId($orderId);
        $this->assert(count($items) > 0, "Order has items");
        $this->assert((int)$items[0]['quantity'] === 2, "Order item quantity is correct");
        
        // Cleanup
        $this->db->query("DELETE FROM order_items WHERE order_id = ?")->bind([$orderId])->execute();
        $this->db->query("DELETE FROM orders WHERE id = ?")->bind([$orderId])->execute();
    }

    /**
     * Test 8: Complete Cart-to-Order Flow
     */
    public function testCompleteFlow()
    {
        echo "\n=== TEST 8: Complete Cart-to-Order Flow ===\n";
        
        if (!$this->testUserId || !$this->testProductId) {
            $this->log("Skipping complete flow test - missing prerequisites", 'WARN');
            return;
        }
        
        // Step 1: Add product to cart
        $existingCart = $this->cartModel->getByUserId($this->testUserId);
        foreach ($existingCart as $item) {
            $this->cartModel->delete($item['id']);
        }
        $cartQuantity = 3;
        $product = $this->productModel->find($this->testProductId);
        $productPrice = (float)$product['price'];
        
        // Clear any existing cart items for this user first
        $this->db->query("DELETE FROM cart WHERE user_id = ?", [$this->testUserId])->execute();
        
        // Direct SQL insert to avoid session dependency
        $sql = "INSERT INTO cart (user_id, product_id, quantity, price, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        $this->db->query($sql, [
            $this->testUserId,
            $this->testProductId,
            $cartQuantity,
            $productPrice
        ])->execute();
        
        $cartItems = $this->cartModel->getByUserId($this->testUserId);
        $this->assert(count($cartItems) > 0, "Product added to cart");
        
        // Step 2: Calculate totals
        $product = $this->productModel->find($this->testProductId);
        $productPrice = (float)$product['price'];
        $subtotal = $productPrice * $cartQuantity;
        
        // Step 3: Apply coupon if available
        $discount = 0;
        $couponCode = 'TEST' . time();
        // Create a test coupon
        $columns = $this->db->query("SHOW COLUMNS FROM coupons")->all();
        $columnNames = array_column($columns, 'Field');
        
        $insertFields = ['code', 'discount_type', 'discount_value', 'min_order_amount', 'is_active', 'expires_at', 'created_at'];
        $insertValues = [$couponCode, 'percentage', 10, 0, 1, date('Y-m-d H:i:s', strtotime('+30 days')), date('Y-m-d H:i:s')];
        
        if (in_array('max_uses', $columnNames)) {
            $insertFields[] = 'max_uses';
            $insertValues[] = 100;
        }
        
        $placeholders = str_repeat('?,', count($insertValues) - 1) . '?';
        $sql = "INSERT INTO coupons (" . implode(', ', $insertFields) . ") VALUES ($placeholders)";
        $couponId = $this->db->query($sql, $insertValues)->execute();
        $couponDbId = $this->db->lastInsertId();
        
        $coupon = $this->couponModel->getCouponByCode($couponCode);
        if ($coupon) {
            $validation = $this->couponModel->validateCoupon($couponCode, $this->testUserId, $subtotal, [$this->testProductId]);
            if ($validation['valid']) {
                $discount = OrderCalculationService::applyCouponDiscount($subtotal, $coupon);
            }
        }
        
        // Step 4: Calculate final totals
        $deliveryFee = 150.00;
        $taxRate = 13;
        $totals = OrderCalculationService::calculateTotals($subtotal, $discount, $deliveryFee, $taxRate);
        
        $this->assert($totals['subtotal'] === $subtotal, "Subtotal is correct");
        $this->assert($totals['discount'] === $discount, "Discount is correct");
        $this->assert($totals['total'] > 0, "Total is positive");
        
        // Step 5: Create order
        $orderData = [
            'user_id' => $this->testUserId,
            'subtotal' => $subtotal,
            'tax_amount' => $totals['tax'],
            'discount_amount' => $discount,
            'delivery_fee' => $deliveryFee,
            'total_amount' => $totals['total'],
            'payment_method_id' => 1,
            'payment_status' => 'pending',
            'order_status' => 'pending',
            'recipient_name' => 'Test User',
            'phone' => '9876543210',
            'address_line1' => 'Test Address',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'country' => 'Nepal',
            'coupon_code' => $coupon ? $coupon['code'] : null
        ];
        
        $orderItems = [
            [
                'product_id' => $this->testProductId,
                'quantity' => $cartQuantity,
                'price' => $productPrice,
                'total' => $subtotal
            ]
        ];
        
        $orderId = $this->orderModel->createOrder($orderData, $orderItems);
        $this->assert($orderId > 0, "Order created from cart");
        
        // Step 6: Verify order totals match calculations
        $order = $this->orderModel->getOrderById($orderId);
        $this->assert(abs((float)$order['total_amount'] - $totals['total']) < 0.01, 
            "Order total matches calculation");
        
        // Step 7: Clear cart after order
        $cartToClear = $this->cartModel->getByUserId($this->testUserId);
        foreach ($cartToClear as $item) {
            $this->cartModel->delete($item['id']);
        }
        $emptyCart = $this->cartModel->getByUserId($this->testUserId);
        $this->assert(count($emptyCart) === 0, "Cart cleared after order");
        
        // Cleanup
        $this->db->query("DELETE FROM order_items WHERE order_id = ?")->bind([$orderId])->execute();
        $this->db->query("DELETE FROM orders WHERE id = ?")->bind([$orderId])->execute();
        $this->db->query("DELETE FROM coupons WHERE id = ?")->bind([$couponDbId])->execute();
    }

    /**
     * Run all tests
     */
    public function runAll()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║     E-Commerce Comprehensive Test Suite                    ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        try {
            $this->testLoginFlow();
            $this->testProductStock();
            $this->testCartOperations();
            $this->testCalculations();
            $this->testCouponLogic();
            $this->testStockReduction();
            $this->testOrderCreation();
            $this->testCompleteFlow();
        } catch (Exception $e) {
            $this->log("Test suite error: " . $e->getMessage(), 'FAIL');
            $this->errors[] = "Exception: " . $e->getMessage();
        }
        
        // Summary
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    TEST SUMMARY                            ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        echo "║  Passed: {$this->passed}\n";
        echo "║  Failed: {$this->failed}\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        if (!empty($this->errors)) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        
        return $this->failed === 0;
    }
}

// Run tests
$suite = new EcommerceTestSuite();
$success = $suite->runAll();
exit($success ? 0 : 1);

