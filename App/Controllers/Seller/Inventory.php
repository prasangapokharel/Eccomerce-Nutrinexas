<?php

namespace App\Controllers\Seller;

use App\Models\Product;
use App\Core\Cache;
use Exception;

class Inventory extends BaseSellerController
{
    private $productModel;
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->cache = new Cache();
    }

    /**
     * Inventory management
     */
    public function index()
    {
        $lowStock = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
        
        $products = $this->getInventoryProducts($lowStock);

        $this->view('seller/inventory/index', [
            'title' => 'Inventory Management',
            'products' => $products,
            'lowStockFilter' => $lowStock
        ]);
    }

    /**
     * Update stock
     */
    public function updateStock($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/inventory');
            return;
        }

        try {
            $product = $this->productModel->find($id);
            
            if (!$product || $product['seller_id'] != $this->sellerId) {
                $this->setFlash('error', 'Product not found');
                $this->redirect('seller/inventory');
                return;
            }

            $quantity = (int)($_POST['stock_quantity'] ?? 0);
            
            if ($quantity < 0) {
                $this->setFlash('error', 'Stock quantity cannot be negative');
                $this->redirect('seller/inventory');
                return;
            }

            $result = $this->productModel->updateStock($id, $quantity);
            
            if ($result) {
                // Clear inventory cache
                $this->cache->deletePattern('seller_inventory_' . $this->sellerId . '_*');
                $this->setFlash('success', 'Stock updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update stock');
            }
        } catch (Exception $e) {
            error_log('Update stock error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating stock');
        }

        $this->redirect('seller/inventory');
    }

    /**
     * Get inventory products
     */
    private function getInventoryProducts($lowStock = false)
    {
        $cacheKey = 'seller_inventory_' . $this->sellerId . '_' . ($lowStock ? 'low' : 'all');
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $sql = "SELECT p.id, p.product_name, p.stock_quantity, p.price, p.sale_price, p.status, p.category,
                       pi.image_url as primary_image_url
                FROM products p
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                WHERE p.seller_id = ?";
        
        $params = [$this->sellerId];
        
        if ($lowStock) {
            $sql .= " AND p.stock_quantity < 10 AND p.stock_quantity > 0";
        }
        
        $sql .= " ORDER BY p.stock_quantity ASC, p.product_name ASC";
        
        $db = \App\Core\Database::getInstance();
        $products = $db->query($sql, $params)->all();
        
        // Cache for 2 minutes (inventory changes frequently)
        $this->cache->set($cacheKey, $products, 120);
        
        return $products;
    }
}

