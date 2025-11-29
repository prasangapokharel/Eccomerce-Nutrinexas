<?php

namespace App\Controllers\Affiliate;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use App\Models\Review;
use App\Models\Wishlist;

class AffiliateController extends Controller
{
    private $productModel;
    private $productImageModel;
    private $settingModel;
    private $reviewModel;
    private $wishlistModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->settingModel = new Setting();
        $this->reviewModel = new Review();
        $this->wishlistModel = new Wishlist();
    }

    /**
     * Display affiliate products page
     */
    public function products()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get filters
        $sort = $_GET['sort'] ?? 'commission-high';
        $category = $_GET['category'] ?? '';
        $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
        $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;

        // Get default commission rate from settings
        $defaultCommissionRate = (float)$this->settingModel->get('commission_rate', 10);

        // Get products with affiliate commission set
        $products = $this->getAffiliateProducts($limit, $offset, $sort, $category, $minPrice, $maxPrice);

        // Add image URLs and format products
        foreach ($products as &$product) {
            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
            $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);

            // Calculate final price
            $product['final_price'] = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                ? $product['sale_price'] 
                : $product['price'];

            // Get commission rate (product-specific or default)
            $product['commission_rate'] = isset($product['affiliate_commission']) && $product['affiliate_commission'] > 0 
                ? (float)$product['affiliate_commission'] 
                : $defaultCommissionRate;
            $product['commission_display'] = number_format($product['commission_rate'], 1) . '%';

            // Add review stats
            try {
                $reviewStats = $this->reviewModel->getAverageRating($product['id']);
                $product['avg_rating'] = $reviewStats ? round($reviewStats, 1) : 0;
                $product['review_count'] = $this->reviewModel->getReviewCount($product['id']);
            } catch (\Exception $e) {
                $product['avg_rating'] = 0;
                $product['review_count'] = 0;
            }

            // Add wishlist status
            if (Session::has('user_id')) {
                try {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                } catch (\Exception $e) {
                    $product['in_wishlist'] = false;
                }
            } else {
                $product['in_wishlist'] = false;
            }
        }
        unset($product);

        // Get total count for pagination
        $totalProducts = $this->getAffiliateProductsCount($category, $minPrice, $maxPrice);
        $totalPages = ceil($totalProducts / $limit);

        // Get categories for filter
        $categories = $this->getCategories();

        $this->view('affiliate/products', [
            'products' => $products,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'defaultCommissionRate' => $defaultCommissionRate,
            'sort' => $sort,
            'category' => $category,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'categories' => $categories,
            'title' => 'Affiliate Products'
        ]);
    }

    /**
     * Get products with affiliate commission
     */
    private function getAffiliateProducts($limit = 20, $offset = 0, $sort = 'commission-high', $category = '', $minPrice = null, $maxPrice = null)
    {
        // Check if affiliate_commission column exists
        $hasAffiliateColumn = $this->checkAffiliateColumnExists();
        
        $params = [];
        
        if ($hasAffiliateColumn) {
            $sql = "SELECT p.*
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                    AND (p.affiliate_commission IS NOT NULL AND p.affiliate_commission > 0)";
        } else {
            // Show all active products if column doesn't exist (using default commission)
            $sql = "SELECT p.*
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)";
        }

        // Apply filters
        if (!empty($category)) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }

        if ($minPrice !== null) {
            $sql .= " AND p.price >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sql .= " AND p.price <= ?";
            $params[] = $maxPrice;
        }

        // Apply sorting
        switch ($sort) {
            case 'price-low':
                $sql .= " ORDER BY p.price ASC, p.created_at DESC";
                break;
            case 'price-high':
                $sql .= " ORDER BY p.price DESC, p.created_at DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY p.total_sales DESC, p.created_at DESC";
                break;
            case 'commission-high':
            default:
                if ($hasAffiliateColumn) {
                    $sql .= " ORDER BY p.affiliate_commission DESC, p.created_at DESC";
                } else {
                    $sql .= " ORDER BY p.created_at DESC";
                }
                break;
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->productModel->getDb()->query($sql, $params)->all();
    }

    /**
     * Get total count of affiliate products
     */
    private function getAffiliateProductsCount($category = '', $minPrice = null, $maxPrice = null)
    {
        $hasAffiliateColumn = $this->checkAffiliateColumnExists();
        $params = [];
        
        if ($hasAffiliateColumn) {
            $sql = "SELECT COUNT(*) as count
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                    AND (p.affiliate_commission IS NOT NULL AND p.affiliate_commission > 0)";
        } else {
            $sql = "SELECT COUNT(*) as count
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)";
        }

        // Apply filters
        if (!empty($category)) {
            $sql .= " AND p.category = ?";
            $params[] = $category;
        }

        if ($minPrice !== null) {
            $sql .= " AND p.price >= ?";
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $sql .= " AND p.price <= ?";
            $params[] = $maxPrice;
        }

        $result = $this->productModel->getDb()->query($sql, $params)->single();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get categories for filter
     */
    private function getCategories()
    {
        $hasAffiliateColumn = $this->checkAffiliateColumnExists();
        
        if ($hasAffiliateColumn) {
            $sql = "SELECT DISTINCT p.category
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                    AND (p.affiliate_commission IS NOT NULL AND p.affiliate_commission > 0)
                    AND p.category IS NOT NULL AND p.category != ''
                    ORDER BY p.category ASC";
        } else {
            $sql = "SELECT DISTINCT p.category
                    FROM products p
                    WHERE p.status = 'active'
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                    AND p.category IS NOT NULL AND p.category != ''
                    ORDER BY p.category ASC";
        }

        $results = $this->productModel->getDb()->query($sql)->all();
        return array_column($results, 'category');
    }

    /**
     * Check if affiliate_commission column exists
     */
    private function checkAffiliateColumnExists()
    {
        try {
            $result = $this->productModel->getDb()->query(
                "SELECT COUNT(*) as count 
                 FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = 'products' 
                 AND COLUMN_NAME = 'affiliate_commission'"
            )->single();
            
            return (int)($result['count'] ?? 0) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get product image URL
     */
    private function getProductImageUrl($product, $primaryImage = null)
    {
        if (!empty($product['image'])) {
            return $product['image'];
        }
        
        if ($primaryImage && !empty($primaryImage['image_url'])) {
            return $primaryImage['image_url'];
        }
        
        $images = $this->productImageModel->getByProductId($product['id']);
        if (!empty($images[0]['image_url'])) {
            return $images[0]['image_url'];
        }
        
        return \App\Core\View::asset('images/products/default.jpg');
    }
}

