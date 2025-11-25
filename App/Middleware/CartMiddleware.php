<?php

namespace App\Middleware;

use App\Core\Session;

class CartMiddleware
{
    /**
     * Handle cart operations for both guest and logged-in users
     */
    public function handle($request, $next)
    {
        // Initialize guest cart if not exists
        if (!Session::has('user_id') && !isset($_SESSION['guest_cart'])) {
            $_SESSION['guest_cart'] = [];
            $_SESSION['cart_count'] = 0;
        }
        
        return $next($request);
    }
    
    /**
     * Get current user ID (guest or logged-in)
     */
    public function getCurrentUserId()
    {
        return Session::has('user_id') ? Session::get('user_id') : 'guest_' . session_id();
    }
    
    /**
     * Check if user is guest
     */
    public function isGuest()
    {
        return !Session::has('user_id');
    }
    
    /**
     * Get cart data for current user
     */
    public function getCartData()
    {
        if ($this->isGuest()) {
            return $this->getGuestCart();
        } else {
            return $this->getUserCart();
        }
    }
    
    /**
     * Get guest cart from session
     */
    private function getGuestCart()
    {
        return $_SESSION['guest_cart'] ?? [];
    }
    
    /**
     * Get user cart from database
     */
    private function getUserCart()
    {
        $cartModel = new \App\Models\Cart();
        return $cartModel->getByUserId(Session::get('user_id'));
    }
    
    /**
     * Add item to appropriate cart
     */
    public function addToCart($productId, $quantity, $product, $color = null, $size = null)
    {
        if ($this->isGuest()) {
            return $this->addToGuestCart($productId, $quantity, $product, $color, $size);
        } else {
            return $this->addToUserCart($productId, $quantity, $product, $color, $size);
        }
    }
    
    /**
     * Add item to guest cart
     */
    private function addToGuestCart($productId, $quantity, $product, $color = null, $size = null)
    {
        $guestCart = $this->getGuestCart();
        
        // Create unique key for product with color/size combination
        $cartKey = $productId . '_' . ($color ?? '') . '_' . ($size ?? '');
        
        if (isset($guestCart[$cartKey])) {
            $guestCart[$cartKey]['quantity'] += $quantity;
        } else {
            $guestCart[$cartKey] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product['price'],
                'product_name' => $product['product_name'],
                'image_url' => $product['image_url'] ?? '',
                'sale_price' => $product['sale_price'] ?? null,
                'color' => $color,
                'size' => $size
            ];
        }
        
        $_SESSION['guest_cart'] = $guestCart;
        $_SESSION['cart_count'] = array_sum(array_column($guestCart, 'quantity'));
        
        return true;
    }
    
    /**
     * Add item to user cart
     */
    private function addToUserCart($productId, $quantity, $product, $color = null, $size = null)
    {
        $cartModel = new \App\Models\Cart();
        $userId = Session::get('user_id');
        
        $cartModel->addItem([
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $product['price'],
            'color' => $color,
            'size' => $size
        ]);
        
        $_SESSION['cart_count'] = $cartModel->getCartCount($userId);
        
        return true;
    }
    
    /**
     * Get cart count
     */
    public function getCartCount()
    {
        if ($this->isGuest()) {
            return array_sum(array_column($this->getGuestCart(), 'quantity'));
        } else {
            $cartModel = new \App\Models\Cart();
            return $cartModel->getCartCount(Session::get('user_id'));
        }
    }
    
    /**
     * Get cart total
     */
    public function getCartTotal()
    {
        if ($this->isGuest()) {
            $guestCart = $this->getGuestCart();
            return array_sum(array_map(function($item) {
                $price = $item['sale_price'] && $item['sale_price'] > 0 ? $item['sale_price'] : $item['price'];
                return $item['quantity'] * $price;
            }, $guestCart));
        } else {
            $cartModel = new \App\Models\Cart();
            return $cartModel->getCartTotal(Session::get('user_id'));
        }
    }
    
    /**
     * Migrate guest cart to user cart when user logs in
     */
    public function migrateGuestCartToUser($userId)
    {
        if (!$this->isGuest()) {
            return; // User is already logged in
        }
        
        $guestCart = $this->getGuestCart();
        if (empty($guestCart)) {
            return; // No items in guest cart
        }
        
        $cartModel = new \App\Models\Cart();
        $productModel = new \App\Models\Product();
        
        foreach ($guestCart as $item) {
            $product = $productModel->find($item['product_id']);
            if ($product) {
                $cartModel->addItem([
                    'user_id' => $userId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }
        }
        
        // Clear guest cart
        unset($_SESSION['guest_cart']);
        $_SESSION['cart_count'] = $cartModel->getCartCount($userId);
    }
    
    /**
     * Clear guest cart from session
     */
    public function clearGuestCart()
    {
        unset($_SESSION['guest_cart']);
        $_SESSION['cart_count'] = 0;
        return true;
    }
}


