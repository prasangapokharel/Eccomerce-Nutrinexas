<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Seller;
use App\Models\Product;
use Exception;

class SellerPublicController extends Controller
{
    private $sellerModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->sellerModel = new Seller();
        $this->productModel = new Product();
    }

    /**
     * List all active sellers (public page)
     */
    public function index()
    {
        // Initialize performance cache
        if (!class_exists('App\Helpers\PerformanceCache')) {
            require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
        }
        \App\Helpers\PerformanceCache::init();
        
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;
        
        // Check cache (only for page 1, no search)
        $cacheKey = 'sellers_public_' . $page . '_' . md5($search);
        if ($page === 1 && empty($search)) {
            $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
            if ($cachedData) {
                $this->view('seller/public/index', $cachedData);
                return;
            }
        }
        
        // Get sellers
        $sellers = $this->getSellers($search, $limit, $offset);
        $total = $this->getSellerCount($search);
        $totalPages = ceil($total / $limit);
        
        $viewData = [
            'title' => 'Our Sellers - NutriNexus',
            'description' => 'Discover trusted sellers and premium products on NutriNexus',
            'sellers' => $sellers,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ];
        
        // Cache for 30 minutes (only for page 1, no search)
        if ($page === 1 && empty($search)) {
            \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $viewData, 1800);
        }
        
        $this->view('seller/public/index', $viewData);
    }

    /**
     * Get active sellers with product counts
     */
    private function getSellers($search = '', $limit = 12, $offset = 0)
    {
        $sql = "SELECT s.id, s.name, s.company_name, s.logo_url, s.description, s.status,
                       COUNT(DISTINCT p.id) as product_count,
                       AVG(pr.rating) as avg_rating
                FROM sellers s
                LEFT JOIN products p ON s.id = p.seller_id AND p.status = 'active' AND p.approval_status = 'approved'
                LEFT JOIN reviews pr ON p.id = pr.product_id
                WHERE s.status = 'active' AND s.is_approved = 1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (s.name LIKE ? OR s.company_name LIKE ? OR s.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " GROUP BY s.id
                  ORDER BY product_count DESC, s.created_at DESC
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->sellerModel->getDb()->query($sql)->bind($params)->all();
    }

    /**
     * Get total seller count
     */
    private function getSellerCount($search = '')
    {
        $sql = "SELECT COUNT(DISTINCT s.id) as count
                FROM sellers s
                WHERE s.status = 'active' AND s.is_approved = 1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (s.name LIKE ? OR s.company_name LIKE ? OR s.description LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->sellerModel->getDb()->query($sql)->bind($params)->single();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Public seller profile page
     */
    public function profile($companyName)
    {
        // Decode URL-encoded company name
        $companyName = urldecode($companyName);
        
        // Find seller by company name (handle both company_name and name fields)
        $seller = $this->sellerModel->getDb()->query(
            "SELECT id, name, company_name, email, phone, address, logo_url, cover_banner_url, 
                    description, social_media, status, commission_rate, created_at, updated_at, theme_color
             FROM sellers 
             WHERE (company_name = ? OR name = ?) AND status = 'active'"
        )->bind([$companyName, $companyName])->single();
        
        if (!$seller) {
            $this->view('errors/404', ['title' => 'Seller Not Found']);
            return;
        }
        
        // Get search query
        $search = $_GET['search'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = 24;
        $offset = ($page - 1) * $limit;
        
        // Get products
        $products = $this->getProducts($seller['id'], $search, $limit, $offset);
        $total = $this->getProductCount($seller['id'], $search);
        $totalPages = ceil($total / $limit);
        
        // Parse social media
        $socialMedia = !empty($seller['social_media']) ? json_decode($seller['social_media'], true) : [];
        
        $this->view('seller/public/profile', [
            'title' => $seller['company_name'] ?? $seller['name'] . ' - Store',
            'seller' => $seller,
            'products' => $products,
            'socialMedia' => $socialMedia,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }

    /**
     * Get products by seller with search
     */
    private function getProducts($sellerId, $search, $limit, $offset)
    {
        $sql = "SELECT p.*, 
                       (SELECT pi.image_url 
                        FROM product_images pi 
                        WHERE pi.product_id = p.id AND pi.is_primary = 1 
                        LIMIT 1) as primary_image_url,
                       (SELECT AVG(r.rating) 
                        FROM reviews r 
                        WHERE r.product_id = p.id) as avg_rating,
                       (SELECT COUNT(*) 
                        FROM reviews r 
                        WHERE r.product_id = p.id) as review_count
                FROM products p
                WHERE p.seller_id = ? 
                  AND p.status = 'active' 
                  AND p.approval_status = 'approved'";
        
        $params = [$sellerId];
        
        if (!empty($search)) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY p.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $results = $this->productModel->getDb()->query($sql)->bind($params)->all();
        
        // Format products
        foreach ($results as &$product) {
            $product['image_url'] = $product['primary_image_url'] ?? $product['image_url'] ?? null;
            $product['avg_rating'] = (float)($product['avg_rating'] ?? 0);
            $product['review_count'] = (int)($product['review_count'] ?? 0);
        }
        
        return $results;
    }

    /**
     * Get product count
     */
    private function getProductCount($sellerId, $search)
    {
        $sql = "SELECT COUNT(*) as count 
                FROM products p
                WHERE p.seller_id = ? 
                  AND p.status = 'active' 
                  AND p.approval_status = 'approved'";
        
        $params = [$sellerId];
        
        if (!empty($search)) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $result = $this->productModel->getDb()->query($sql)->bind($params)->single();
        return (int)($result['count'] ?? 0);
    }
}

