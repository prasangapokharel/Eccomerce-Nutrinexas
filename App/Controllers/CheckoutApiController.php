<?php

namespace App\Controllers;

/**
 * Checkout API Controller
 * Handles checkout process, payment methods, and order placement
 */
class CheckoutApiController extends ApiController {
    
    public function __construct() {
        parent::__construct();
        $this->handleRequest();
    }
    
    /**
     * Route the request to appropriate method
     */
    private function handleRequest() {
        $pathParts = explode('/', $this->endpoint);
        
        if (count($pathParts) < 3 || $pathParts[1] !== 'checkout') {
            $this->sendError('Invalid endpoint', 404);
        }
        
        $action = $pathParts[2] ?? 'index';
        $id = $pathParts[3] ?? null;
        
        switch ($this->requestMethod) {
            case 'GET':
                if ($action === 'payment-methods') {
                    $this->getPaymentMethods();
                } elseif ($action === 'cart') {
                    $this->getCart();
                } elseif ($action === 'shipping-options') {
                    $this->getShippingOptions();
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                if ($action === 'add-to-cart') {
                    $this->addToCart();
                } elseif ($action === 'update-cart') {
                    $this->updateCart();
                } elseif ($action === 'remove-from-cart') {
                    $this->removeFromCart();
                } elseif ($action === 'apply-coupon') {
                    $this->applyCoupon();
                } elseif ($action === 'calculate-shipping') {
                    $this->calculateShipping();
                } elseif ($action === 'place-order') {
                    $this->placeOrder();
                } else {
                    $this->index();
                }
                break;
            case 'DELETE':
                if ($action === 'clear-cart') {
                    $this->clearCart();
                } else {
                    $this->sendError('Method not allowed', 405);
                }
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Get available payment methods
     */
    public function getPaymentMethods() {
        $paymentMethods = [
            [
                'id' => 'cash_on_delivery',
                'name' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order',
                'icon' => '💵',
                'enabled' => true,
                'processing_fee' => 0,
                'min_amount' => 0,
                'max_amount' => null
            ],
            [
                'id' => 'esewa',
                'name' => 'eSewa',
                'description' => 'Digital wallet payment',
                'icon' => '📱',
                'enabled' => true,
                'processing_fee' => 0.02, // 2%
                'min_amount' => 100,
                'max_amount' => 50000
            ],
            [
                'id' => 'khalti',
                'name' => 'Khalti',
                'description' => 'Digital payment solution',
                'icon' => '💳',
                'enabled' => true,
                'processing_fee' => 0.02, // 2%
                'min_amount' => 100,
                'max_amount' => 100000
            ],
            [
                'id' => 'bank_transfer',
                'name' => 'Bank Transfer',
                'description' => 'Direct bank transfer',
                'icon' => '🏦',
                'enabled' => true,
                'processing_fee' => 0,
                'min_amount' => 1000,
                'max_amount' => null
            ],
            [
                'id' => 'credit_card',
                'name' => 'Credit/Debit Card',
                'description' => 'Visa, Mastercard, American Express',
                'icon' => '💳',
                'enabled' => false, // Disabled for now
                'processing_fee' => 0.025, // 2.5%
                'min_amount' => 100,
                'max_amount' => 100000
            ]
        ];
        
        $this->sendResponse([
            'payment_methods' => $paymentMethods,
            'default_method' => 'cash_on_delivery'
        ]);
    }
    
    /**
     * Get user's shopping cart
     */
    public function getCart() {
        $this->authenticate();
        
        $userId = $this->user['id'];
        
        // Get cart items
        $cartSql = "SELECT c.*, p.product_name, p.image, p.price, p.sale_price, p.stock_quantity, p.is_scheduled, p.scheduled_date
                    FROM cart c 
                    LEFT JOIN products p ON c.product_id = p.id 
                    WHERE c.user_id = ?";
        
        $cartItemsStmt = $this->db->query($cartSql, [$userId]);
        if (!$cartItemsStmt) {
            $this->sendError('Failed to fetch cart items', 500);
        }
        $cartItems = $cartItemsStmt->fetchAll();
        
        // Calculate totals
        $subtotal = 0;
        $totalItems = 0;
        $scheduledItems = [];
        
        foreach ($cartItems as &$item) {
            $price = $item['sale_price'] ?: $item['price'];
            $item['final_price'] = $price;
            $item['item_total'] = $price * $item['quantity'];
            $subtotal += $item['item_total'];
            $totalItems += $item['quantity'];
            
            // Check if item is scheduled
            if ($item['is_scheduled'] && $item['scheduled_date']) {
                $scheduledDate = new \DateTime($item['scheduled_date']);
                $now = new \DateTime();
                if ($scheduledDate > $now) {
                    $scheduledItems[] = $item;
                }
            }
        }
        
        // Get user addresses
        $addressesSql = "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC";
        $addressesStmt = $this->db->query($addressesSql, [$userId]);
        if (!$addressesStmt) {
            $this->sendError('Failed to fetch addresses', 500);
        }
        $addresses = $addressesStmt->fetchAll();
        
        // Get available coupons
        $couponsSql = "SELECT * FROM coupons WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())";
        $couponsStmt = $this->db->query($couponsSql);
        if (!$couponsStmt) {
            $this->sendError('Failed to fetch coupons', 500);
        }
        $coupons = $couponsStmt->fetchAll();
        
        $cart = [
            'items' => $cartItems,
            'summary' => [
                'total_items' => $totalItems,
                'subtotal' => $subtotal,
                'shipping_cost' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => $subtotal
            ],
            'addresses' => $addresses,
            'coupons' => $coupons,
            'scheduled_items' => $scheduledItems,
            'can_checkout' => empty($scheduledItems) && $subtotal > 0
        ];
        
        $this->sendResponse($cart);
    }
    
    /**
     * Add item to cart
     */
    public function addToCart() {
        $this->authenticate();
        
        $this->validateRequired($this->params, ['product_id', 'quantity']);
        
        $userId = $this->user['id'];
        $productId = (int)$this->params['product_id'];
        $quantity = (int)$this->params['quantity'];
        
        if ($quantity <= 0) {
            $this->sendError('Quantity must be greater than 0', 400);
        }
        
        // Check if product exists and is available
        $productStmt = $this->db->query(
            "SELECT id, product_name, price, stock_quantity, is_scheduled, scheduled_date FROM products WHERE id = ?", 
            [$productId]
        );
        if (!$productStmt) {
            $this->sendError('Failed to fetch product', 500);
        }
        $product = $productStmt->fetch();
        
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        // Check if product is scheduled
        if ($product['is_scheduled'] && $product['scheduled_date']) {
            $scheduledDate = new \DateTime($product['scheduled_date']);
            $now = new \DateTime();
            if ($scheduledDate > $now) {
                $this->sendError('Product is not yet available for purchase', 400);
            }
        }
        
        // Check stock availability
        if ($product['stock_quantity'] < $quantity) {
            $this->sendError("Only {$product['stock_quantity']} items available in stock", 400);
        }
        
        // Check if item already in cart
        $existingItemStmt = $this->db->query(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?", 
            [$userId, $productId]
        );
        if (!$existingItemStmt) {
            $this->sendError('Failed to check existing cart item', 500);
        }
        $existingItem = $existingItemStmt->fetch();
        
        if ($existingItem) {
            // Update quantity
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock_quantity']) {
                $this->sendError("Cannot add more items than available in stock", 400);
            }
            
            $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $result = $this->db->query($sql, [$newQuantity, $existingItem['id']]);
        } else {
            // Add new item
            $sql = "INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
            $result = $this->db->query($sql, [$userId, $productId, $quantity]);
        }
        
        if ($result) {
            $this->sendResponse(['message' => 'Item added to cart successfully']);
        } else {
            $this->sendError('Failed to add item to cart', 500);
        }
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCart() {
        $this->authenticate();
        
        $this->validateRequired($this->params, ['product_id', 'quantity']);
        
        $userId = $this->user['id'];
        $productId = (int)$this->params['product_id'];
        $quantity = (int)$this->params['quantity'];
        
        if ($quantity <= 0) {
            $this->sendError('Quantity must be greater than 0', 400);
        }
        
        // Check stock availability
        $productStmt = $this->db->query(
            "SELECT stock_quantity FROM products WHERE id = ?", 
            [$productId]
        );
        if (!$productStmt) {
            $this->sendError('Failed to fetch product', 500);
        }
        $product = $productStmt->fetch();
        
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        if ($product['stock_quantity'] < $quantity) {
            $this->sendError("Only {$product['stock_quantity']} items available in stock", 400);
        }
        
        // Update cart item
        $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql, [$quantity, $userId, $productId]);
        
        if ($result) {
            $this->sendResponse(['message' => 'Cart updated successfully']);
        } else {
            $this->sendError('Failed to update cart', 500);
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart() {
        $this->authenticate();
        
        $this->validateRequired($this->params, ['product_id']);
        
        $userId = $this->user['id'];
        $productId = (int)$this->params['product_id'];
        
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql, [$userId, $productId]);
        
        if ($result) {
            $this->sendResponse(['message' => 'Item removed from cart successfully']);
        } else {
            $this->sendError('Failed to remove item from cart', 500);
        }
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart() {
        $this->authenticate();
        
        $userId = $this->user['id'];
        
        $sql = "DELETE FROM cart WHERE user_id = ?";
        $result = $this->db->query($sql, [$userId]);
        
        if ($result) {
            $this->sendResponse(['message' => 'Cart cleared successfully']);
        } else {
            $this->sendError('Failed to clear cart', 500);
        }
    }
    
    /**
     * Apply coupon code
     */
    public function applyCoupon() {
        $this->authenticate();
        
        $this->validateRequired($this->params, ['coupon_code']);
        
        $userId = $this->user['id'];
        $couponCode = strtoupper(trim($this->params['coupon_code']));
        
        // Get coupon details
        $couponStmt = $this->db->query(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())", 
            [$couponCode]
        );
        if (!$couponStmt) {
            $this->sendError('Failed to fetch coupon', 500);
        }
        $coupon = $couponStmt->fetch();
        
        if (!$coupon) {
            $this->sendError('Invalid or expired coupon code', 400);
        }
        
        // Check usage limits
        $usageCountStmt = $this->db->query(
            "SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = ? AND user_id = ?", 
            [$coupon['id'], $userId]
        );
        if (!$usageCountStmt) {
            $this->sendError('Failed to fetch coupon usage', 500);
        }
        $usageCount = $usageCountStmt->fetch();
        
        if ($usageCount['count'] >= $coupon['max_usage_per_user']) {
            $this->sendError('Coupon usage limit exceeded', 400);
        }
        
        // Get cart total
        $cartTotalStmt = $this->db->query(
            "SELECT SUM(c.quantity * COALESCE(p.sale_price, p.price)) as total 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?", 
            [$userId]
        );
        if (!$cartTotalStmt) {
            $this->sendError('Failed to fetch cart total', 500);
        }
        $cartTotal = $cartTotalStmt->fetch();
        
        $total = $cartTotal['total'] ?? 0;
        
        // Check minimum order amount
        if ($total < $coupon['min_order_amount']) {
            $this->sendError("Minimum order amount of {$coupon['min_order_amount']} required for this coupon", 400);
        }
        
        // Calculate discount
        $discountAmount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = ($total * $coupon['discount_value']) / 100;
            if ($coupon['max_discount_amount']) {
                $discountAmount = min($discountAmount, $coupon['max_discount_amount']);
            }
        } else {
            $discountAmount = $coupon['discount_value'];
        }
        
        $this->sendResponse([
            'coupon' => $coupon,
            'discount_amount' => $discountAmount,
            'final_total' => $total - $discountAmount
        ]);
    }
    
    /**
     * Get shipping options
     */
    public function getShippingOptions() {
        $this->authenticate();
        
        $addressId = $this->params['address_id'] ?? null;
        
        if (!$addressId) {
            $this->sendError('Address ID is required', 400);
        }
        
        // Get address details
        $addressStmt = $this->db->query(
            "SELECT * FROM addresses WHERE id = ? AND user_id = ?", 
            [$addressId, $this->user['id']]
        );
        if (!$addressStmt) {
            $this->sendError('Failed to fetch address', 500);
        }
        $address = $addressStmt->fetch();
        
        if (!$address) {
            $this->sendError('Invalid address', 400);
        }
        
        // Get cart total weight
        $cartWeightStmt = $this->db->query(
            "SELECT SUM(c.quantity * COALESCE(p.weight, 0)) as total_weight 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?", 
            [$this->user['id']]
        );
        if (!$cartWeightStmt) {
            $this->sendError('Failed to fetch cart weight', 500);
        }
        $cartWeight = $cartWeightStmt->fetch();
        
        $totalWeight = $cartWeight['total_weight'] ?? 0;
        
        // Define shipping options based on location and weight
        $shippingOptions = [
            [
                'id' => 'standard',
                'name' => 'Standard Delivery',
                'description' => '3-5 business days',
                'cost' => $this->calculateShippingCost($address['city'], $totalWeight, 'standard'),
                'estimated_days' => '3-5 days'
            ],
            [
                'id' => 'express',
                'name' => 'Express Delivery',
                'description' => '1-2 business days',
                'cost' => $this->calculateShippingCost($address['city'], $totalWeight, 'express'),
                'estimated_days' => '1-2 days'
            ],
            [
                'id' => 'same_day',
                'name' => 'Same Day Delivery',
                'description' => 'Available in Kathmandu Valley',
                'cost' => $this->calculateShippingCost($address['city'], $totalWeight, 'same_day'),
                'estimated_days' => 'Same day',
                'available' => in_array(strtolower($address['city']), ['kathmandu', 'lalitpur', 'bhaktapur'])
            ]
        ];
        
        $this->sendResponse([
            'shipping_options' => $shippingOptions,
            'address' => $address,
            'total_weight' => $totalWeight
        ]);
    }
    
    /**
     * Calculate shipping cost
     */
    private function calculateShippingCost($city, $weight, $method) {
        $baseCost = 0;
        
        // Base cost by city
        $cityCosts = [
            'kathmandu' => 100,
            'lalitpur' => 120,
            'bhaktapur' => 150,
            'pokhara' => 200,
            'biratnagar' => 250,
            'butwal' => 180
        ];
        
        $cityLower = strtolower($city);
        $baseCost = $cityCosts[$cityLower] ?? 300; // Default for other cities
        
        // Add weight-based cost
        if ($weight > 0) {
            $weightCost = ceil($weight / 1000) * 50; // 50 per kg
            $baseCost += $weightCost;
        }
        
        // Method multiplier
        $methodMultipliers = [
            'standard' => 1.0,
            'express' => 1.5,
            'same_day' => 2.0
        ];
        
        $baseCost *= $methodMultipliers[$method] ?? 1.0;
        
        return round($baseCost);
    }

    /**
     * Calculate shipping for selected address and method
     */
    public function calculateShipping() {
        $this->authenticate();
        $this->validateRequired($this->params, ['address_id', 'shipping_method']);

        $userId = $this->user['id'];
        $addressId = (int)($this->params['address_id']);
        $shippingMethod = $this->params['shipping_method'];

        $addressStmt = $this->db->query(
            "SELECT * FROM addresses WHERE id = ? AND user_id = ?",
            [$addressId, $userId]
        );
        if (!$addressStmt) {
            $this->sendError('Failed to fetch address', 500);
        }
        $address = $addressStmt->fetch();
        if (!$address) {
            $this->sendError('Invalid address', 400);
        }

        $cartWeightStmt = $this->db->query(
            "SELECT SUM(c.quantity * COALESCE(p.weight, 0)) as total_weight 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?",
            [$userId]
        );
        if (!$cartWeightStmt) {
            $this->sendError('Failed to fetch cart weight', 500);
        }
        $cartWeight = $cartWeightStmt->fetch();
        $totalWeight = $cartWeight['total_weight'] ?? 0;

        $shippingCost = $this->calculateShippingCost($address['city'], $totalWeight, $shippingMethod);

        $this->sendResponse([
            'shipping_cost' => $shippingCost,
            'address' => $address,
            'shipping_method' => $shippingMethod,
            'total_weight' => $totalWeight
        ]);
    }
    
    /**
     * Place order
     */
    public function placeOrder() {
        $this->authenticate();
        
        $this->validateRequired($this->params, ['address_id', 'payment_method', 'shipping_method']);
        
        $userId = $this->user['id'];
        $addressId = (int)$this->params['address_id'];
        $paymentMethod = $this->params['payment_method'];
        $shippingMethod = $this->params['shipping_method'];
        $couponCode = $this->params['coupon_code'] ?? null;
        $notes = $this->params['notes'] ?? '';
        
        // Validate address
        $addressStmt = $this->db->query(
            "SELECT * FROM addresses WHERE id = ? AND user_id = ?", 
            [$addressId, $userId]
        );
        if (!$addressStmt) {
            $this->sendError('Failed to fetch address', 500);
        }
        $address = $addressStmt->fetch();
        
        if (!$address) {
            $this->sendError('Invalid address', 400);
        }
        
        // Validate payment method
        $paymentMethods = $this->getPaymentMethods();
        $validPaymentMethod = false;
        foreach ($paymentMethods['data']['payment_methods'] as $method) {
            if ($method['id'] === $paymentMethod && $method['enabled']) {
                $validPaymentMethod = true;
                break;
            }
        }
        
        if (!$validPaymentMethod) {
            $this->sendError('Invalid payment method', 400);
        }
        
        // Get cart items
        $cartItemsStmt = $this->db->query(
            "SELECT c.*, p.product_name, p.price, p.sale_price, p.stock_quantity, p.weight 
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?", 
            [$userId]
        );
        if (!$cartItemsStmt) {
            $this->sendError('Failed to fetch cart items', 500);
        }
        $cartItems = $cartItemsStmt->fetchAll();
        
        if (empty($cartItems)) {
            $this->sendError('Cart is empty', 400);
        }
        
        // Calculate totals
        $subtotal = 0;
        $totalWeight = 0;
        $orderItems = [];
        
        foreach ($cartItems as $item) {
            $price = $item['sale_price'] ?: $item['price'];
            $itemTotal = $price * $item['quantity'];
            $subtotal += $itemTotal;
            $totalWeight += ($item['weight'] ?? 0) * $item['quantity'];
            
            // Check stock
            if ($item['stock_quantity'] < $item['quantity']) {
                $this->sendError("Insufficient stock for {$item['product_name']}", 400);
            }
            
            $orderItems[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $price,
                'total' => $itemTotal
            ];
        }
        
        // Calculate shipping cost
        $shippingCost = $this->calculateShippingCost($address['city'], $totalWeight, $shippingMethod);
        
        // Apply coupon if provided
        $discountAmount = 0;
        $couponId = null;
        if ($couponCode) {
            $coupon = $this->db->query(
                "SELECT * FROM coupons WHERE code = ? AND is_active = 1", 
                [$couponCode]
            )->fetch();
            
            if ($coupon && $subtotal >= $coupon['min_order_amount']) {
                if ($coupon['discount_type'] === 'percentage') {
                    $discountAmount = ($subtotal * $coupon['discount_value']) / 100;
                    if ($coupon['max_discount_amount']) {
                        $discountAmount = min($discountAmount, $coupon['max_discount_amount']);
                    }
                } else {
                    $discountAmount = $coupon['discount_value'];
                }
                $couponId = $coupon['id'];
            }
        }
        
        // Calculate tax using dynamic rate from settings
        $settingModel = new \App\Models\Setting();
        $taxRate = $settingModel->get('tax_rate', 13) / 100; // Convert percentage to decimal
        $taxAmount = ($subtotal - $discountAmount) * $taxRate;
        
        $totalAmount = $subtotal - $discountAmount + $shippingCost + $taxAmount;
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // Create order
            $orderData = [
                'customer_id' => $userId,
                'address_id' => $addressId,
                'coupon_id' => $couponId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'shipping_method' => $shippingMethod,
                'notes' => $notes,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $orderSql = "INSERT INTO orders (
                customer_id, address_id, coupon_id, subtotal, discount_amount, 
                shipping_cost, tax_amount, total_amount, status, payment_method, 
                shipping_method, notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $orderResult = $this->db->query($orderSql, array_values($orderData));
            
            if (!$orderResult) {
                throw new \Exception('Failed to create order');
            }
            
            $orderId = $this->db->lastInsertId();
            
            // Create order items
            foreach ($orderItems as $item) {
                $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)";
                $itemResult = $this->db->query($itemSql, [$orderId, $item['product_id'], $item['quantity'], $item['price'], $item['total']]);
                
                if (!$itemResult) {
                    throw new \Exception('Failed to create order item');
                }
                
                // NOTE: Stock should NOT be reduced here - it should only be reduced when order is confirmed/paid
                // Stock reduction happens in AdminController::updateOrderStatus when status changes to 'paid'/'processing'/'confirmed'
            }
            
            // Record coupon usage if applicable
            if ($couponId) {
                $usageSql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, used_at) VALUES (?, ?, ?, NOW())";
                $this->db->query($usageSql, [$couponId, $userId, $orderId]);
            }
            
            // Clear cart
            $this->db->query("DELETE FROM cart WHERE user_id = ?", [$userId]);
            
            // Commit transaction
            $this->db->commit();
            
            // Get the created order
            $createdOrderStmt = $this->db->query(
                "SELECT o.*, u.name as customer_name, u.email as customer_email 
                 FROM orders o 
                 LEFT JOIN users u ON o.customer_id = u.id 
                 WHERE o.id = ?", 
                [$orderId]
            );
            if (!$createdOrderStmt) {
                throw new \Exception('Failed to fetch created order');
            }
            $createdOrder = $createdOrderStmt->fetch();
            
            $this->sendResponse([
                'order' => $createdOrder,
                'message' => 'Order placed successfully',
                'order_number' => 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT)
            ], 201);
            
            // Notify sellers about new order asynchronously (non-blocking) - after response sent
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
                try {
                    $notificationService = new \App\Services\SellerNotificationService();
                    $notificationService->notifyNewOrder($orderId);
                } catch (\Exception $e) {
                    error_log('API Checkout: Error sending seller notifications: ' . $e->getMessage());
                }
            } else {
                register_shutdown_function(function() use ($orderId) {
                    try {
                        $notificationService = new \App\Services\SellerNotificationService();
                        $notificationService->notifyNewOrder($orderId);
                    } catch (\Exception $e) {
                        error_log('API Checkout: Error sending seller notifications: ' . $e->getMessage());
                    }
                });
            }
            
        } catch (\Exception $e) {
            // Rollback transaction
            $this->db->rollBack();
            $this->sendError('Failed to place order: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get checkout summary
     */
    public function index() {
        $this->authenticate();
        
        $userId = $this->user['id'];
        
        // Get cart summary
        $cartSummaryStmt = $this->db->query(
            "SELECT SUM(c.quantity) as total_items, 
                    SUM(c.quantity * COALESCE(p.sale_price, p.price)) as subtotal,
                    SUM(c.quantity * COALESCE(p.weight, 0)) as total_weight
             FROM cart c 
             LEFT JOIN products p ON c.product_id = p.id 
             WHERE c.user_id = ?", 
            [$userId]
        );
        if (!$cartSummaryStmt) {
            $this->sendError('Failed to fetch cart summary', 500);
        }
        $cartSummary = $cartSummaryStmt->fetch();
        
        // Get user addresses
        $addressesStmt = $this->db->query(
            "SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC", 
            [$userId]
        );
        if (!$addressesStmt) {
            $this->sendError('Failed to fetch addresses', 500);
        }
        $addresses = $addressesStmt->fetchAll();
        
        // Get payment methods
        $paymentMethods = $this->getPaymentMethods();
        
        $checkout = [
            'cart_summary' => $cartSummary,
            'addresses' => $addresses,
            'payment_methods' => $paymentMethods['data']['payment_methods'],
            'shipping_methods' => [
                'standard' => 'Standard Delivery (3-5 days)',
                'express' => 'Express Delivery (1-2 days)',
                'same_day' => 'Same Day Delivery (Kathmandu Valley)'
            ]
        ];
        
        $this->sendResponse($checkout);
    }
}
?>

