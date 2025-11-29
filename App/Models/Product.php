<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'id';
    
    private $cache;
    private $cachePrefix = 'product_';
    private $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get all products with pagination
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function getAllProducts($limit = 20, $offset = 0, $filters = [])
    {
        $sql = "SELECT p.*
                FROM {$this->table} p
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $sql .= " AND p.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->query($sql, $params)->all();
    }

    /**
     * Get product by ID with full details
     *
     * @param int $id
     * @return array|false
     */
    public function getProductById($id)
    {
        $cacheKey = $this->cachePrefix . 'id_' . $id;
        return $this->cache->remember($cacheKey, function () use ($id) {
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, image, is_featured, status, created_at, updated_at,
                           is_scheduled, scheduled_date, scheduled_duration, scheduled_message,
                           sale_start_date, sale_end_date, sale_discount_percent, is_on_sale,
                           is_digital, colors, product_type_main, product_type, seller_id
                    FROM {$this->table} 
                    WHERE id = ?";
            $product = $this->db->query($sql, [$id])->single();
            
            if ($product) {
                $product = $this->applySalePrice($product);
                $product = $this->checkScheduledStatus($product);
            }
            
            return $product;
        }, $this->cacheTTL);
    }
    
    /**
     * Apply sale price calculation
     */
    public function applySalePrice($product)
    {
        $now = date('Y-m-d H:i:s');
        
        // Check if product is on sale
        if (!empty($product['is_on_sale']) && 
            !empty($product['sale_start_date']) && 
            !empty($product['sale_end_date']) &&
            $product['sale_start_date'] <= $now && 
            $product['sale_end_date'] >= $now &&
            !empty($product['sale_discount_percent']) &&
            $product['sale_discount_percent'] > 0) {
            
            // Calculate sale price from discount percent
            $originalPrice = floatval($product['price']);
            $discountPercent = floatval($product['sale_discount_percent']);
            $discountAmount = ($originalPrice * $discountPercent) / 100;
            $calculatedSalePrice = $originalPrice - $discountAmount;
            
            // Use calculated sale price if no manual sale_price set, or if calculated is better
            if (empty($product['sale_price']) || $calculatedSalePrice < floatval($product['sale_price'])) {
                $product['sale_price'] = $calculatedSalePrice;
            }
        }
        
        return $product;
    }
    
    /**
     * Check if product is scheduled
     */
    private function checkScheduledStatus($product)
    {
        if (!empty($product['is_scheduled']) && !empty($product['scheduled_date'])) {
            $now = date('Y-m-d H:i:s');
            $scheduledDate = $product['scheduled_date'];
            
            // Product is scheduled only if launch date is in the future
            // If launch date equals or is before current date, allow ordering
            if ($scheduledDate > $now) {
                $product['is_scheduled'] = 1;
                $product['scheduled_available'] = false;
            } else {
                // Launch date has arrived or passed - allow ordering
                $product['is_scheduled'] = 0;
                $product['scheduled_available'] = true;
            }
        } else {
            $product['is_scheduled'] = 0;
            $product['scheduled_available'] = true;
        }
        
        return $product;
    }

    /**
     * Get product by slug
     *
     * @param string $slug
     * @return array|false
     */
    public function getProductBySlug($slug)
    {
        $cacheKey = $this->cachePrefix . 'slug_' . $slug;
        return $this->cache->remember($cacheKey, function () use ($slug) {
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, image, is_featured, status, created_at, updated_at,
                           is_digital, colors, product_type_main, product_type
                    FROM {$this->table} 
                    WHERE slug = ? AND status = 'active' 
                    AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)";
            return $this->db->query($sql, [$slug])->single();
        }, $this->cacheTTL);
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductsByCategory($categoryName, $limit = 20, $offset = 0, $sort = 'newest')
    {
        $cacheKey = $this->cachePrefix . 'category_' . $categoryName . '_' . $limit . '_' . $offset . '_' . $sort;
        return $this->cache->remember($cacheKey, function () use ($categoryName, $limit, $offset, $sort) {
            // Determine sort order
            $orderBy = "created_at DESC";
            switch ($sort) {
                case 'price_low':
                    $orderBy = "price ASC";
                    break;
                case 'price_high':
                    $orderBy = "price DESC";
                    break;
                case 'name':
                    $orderBy = "product_name ASC";
                    break;
                case 'newest':
                default:
                    $orderBy = "created_at DESC";
                    break;
            }
            
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, image, is_featured, status, created_at, updated_at
                    FROM {$this->table} 
                    WHERE category = ? AND status = 'active' 
                    AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)
                    ORDER BY {$orderBy}
                    LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$categoryName, $limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get products by category and subtype
     *
     * @param string $categoryName
     * @param string $subtype
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductsByCategoryAndSubtype($categoryName, $subtype, $limit = 20, $offset = 0, $sort = 'newest')
    {
        $cacheKey = $this->cachePrefix . 'category_subtype_' . $categoryName . '_' . $subtype . '_' . $limit . '_' . $offset . '_' . $sort;
        return $this->cache->remember($cacheKey, function () use ($categoryName, $subtype, $limit, $offset, $sort) {
            // Determine sort order
            $orderBy = "created_at DESC";
            switch ($sort) {
                case 'price_low':
                    $orderBy = "price ASC";
                    break;
                case 'price_high':
                    $orderBy = "price DESC";
                    break;
                case 'name':
                    $orderBy = "product_name ASC";
                    break;
                case 'newest':
                default:
                    $orderBy = "created_at DESC";
                    break;
            }
            
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, subtype, image, is_featured, status, created_at, updated_at
                    FROM {$this->table} 
                    WHERE category = ? AND subtype = ? AND status = 'active'
                    ORDER BY {$orderBy}
                    LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$categoryName, $subtype, $limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get product count by category and subtype
     *
     * @param string $categoryName
     * @param string $subtype
     * @return int
     */
    public function getProductCountByCategoryAndSubtype($categoryName, $subtype)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE category = ? AND subtype = ? AND status = 'active'";
        $result = $this->db->query($sql, [$categoryName, $subtype])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get featured products
     *
     * @param int $limit
     * @return array
     */
    public function getFeaturedProducts($limit = 8)
    {
        $cacheKey = $this->cachePrefix . 'featured_' . $limit;
        return $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, image, is_featured, status, created_at, updated_at
                    FROM {$this->table} 
                    WHERE is_featured = 1 AND status = 'active' 
                    AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)
                    ORDER BY created_at DESC
                    LIMIT ?";
            return $this->db->query($sql, [$limit])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get related products
     *
     * @param int $productId
     * @param int $categoryId
     * @param int $limit
     * @return array
     */
    public function getRelatedProducts($productId, $categoryName, $limit = 4)
    {
        $cacheKey = $this->cachePrefix . 'related_' . $productId . '_' . $categoryName . '_' . $limit;
        return $this->cache->remember($cacheKey, function () use ($productId, $categoryName, $limit) {
            $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                           stock_quantity, category, image, is_featured, status, created_at, updated_at
                    FROM {$this->table} 
                    WHERE id != ? AND category = ? AND status = 'active' 
                    AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)
                    ORDER BY created_at DESC
                    LIMIT ?";
            return $this->db->query($sql, [$productId, $categoryName, $limit])->all();
        }, $this->cacheTTL);
    }

    /**
     * Search products
     *
     * @param string $searchTerm
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchProducts($searchTerm, $sort = 'newest', $limit = 20, $offset = 0)
    {
        $searchPattern = "%{$searchTerm}%";
        
        // Handle sorting
        $orderBy = "p.created_at DESC";
        switch ($sort) {
            case 'price-low':
                $orderBy = "p.price ASC";
                break;
            case 'price-high':
                $orderBy = "p.price DESC";
                break;
            case 'popular':
                $orderBy = "p.is_featured DESC, p.created_at DESC";
                break;
            case 'newest':
            default:
                $orderBy = "p.created_at DESC";
                break;
        }
        
        $sql = "SELECT p.*
                FROM {$this->table} p
                WHERE (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
                AND p.status = 'active'
                AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$searchPattern, $searchPattern, $searchPattern, $limit, $offset])->all();
    }

    /**
     * Get total count of search results
     *
     * @param string $searchTerm
     * @return int
     */
    public function getSearchCount($searchTerm)
    {
        $searchPattern = "%{$searchTerm}%";
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table} p
                WHERE (p.product_name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
                AND p.status = 'active'
                AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)";
        
        $result = $this->db->query($sql, [$searchPattern, $searchPattern, $searchPattern])->single();
        return (int)($result['count'] ?? 0);
    }

    /**
     * Get ranked products using Flipkart/Amazon-style algorithm
     * 
     * Factors:
     * - Fresh products (new < 7 days: +40, < 30 days: +20)
     * - Product rating (rating * 10)
     * - Seller rating (seller_rating * 5)
     * - Monthly sales (min(sales, 100))
     * - Price fairness (+10 if good price)
     * - Stock penalty (-20 if stock < 5)
     * - Sponsored ads (+60 if active ad)
     * - Category trend score
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRankedProducts($limit = 20, $offset = 0)
    {
        $sql = "
            SELECT 
                p.*,
                COALESCE(product_ratings.avg_rating, 0) as product_rating,
                COALESCE(seller_ratings.avg_rating, 0) as seller_rating,
                COALESCE(monthly_sales.count, 0) as monthly_sales,
                CASE 
                    WHEN p.sale_price > 0 AND p.sale_price < p.price * 0.9 THEN 1 
                    ELSE 0 
                END as price_is_good,
                CASE 
                    WHEN MAX(active_ads.ad_id) IS NOT NULL THEN 1 
                    ELSE 0 
                END as is_sponsored,
                COALESCE(category_trend.score, 0) as category_trend_score,
                (
                    (CASE 
                        WHEN DATEDIFF(NOW(), p.created_at) < 7 THEN 40
                        WHEN DATEDIFF(NOW(), p.created_at) < 30 THEN 20
                        ELSE 0
                    END)
                    +
                    (COALESCE(product_ratings.avg_rating, 0) * 10)
                    +
                    (COALESCE(seller_ratings.avg_rating, 0) * 5)
                    +
                    (LEAST(COALESCE(monthly_sales.count, 0), 100))
                    +
                    (CASE 
                        WHEN p.sale_price > 0 AND p.sale_price < p.price * 0.9 THEN 10 
                        ELSE 0 
                    END)
                    +
                    (CASE WHEN p.stock_quantity < 5 THEN -20 ELSE 0 END)
                    +
                    (CASE 
                        WHEN MAX(active_ads.ad_id) IS NOT NULL THEN 60 
                        ELSE 0 
                    END)
                    +
                    COALESCE(category_trend.score, 0)
                ) AS score
            FROM products p
            LEFT JOIN (
                SELECT product_id, AVG(rating) as avg_rating
                FROM reviews
                GROUP BY product_id
            ) product_ratings ON p.id = product_ratings.product_id
            LEFT JOIN (
                SELECT s.id as seller_id, COALESCE(AVG(r.rating), 0) as avg_rating
                FROM sellers s
                LEFT JOIN products sp ON s.id = sp.seller_id
                LEFT JOIN reviews r ON sp.id = r.product_id
                GROUP BY s.id
            ) seller_ratings ON p.seller_id = seller_ratings.seller_id
            LEFT JOIN (
                SELECT oi.product_id, COUNT(*) as count
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND o.status != 'cancelled'
                GROUP BY oi.product_id
            ) monthly_sales ON p.id = monthly_sales.product_id
            LEFT JOIN (
                SELECT a.product_id, MAX(a.id) as ad_id
                FROM ads a
                INNER JOIN ads_types at ON a.ads_type_id = at.id
                WHERE a.status = 'active'
                AND at.name = 'product_internal'
                AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
                AND DATE(CURDATE()) >= DATE(a.start_date) 
                AND DATE(CURDATE()) <= DATE(a.end_date)
                AND a.product_id IS NOT NULL
                GROUP BY a.product_id
            ) active_ads ON p.id = active_ads.product_id
            LEFT JOIN (
                SELECT p2.category, COUNT(*) * 0.1 as score
                FROM products p2
                INNER JOIN order_items oi ON p2.id = oi.product_id
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND o.status != 'cancelled'
                GROUP BY p2.category
            ) category_trend ON p.category = category_trend.category
            WHERE p.status = 'active'
            AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
            GROUP BY p.id, category_trend.score
            ORDER BY score DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        return $this->db->query($sql, [$limit, $offset])->all();
    }

    /**
     * Get products on sale
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductsOnSale($limit = 20, $offset = 0)
    {
        $cacheKey = $this->cachePrefix . 'sale_' . $limit . '_' . $offset;
        return $this->cache->remember($cacheKey, function () use ($limit, $offset) {
            $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug
                    FROM {$this->table} p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.sale_price > 0 AND p.sale_price < p.price AND p.status = 'active' 
                    AND (p.approval_status = 'approved' OR p.approval_status IS NULL OR p.seller_id IS NULL OR p.seller_id = 0)
                    ORDER BY (p.price - p.sale_price) DESC
                    LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get low stock products
     *
     * @param int $threshold
     * @param int $limit
     * @return array
     */
    public function getLowStockProducts($threshold = 10, $limit = 50)
    {
        $sql = "SELECT id, product_name, slug, short_description, price, sale_price, 
                       stock_quantity, category, image, is_featured, created_at
                FROM {$this->table} 
                WHERE stock_quantity <= ? AND status = 'active'
                ORDER BY stock_quantity ASC
                LIMIT ?";
        
        return $this->db->query($sql, [$threshold, $limit])->all();
    }

    /**
     * Get out of stock products
     *
     * @param int $limit
     * @return array
     */
    public function getOutOfStockProducts($limit = 50)
    {
        $sql = "SELECT id, product_name, slug, short_description, price, sale_price, 
                       stock_quantity, category, image, is_featured, created_at
                FROM {$this->table} 
                WHERE stock_quantity <= 0 AND status = 'active'
                ORDER BY updated_at DESC
                LIMIT ?";
        
        return $this->db->query($sql, [$limit])->all();
    }

    /**
     * Create new product
     *
     * @param array $data
     * @return int|false
     */
    public function createProduct($data)
    {
        $fields = ['product_name', 'slug', 'description', 'short_description', 'price', 'sale_price',
                   'stock_quantity', 'category', 'subtype', 'image', 'tags',
                   'is_featured', 'status', 'approval_status', 'meta_title', 'meta_description', 'seller_id',
                   'is_scheduled', 'scheduled_date', 'scheduled_end_date', 'scheduled_duration', 'scheduled_message'];
        $placeholders = [];
        $values = [];
        
        foreach ($fields as $field) {
            $placeholders[] = '?';
            if ($field === 'product_name') {
                $values[] = $data['name'] ?? $data['product_name'] ?? null;
            } elseif ($field === 'slug') {
                $values[] = $data['slug'] ?? ($data['product_name'] ? $this->generateSlug($data['product_name']) : null);
            } elseif ($field === 'seller_id') {
                $values[] = $data['seller_id'] ?? null;
            } elseif ($field === 'approval_status') {
                $values[] = $data['approval_status'] ?? ($data['seller_id'] ? 'pending' : null);
            } else {
                $values[] = $data[$field] ?? null;
            }
        }
        
        $fields[] = 'created_at';
        $fields[] = 'updated_at';
        $placeholders[] = 'NOW()';
        $placeholders[] = 'NOW()';
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $result = $this->db->query($sql, $values)->execute();

        if ($result) {
            $this->invalidateCache();
            
            // Clear PerformanceCache for homepage
            if (class_exists('App\Helpers\PerformanceCache')) {
                try {
                    // Clear all homepage cache files
                    $cacheDir = ROOT_DIR . '/App/storage/cache/static/';
                    if (is_dir($cacheDir)) {
                        $files = glob($cacheDir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $content = @file_get_contents($file);
                                if ($content) {
                                    $data = @unserialize(@gzuncompress($content));
                                    if ($data && isset($data['content']) && is_array($data['content'])) {
                                        // Check if this is homepage data cache
                                        if (isset($data['content']['sliders']) || isset($data['content']['products'])) {
                                            @unlink($file);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // Clear database query cache
                    $dbCacheDir = ROOT_DIR . '/App/storage/cache/database/';
                    if (is_dir($dbCacheDir)) {
                        $files = glob($dbCacheDir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                @unlink($file);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Product create cache clear error: ' . $e->getMessage());
                }
            }
            
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update product
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProduct($id, $data)
    {
        $fields = [];
        $values = [];
        $existingSlug = $this->getSlugById($id);
        
        $allowedFields = [
            'product_name',
            'slug',
            'description',
            'short_description',
            'price',
            'sale_price',
            'stock_quantity',
            'category',
            'subtype',
            'image',
            'tags',
            'is_featured',
            'status',
            'approval_status',
            'approval_notes',
            'approved_by',
            'approved_at',
            'meta_title',
            'meta_description',
            'seller_id',
            'affiliate_commission',
            // Scheduling / launch fields
            'is_scheduled',
            'scheduled_date',
            'scheduled_end_date',
            'scheduled_duration',
            'scheduled_message',
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                // Handle NULL values for affiliate_commission
                if ($field === 'affiliate_commission' && $data[$field] === null) {
                    $fields[] = "{$field} = NULL";
                } else {
                    $fields[] = "{$field} = ?";
                    $values[] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $result = $this->db->query($sql, $values)->execute();

        if ($result) {
            // Clear PerformanceCache for homepage when product is updated
            if (class_exists('App\Helpers\PerformanceCache')) {
                try {
                    // Clear all homepage cache files
                    $cacheDir = ROOT_DIR . '/App/storage/cache/static/';
                    if (is_dir($cacheDir)) {
                        $files = glob($cacheDir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $content = @file_get_contents($file);
                                if ($content) {
                                    $data = @unserialize(@gzuncompress($content));
                                    if ($data && isset($data['content']) && is_array($data['content'])) {
                                        // Check if this is homepage data cache
                                        if (isset($data['content']['sliders']) || isset($data['content']['products'])) {
                                            @unlink($file);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // Clear database query cache
                    $dbCacheDir = ROOT_DIR . '/App/storage/cache/database/';
                    if (is_dir($dbCacheDir)) {
                        $files = glob($dbCacheDir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                @unlink($file);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Product update cache clear error: ' . $e->getMessage());
                }
            }
            $newSlug = $data['slug'] ?? null;
            $slugsToInvalidate = array_filter(array_unique([$existingSlug, $newSlug]), function ($value) {
                return !empty($value);
            });
            $this->invalidateCache($id, $slugsToInvalidate);
        }
        return $result;
    }

    /**
     * Update product stock
     *
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function updateStock($id, $quantity)
    {
        // Auto-set status to 'inactive' when stock hits zero
        $status = $quantity <= 0 ? 'inactive' : 'active';
        $sql = "UPDATE {$this->table} SET stock_quantity = ?, status = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$quantity, $status, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Decrease product stock
     *
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock($id, $quantity)
    {
        $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity - ?, updated_at = NOW() WHERE id = ? AND stock_quantity >= ?";
        $result = $this->db->query($sql, [$quantity, $id, $quantity])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Increase product stock
     *
     * @param int $id
     * @param int $quantity
     * @return bool
     */
    public function increaseStock($id, $quantity)
    {
        $sql = "UPDATE {$this->table} SET stock_quantity = stock_quantity + ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$quantity, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Toggle product featured status
     *
     * @param int $id
     * @return bool
     */
    public function toggleFeatured($id)
    {
        $sql = "UPDATE {$this->table} SET is_featured = NOT is_featured, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Toggle product status
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET status = CASE 
                    WHEN status = 'active' THEN 'inactive' 
                    ELSE 'active' 
                END, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Set product status explicitly
     *
     * @param int $id
     * @param string $status ('active'|'inactive')
     * @return bool
     */
    public function setStatus($id, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$status, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Set featured flag explicitly
     *
     * @param int $id
     * @param bool|int $isFeatured
     * @return bool
     */
    public function setFeatured($id, $isFeatured)
    {
        $sql = "UPDATE {$this->table} SET is_featured = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$isFeatured ? 1 : 0, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Delete product
     *
     * @param int $id
     * @return bool
     */
    public function deleteProduct($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Get product statistics
     *
     * @return array
     */
    public function getProductStats()
    {
        $cacheKey = $this->cachePrefix . 'stats';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT 
                        COUNT(*) as total_products,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_products,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_products,
                        SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_products,
                        SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                        SUM(CASE WHEN stock_quantity <= 10 THEN 1 ELSE 0 END) as low_stock,
                        AVG(price) as avg_price,
                        SUM(stock_quantity) as total_stock
                    FROM {$this->table}";
            return $this->db->query($sql)->single();
        }, $this->cacheTTL);
    }

    /**
     * Generate slug from name
     *
     * @param string $name
     * @return string
     */
    public function generateSlug($name)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return bool
     */
    private function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params)->single();
        return $result['count'] > 0;
    }

    /**
     * Check if SKU exists
     *
     * @param string $sku
     * @param int|null $excludeId
     * @return bool
     */
    public function skuExists($sku, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE sku = ?";
        $params = [$sku];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params)->single();
        return $result['count'] > 0;
    }

    /**
     * Get total product count
     *
     * @return int
     */
    public function getProductCount()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE status = 'active' 
                AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)";
        $result = $this->db->query($sql)->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get products with images for homepage
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProductsWithImages($limit = 12, $offset = 0)
    {
        $sql = "SELECT id, product_name, slug, short_description, price, sale_price, 
                       stock_quantity, category, image, is_featured, created_at
                FROM {$this->table} 
                WHERE status = 'active' 
                AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$limit, $offset])->all();
    }

    /**
     * Get popular products (based on sales count)
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPopularProducts($limit = 8, $offset = 0)
    {
        $sql = "SELECT id, product_name, slug, short_description, price, sale_price, 
                       stock_quantity, category, image, is_featured, total_sales, created_at
                FROM {$this->table} 
                WHERE status = 'active' 
                AND (approval_status = 'approved' OR approval_status IS NULL OR seller_id IS NULL OR seller_id = 0)
                ORDER BY total_sales DESC, created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$limit, $offset])->all();
    }

    /**
     * Find product by slug with images
     *
     * @param string $slug
     * @return array|false
     */
    public function findBySlugWithImages($slug)
    {
        $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                       stock_quantity, category, subtype, image, is_featured, status, created_at, updated_at,
                       is_digital, product_type_main, product_type, colors, size_available, weight, serving,
                       flavor, material, ingredients, optimal_weight, serving_size, capsule, cost_price, 
                       compare_price, commission_rate, meta_title, meta_description, tags, is_scheduled,
                       scheduled_date, scheduled_duration, scheduled_message, is_on_sale, sale_start_date,
                       sale_end_date, sale_discount_percent, seller_id
                FROM {$this->table} 
                WHERE slug = ? AND status = 'active'";
        
        return $this->db->query($sql, [$slug])->single();
    }

    /**
     * Find product by ID with images
     *
     * @param int $id
     * @return array|false
     */
    public function findWithImages($id)
    {
        $sql = "SELECT id, product_name, slug, description, short_description, price, sale_price, 
                       stock_quantity, category, subtype, image, is_featured, status, created_at, updated_at,
                       is_digital, product_type_main, product_type, colors, size_available, weight, serving,
                       flavor, material, ingredients, optimal_weight, serving_size, capsule, cost_price, 
                       compare_price, commission_rate, meta_title, meta_description, tags, is_scheduled,
                       scheduled_date, scheduled_duration, scheduled_message, is_on_sale, sale_start_date,
                       sale_end_date, sale_discount_percent, seller_id
                FROM {$this->table} 
                WHERE id = ?";
        
        return $this->db->query($sql, [$id])->single();
    }

    /**
     * Get product count by category
     *
     * @param string $category
     * @return int
     */
    public function getProductCountByCategory($category)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE category = ? AND status = 'active'";
        $result = $this->db->query($sql, [$category])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get review statistics for a product
     *
     * @param int $productId
     * @return array
     */
    public function getReviewStats($productId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews 
                WHERE product_id = ?";
        
        $result = $this->db->query($sql, [$productId])->single();
        
        if (!$result) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0
            ];
        }
        
        return [
            'total_reviews' => (int)$result['total_reviews'],
            'average_rating' => round((float)$result['average_rating'], 2),
            'five_star' => (int)$result['five_star'],
            'four_star' => (int)$result['four_star'],
            'three_star' => (int)$result['three_star'],
            'two_star' => (int)$result['two_star'],
            'one_star' => (int)$result['one_star']
        ];
    }
    
    /**
     * Get review stats for multiple products (batch loading)
     *
     * @param array $productIds
     * @return array
     */
    public function getReviewStatsBatch(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $sql = "SELECT 
                    product_id,
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating
                FROM reviews 
                WHERE product_id IN ($placeholders)
                GROUP BY product_id";
        
        $results = $this->db->query($sql, $productIds)->all();
        
        $stats = [];
        foreach ($productIds as $id) {
            $stats[$id] = [
                'total_reviews' => 0,
                'average_rating' => 0
            ];
        }
        
        foreach ($results as $row) {
            $stats[$row['product_id']] = [
                'total_reviews' => (int)$row['total_reviews'],
                'average_rating' => round((float)$row['average_rating'], 2)
            ];
        }
        
        return $stats;
        
        if (!$result) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0
            ];
        }
        
        return [
            'total_reviews' => (int)$result['total_reviews'],
            'average_rating' => round((float)$result['average_rating'], 1),
            'five_star' => (int)$result['five_star'],
            'four_star' => (int)$result['four_star'],
            'three_star' => (int)$result['three_star'],
            'two_star' => (int)$result['two_star'],
            'one_star' => (int)$result['one_star']
        ];
    }

    /**
     * Get product count by status
     *
     * @param string $status
     * @return int
     */
    public function getCountByStatus($status)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->query($sql, [$status])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get all active products for sitemap generation
     *
     * @return array
     */
    public function getAllActiveProducts()
    {
        $sql = "SELECT id, product_name, slug, updated_at FROM {$this->table} WHERE status = 'active' ORDER BY updated_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get all distinct active product categories for sitemap generation
     *
     * @return array
     */
    public function getAllCategories()
    {
        $sql = "SELECT DISTINCT category FROM {$this->table} 
                WHERE status = 'active' AND category IS NOT NULL AND TRIM(category) != ''
                ORDER BY category ASC";
        return $this->db->query($sql)->all();
    }

    /**
     * Invalidate cache entries
     *
     * @param int|null $id
     * @param array $slugs
     */
    private function invalidateCache($id = null, array $slugs = [])
    {
        if ($id) {
            $this->cache->delete($this->cachePrefix . 'id_' . $id);
            if (empty($slugs)) {
                $slugFromDb = $this->getSlugById($id);
                if ($slugFromDb) {
                    $slugs[] = $slugFromDb;
                }
            }
        }

        foreach (array_unique(array_filter($slugs)) as $slug) {
            $this->cache->delete($this->cachePrefix . 'slug_' . $slug);
        }
        
        // Invalidate list caches
        $this->cache->deletePattern($this->cachePrefix . 'category_*');
        $this->cache->deletePattern($this->cachePrefix . 'featured_*');
        $this->cache->deletePattern($this->cachePrefix . 'related_*');
        $this->cache->deletePattern($this->cachePrefix . 'sale_*');
        $this->cache->delete($this->cachePrefix . 'stats');
    }

    /**
     * Get slug for a product by id
     */
    private function getSlugById($id)
    {
        $result = $this->db->query("SELECT slug FROM {$this->table} WHERE id = ?", [$id])->single();
        return $result['slug'] ?? null;
    }

    /**
     * Get products by seller ID
     */
    public function getProductsBySellerId($sellerId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT p.*, 
                       (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
                FROM {$this->table} p 
                WHERE p.seller_id = ? 
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$sellerId, $limit, $offset])->all();
    }

    /**
     * Get product count by seller
     */
    public function getProductCountBySeller($sellerId)
    {
        $result = $this->db->query("SELECT COUNT(*) as count FROM {$this->table} WHERE seller_id = ?", [$sellerId])->single();
        return $result['count'] ?? 0;
    }
}