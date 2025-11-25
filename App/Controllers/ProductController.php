<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Cache;
use Exception;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\Wishlist;

/**
 * Product Controller
 * Handles product-related functionality
 */
class ProductController extends Controller
{
        /**
         * @var Product
         */
        private $productModel;
        private $productImageModel;

        /**
         * @var Review
         */
        private $reviewModel;

        /**
         * @var Wishlist
         */
        private $wishlistModel;
        private $cache;

        /**
         * Constructor
         */
        public function __construct()
        {
            parent::__construct();
            $this->productModel = new Product();
            $this->productImageModel = new ProductImage();
            $this->reviewModel = new Review();
            $this->wishlistModel = new Wishlist();
            $this->cache = new Cache();
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
         * Display all products
         */
        public function index()
        {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get filters
            $sort = $_GET['sort'] ?? '';
            $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
            $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
            $inStock = isset($_GET['in_stock']) ? true : false;
            $lowStock = isset($_GET['low_stock']) ? true : false;

            // Apply filters and sorting
            $products = $this->getFilteredProducts($limit, $offset, $sort, $minPrice, $maxPrice, $inStock, $lowStock);
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Handle scheduled product logic
                $product['is_scheduled'] = false;
                $product['remaining_days'] = 0;
                
                if (isset($product['is_scheduled']) && $product['is_scheduled']) {
                    if (!empty($product['scheduled_date'])) {
                        // Use specific scheduled date
                        $scheduledDate = new \DateTime($product['scheduled_date']);
                        $now = new \DateTime();
                        $product['is_scheduled'] = $scheduledDate > $now;
                        if ($product['is_scheduled']) {
                            $product['remaining_days'] = $now->diff($scheduledDate)->days;
                        }
                    } elseif (!empty($product['scheduled_duration'])) {
                        // Use duration from creation date
                        $createdDate = new \DateTime($product['created_at']);
                        $scheduledDate = clone $createdDate;
                        $scheduledDate->add(new \DateInterval('P' . $product['scheduled_duration'] . 'D'));
                        $now = new \DateTime();
                        $product['is_scheduled'] = $scheduledDate > $now;
                        if ($product['is_scheduled']) {
                            $product['remaining_days'] = $now->diff($scheduledDate)->days;
                        }
                    }
                }
            }
            $totalProducts = $this->getFilteredProductsCount($sort, $minPrice, $maxPrice, $inStock, $lowStock);
            $totalPages = ceil($totalProducts / $limit);

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            // Check for active ads and mark products as sponsored
            $allProductIds = array_column($products, 'id');
            $adsByProductId = [];
            
            if (!empty($allProductIds)) {
                $db = \App\Core\Database::getInstance();
                $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
                
                // Check if product_id has active ad
                $allActiveAds = $db->query(
                    "SELECT a.product_id, a.id as ad_id
                     FROM ads a
                     INNER JOIN ads_types at ON a.ads_type_id = at.id
                     WHERE a.product_id IN ($placeholders)
                     AND a.status = 'active'
                     AND at.name = 'product_internal'
                     AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                     AND CURDATE() BETWEEN a.start_date AND a.end_date
                     AND a.product_id IS NOT NULL",
                    $allProductIds
                )->all();
                
                foreach ($allActiveAds as $ad) {
                    $adsByProductId[$ad['product_id']] = $ad['ad_id'];
                }
            }
            
            // Mark products with active ads as sponsored
            foreach ($products as &$product) {
                if (isset($adsByProductId[$product['id']])) {
                    $product['is_sponsored'] = true;
                    $product['ad_id'] = $adsByProductId[$product['id']];
                }
            }
            unset($product);

            $this->view('products/index', [
                'products' => $products,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalProducts' => $totalProducts,
                'title' => 'All Products',
            ]);
        }

        /**
         * Get filtered products
         */
        private function getFilteredProducts($limit, $offset, $sort = '', $minPrice = null, $maxPrice = null, $inStock = false, $lowStock = false)
        {
            $db = \App\Core\Database::getInstance();
            $where = ["p.status = 'active'", "(p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)"];
            $params = [];

            // Price filters
            if ($minPrice !== null && $minPrice > 0) {
                $where[] = "COALESCE(p.sale_price, p.price) >= ?";
                $params[] = $minPrice;
            }
            if ($maxPrice !== null && $maxPrice > 0) {
                $where[] = "COALESCE(p.sale_price, p.price) <= ?";
                $params[] = $maxPrice;
            }

            // Stock filters
            if ($inStock) {
                $where[] = "p.stock_quantity > 0";
            }
            if ($lowStock) {
                $where[] = "p.stock_quantity < 10 AND p.stock_quantity > 0";
            }

            $whereClause = implode(' AND ', $where);

            // Sorting
            $orderBy = "p.created_at DESC";
            switch ($sort) {
                case 'price-low':
                case 'price_low':
                    $orderBy = "COALESCE(p.sale_price, p.price) ASC";
                    break;
                case 'price-high':
                case 'price_high':
                    $orderBy = "COALESCE(p.sale_price, p.price) DESC";
                    break;
                case 'name':
                    $orderBy = "p.product_name ASC";
                    break;
                case 'popular':
                    $orderBy = "p.is_featured DESC, p.total_sales DESC, p.sales_count DESC, p.created_at DESC";
                    break;
                case 'newest':
                default:
                    $orderBy = "p.created_at DESC";
                    break;
            }

            $sql = "SELECT p.* FROM products p WHERE $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $products = $db->query($sql, $params)->all();

            // Add images
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
            }

            return $products;
        }

        /**
         * Get filtered products count
         */
        private function getFilteredProductsCount($sort = '', $minPrice = null, $maxPrice = null, $inStock = false, $lowStock = false)
        {
            $db = \App\Core\Database::getInstance();
            $where = ["p.status = 'active'", "(p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)"];
            $params = [];

            // Price filters
            if ($minPrice !== null && $minPrice > 0) {
                $where[] = "COALESCE(p.sale_price, p.price) >= ?";
                $params[] = $minPrice;
            }
            if ($maxPrice !== null && $maxPrice > 0) {
                $where[] = "COALESCE(p.sale_price, p.price) <= ?";
                $params[] = $maxPrice;
            }

            // Stock filters
            if ($inStock) {
                $where[] = "p.stock_quantity > 0";
            }
            if ($lowStock) {
                $where[] = "p.stock_quantity < 10 AND p.stock_quantity > 0";
            }

            $whereClause = implode(' AND ', $where);
            $sql = "SELECT COUNT(*) as count FROM products p WHERE $whereClause";
            $result = $db->query($sql, $params)->single();
            return (int)($result['count'] ?? 0);
        }

        /**
         * Infinite scroll API endpoint for home page
         */
        public function infiniteScroll()
        {
            header('Content-Type: application/json');
            
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = 12; // Load 12 products per page
            $offset = ($page - 1) * $limit;
            
            try {
                // Try ranked products first, fallback to simple query if it fails
                try {
                    $products = $this->productModel->getRankedProducts($limit, $offset);
                } catch (Exception $e) {
                    error_log('Ranked products failed, using fallback: ' . $e->getMessage());
                    $products = $this->productModel->getProductsWithImages($limit, $offset);
                }
                
                if (empty($products)) {
                    echo json_encode([
                        'success' => true,
                        'products' => [],
                        'hasMore' => false,
                        'page' => $page,
                        'total' => 0
                    ]);
                    exit;
                }
                
                // Add image URLs and format products
                foreach ($products as &$product) {
                    $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                    $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                    
                    // Calculate final price
                    $product['final_price'] = ($product['sale_price'] > 0 && $product['sale_price'] < $product['price']) 
                        ? $product['sale_price'] 
                        : $product['price'];
                    
                    // Add review stats
                    try {
                        $reviewStats = $this->reviewModel->getAverageRating($product['id']);
                        $product['avg_rating'] = $reviewStats ? round($reviewStats, 1) : 0;
                        $product['review_count'] = $this->reviewModel->getReviewCount($product['id']);
                    } catch (Exception $e) {
                        $product['avg_rating'] = 0;
                        $product['review_count'] = 0;
                    }
                    
                    // Add wishlist status
                    if (Session::has('user_id')) {
                        try {
                            $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                        } catch (Exception $e) {
                            $product['in_wishlist'] = false;
                        }
                    } else {
                        $product['in_wishlist'] = false;
                    }
                }
                unset($product);
                
                // Check if there are more products
                $totalProducts = $this->productModel->getProductCount();
                $hasMore = ($offset + count($products)) < $totalProducts;
                
                echo json_encode([
                    'success' => true,
                    'products' => $products,
                    'hasMore' => $hasMore,
                    'page' => $page,
                    'total' => $totalProducts
                ]);
            } catch (Exception $e) {
                error_log('Infinite scroll error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to load products',
                    'message' => $e->getMessage()
                ]);
            }
            exit;
        }

        /**
         * Display single product
         * 
         * @param string|int $slug Product slug or ID
         */
        public function viewProduct($slug = null)
        {
            if (!$slug) {
                $this->setFlash('error', 'Product not found');
                $this->redirect('products');
                return;
            }

            $product = $this->productModel->findBySlugWithImages($slug);

            if (!$product && is_numeric($slug)) {
                $product = $this->productModel->findWithImages($slug);
            }

            if (!$product) {
                $this->setFlash('error', 'Product not found');
                $this->redirect('products');
                return;
            }
            
            // Apply sale price calculation if product is on sale
            $product = $this->applyProductSalePrice($product);

            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
            $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);

            // Get seller information if product has a seller
            $seller = null;
            if (!empty($product['seller_id'])) {
                $sellerModel = new \App\Models\Seller();
                $seller = $sellerModel->getDb()->query(
                    "SELECT id, name, company_name, logo_url, status 
                     FROM sellers 
                     WHERE id = ? AND status = 'active'"
                )->bind([$product['seller_id']])->single();
            }

            $reviews = $this->reviewModel->getByProductId($product['id']);
            $averageRating = $this->reviewModel->getAverageRating($product['id']);
            $reviewCount = $this->reviewModel->getReviewCount($product['id']);
            $relatedProducts = $this->getLocalRecommendations($product['id'], 4);
            foreach ($relatedProducts as &$relatedProduct) {
                $primaryImage = $this->productImageModel->getPrimaryImage($relatedProduct['id']);
                $relatedProduct['image_url'] = $this->getProductImageUrl($relatedProduct, $primaryImage);
            }

            if (Session::has('user_id')) {
                $inWishlist = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                $hasReviewed = $this->reviewModel->hasUserReviewed(Session::get('user_id'), $product['id']);
            } else {
                $inWishlist = false;
                $hasReviewed = false;
            }

            // Handle scheduled product logic
            $isScheduled = false;
            $remainingDays = 0;
            $scheduledDate = null;
            
            if (isset($product['is_scheduled']) && $product['is_scheduled']) {
                if (!empty($product['scheduled_date'])) {
                    // Use specific scheduled date
                    $scheduledDate = new \DateTime($product['scheduled_date']);
                    $now = new \DateTime();
                    $isScheduled = $scheduledDate > $now;
                    if ($isScheduled) {
                        $remainingDays = $now->diff($scheduledDate)->days;
                    }
                } elseif (!empty($product['scheduled_duration'])) {
                    // Use duration from creation date
                    $createdDate = new \DateTime($product['created_at']);
                    $scheduledDate = clone $createdDate;
                    $scheduledDate->add(new \DateInterval('P' . $product['scheduled_duration'] . 'D'));
                    $now = new \DateTime();
                    $isScheduled = $scheduledDate > $now;
                    if ($isScheduled) {
                        $remainingDays = $now->diff($scheduledDate)->days;
                    }
                }
            }

            $this->view('products/view', [
                'product' => $product,
                'seller' => $seller,
                'reviews' => $reviews,
                'averageRating' => $averageRating,
                'reviewCount' => $reviewCount,
                'relatedProducts' => $relatedProducts,
                'inWishlist' => $inWishlist,
                'hasReviewed' => $hasReviewed,
                'isScheduled' => $isScheduled,
                'remainingDays' => $remainingDays,
                'scheduledDate' => $scheduledDate,
                'title' => $product['product_name'],
            ]);
        }

        /**
         * Search products
         */
        public function search()
        {
            $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;

            if (empty($keyword)) {
                $this->setFlash('error', 'Please enter a search keyword');
                $this->redirect('products');
                return;
            }
            
            // Get total count for pagination
            $totalCount = $this->productModel->getSearchCount($keyword);
            $totalPages = ceil($totalCount / $limit);
            
            // SERVER-SIDE CACHING FOR SEARCH RESULTS
            // Cache only the base product list with image URLs (exclude user-specific fields and ads)
            $cacheKey = 'search_' . md5($keyword . '|' . $sort . '|' . $page);
            $cached = $this->cache->get($cacheKey);

            if ($cached !== false && is_array($cached)) {
                $products = $cached;
            } else {
                // Get products with sorting and pagination
                $products = $this->productModel->searchProducts($keyword, $sort, $limit, $offset);
                foreach ($products as &$product) {
                    $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                    $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                }
                // Cache for 15 minutes (ads check happens after cache, so no need to cache ad status)
                $this->cache->set($cacheKey, $products, 900);
            }

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            // Insert additional sponsored ads at specific positions (Flipkart style: 1st, 3rd, 6th, then every 10th)
            $sponsoredAdsService = new \App\Services\SponsoredAdsService();
            $products = $sponsoredAdsService->insertSponsoredInSearchResults($products, $keyword);
            
            // Get ALL product IDs (including newly inserted ones) to check for active ads
            $allProductIds = array_column($products, 'id');
            $adsByProductId = [];
            
            if (!empty($allProductIds)) {
                $db = \App\Core\Database::getInstance();
                $placeholders = implode(',', array_fill(0, count($allProductIds), '?'));
                
                // Check if product_id has active ad - Check ALL products
                $allActiveAds = $db->query(
                    "SELECT a.product_id, a.id as ad_id
                     FROM ads a
                     INNER JOIN ads_types at ON a.ads_type_id = at.id
                     WHERE a.product_id IN ($placeholders)
                     AND a.status = 'active'
                     AND at.name = 'product_internal'
                     AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                     AND CURDATE() BETWEEN a.start_date AND a.end_date
                     AND a.product_id IS NOT NULL",
                    $allProductIds
                )->all();
                
                foreach ($allActiveAds as $ad) {
                    $adsByProductId[$ad['product_id']] = $ad['ad_id'];
                }
            }
            
            // Process all products: add image_url, review stats, and mark as sponsored if has active ad
            foreach ($products as &$product) {
                // Add image URL
                if (empty($product['image_url'])) {
                    $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                    $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                }
                
                // Add review stats
                if (!isset($product['review_count'])) {
                    $reviewStats = $this->productModel->getReviewStats($product['id']);
                    $product['review_count'] = $reviewStats['total_reviews'];
                    $product['avg_rating'] = $reviewStats['average_rating'];
                }
                
                // CRITICAL: Mark as sponsored if product has active ad
                // This ensures ALL products (existing + newly inserted) are marked correctly
                if (isset($adsByProductId[$product['id']])) {
                    $product['is_sponsored'] = true;
                    $product['ad_id'] = $adsByProductId[$product['id']];
                }
            }
            unset($product);

            $this->view('products/search', [
                'products' => $products,
                'keyword' => $keyword,
                'sort' => $sort,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'title' => 'Search Results: ' . $keyword,
            ]);
        }

        /**
         * Search products by flavor
         */
        public function searchByFlavor()
        {
            $flavor = isset($_GET['flavor']) ? trim($_GET['flavor']) : '';

            if (empty($flavor)) {
                $this->setFlash('error', 'Please specify a flavor');
                $this->redirect('products');
                return;
            }

            $sql = "SELECT * FROM products WHERE flavor LIKE ? ORDER BY id DESC";
            $products = $this->productModel->query($sql, ['%' . $flavor . '%']);
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Add review statistics
                $reviewStats = $this->productModel->getReviewStats($product['id']);
                $product['review_count'] = $reviewStats['total_reviews'];
                $product['avg_rating'] = $reviewStats['average_rating'];
            }

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            $this->view('products/flavor', [
                'products' => $products,
                'flavor' => $flavor,
                'title' => 'Products with ' . $flavor . ' Flavor',
            ]);
        }

        /**
         * Filter products by capsule type
         */
        public function filterByCapsule($isCapsule = 1)
        {
            $sql = "SELECT * FROM products WHERE capsule = ? ORDER BY id DESC";
            $products = $this->productModel->query($sql, [$isCapsule]);
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
            }

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            $this->view('products/capsule', [
                'products' => $products,
                'isCapsule' => $isCapsule,
                'title' => $isCapsule ? 'Capsule Products' : 'Non-Capsule Products',
            ]);
        }

        /**
         * Submit a product review
         */
        public function submitReview()
        {
            if (!Session::has('user_id')) {
                $this->setFlash('error', 'Please login to submit a review');
                $this->redirect('auth/login');
                return;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlash('error', 'Invalid request method');
                $this->redirect('products');
                return;
            }

            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
            $review = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

            $product = $this->productModel->find($productId);
            $hasReviewed = $this->reviewModel->hasUserReviewed(Session::get('user_id'), $productId);

            $errors = [];

            if (!$productId || !$product) {
                $errors['product_id'] = 'Invalid product';
            }

            if ($hasReviewed) {
                $errors['general'] = 'You have already reviewed this product';
            }

            if ($rating < 1 || $rating > 5) {
                $errors['rating'] = 'Rating must be between 1 and 5';
            }

            if (empty($review) || strlen($review) < 10) {
                $errors['review'] = 'Review must be at least 10 characters long';
            }

            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', array_values($errors)));
                $this->redirect('products/view/' . ($product['slug'] ?? $productId));
                return;
            }

            $data = [
                'product_id' => $productId,
                'user_id' => Session::get('user_id'),
                'rating' => $rating,
                'review' => $review
            ];

            if ($this->reviewModel->create($data)) {
                $this->setFlash('success', 'Your review has been submitted successfully');
            } else {
                $this->setFlash('error', 'Failed to submit your review due to a database error');
            }

            $this->redirect('products/view/' . ($product['slug'] ?? $productId));
        }

        /**
         * Display products by category
         * 
         * @param string $category Category name
         * @param string $subtype Optional subtype name
         */
        public function category($category = null, $subtype = null)
        {
            if (!$category) {
                $this->setFlash('error', 'Category not specified');
                $this->redirect('products');
                return;
            }

            // URL decode category and subtype
            $category = urldecode($category);
            if ($subtype) {
                $subtype = urldecode($subtype);
            }

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
            // Normalize sort values: price-low -> price_low, price-high -> price_high
            $sort = str_replace('-', '_', $sort);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // If subtype is provided, filter by both category and subtype
            if ($subtype) {
                $products = $this->productModel->getProductsByCategoryAndSubtype($category, $subtype, $limit, $offset, $sort);
                $totalProducts = $this->productModel->getProductCountByCategoryAndSubtype($category, $subtype);
                $title = ucfirst($category) . ' - ' . ucfirst($subtype) . ' Products';
            } else {
                // Get products with sorting by category only
                $products = $this->productModel->getProductsByCategory($category, $limit, $offset, $sort);
                $totalProducts = $this->productModel->getProductCountByCategory($category);
                $title = ucfirst($category) . ' Products';
            }

            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Add review statistics
                $reviewStats = $this->productModel->getReviewStats($product['id']);
                $product['review_count'] = $reviewStats['total_reviews'];
                $product['avg_rating'] = $reviewStats['average_rating'];
            }
            $totalPages = ceil($totalProducts / $limit);

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            // Insert sponsored ads at top and every 8-12 products
            $sponsoredAdsService = new \App\Services\SponsoredAdsService();
            $products = $sponsoredAdsService->insertSponsoredInCategoryResults($products, $category, $subtype);

            $this->view('products/category', [
                'products' => $products,
                'category' => $category,
                'subtype' => $subtype,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'sort' => $sort,
                'title' => $title,
            ]);
        }

        /**
         * Display products by category and subtype
         * 
         * @param string $category Category name
         * @param string $subtype Subtype name
         */
        public function categorySubtype($category = null, $subtype = null)
        {
            if (!$category || !$subtype) {
                $this->setFlash('error', 'Category or subtype not specified');
                $this->redirect('products');
                return;
            }
            
            // URL decode category and subtype
            $category = urldecode($category);
            $subtype = urldecode($subtype);

            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
            // Normalize sort values: price-low -> price_low, price-high -> price_high
            $sort = str_replace('-', '_', $sort);
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get products with category and subtype filtering
            $products = $this->productModel->getProductsByCategoryAndSubtype($category, $subtype, $limit, $offset, $sort);
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
            }
            $totalProducts = $this->productModel->getProductCountByCategoryAndSubtype($category, $subtype);
            $totalPages = ceil($totalProducts / $limit);

            if (Session::has('user_id')) {
                foreach ($products as &$product) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                }
            }

            $this->view('products/category', [
                'products' => $products,
                'category' => $category,
                'subtype' => $subtype,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'sort' => $sort,
                'title' => $category . ' - ' . $subtype . ' Products',
            ]);
        }

        /**
         * Get local product recommendations based on category
         * 
         * @param int $productId
         * @param int $limit
         * @return array
         */
        /**
         * Apply sale price to product
         */
        private function applyProductSalePrice($product)
        {
            if (empty($product)) return $product;
            
            $now = date('Y-m-d H:i:s');
            $originalPrice = floatval($product['price'] ?? 0);
            
            // Check if product is on site-wide sale
            if (!empty($product['is_on_sale']) && 
                !empty($product['sale_start_date']) && 
                !empty($product['sale_end_date']) &&
                !empty($product['sale_discount_percent']) &&
                $product['sale_discount_percent'] > 0) {
                
                if ($product['sale_start_date'] <= $now && $product['sale_end_date'] >= $now) {
                    $discountPercent = floatval($product['sale_discount_percent']);
                    $calculatedSalePrice = $originalPrice - (($originalPrice * $discountPercent) / 100);
                    
                    // Use calculated sale price if no manual sale_price set, or if calculated is better
                    if (empty($product['sale_price']) || $calculatedSalePrice < floatval($product['sale_price'])) {
                        $product['sale_price'] = $calculatedSalePrice;
                    }
                }
            }
            
            return $product;
        }

        private function getLocalRecommendations($productId, $limit = 4)
        {
            $product = $this->productModel->find($productId);

            if (!$product) {
                return [];
            }

            $category = $product['category'] ?? '';

            if (empty($category)) {
                return [];
            }

            $results = $this->productModel->getRelatedProducts($productId, $category, $limit);

            foreach ($results as &$relatedProduct) {
                $primaryImage = $this->productImageModel->getPrimaryImage($relatedProduct['id']);
                $relatedProduct['image_url'] = $this->getProductImageUrl($relatedProduct, $primaryImage);
            }

            return is_array($results) ? $results : [];
        }

        /**
         * Live search products via AJAX
         */
        public function liveSearch()
        {
            // Allow both AJAX and regular requests for testing
            if (!$this->isAjaxRequest() && !isset($_GET['q'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request']);
                return;
            }

            $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
            
            // Debug logging
            error_log('Live search keyword: ' . $keyword);
            error_log('AJAX request: ' . ($this->isAjaxRequest() ? 'Yes' : 'No'));

            if (empty($keyword) || strlen($keyword) < 2) {
                $this->jsonResponse(['success' => true, 'products' => []]);
                return;
            }

            try {
                $products = $this->productModel->searchProducts($keyword, 8, 0); // Limit to 8 results for live search
                
                // Debug logging
                error_log('Found products: ' . count($products));
                
                // Add image URLs to products
                foreach ($products as &$product) {
                    $product['image_url'] = $this->getProductImageUrl($product);
                }

                $this->jsonResponse([
                    'success' => true,
                    'products' => $products,
                    'count' => count($products)
                ]);
            } catch (Exception $e) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Search failed',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }