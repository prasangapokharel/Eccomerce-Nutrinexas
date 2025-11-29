<?php

namespace App\Controllers\Cart;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\Coupon;
use Exception;

/**
 * Cart Controller
 * Handles shopping cart functionality
 */
class CartController extends Controller
{
        private $productModel;
        private $cartModel;
        private $productImageModel;
        private $settingModel;
        private $couponModel;

        public function __construct()
        {
            parent::__construct();
            $this->productModel = new Product();
            $this->cartModel = new Cart();
            $this->productImageModel = new ProductImage();
            $this->settingModel = new Setting();
            $this->couponModel = new Coupon();
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

        /**
         * Display cart
         */
        public function index()
        {
            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $isGuest = $cartMiddleware->isGuest();
            
            if ($isGuest) {
                // Handle guest cart
                $cartItems = $cartMiddleware->getCartData();
                $enhancedItems = [];
                $total = 0;
                
                foreach ($cartItems as $item) {
                    $product = $this->productModel->find($item['product_id']);
                    if ($product) {
                        $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                        $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                        
                        $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                            ? $product['sale_price'] 
                            : $product['price'];
                        
                        $enhancedItems[] = [
                            'id' => $item['product_id'],
                            'product_id' => $item['product_id'],
                            'quantity' => $item['quantity'],
                            'subtotal' => $currentPrice * $item['quantity'],
                            'product' => [
                                'id' => $product['id'],
                                'product_id' => $item['product_id'],
                                'product_name' => $product['product_name'],
                                'price' => $product['price'],
                                'sale_price' => $product['sale_price'],
                                'current_price' => $currentPrice,
                                'image_url' => $product['image_url'],
                                'stock_quantity' => $product['stock_quantity']
                            ]
                        ];
                        $total += $currentPrice * $item['quantity'];
                    }
                }
            } else {
                // Handle logged-in user cart
                $userId = Session::get('user_id');
                $cartItems = $this->cartModel->getCartWithProducts($userId);

                // Enhance cart items with product images
                $enhancedItems = [];
                $total = 0;
                
                foreach ($cartItems as $item) {
                    $product = $this->productModel->find($item['product_id']);
                    if ($product) {
                        $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                        $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                        
                        $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                            ? $product['sale_price'] 
                            : $product['price'];
                        
                        $subtotal = $currentPrice * $item['quantity'];
                        $total += $subtotal;
                        
                        $enhancedItems[] = [
                            'id' => $item['id'],
                            'product' => $product,
                            'quantity' => $item['quantity'],
                            'subtotal' => $subtotal,
                            'current_price' => $currentPrice
                        ];
                    }
                }
            }

            $taxRate = $this->settingModel->get('tax_rate', 12) / 100; // Get tax rate from settings, default to 12%
            $tax = $total * $taxRate;
            $finalTotal = $total + $tax;
            $originalTotals = [
                'subtotal' => $total,
                'tax' => $tax,
                'final' => $finalTotal
            ];

            $appliedCoupon = $_SESSION['applied_coupon'] ?? null;
            $couponDiscount = 0;

            if (!empty($appliedCoupon) && is_array($appliedCoupon)) {
                $couponDiscount = $this->couponModel->calculateDiscount($appliedCoupon, $total);
                $discountedSubtotal = max(0, $total - $couponDiscount);
                $discountedTax = $discountedSubtotal * $taxRate;
                $total = $discountedSubtotal;
                $tax = $discountedTax;
                $finalTotal = $discountedSubtotal + $discountedTax;
            }
            
            $this->view('cart/index', [
                'cartItems' => $enhancedItems,
                'total' => $total,
                'tax' => $tax,
                'finalTotal' => $finalTotal,
                'taxRate' => $taxRate,
                'appliedCoupon' => $appliedCoupon,
                'couponDiscount' => $couponDiscount,
                'originalTotals' => $originalTotals,
                'title' => 'Shopping Cart',
            ]);
        }

        /**
         * Get cart count for AJAX requests
         */
        public function count()
        {
            if ($this->isAjaxRequest()) {
                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $count = $cartMiddleware->getCartCount();
                $this->jsonResponse(['success' => true, 'count' => $count]);
                return;
            }
            
            // For non-AJAX requests, redirect to cart page
            $this->redirect('cart');
        }

        /**
         * Get cart data for drawer
         */
        public function drawer()
        {
            if ($this->isAjaxRequest()) {
                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $isGuest = $cartMiddleware->isGuest();
                
                if ($isGuest) {
                    $cartItems = $cartMiddleware->getCartData();
                    $enhancedItems = [];
                    $total = 0;
                    
                    foreach ($cartItems as $item) {
                        $product = $this->productModel->find($item['product_id']);
                        if ($product) {
                            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                            $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                            
                            $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                                ? $product['sale_price'] 
                                : $product['price'];
                            
                            $enhancedItems[] = [
                                'id' => $item['product_id'],
                                'product' => $product,
                                'quantity' => $item['quantity'],
                                'subtotal' => $currentPrice * $item['quantity']
                            ];
                            $total += $currentPrice * $item['quantity'];
                        }
                    }
                } else {
                    $userId = Session::get('user_id');
                    $cartItems = $this->cartModel->getCartWithProducts($userId);
                    $enhancedItems = [];
                    $total = 0;
                    
                    foreach ($cartItems as $item) {
                        $product = $this->productModel->find($item['product_id']);
                        if ($product) {
                            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                            $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                            
                            $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                                ? $product['sale_price'] 
                                : $product['price'];
                            
                            $enhancedItems[] = [
                                'id' => $item['id'],
                                'product' => $product,
                                'quantity' => $item['quantity'],
                                'subtotal' => $currentPrice * $item['quantity']
                            ];
                            $total += $currentPrice * $item['quantity'];
                        }
                    }
                }

                $taxRatePercent = $this->settingModel->get('tax_rate', 12);
                $taxRate = $taxRatePercent / 100;
                $tax = $total * $taxRate;
                $delivery = 0; // Default delivery fee
                $finalTotal = $total + $tax + $delivery;
                
                $this->jsonResponse([
                    'success' => true,
                    'cartItems' => $enhancedItems,
                    'total' => $total,
                    'tax' => $tax,
                    'taxRate' => $taxRatePercent,
                    'delivery' => $delivery,
                    'finalTotal' => $finalTotal
                ]);
                return;
            }
            
            $this->redirect('cart');
        }

        /**
         * Add item to cart
         */
        public function add()
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
                $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

                if (!$productId || $quantity < 1) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Invalid product information']);
                        return;
                    }
                    $this->setFlash('error', 'Invalid product information');
                    $this->redirect('products');
                    return;
                }

                $product = $this->productModel->find($productId);
                
                // Support both guest and logged-in users
                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $isGuest = $cartMiddleware->isGuest();
                
                if ($isGuest) {
                    $currentCart = $cartMiddleware->getCartData();
                } else {
                    $userId = Session::get('user_id');
                    $currentCart = $this->cartModel->getByUserId($userId);
                }

                if (!$product) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Product not found']);
                        return;
                    }
                    $this->setFlash('error', 'Product not found');
                    $this->redirect('products');
                    return;
                }

                $currentQuantity = isset($currentCart[$productId]) ? $currentCart[$productId]['quantity'] : 0;
                $totalQuantity = $currentQuantity + $quantity;

                // Maximum 3 quantity per product limit
                if ($totalQuantity > 3) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse([
                            'success' => false, 
                            'message' => 'Maximum 3 quantity allowed per product. You already have ' . $currentQuantity . ' in cart.'
                        ]);
                        return;
                    }
                    $this->setFlash('error', 'Maximum 3 quantity allowed per product');
                    $this->redirect('products/view/' . $productId);
                    return;
                }

                if ($product['stock_quantity'] < $totalQuantity) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse([
                            'success' => false, 
                            'message' => 'Not enough stock available. Available: ' . $product['stock_quantity'] . ', In cart: ' . $currentQuantity
                        ]);
                        return;
                    }
                    $this->setFlash('error', 'Not enough stock available');
                    $this->redirect('products/view/' . $productId);
                    return;
                }

                // Get selected color and size from POST
                $selectedColor = isset($_POST['color']) ? trim($_POST['color']) : null;
                $selectedSize = isset($_POST['size']) ? trim($_POST['size']) : null;
                
                // Add to cart using middleware with color and size
                $cartMiddleware->addToCart($productId, $quantity, $product, $selectedColor, $selectedSize);

                if ($this->isAjaxRequest()) {
                    $cartCount = $cartMiddleware->getCartCount();
                    $cartTotal = $cartMiddleware->getCartTotal();
                    $taxRate = $this->settingModel->get('tax_rate', 12) / 100; // Get tax rate from settings, default to 12%
                    $tax = $cartTotal * $taxRate;
                    $finalTotal = $cartTotal + $tax;
                    
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Product added to cart successfully',
                        'cart_count' => $cartCount,
                        'cart_total' => $cartTotal,
                        'tax' => $tax,
                        'final_total' => $finalTotal,
                        'product_name' => $product['product_name'],
                        'redirect_url' => \App\Core\View::url('cart')
                    ]);
                } else {
                    $this->setFlash('success', 'Product added to cart');
                    $this->redirect('cart');
                }
            } else {
                $this->redirect('products');
            }
        }

        /**
         * Update cart item quantity
         */
        public function update()
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
                $action = isset($_POST['action']) ? $_POST['action'] : '';

                if (!$productId || !in_array($action, ['increase', 'decrease'])) {
                    error_log("Cart update: Invalid parameters - productId: {$productId}, action: {$action}");
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Invalid update parameters']);
                        return;
                    }
                    $this->redirect('cart');
                    return;
                }

                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $isGuest = $cartMiddleware->isGuest();
                
                if ($isGuest) {
                    $cartItems = $cartMiddleware->getCartData();
                } else {
                    $userId = Session::get('user_id');
                    $cartItems = $this->cartModel->getCartWithProducts($userId);
                }
                $product = null;
                $cartItem = null;

                // Find the cart item for this product
                foreach ($cartItems as $item) {
                    if ($item['product_id'] == $productId) {
                        $cartItem = $item;
                        break;
                    }
                }

                if (!$cartItem) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart']);
                        return;
                    }
                    $this->redirect('cart');
                    return;
                }

                $product = $this->productModel->find($productId);
                if (!$product) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Product not found']);
                        return;
                    }
                    $this->setFlash('error', 'Product not found');
                    $this->redirect('cart');
                    return;
                }

                if ($action === 'increase') {
                    $newQuantity = $cartItem['quantity'] + 1;

                    // Maximum 3 quantity per product limit
                    if ($newQuantity > 3) {
                        if ($this->isAjaxRequest()) {
                            $this->jsonResponse([
                                'success' => false, 
                                'message' => 'Maximum 3 quantity allowed per product'
                            ]);
                            return;
                        }
                        $this->setFlash('error', 'Maximum 3 quantity allowed per product');
                        $this->redirect('cart');
                        return;
                    }

                    if ($product['stock_quantity'] < $newQuantity) {
                        if ($this->isAjaxRequest()) {
                            $this->jsonResponse([
                                'success' => false, 
                                'message' => 'Not enough stock available. Maximum: ' . $product['stock_quantity']
                            ]);
                            return;
                        }
                        $this->setFlash('error', 'Not enough stock available');
                        $this->redirect('cart');
                        return;
                    }
                } elseif ($action === 'decrease') {
                    $newQuantity = $cartItem['quantity'] - 1;
                    if ($newQuantity < 1) {
                        if ($this->isAjaxRequest()) {
                            $this->jsonResponse([
                                'success' => false, 
                                'message' => 'Quantity cannot be less than 1. Use remove button to delete item.'
                            ]);
                            return;
                        }
                        $this->setFlash('error', 'Quantity cannot be less than 1');
                        $this->redirect('cart');
                        return;
                    }
                }

                // Update the cart item quantity
                if ($isGuest) {
                    $guestCart = $cartMiddleware->getCartData();
                    
                    // Ensure product_id is integer for array key access
                    $productIdKey = (int)$productId;
                    
                    // Verify the product exists in guest cart before updating
                    if (!isset($guestCart[$productIdKey])) {
                        error_log("Cart update error: Product ID {$productIdKey} not found in guest cart. Available keys: " . implode(', ', array_keys($guestCart)));
                        if ($this->isAjaxRequest()) {
                            $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart. Please refresh the page.']);
                            return;
                        }
                        $this->setFlash('error', 'Item not found in cart. Please refresh the page.');
                        $this->redirect('cart');
                        return;
                    }
                    
                    if ($action === 'increase') {
                        $guestCart[$productIdKey]['quantity'] += 1;
                    } elseif ($action === 'decrease') {
                        $guestCart[$productIdKey]['quantity'] -= 1;
                        if ($guestCart[$productIdKey]['quantity'] <= 0) {
                            unset($guestCart[$productIdKey]);
                        }
                    }
                    $_SESSION['guest_cart'] = $guestCart;
                    $_SESSION['cart_count'] = array_sum(array_column($guestCart, 'quantity'));
                } else {
                    if ($action === 'increase') {
                        $newQuantity = $cartItem['quantity'] + 1;
                        $this->cartModel->updateQuantity($cartItem['id'], $newQuantity);
                    } elseif ($action === 'decrease') {
                        $newQuantity = $cartItem['quantity'] - 1;
                        $this->cartModel->updateQuantity($cartItem['id'], $newQuantity);
                    }
                    $_SESSION['cart_count'] = $this->cartModel->getCartCount($userId);
                }

                if ($this->isAjaxRequest()) {
                    $cartCount = $cartMiddleware->getCartCount();
                    $cartTotal = $cartMiddleware->getCartTotal();
                    $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
                    $tax = $cartTotal * $taxRate;
                    $finalTotal = $cartTotal + $tax;
                    
                    $itemQuantity = 0;
                    $itemSubtotal = 0;
                    
                    if ($isGuest) {
                        $guestCart = $cartMiddleware->getCartData();
                        $productIdKey = (int)$productId;
                        if (isset($guestCart[$productIdKey])) {
                            $itemQuantity = $guestCart[$productIdKey]['quantity'];
                            $product = $this->productModel->find($productIdKey);
                            if ($product) {
                                $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                                    ? $product['sale_price'] 
                                    : $product['price'];
                                $itemSubtotal = $currentPrice * $itemQuantity;
                            }
                        }
                    } else {
                        $cartItems = $this->cartModel->getCartWithProducts($userId);
                        foreach ($cartItems as $item) {
                            if ($item['product_id'] == $productId) {
                                $itemQuantity = $item['quantity'];
                                $product = $this->productModel->find($item['product_id']);
                                if ($product) {
                                    $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                                        ? $product['sale_price'] 
                                        : $product['price'];
                                    $itemSubtotal = $currentPrice * $item['quantity'];
                                }
                                break;
                            }
                        }
                    }
                    
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Cart updated successfully',
                        'cart_count' => $cartCount,
                        'cart_total' => $cartTotal,
                        'tax' => $tax,
                        'final_total' => $finalTotal,
                        'item_quantity' => $itemQuantity,
                        'item_subtotal' => $itemSubtotal,
                        'empty_cart' => $cartCount == 0,
                    ]);
                } else {
                    $this->redirect('cart');
                }
            } else {
                $this->redirect('cart');
            }
        }

        /**
         * Remove item from cart
         */
        public function remove($productId = null)
        {
            if ($productId) {
                $productId = (int)$productId;
            } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
                $productId = (int)$_POST['product_id'];
            }

            // Support removing by explicit cart item ID when provided
            $cartItemId = null;
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_item_id'])) {
                $cartItemId = (int)$_POST['cart_item_id'];
            }

            if (!$productId) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID']);
                    return;
                }
                $this->setFlash('error', 'Invalid product ID');
                $this->redirect('cart');
                return;
            }

            $cartMiddleware = new \App\Middleware\CartMiddleware();
            $isGuest = $cartMiddleware->isGuest();
            
            if ($isGuest) {
                // Remove from guest cart
                $guestCart = $cartMiddleware->getCartData();
                $productIdKey = (int)$productId;
                if (isset($guestCart[$productIdKey])) {
                    unset($guestCart[$productIdKey]);
                    $_SESSION['guest_cart'] = $guestCart;
                    $_SESSION['cart_count'] = array_sum(array_column($guestCart, 'quantity'));
                }
            } else {
                $userId = Session::get('user_id');

                // If cart_item_id provided, try to delete directly after ownership check
                if ($cartItemId) {
                    $directItem = $this->cartModel->find($cartItemId);
                    if ($directItem && (int)$directItem['user_id'] === (int)$userId) {
                        $removed = $this->cartModel->removeItem($cartItemId);
                        if (!$removed) {
                            if ($this->isAjaxRequest()) {
                                $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart']);
                                return;
                            }
                            $this->setFlash('error', 'Item not found in cart');
                            $this->redirect('cart');
                            return;
                        }
                        $_SESSION['cart_count'] = $this->cartModel->getCartCount($userId);
                        // Successful direct removal; continue to response below
                        goto respond_after_delete;
                    }
                    // Fall through to product-based lookup if ownership mismatch
                }

                // Find the cart item first by user and product
                $cartItem = $this->cartModel->findByUserAndProduct($userId, $productId);
                if (!$cartItem) {
                    // As a safety net, try removing from guest cart if it exists
                    $guestCart = $_SESSION['guest_cart'] ?? [];
                    $productIdKey = (int)$productId;
                    if (isset($guestCart[$productIdKey])) {
                        unset($guestCart[$productIdKey]);
                        $_SESSION['guest_cart'] = $guestCart;
                        $_SESSION['cart_count'] = array_sum(array_column($guestCart, 'quantity'));
                        // Continue to response below
                    } else {
                        if ($this->isAjaxRequest()) {
                            $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart']);
                            return;
                        }
                        $this->setFlash('error', 'Item not found in cart');
                        $this->redirect('cart');
                        return;
                    }
                }

                // Delete found DB cart item
                if (isset($cartItem)) {
                    $removed = $this->cartModel->removeItem($cartItem['id']);
                } else {
                    $removed = true; // already removed from guest fallback
                }

                if (!$removed) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart']);
                        return;
                    }
                    $this->setFlash('error', 'Item not found in cart');
                    $this->redirect('cart');
                    return;
                }

                $_SESSION['cart_count'] = $this->cartModel->getCartCount($userId);
            }

respond_after_delete:
            if ($this->isAjaxRequest()) {
                $cartCount = $cartMiddleware->getCartCount();
                $cartTotal = $cartMiddleware->getCartTotal();
                $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
                $tax = $cartTotal * $taxRate;
                $finalTotal = $cartTotal + $tax;
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Item removed from cart successfully',
                    'cart_count' => $cartCount,
                    'cart_total' => $cartTotal,
                    'tax' => $tax,
                    'final_total' => $finalTotal,
                    'empty_cart' => $cartCount == 0,
                ]);
            } else {
                $this->setFlash('success', 'Item removed from cart');
                $this->redirect('cart');
            }
        }

        /**
         * Clear cart
         */
        public function clear()
        {
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
                $cartMiddleware = new \App\Middleware\CartMiddleware();
                $isGuest = $cartMiddleware->isGuest();
                
                if ($isGuest) {
                    // Clear guest cart
                    $_SESSION['guest_cart'] = [];
                    $_SESSION['cart_count'] = 0;
                } else {
                    // Clear user cart
                    $userId = Session::get('user_id');
                    $this->cartModel->clearCart($userId);
                    $_SESSION['cart_count'] = 0;
                }

                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Cart cleared successfully',
                        'cart_count' => 0,
                        'cart_total' => 0,
                        'tax' => 0,
                        'final_total' => 0,
                        'empty_cart' => true
                    ]);
                    return;
                }

                $this->setFlash('success', 'Cart cleared successfully');
            }

            $this->redirect('cart');
        }

        /**
         * Get cart count (AJAX endpoint)
         */
        public function getCount()
        {
        $cartMiddleware = new \App\Middleware\CartMiddleware();
        $count = $cartMiddleware->getCartCount();
        $_SESSION['cart_count'] = $count;

        $this->jsonResponse([
            'success' => true,
            'cart_count' => $count
        ]);
        }

        /**
         * Get cart summary (AJAX endpoint)
         */
        public function getSummary()
        {
            if (!Session::has('user_id')) {
                $this->jsonResponse(['success' => false, 'message' => 'User not logged in']);
                return;
            }

            $userId = Session::get('user_id');
            $cartItems = $this->cartModel->getCartWithProducts($userId);
            $cartTotal = $this->cartModel->getCartTotal($userId);
            $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
            $tax = $cartTotal * $taxRate;
            $finalTotal = $cartTotal + $tax;

            $this->jsonResponse([
                'success' => true,
                'cart_count' => $this->cartModel->getCartCount($userId),
                'cart_total' => $cartTotal,
                'tax' => $tax,
                'final_total' => $finalTotal,
                'items' => $cartItems,
            ]);
        }

        /**
         * Bulk add items to cart
         */
        public function bulkAdd()
        {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $items = isset($_POST['items']) ? $_POST['items'] : [];
                $userId = Session::get('user_id');

                if (empty($items) || !is_array($items)) {
                    if ($this->isAjaxRequest()) {
                        $this->jsonResponse(['success' => false, 'message' => 'No items provided']);
                        return;
                    }
                    $this->setFlash('error', 'No items provided');
                    $this->redirect('cart');
                    return;
                }

                $validItems = [];
                $errors = [];

                foreach ($items as $item) {
                    $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;

                    if ($productId && $quantity > 0) {
                        $product = $this->productModel->find($productId);

                        if ($product && $product['stock_quantity'] >= $quantity) {
                            $validItems[] = [
                                'product_id' => $productId,
                                'quantity' => $quantity,
                                'product' => $product,
                            ];
                        } else {
                            $errors[] = "Product ID {$productId}: " . 
                                       ($product ? 'Insufficient stock' : 'Product not found');
                        }
                    }
                }

                $addedCount = 0;
                foreach ($validItems as $item) {
                    $this->cartModel->addItem($item['product_id'], $item['quantity'], $item['product']['price']);
                    $addedCount++;
                }

                if ($addedCount > 0) {
                    $_SESSION['cart_count'] = $this->cartModel->getCartCount($userId);
                }

                $message = "Added {$addedCount} items to cart";
                if (!empty($errors)) {
                    $message .= ". Errors: " . implode(', ', $errors);
                }

                if ($this->isAjaxRequest()) {
                    $cartItems = $this->cartModel->getCartWithProducts($userId);
                    $cartTotal = $this->cartModel->getCartTotal($userId);
                    $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
                    $tax = $cartTotal * $taxRate;
                    $finalTotal = $cartTotal + $tax;
                    
                    $this->jsonResponse([
                        'success' => $addedCount > 0,
                        'message' => $message,
                        'added_count' => $addedCount,
                        'errors' => $errors,
                        'cart_count' => $_SESSION['cart_count'],
                        'cart_total' => $cartTotal,
                        'tax' => $tax,
                        'final_total' => $finalTotal,
                    ]);
                } else {
                    if ($addedCount > 0) {
                        $this->setFlash('success', $message);
                    } else {
                        $this->setFlash('error', 'No items could be added to cart');
                    }
                    $this->redirect('cart');
                }
            } else {
                $this->redirect('cart');
            }
        }

        /**
         * Validate cart items
         */
        public function validateCart()
        {
            $cartItems = $this->cartModel->getItems();
            $validationResults = [];
            $hasIssues = false;

            foreach ($cartItems as $productId => $item) {
                $product = $this->productModel->find($productId);

                $result = [
                    'product_id' => $productId,
                    'cart_quantity' => $item['quantity'],
                    'available_stock' => $product ? $product['stock_quantity'] : 0,
                    'product_exists' => $product !== null,
                    'stock_sufficient' => $product && $product['stock_quantity'] >= $item['quantity']
                ];

                $validationResults[$productId] = $result;

                if (!$result['product_exists'] || !$result['stock_sufficient']) {
                    $hasIssues = true;
                }
            }

            if ($this->isAjaxRequest()) {
                $this->jsonResponse([
                    'success' => true,
                    'has_issues' => $hasIssues,
                    'validation_results' => $validationResults,
                ]);
            } else {
                if ($hasIssues) {
                    $this->setFlash('warning', 'Some items in your cart have stock issues. Please review your cart.');
                } else {
                    $this->setFlash('success', 'All cart items are valid and in stock.');
                }
                $this->redirect('cart');
            }
        }

        /**
         * Get live cart data for AJAX requests
         */
        public function getLive()
        {
            if (!$this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
                return;
            }

            if (!Session::has('user_id')) {
                $this->jsonResponse(['success' => false, 'message' => 'User not logged in']);
                return;
            }

            try {
                $userId = Session::get('user_id');
                $cartItems = $this->cartModel->getCartWithProducts($userId);
                
                // Format items for sticky cart - use EXACTLY the same structure as cart/index.php
                $formattedItems = [];
                foreach ($cartItems as $item) {
                    $product = $item['product'];
                    
                    // Create the same structure that cart/index.php uses
                    $formattedItems[] = [
                        'id' => $product['id'],
                        'name' => $product['product_name'] ?? 'Unknown Product',
                        'price' => $item['price'], // This is the actual price being used (sale_price or regular price)
                        'image' => $item['product']['image_url'] ?? \App\Core\View::asset('images/products/default.jpg'),
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal']
                    ];
                }

                $cartTotal = $this->cartModel->getCartTotal($userId);
                
                // Debug: Log the data being sent
                error_log('CartController getLive - Raw cart data: ' . json_encode($cartItems));
                error_log('CartController getLive - Formatted items: ' . json_encode($formattedItems));
                
                $this->jsonResponse([
                    'success' => true,
                    'items' => $formattedItems,
                    'total_count' => $this->cartModel->getCartCount($userId),
                    'total_amount' => $cartTotal
                ]);
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error loading cart data',
                    'error' => $e->getMessage()
                ]);
            }
        }

        /**
         * Update product quantity in cart
         */
        public function updateQuantity()
        {
            if (!$this->isAjaxRequest() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
            $change = isset($input['change']) ? (int)$input['change'] : 0;

            if (!$productId || $change === 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid parameters']);
                return;
            }

            if (!Session::has('user_id')) {
                $this->jsonResponse(['success' => false, 'message' => 'User not logged in']);
                return;
            }

            try {
                $userId = Session::get('user_id');
                $existingItem = $this->cartModel->getByUserAndProduct($userId, $productId);

                if (!$existingItem) {
                    $this->jsonResponse(['success' => false, 'message' => 'Item not found in cart']);
                    return;
                }

                $cartItemId = $existingItem['id'];
                $currentQuantity = (int)($existingItem['quantity'] ?? 0);
                $newQuantity = $currentQuantity + $change;

                if ($newQuantity <= 0) {
                    $this->cartModel->removeItem($cartItemId);
                    $message = 'Item removed from cart';
                } else {
                    $this->cartModel->updateQuantity($cartItemId, $newQuantity);
                    $message = 'Quantity updated';
                }

                // Get updated cart data
                $cartItems = $this->cartModel->getCartWithProducts($userId);
                $cartTotal = $this->cartModel->getCartTotal($userId);
                $taxRate = $this->settingModel->get('tax_rate', 12) / 100;
                $tax = $cartTotal * $taxRate;
                $finalTotal = $cartTotal + $tax;
                $totalCount = $this->cartModel->getCartCount($userId);

                // Find the specific item to get its subtotal
                $itemSubtotal = 0;
                $itemQuantity = 0;
                foreach ($cartItems as $item) {
                    if ($item['product_id'] == $productId) {
                        $itemQuantity = $item['quantity'];
                        $product = $this->productModel->find($item['product_id']);
                        if ($product) {
                            $currentPrice = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                                ? $product['sale_price'] 
                                : $product['price'];
                            $itemSubtotal = $currentPrice * $item['quantity'];
                        }
                        break;
                    }
                }

                $this->jsonResponse([
                    'success' => true,
                    'message' => $message,
                    'item_quantity' => $itemQuantity,
                    'item_subtotal' => $itemSubtotal,
                    'new_quantity' => $newQuantity > 0 ? $newQuantity : 0,
                    'total_count' => $totalCount,
                    'cart_total' => $cartTotal,
                    'tax' => $tax,
                    'final_total' => $finalTotal
                ]);
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Error updating quantity',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }