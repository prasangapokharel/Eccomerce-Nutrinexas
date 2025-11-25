<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Cache;

class ProductVariant extends \App\Core\Model
{
    protected $table = 'product_variants';
    protected $primaryKey = 'id';
    
    private $cache;
    
    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }
    
    /**
     * Get all variants for a specific product
     */
    public function getVariantsByProduct($productId, $activeOnly = true)
    {
        $cacheKey = "product_variants_{$productId}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $activeOnly) {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY variant_name ASC";
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            return $stmt->all();
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get a specific variant by ID
     */
    public function getVariantById($variantId)
    {
        $cacheKey = "variant_{$variantId}";
        
        return $this->cache->remember($cacheKey, function() use ($variantId) {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1");
            $stmt->bind([$variantId]);
            return $stmt->single();
        }, 3600);
    }
    
    /**
     * Get variant by SKU
     */
    public function getVariantBySku($sku)
    {
        $cacheKey = "variant_sku_{$sku}";
        
        return $this->cache->remember($cacheKey, function() use ($sku) {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE sku = ? AND is_active = 1");
            $stmt->bind([$sku]);
            return $stmt->single();
        }, 3600);
    }
    
    /**
     * Get variants with specific attributes
     */
    public function getVariantsByAttributes($productId, $attributes = [])
    {
        $cacheKey = "product_variants_attrs_{$productId}_" . md5(serialize($attributes));
        
        return $this->cache->remember($cacheKey, function() use ($productId, $attributes) {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ? AND is_active = 1";
            $params = [$productId];
            
            if (!empty($attributes)) {
                foreach ($attributes as $key => $value) {
                    $sql .= " AND JSON_EXTRACT(attributes, '$.{$key}') = ?";
                    $params[] = $value;
                }
            }
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            return $stmt->all();
        }, 1800); // Cache for 30 minutes
    }
    
    /**
     * Get available attribute combinations for a product
     */
    public function getAvailableAttributeCombinations($productId)
    {
        $cacheKey = "product_attr_combinations_{$productId}";
        
        return $this->cache->remember($cacheKey, function() use ($productId) {
            $sql = "SELECT DISTINCT attributes FROM {$this->table} 
                    WHERE product_id = ? AND is_active = 1 AND stock_quantity > 0";
            
            $stmt = $this->db->query($sql);
            $stmt->bind([$productId]);
            $results = $stmt->all();
            
            $combinations = [];
            foreach ($results as $result) {
                if ($result['attributes']) {
                    $combinations[] = json_decode($result['attributes'], true);
                }
            }
            
            return $combinations;
        }, 1800);
    }
    
    /**
     * Create a new variant
     */
    public function createVariant($data)
    {
        $sql = "INSERT INTO {$this->table} (
            product_id, variant_name, sku, barcode, attributes, 
            price, sale_price, cost_price, stock_quantity, 
            min_stock_threshold, is_active, is_featured, 
            variant_images, primary_image
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->bind([
            $data['product_id'],
            $data['variant_name'],
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['attributes'] ? json_encode($data['attributes']) : null,
            $data['price'] ?? null,
            $data['sale_price'] ?? null,
            $data['cost_price'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['min_stock_threshold'] ?? 5,
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['variant_images'] ? json_encode($data['variant_images']) : null,
            $data['primary_image'] ?? null
        ])->execute();
        
        if ($result) {
            $this->invalidateProductVariantCaches($data['product_id']);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update a variant
     */
    public function updateVariant($variantId, $data)
    {
        $sql = "UPDATE {$this->table} SET 
            variant_name = ?, sku = ?, barcode = ?, attributes = ?, 
            price = ?, sale_price = ?, cost_price = ?, stock_quantity = ?, 
            min_stock_threshold = ?, is_active = ?, is_featured = ?, 
            variant_images = ?, primary_image = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->bind([
            $data['variant_name'],
            $data['sku'] ?? null,
            $data['barcode'] ?? null,
            $data['attributes'] ? json_encode($data['attributes']) : null,
            $data['price'] ?? null,
            $data['sale_price'] ?? null,
            $data['cost_price'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['min_stock_threshold'] ?? 5,
            $data['is_active'] ?? 1,
            $data['is_featured'] ?? 0,
            $data['variant_images'] ? json_encode($data['variant_images']) : null,
            $data['primary_image'] ?? null,
            $variantId
        ])->execute();
        
        if ($result) {
            // Get product_id for cache invalidation
            $variant = $this->getVariantById($variantId);
            if ($variant) {
                $this->invalidateProductVariantCaches($variant['product_id']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete a variant
     */
    public function deleteVariant($variantId)
    {
        $variant = $this->getVariantById($variantId);
        if (!$variant) {
            return false;
        }
        
        $stmt = $this->db->query("DELETE FROM {$this->table} WHERE id = ?");
        $result = $stmt->bind([$variantId])->execute();
        
        if ($result) {
            $this->invalidateProductVariantCaches($variant['product_id']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Update stock quantity for a variant
     */
    public function updateStockQuantity($variantId, $quantity)
    {
        $sql = "UPDATE {$this->table} SET stock_quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->query($sql);
        $result = $stmt->bind([$quantity, $variantId])->execute();
        
        if ($result) {
            $variant = $this->getVariantById($variantId);
            if ($variant) {
                $this->invalidateProductVariantCaches($variant['product_id']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Get low stock variants
     */
    public function getLowStockVariants($productId = null)
    {
        $sql = "SELECT v.*, p.product_name 
                FROM {$this->table} v 
                JOIN products p ON v.product_id = p.id 
                WHERE v.stock_quantity <= v.min_stock_threshold 
                AND v.is_active = 1";
        
        $params = [];
        if ($productId) {
            $sql .= " AND v.product_id = ?";
            $params[] = $productId;
        }
        
        $sql .= " ORDER BY v.stock_quantity ASC";
        
        $stmt = $this->db->query($sql);
        $stmt->bind($params);
        return $stmt->all();
    }
    
    /**
     * Invalidate cache for product variants
     */
    private function invalidateProductVariantCaches($productId)
    {
        $this->cache->deletePattern("product_variants_{$productId}_*");
        $this->cache->deletePattern("product_attr_combinations_{$productId}");
        $this->cache->deletePattern("variant_*");
    }
    
    /**
     * Get variant price (variant price or fallback to product price)
     */
    public function getVariantPrice($variantId, $productPrice = 0)
    {
        $variant = $this->getVariantById($variantId);
        if ($variant && $variant['price'] !== null) {
            return $variant['price'];
        }
        return $productPrice;
    }
    
    /**
     * Get variant sale price
     */
    public function getVariantSalePrice($variantId)
    {
        $variant = $this->getVariantById($variantId);
        return $variant ? $variant['sale_price'] : null;
    }
}
