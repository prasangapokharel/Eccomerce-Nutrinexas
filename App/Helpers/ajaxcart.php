<?php
namespace App\Helpers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductImage;
use App\Core\Session;

class AjaxCart
{
    private $cartModel;
    private $productModel;
    private $productImageModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
    }

    /**
     * Add item to cart via AJAX
     */
    public function addItem($productId, $quantity = 1)
    {
        try {
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }

            if ($product['stock_quantity'] < $quantity) {
                return ['success' => false, 'message' => 'Not enough stock available'];
            }

            $this->cartModel->addItem($productId, $quantity, $product['price']);
            Session::set('cart_count', $this->cartModel->getItemCount());

            return [
                'success' => true,
                'message' => 'Product added to cart',
                'cart_count' => $this->cartModel->getItemCount(),
                'cart_html' => $this->getCartHtml()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error adding item to cart'];
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItem($productId, $action)
    {
        try {
            if ($action === 'increase') {
                $cart = $this->cartModel->getItems();
                $product = $this->productModel->find($productId);
                
                if ($product && isset($cart[$productId])) {
                    $newQuantity = $cart[$productId]['quantity'] + 1;
                    
                    if ($product['stock_quantity'] < $newQuantity) {
                        return ['success' => false, 'message' => 'Not enough stock available'];
                    }
                }
            }

            $this->cartModel->updateItem($productId, $action);
            Session::set('cart_count', $this->cartModel->getItemCount());

            $cartData = $this->cartModel->getCartWithProducts($this->productModel);

            return [
                'success' => true,
                'cart_count' => $this->cartModel->getItemCount(),
                'cart_total' => $cartData['total'],
                'tax' => $cartData['tax'],
                'final_total' => $cartData['final_total'],
                'cart_html' => $this->getCartHtml()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error updating cart'];
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem($productId)
    {
        try {
            $this->cartModel->removeItem($productId);
            Session::set('cart_count', $this->cartModel->getItemCount());

            $cartData = $this->cartModel->getCartWithProducts($this->productModel);

            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'cart_count' => $this->cartModel->getItemCount(),
                'cart_total' => $cartData['total'],
                'tax' => $cartData['tax'],
                'final_total' => $cartData['final_total'],
                'empty_cart' => empty($cartData['items']),
                'cart_html' => $this->getCartHtml()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error removing item'];
        }
    }

    /**
     * Clear entire cart
     */
    public function clearCart()
    {
        try {
            $this->cartModel->clear();
            Session::set('cart_count', 0);

            return [
                'success' => true,
                'message' => 'Cart cleared successfully',
                'cart_count' => 0,
                'cart_html' => $this->getEmptyCartHtml()
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error clearing cart'];
        }
    }

    /**
     * Get cart HTML for AJAX updates
     */
    private function getCartHtml()
    {
        $cartData = $this->cartModel->getCartWithProducts($this->productModel);
        
        // Debug: Log cart data
        error_log('AjaxCart getCartHtml - Cart data: ' . json_encode($cartData));
        
        if (empty($cartData['items'])) {
            error_log('AjaxCart getCartHtml - Cart is empty');
            return $this->getEmptyCartHtml();
        }

        $html = '<div class="bg-white rounded shadow-sm overflow-hidden">';
        $html .= '<div class="p-6 border-b border-gray-200">';
        $html .= '<h2 class="text-xl text-primary">Cart Items (' . count($cartData['items']) . ')</h2>';
        $html .= '</div>';
        
        $html .= '<div class="divide-y divide-gray-200">';
        
        foreach ($cartData['items'] as $item) {
            // Get proper image URL using the same logic as main cart
            $imageUrl = $this->getProductImageUrl($item['product']);
            
            $html .= '<div class="p-6">';
            $html .= '<div class="flex items-start">';
            $html .= '<img src="' . htmlspecialchars($imageUrl) . '" ';
            $html .= 'alt="' . htmlspecialchars($item['product']['product_name']) . '" class="w-20 h-20 object-cover rounded">';
            $html .= '<div class="ml-4 flex-1">';
            $html .= '<div class="flex justify-between">';
            $html .= '<h3 class="font-medium text-primary">' . htmlspecialchars($item['product']['product_name']) . '</h3>';
            // Pass both cart_item_id and product_id for robust deletion
            $html .= '<button onclick="removeCartItem(' . $item['id'] . ', ' . $item['product']['id'] . ')" class="text-gray-400 hover:text-red-500">';
            $html .= '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />';
            $html .= '</svg></button></div>';
            $html .= '<div class="flex justify-between mt-2">';
            $html .= '<div class="flex items-center">';
            $html .= '<button onclick="updateCartItem(' . $item['product']['id'] . ', \'decrease\')" class="bg-gray-100 px-2 py-1 text-primary">-</button>';
            $html .= '<span class="mx-2">' . $item['quantity'] . '</span>';
            $html .= '<button onclick="updateCartItem(' . $item['product']['id'] . ', \'increase\')" class="bg-gray-100 px-2 py-1 text-primary">+</button>';
            $html .= '</div>';
            $html .= '<span class="text-accent font-medium">₹' . number_format($item['subtotal'], 2) . '</span>';
            $html .= '</div></div></div></div>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }

    /**
     * Get empty cart HTML
     */
    private function getEmptyCartHtml()
    {
        return '<div class="bg-white rounded shadow-sm p-8 text-center">
                    <svg class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h2 class="text-xl font-semibold text-primary mb-2">Your cart is empty</h2>
                    <p class="text-gray-600 mb-6">Explore our products and start shopping today.</p>
                    <a href="/products" class="inline-block clip  bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-colors">Start Shopping</a>
                </div>';
    }

    /**
     * Get the URL for a product's image with proper fallback logic
     * Uses the same logic as the main cart system
     * 
     * @param array $product The product data
     * @return string The image URL
     */
    private function getProductImageUrl($product)
    {
        // Debug: Log product data for image processing
        error_log('AjaxCart getProductImageUrl - Product: ' . json_encode($product));
        
        // 1. Check if product has direct image URL
        if (!empty($product['image'])) {
            error_log('AjaxCart getProductImageUrl - Using direct image: ' . $product['image']);
            return $product['image'];
        }
        
        // 2. Check for image_url field (added by Cart model)
        if (!empty($product['image_url'])) {
            error_log('AjaxCart getProductImageUrl - Using image_url: ' . $product['image_url']);
            return $product['image_url'];
        }
        
        // 3. Check for images array from product_images table
        if (!empty($product['images']) && !empty($product['images'][0]['image_url'])) {
            error_log('AjaxCart getProductImageUrl - Using images array: ' . $product['images'][0]['image_url']);
            return $product['images'][0]['image_url'];
        }
        
        // 4. Fallback to default image
        $defaultImage = '/images/default-avatar.png'; // Use existing default image
        error_log('AjaxCart getProductImageUrl - Using default image: ' . $defaultImage);
        return $defaultImage;
    }

    /**
     * Test method to debug cart functionality
     */
    public function debugCart()
    {
        try {
            $cartData = $this->cartModel->getCartWithProducts($this->productModel);
            error_log('AjaxCart debugCart - Full cart data: ' . json_encode($cartData));
            
            if (!empty($cartData['items'])) {
                foreach ($cartData['items'] as $index => $item) {
                    error_log("AjaxCart debugCart - Item {$index}: " . json_encode($item));
                    if (isset($item['product'])) {
                        error_log("AjaxCart debugCart - Item {$index} product: " . json_encode($item['product']));
                    }
                }
            }
            
            return $cartData;
        } catch (\Exception $e) {
            error_log('AjaxCart debugCart - Error: ' . $e->getMessage());
            return null;
        }
    }
}
