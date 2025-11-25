<?php
namespace App\Controllers\Api;

use App\Models\Product;
use App\Models\Category;

class ProductsApiController extends BaseApiController
{
    private $productModel;
    private $categoryModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }
    
    /**
     * Get all products with pagination
     * GET /api/products
     */
    public function index()
    {
        $this->requirePermission('read');
        
        $pagination = $this->getPaginationParams();
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        
        $products = $this->productModel->getAllProducts($pagination['limit'], $pagination['offset'], $search, $category, $sort, $order);
        $total = $this->productModel->getProductsCount($search, $category);
        
        $formattedProducts = array_map([$this, 'formatProduct'], $products);
        
        $this->jsonResponse($this->formatPaginatedResponse($formattedProducts, $total, $pagination));
    }
    
    /**
     * Get single product by ID or slug
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $this->requirePermission('read');
        
        $product = $this->productModel->findBySlugOrId($id);
        
        if (!$product) {
            $this->jsonResponse(['error' => 'Product not found'], 404);
        }
        
        $this->jsonResponse(['data' => $this->formatProduct($product)]);
    }
    
    /**
     * Get products by category
     * GET /api/products/category/{category}
     */
    public function category($category)
    {
        $this->requirePermission('read');
        
        $pagination = $this->getPaginationParams();
        $sort = $_GET['sort'] ?? 'created_at';
        $order = $_GET['order'] ?? 'DESC';
        
        $products = $this->productModel->getProductsByCategory($category, $pagination['limit'], $pagination['offset'], $sort, $order);
        $total = $this->productModel->getProductsCountByCategory($category);
        
        $formattedProducts = array_map([$this, 'formatProduct'], $products);
        
        $this->jsonResponse($this->formatPaginatedResponse($formattedProducts, $total, $pagination));
    }
    
    /**
     * Search products
     * GET /api/products/search?q={query}
     */
    public function search()
    {
        $this->requirePermission('read');
        
        $query = $_GET['q'] ?? '';
        
        if (empty($query)) {
            $this->jsonResponse(['error' => 'Search query required'], 400);
        }
        
        $pagination = $this->getPaginationParams();
        $products = $this->productModel->searchProducts($query, $pagination['limit'], $pagination['offset']);
        $total = $this->productModel->getSearchCount($query);
        
        $formattedProducts = array_map([$this, 'formatProduct'], $products);
        
        $this->jsonResponse($this->formatPaginatedResponse($formattedProducts, $total, $pagination));
    }
    
    /**
     * Get all categories
     * GET /api/categories
     */
    public function categories()
    {
        $this->requirePermission('read');
        
        $categories = $this->categoryModel->getAll();
        
        $formattedCategories = array_map(function($category) {
            return [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'product_count' => $category['product_count'] ?? 0,
                'image_url' => $category['image_url']
            ];
        }, $categories);
        
        $this->jsonResponse(['data' => $formattedCategories]);
    }
    
    /**
     * Format product data for API response
     */
    private function formatProduct($product)
    {
        return [
            'id' => $product['id'],
            'name' => $product['product_name'],
            'slug' => $product['slug'],
            'description' => $product['description'],
            'short_description' => $product['short_description'],
            'price' => (float)$product['price'],
            'sale_price' => $product['sale_price'] ? (float)$product['sale_price'] : null,
            'category' => $product['category'],
            'brand' => $product['brand'],
            'stock_quantity' => (int)$product['stock_quantity'],
            'is_featured' => (bool)$product['is_featured'],
            'is_active' => (bool)$product['is_active'],
            'images' => $this->formatProductImages($product),
            'specifications' => $this->formatSpecifications($product),
            'created_at' => $product['created_at'],
            'updated_at' => $product['updated_at']
        ];
    }
    
    /**
     * Format product images
     */
    private function formatProductImages($product)
    {
        $images = [];
        
        if (!empty($product['images'])) {
            foreach ($product['images'] as $image) {
                $images[] = [
                    'id' => $image['id'],
                    'url' => $image['image_url'],
                    'is_primary' => (bool)$image['is_primary'],
                    'alt_text' => $image['alt_text']
                ];
            }
        } elseif (!empty($product['image'])) {
            $images[] = [
                'id' => 1,
                'url' => $product['image'],
                'is_primary' => true,
                'alt_text' => $product['product_name']
            ];
        }
        
        return $images;
    }
    
    /**
     * Format product specifications
     */
    private function formatSpecifications($product)
    {
        if (empty($product['specifications'])) {
            return [];
        }
        
        $specs = json_decode($product['specifications'], true);
        
        if (!is_array($specs)) {
            return [];
        }
        
        return $specs;
    }
}




























