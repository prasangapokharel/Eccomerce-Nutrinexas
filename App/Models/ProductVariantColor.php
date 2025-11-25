<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

/**
 * Product Variant Model for Color/Size Variants
 * Uses the product_variants table created in migration
 */
class ProductVariantColor extends Model
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
     * Get all variants for a product
     *
     * @param int $productId
     * @param bool $activeOnly
     * @return array
     */
    public function getVariantsByProduct($productId, $activeOnly = true)
    {
        $cacheKey = "product_variants_{$productId}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $activeOnly) {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];
            
            if ($activeOnly) {
                $sql .= " AND status = 'active'";
            }
            
            $sql .= " ORDER BY is_default DESC, variant_name ASC";
            
            return $this->db->query($sql, $params)->all();
        }, 3600);
    }
    
    /**
     * Get color variants for a product
     *
     * @param int $productId
     * @return array
     */
    public function getColorVariants($productId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = ? AND variant_type = 'color' AND status = 'active'
                ORDER BY is_default DESC, variant_name ASC";
        return $this->db->query($sql, [$productId])->all();
    }
    
    /**
     * Get size variants for a product
     *
     * @param int $productId
     * @return array
     */
    public function getSizeVariants($productId)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = ? AND variant_type = 'size' AND status = 'active'
                ORDER BY variant_name ASC";
        return $this->db->query($sql, [$productId])->all();
    }
    
    /**
     * Get variant by ID
     *
     * @param int $variantId
     * @return array|false
     */
    public function getVariantById($variantId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->query($sql, [$variantId])->single();
    }
    
    /**
     * Create a variant
     *
     * @param array $data
     * @return int|false
     */
    public function createVariant($data)
    {
        $sql = "INSERT INTO {$this->table} (
                    product_id, variant_type, variant_name, variant_value,
                    price_adjustment, stock_quantity, sku, image,
                    is_default, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, [
            $data['product_id'],
            $data['variant_type'] ?? 'color',
            $data['variant_name'],
            $data['variant_value'] ?? '',
            $data['price_adjustment'] ?? 0.00,
            $data['stock_quantity'] ?? 0,
            $data['sku'] ?? null,
            $data['image'] ?? null,
            $data['is_default'] ?? 0,
            $data['status'] ?? 'active'
        ])->execute();
        
        if ($result) {
            $this->invalidateCache($data['product_id']);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update variant
     *
     * @param int $variantId
     * @param array $data
     * @return bool
     */
    public function updateVariant($variantId, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                    variant_type = ?, variant_name = ?, variant_value = ?,
                    price_adjustment = ?, stock_quantity = ?, sku = ?,
                    image = ?, is_default = ?, status = ?
                WHERE id = ?";
        
        $result = $this->db->query($sql, [
            $data['variant_type'] ?? 'color',
            $data['variant_name'],
            $data['variant_value'] ?? '',
            $data['price_adjustment'] ?? 0.00,
            $data['stock_quantity'] ?? 0,
            $data['sku'] ?? null,
            $data['image'] ?? null,
            $data['is_default'] ?? 0,
            $data['status'] ?? 'active',
            $variantId
        ])->execute();
        
        if ($result) {
            $variant = $this->getVariantById($variantId);
            if ($variant) {
                $this->invalidateCache($variant['product_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Delete variant
     *
     * @param int $variantId
     * @return bool
     */
    public function deleteVariant($variantId)
    {
        $variant = $this->getVariantById($variantId);
        if (!$variant) {
            return false;
        }
        
        $result = $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$variantId])->execute();
        
        if ($result) {
            $this->invalidateCache($variant['product_id']);
        }
        
        return $result;
    }
    
    /**
     * Set default variant
     *
     * @param int $productId
     * @param int $variantId
     * @return bool
     */
    public function setDefaultVariant($productId, $variantId)
    {
        // First, unset all defaults for this product
        $this->db->query("UPDATE {$this->table} SET is_default = 0 WHERE product_id = ?", [$productId])->execute();
        
        // Then set the new default
        $result = $this->db->query("UPDATE {$this->table} SET is_default = 1 WHERE id = ? AND product_id = ?", [$variantId, $productId])->execute();
        
        if ($result) {
            $this->invalidateCache($productId);
        }
        
        return $result;
    }
    
    /**
     * Invalidate cache for a product
     *
     * @param int $productId
     */
    private function invalidateCache($productId)
    {
        $this->cache->delete("product_variants_{$productId}_active");
        $this->cache->delete("product_variants_{$productId}_all");
    }
}

