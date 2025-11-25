<?php
namespace App\Controllers\Api;

use App\Models\Cart;
use App\Models\Product;

class CartApiController extends BaseApiController
{
    private $cartModel;
    private $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->productModel = new Product();
    }
    
    /**
     * Get cart items
     * GET /api/cart
     */
    public function index()
    {
        $this->requirePermission('read');
        
        $userId = $this->currentUser['user_id'];
        $cartItems = $this->cartModel->getUserCart($userId);
        
        $formattedItems = array_map([$this, 'formatCartItem'], $cartItems);
        
        $this->jsonResponse([
            'data' => $formattedItems,
            'summary' => $this->calculateCartSummary($cartItems)
        ]);
    }
    
    /**
     * Add item to cart
     * POST /api/cart/add
     */
    public function add()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['product_id', 'quantity']);
        
        $userId = $this->currentUser['user_id'];
        $productId = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        
        // Validate product exists and is in stock
        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        
        if ($product['stock_quantity'] < $quantity) {
            $this->jsonResponse(['error' => 'Insufficient stock'], 400);
        }
        
        // Add to cart
        $result = $this->cartModel->addToCart($userId, $productId, $quantity);
        
        if ($result) {
            $cartItem = $this->cartModel->getCartItem($userId, $productId);
            $this->jsonResponse([
                'message' => 'Item added to cart successfully',
                'data' => $this->formatCartItem($cartItem)
            ], 201);
        } else {
            $this->jsonResponse(['error' => 'Failed to add item to cart'], 500);
        }
    }
    
    /**
     * Update cart item quantity
     * PUT /api/cart/update
     */
    public function update()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['product_id', 'quantity']);
        
        $userId = $this->currentUser['user_id'];
        $productId = (int)$data['product_id'];
        $quantity = (int)$data['quantity'];
        
        if ($quantity <= 0) {
            $this->jsonResponse(['error' => 'Quantity must be greater than 0'], 400);
        }
        
        // Check product stock
        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        
        if ($product['stock_quantity'] < $quantity) {
            $this->jsonResponse(['error' => 'Insufficient stock'], 400);
        }
        
        // Update cart item
        $result = $this->cartModel->updateCartItem($userId, $productId, $quantity);
        
        if ($result) {
            $cartItem = $this->cartModel->getCartItem($userId, $productId);
            $this->jsonResponse([
                'message' => 'Cart item updated successfully',
                'data' => $this->formatCartItem($cartItem)
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update cart item'], 500);
        }
    }
    
    /**
     * Remove item from cart
     * DELETE /api/cart/remove
     */
    public function remove()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['product_id']);
        
        $userId = $this->currentUser['user_id'];
        $productId = (int)$data['product_id'];
        
        $result = $this->cartModel->removeFromCart($userId, $productId);
        
        if ($result) {
            $this->jsonResponse(['message' => 'Item removed from cart successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to remove item from cart'], 500);
        }
    }
    
    /**
     * Clear entire cart
     * DELETE /api/cart/clear
     */
    public function clear()
    {
        $this->requirePermission('write');
        
        $userId = $this->currentUser['user_id'];
        $result = $this->cartModel->clearCart($userId);
        
        if ($result) {
            $this->jsonResponse(['message' => 'Cart cleared successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to clear cart'], 500);
        }
    }
    
    /**
     * Format cart item for API response
     */
    private function formatCartItem($item)
    {
        return [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'product_name' => $item['product_name'],
            'product_slug' => $item['product_slug'],
            'price' => (float)$item['price'],
            'sale_price' => $item['sale_price'] ? (float)$item['sale_price'] : null,
            'quantity' => (int)$item['quantity'],
            'subtotal' => (float)$item['subtotal'],
            'image_url' => $item['image_url'],
            'stock_quantity' => (int)$item['stock_quantity'],
            'added_at' => $item['created_at']
        ];
    }
    
    /**
     * Calculate cart summary
     */
    private function calculateCartSummary($cartItems)
    {
        $totalItems = 0;
        $subtotal = 0;
        
        foreach ($cartItems as $item) {
            $totalItems += $item['quantity'];
            $subtotal += $item['subtotal'];
        }
        
        return [
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'tax' => 0, // Calculate tax if needed
            'shipping' => 0, // Calculate shipping if needed
            'total' => $subtotal
        ];
    }
}




























