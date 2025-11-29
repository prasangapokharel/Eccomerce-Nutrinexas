<?php

namespace App\Controllers\Product;

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
            // Initialize performance cache
            if (!class_exists('App\Helpers\PerformanceCache')) {
                require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
            }
            \App\Helpers\PerformanceCache::init();
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 12;
            $offset = ($page - 1) * $limit;

            // Get filters
            $sort = $_GET['sort'] ?? '';
            $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
            $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
            $inStock = isset($_GET['in_stock']) ? true : false;
            $lowStock = isset($_GET['low_stock']) ? true : false;

            // Check cache for products list (only for page 1, no filters)
            $cacheKey = 'products_index_' . $page . '_' . md5($sort . $minPrice . $maxPrice . $inStock . $lowStock);
            if ($page === 1 && empty($sort) && $minPrice === null && $maxPrice === null && !$inStock && !$lowStock) {
                $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
                if ($cachedData) {
                    $this->view('products/index', $cachedData);
                    return;
                }
            }

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

            $viewData = [
                'products' => $products,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalProducts' => $totalProducts,
                'title' => 'All Products',
            ];
            
            // Cache the data for 30 minutes (only for page 1, no filters)
            if ($page === 1 && empty($sort) && $minPrice === null && $maxPrice === null && !$inStock && !$lowStock) {
                \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $viewData, 1800);
            }
            
            $this->view('products/index', $viewData);
        }

        /**
         * AJAX Filter endpoint
         */
        public function filter()
        {
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
                $this->redirect('products');
                return;
            }
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 12;
            $offset = ($page - 1) * $limit;
            
            // Get filters
            $sort = $_GET['sort'] ?? '';
            $minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
            $maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
            $categories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
            $brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
            $sizes = isset($_GET['sizes']) ? (array)$_GET['sizes'] : [];
            $colors = isset($_GET['colors']) ? (array)$_GET['colors'] : [];
            
            // Apply filters
            $products = $this->getFilteredProductsAdvanced($limit, $offset, $sort, $minPrice, $maxPrice, $categories, $brands, $sizes, $colors);
            
            foreach ($products as &$product) {
                $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
                $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);
                
                // Add wishlist status
                if (Session::has('user_id')) {
                    $product['in_wishlist'] = $this->wishlistModel->isInWishlist(Session::get('user_id'), $product['id']);
                } else {
                    $product['in_wishlist'] = false;
                }
            }
            unset($product);
            
            // Render product grid HTML
            ob_start();
            if (empty($products)) {
                echo '<div class="col-span-full bg-white rounded-lg shadow-sm border border-neutral-100 p-8 text-center">
                    <div class="w-14 h-14 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4-8-4V7m16 0L12 3 4 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-neutral-700 mb-1">No Products Found</h3>
                    <p class="text-sm text-neutral-500">Try adjusting your filters</p>
                </div>';
            } else {
                include __DIR__ . '/../components/pricing-helper.php';
                foreach ($products as $product):
                    $badge = null;
                    if (!empty($product['is_new'])) {
                        $badge = ['label' => 'New'];
                    } elseif (!empty($product['is_best_seller'])) {
                        $badge = ['label' => 'Best'];
                    }
                    
                    $cardOptions = [
                        'theme' => 'light',
                        'showCta' => false,
                        'cardClass' => 'w-full h-full',
                    ];
                    
                    if ($badge) {
                        $cardOptions['topRightBadge'] = $badge;
                    }
                    
                    include dirname(__DIR__) . '/views/home/sections/shared/product-card.php';
                endforeach;
            }
            $html = ob_get_clean();
            
            $this->jsonResponse([
                'success' => true,
                'html' => $html,
                'count' => count($products)
            ]);
        }

        /**
         * Get filter data (categories, brands, sizes, colors, price range)
         */
        private function getFilterData()
        {
            $db = \App\Core\Database::getInstance();
            
            // Get categories
            $categories = $db->query(
                "SELECT DISTINCT category FROM products WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category ASC"
            )->all();
            $categoryList = array_column($categories, 'category');
            
            // Get brands (assuming brand is stored in a field, adjust if needed)
            $brands = $db->query(
                "SELECT DISTINCT seller_id FROM products WHERE status = 'active' AND seller_id IS NOT NULL AND seller_id > 0"
            )->all();
            $brandList = array_map(function($b) use ($db) {
                $seller = $db->query("SELECT business_name FROM sellers WHERE id = ?", [$b['seller_id']])->single();
                return $seller['business_name'] ?? 'Brand ' . $b['seller_id'];
            }, $brands);
            $brandList = array_filter($brandList);
            
            // Get sizes from size_available JSON field
            $sizes = $db->query(
                "SELECT DISTINCT size_available FROM products WHERE status = 'active' AND size_available IS NOT NULL"
            )->all();
            $sizeList = [];
            foreach ($sizes as $size) {
                if (!empty($size['size_available'])) {
                    $sizeArray = json_decode($size['size_available'], true);
                    if (is_array($sizeArray)) {
                        $sizeList = array_merge($sizeList, $sizeArray);
                    }
                }
            }
            $sizeList = array_unique(array_filter($sizeList));
            sort($sizeList);
            
            // Get colors from colors JSON field
            $colors = $db->query(
                "SELECT DISTINCT colors FROM products WHERE status = 'active' AND colors IS NOT NULL"
            )->all();
            $colorList = [];
            foreach ($colors as $color) {
                if (!empty($color['colors'])) {
                    $colorArray = json_decode($color['colors'], true);
                    if (is_array($colorArray)) {
                        $colorList = array_merge($colorList, $colorArray);
                    }
                }
            }
            $colorList = array_unique(array_filter($colorList));
            
            // Get price range
            $priceRange = $db->query(
                "SELECT MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price 
                 FROM products WHERE status = 'active'"
            )->single();
            
            return [
                'categories' => $categoryList,
                'brands' => array_values($brandList),
                'sizes' => array_values($sizeList),
                'colors' => array_values($colorList),
                'minPrice' => (int)($priceRange['min_price'] ?? 0),
                'maxPrice' => (int)($priceRange['max_price'] ?? 10000)
            ];
        }

        /**
         * Advanced filtered products with categories, brands, sizes, colors
         */
        private function getFilteredProductsAdvanced($limit, $offset, $sort = '', $minPrice = null, $maxPrice = null, $categories = [], $brands = [], $sizes = [], $colors = [])
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

            // Category filter
            if (!empty($categories)) {
                $placeholders = implode(',', array_fill(0, count($categories), '?'));
                $where[] = "p.category IN ($placeholders)";
                $params = array_merge($params, $categories);
            }

            // Brand filter (by seller_id)
            if (!empty($brands)) {
                $sellerIds = [];
                foreach ($brands as $brandName) {
                    $seller = $db->query("SELECT id FROM sellers WHERE business_name = ?", [$brandName])->single();
                    if ($seller) {
                        $sellerIds[] = $seller['id'];
                    }
                }
                if (!empty($sellerIds)) {
                    $placeholders = implode(',', array_fill(0, count($sellerIds), '?'));
                    $where[] = "p.seller_id IN ($placeholders)";
                    $params = array_merge($params, $sellerIds);
                }
            }

            // Size filter
            if (!empty($sizes)) {
                $sizeConditions = [];
                foreach ($sizes as $size) {
                    $sizeConditions[] = "(p.size_available LIKE ? OR JSON_CONTAINS(p.size_available, ?))";
                    $sizeJson = json_encode($size);
                    $params[] = '%' . $size . '%';
                    $params[] = $sizeJson;
                }
                if (!empty($sizeConditions)) {
                    $where[] = "(" . implode(' OR ', $sizeConditions) . ")";
                }
            }

            // Color filter
            if (!empty($colors)) {
                $colorConditions = [];
                foreach ($colors as $color) {
                    $colorConditions[] = "(p.colors LIKE ? OR JSON_CONTAINS(p.colors, ?))";
                    $colorJson = json_encode($color);
                    $params[] = '%' . $color . '%';
                    $params[] = $colorJson;
                }
                if (!empty($colorConditions)) {
                    $where[] = "(" . implode(' OR ', $colorConditions) . ")";
                }
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
            
            // Ensure sale column is included
            if (!isset($product['sale'])) {
                $product['sale'] = 'off';
            }

            $primaryImage = $this->productImageModel->getPrimaryImage($product['id']);
            $product['image_url'] = $this->getProductImageUrl($product, $primaryImage);

            // Get seller information if product has a seller
            $seller = null;
            $sellerStats = null;
            if (!empty($product['seller_id'])) {
                $sellerModel = new \App\Models\Seller();
                $seller = $sellerModel->getDb()->query(
                    "SELECT id, name, company_name, logo_url, status 
                     FROM sellers 
                     WHERE id = ? AND status = 'active'"
                )->bind([$product['seller_id']])->single();
                
                if ($seller) {
                    // Calculate seller statistics
                    $db = \App\Core\Database::getInstance();
                    
                    // Get reviews for seller's products
                    $reviews = $db->query(
                        "SELECT r.rating 
                         FROM reviews r
                         INNER JOIN products p ON r.product_id = p.id
                         WHERE p.seller_id = ?",
                        [$seller['id']]
                    )->all();
                    
                    $totalReviews = count($reviews);
                    $positiveReviews = 0;
                    $goodReviews = 0;
                    $averageReviews = 0;
                    $negativeReviews = 0;
                    
                    foreach ($reviews as $review) {
                        $rating = (int)($review['rating'] ?? 0);
                        if ($rating >= 5) {
                            $positiveReviews++;
                        } elseif ($rating >= 4) {
                            $goodReviews++;
                        } elseif ($rating >= 3) {
                            $averageReviews++;
                        } else {
                            $negativeReviews++;
                        }
                    }
                    
                    $positiveSellerPercent = $totalReviews > 0 ? round(($positiveReviews / $totalReviews) * 100) : 0;
                    
                    // Get ship on time percentage
                    // Calculate based on delivered orders within expected timeframe (7 days from creation)
                    $orders = $db->query(
                        "SELECT o.id, o.created_at, o.delivered_at, o.status
                         FROM orders o
                         INNER JOIN order_items oi ON o.id = oi.order_id
                         WHERE oi.seller_id = ? 
                         AND o.status IN ('completed', 'delivered')
                         AND o.delivered_at IS NOT NULL",
                        [$seller['id']]
                    )->all();
                    
                    $totalOrders = count($orders);
                    $onTimeOrders = 0;
                    $expectedDeliveryDays = 7; // Expected delivery within 7 days
                    
                    foreach ($orders as $order) {
                        if (!empty($order['delivered_at']) && !empty($order['created_at'])) {
                            $createdDate = strtotime($order['created_at']);
                            $deliveredDate = strtotime($order['delivered_at']);
                            $daysToDeliver = ($deliveredDate - $createdDate) / (60 * 60 * 24);
                            
                            // Consider on-time if delivered within expected days
                            if ($daysToDeliver <= $expectedDeliveryDays) {
                                $onTimeOrders++;
                            }
                        }
                    }
                    
                    $shipOnTimePercent = $totalOrders > 0 ? round(($onTimeOrders / $totalOrders) * 100) : 0;
                    
                    $sellerStats = [
                        'positive_seller_percent' => $positiveSellerPercent,
                        'ship_on_time_percent' => $shipOnTimePercent,
                        'total_reviews' => $totalReviews,
                        'positive_reviews' => $positiveReviews,
                        'good_reviews' => $goodReviews,
                        'average_reviews' => $averageReviews,
                        'negative_reviews' => $negativeReviews
                    ];
                }
            }

            $reviews = $this->reviewModel->getByProductId($product['id']);
            $averageRating = $this->reviewModel->getAverageRating($product['id']);
            $reviewCount = $this->reviewModel->getReviewCount($product['id']);
            $relatedProducts = $this->getLocalRecommendations($product['id'], 3);
            foreach ($relatedProducts as &$relatedProduct) {
                $primaryImage = $this->productImageModel->getPrimaryImage($relatedProduct['id']);
                $relatedProduct['image_url'] = $this->getProductImageUrl($relatedProduct, $primaryImage);
            }
            
            // Get low-price suggested products (exclude current product)
            $lowPriceProducts = [];
            $db = \App\Core\Database::getInstance();
            $suggestedProducts = $db->query(
                "SELECT p.*, 
                        (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image_url
                 FROM products p
                 WHERE p.id != ?
                 AND p.status = 'active'
                 AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                 AND (COALESCE(p.sale_price, p.price) > 0)
                 ORDER BY COALESCE(p.sale_price, p.price) ASC
                 LIMIT 2",
                [$product['id']]
            )->all();
            
            foreach ($suggestedProducts as &$suggested) {
                if (!empty($suggested['primary_image_url'])) {
                    $suggested['image_url'] = filter_var($suggested['primary_image_url'], FILTER_VALIDATE_URL) 
                        ? $suggested['primary_image_url'] 
                        : ASSETS_URL . '/uploads/images/' . $suggested['primary_image_url'];
                } else {
                    $suggested['image_url'] = ASSETS_URL . '/images/products/default.jpg';
                }
                $primaryImage = $this->productImageModel->getPrimaryImage($suggested['id']);
                $suggested['image_url'] = $this->getProductImageUrl($suggested, $primaryImage);
            }
            $lowPriceProducts = $suggestedProducts;
            
            // Get internal product ad for suggestions
            $internalProductAd = null;
            $db = \App\Core\Database::getInstance();
            $adProduct = $db->query(
                "SELECT p.*, a.id as ad_id,
                        (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id AND pi.is_primary = 1 LIMIT 1) as primary_image_url
                 FROM ads a
                 INNER JOIN ads_types at ON a.ads_type_id = at.id
                 INNER JOIN products p ON a.product_id = p.id
                 WHERE at.name = 'product_internal'
                 AND a.status = 'active'
                 AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                 AND CURDATE() BETWEEN a.start_date AND a.end_date
                 AND a.product_id IS NOT NULL
                 AND a.product_id != ?
                 AND p.status = 'active'
                 AND p.approval_status = 'approved'
                 ORDER BY RAND()
                 LIMIT 1",
                [$product['id']]
            )->single();
            
            if ($adProduct) {
                // Get image URL
                if (!empty($adProduct['primary_image_url'])) {
                    $adProduct['image_url'] = filter_var($adProduct['primary_image_url'], FILTER_VALIDATE_URL) 
                        ? $adProduct['primary_image_url'] 
                        : ASSETS_URL . '/uploads/images/' . $adProduct['primary_image_url'];
                } else {
                    $adProduct['image_url'] = ASSETS_URL . '/images/products/default.jpg';
                }
                $primaryImage = $this->productImageModel->getPrimaryImage($adProduct['id']);
                $adProduct['image_url'] = $this->getProductImageUrl($adProduct, $primaryImage);
                $adProduct['is_sponsored'] = true;
                $internalProductAd = $adProduct;
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
            
            $isScheduled = false;
            $remainingDays = 0;
            
            if (isset($product['is_scheduled']) && $product['is_scheduled']) {
                if (!empty($product['scheduled_date'])) {
                    // Use specific scheduled date
                    $scheduledDate = new \DateTime($product['scheduled_date']);
                    $now = new \DateTime();
                    // Product is scheduled only if launch date is in the future
                    // If launch date equals or is before current date, allow ordering
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

            // Get product view count
            $productViewModel = new \App\Models\ProductView();
            $viewCount = $productViewModel->getViewCount($product['id']);
            
            // Get product like count and user's like status
            $productSocialModel = new \App\Models\ProductSocial();
            $likeCount = $productSocialModel->getLikeCount($product['id']);
            $isLiked = false;
            if (Session::has('user_id')) {
                $isLiked = $productSocialModel->isLiked($product['id'], Session::get('user_id'));
            }
            
            // Record view (async via JavaScript, but we can also do it here for immediate count)
            $productViewModel->recordView($product['id']);

            $this->view('products/view', [
                'product' => $product,
                'seller' => $seller,
                'sellerStats' => $sellerStats,
                'reviews' => $reviews,
                'averageRating' => $averageRating,
                'reviewCount' => $reviewCount,
                'viewCount' => $viewCount,
                'likeCount' => $likeCount,
                'isLiked' => $isLiked,
                'relatedProducts' => $relatedProducts,
                'internalProductAd' => $internalProductAd,
                'lowPriceProducts' => $lowPriceProducts,
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

            // Initialize performance cache
            if (!class_exists('App\Helpers\PerformanceCache')) {
                require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
            }
            \App\Helpers\PerformanceCache::init();

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
            
            // Check cache for category page (only for page 1, default sort)
            $cacheKey = 'products_category_' . md5($category . $subtype . $sort . $page);
            if ($page === 1 && $sort === 'newest') {
                $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
                if ($cachedData) {
                    $this->view('products/category', $cachedData);
                    return;
                }
            }

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

            $viewData = [
                'products' => $products,
                'category' => $category,
                'subtype' => $subtype,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'sort' => $sort,
                'title' => $title,
            ];
            
            // Cache the data for 30 minutes (only for page 1, default sort)
            if ($page === 1 && $sort === 'newest') {
                \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $viewData, 1800);
            }
            
            $this->view('products/category', $viewData);
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
        // Sale price calculation is now handled by SaleHelper in views

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