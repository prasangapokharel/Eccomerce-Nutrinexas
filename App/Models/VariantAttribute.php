<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Cache;

class VariantAttribute extends \App\Core\Model
{
    protected $table = 'variant_attributes';
    protected $primaryKey = 'id';
    
    private $cache;
    
    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }
    
    /**
     * Get all attributes for a product
     */
    public function getAttributesByProduct($productId, $activeOnly = true)
    {
        $cacheKey = "product_attributes_{$productId}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $activeOnly) {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY attribute_type ASC, sort_order ASC, attribute_name ASC";
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            return $stmt->all();
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Get attributes grouped by type for a product
     */
    public function getAttributesGroupedByType($productId, $activeOnly = true)
    {
        $cacheKey = "product_attributes_grouped_{$productId}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $activeOnly) {
            $attributes = $this->getAttributesByProduct($productId, $activeOnly);
            
            $grouped = [];
            foreach ($attributes as $attr) {
                $type = $attr['attribute_type'];
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [];
                }
                $grouped[$type][] = $attr;
            }
            
            return $grouped;
        }, 3600);
    }
    
    /**
     * Get specific attribute type options for a product
     */
    public function getAttributeOptions($productId, $attributeType, $activeOnly = true)
    {
        $cacheKey = "product_attr_options_{$productId}_{$attributeType}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $attributeType, $activeOnly) {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE product_id = ? AND attribute_type = ?";
            $params = [$productId, $attributeType];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY sort_order ASC, attribute_name ASC";
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            return $stmt->all();
        }, 3600);
    }
    
    /**
     * Get all available attribute types for a product
     */
    public function getAttributeTypes($productId, $activeOnly = true)
    {
        $cacheKey = "product_attr_types_{$productId}_" . ($activeOnly ? 'active' : 'all');
        
        return $this->cache->remember($cacheKey, function() use ($productId, $activeOnly) {
            $sql = "SELECT DISTINCT attribute_type FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY attribute_type ASC";
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            $results = $stmt->all();
            
            return array_column($results, 'attribute_type');
        }, 3600);
    }
    
    /**
     * Create a new attribute
     */
    public function createAttribute($data)
    {
        $sql = "INSERT INTO {$this->table} (
            product_id, attribute_type, attribute_name, attribute_value, 
            sort_order, is_active
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->bind([
            $data['product_id'],
            $data['attribute_type'],
            $data['attribute_name'],
            $data['attribute_value'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1
        ])->execute();
        
        if ($result) {
            $this->invalidateProductAttributeCaches($data['product_id']);
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Update an attribute
     */
    public function updateAttribute($attributeId, $data)
    {
        $sql = "UPDATE {$this->table} SET 
            attribute_type = ?, attribute_name = ?, attribute_value = ?, 
            sort_order = ?, is_active = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $this->db->query($sql);
        $result = $stmt->bind([
            $data['attribute_type'],
            $data['attribute_name'],
            $data['attribute_value'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $attributeId
        ])->execute();
        
        if ($result) {
            // Get product_id for cache invalidation
            $attribute = $this->getAttributeById($attributeId);
            if ($attribute) {
                $this->invalidateProductAttributeCaches($attribute['product_id']);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete an attribute
     */
    public function deleteAttribute($attributeId)
    {
        $attribute = $this->getAttributeById($attributeId);
        if (!$attribute) {
            return false;
        }
        
        $stmt = $this->db->query("DELETE FROM {$this->table} WHERE id = ?");
        $result = $stmt->bind([$attributeId])->execute();
        
        if ($result) {
            $this->invalidateProductAttributeCaches($attribute['product_id']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Get attribute by ID
     */
    public function getAttributeById($attributeId)
    {
        $cacheKey = "attribute_{$attributeId}";
        
        return $this->cache->remember($cacheKey, function() use ($attributeId) {
            $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = ?");
            $stmt->bind([$attributeId]);
            return $stmt->single();
        }, 3600);
    }
    
    /**
     * Bulk create attributes for a product
     */
    public function bulkCreateAttributes($productId, $attributes)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($attributes as $attr) {
                $attr['product_id'] = $productId;
                $this->createAttribute($attr);
            }
            
            $this->db->commit();
            $this->invalidateProductAttributeCaches($productId);
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
    
    /**
     * Get attribute combinations for variant generation
     */
    public function getAttributeCombinations($productId)
    {
        $cacheKey = "product_attr_combinations_{$productId}";
        
        return $this->cache->remember($cacheKey, function() use ($productId) {
            $grouped = $this->getAttributesGroupedByType($productId);
            
            if (empty($grouped)) {
                return [];
            }
            
            // Generate all possible combinations
            $combinations = [];
            $this->generateCombinations($grouped, $combinations);
            
            return $combinations;
        }, 1800); // Cache for 30 minutes
    }
    
    /**
     * Generate all possible attribute combinations
     */
    private function generateCombinations($grouped, &$combinations, $current = [], $types = null, $index = 0)
    {
        if ($types === null) {
            $types = array_keys($grouped);
        }
        
        if ($index >= count($types)) {
            $combinations[] = $current;
            return;
        }
        
        $type = $types[$index];
        foreach ($grouped[$type] as $attr) {
            $current[$type] = $attr['attribute_name'];
            $this->generateCombinations($grouped, $combinations, $current, $types, $index + 1);
        }
    }
    
    /**
     * Check if attribute combination exists
     */
    public function attributeCombinationExists($productId, $attributes)
    {
        $cacheKey = "product_attr_exists_{$productId}_" . md5(serialize($attributes));
        
        return $this->cache->remember($cacheKey, function() use ($productId, $attributes) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ?";
            $params = [$productId];
            
            foreach ($attributes as $type => $value) {
                $sql .= " AND attribute_type = ? AND attribute_name = ?";
                $params[] = $type;
                $params[] = $value;
            }
            
            $stmt = $this->db->query($sql);
            $stmt->bind($params);
            $result = $stmt->single();
            
            return $result['count'] > 0;
        }, 1800);
    }
    
    /**
     * Invalidate cache for product attributes
     */
    private function invalidateProductAttributeCaches($productId)
    {
        $this->cache->deletePattern("product_attributes_{$productId}_*");
        $this->cache->deletePattern("product_attr_types_{$productId}_*");
        $this->cache->deletePattern("product_attr_options_{$productId}_*");
        $this->cache->deletePattern("product_attr_combinations_{$productId}");
        $this->cache->deletePattern("product_attr_exists_{$productId}_*");
        $this->cache->deletePattern("attribute_*");
    }
    
    /**
     * Get attribute display name (human readable)
     */
    public function getAttributeDisplayName($attributeType)
    {
        $displayNames = [
            'flavor' => 'Flavor',
            'size' => 'Size',
            'weight' => 'Weight',
            'color' => 'Color',
            'material' => 'Material',
            'serving' => 'Serving Size',
            'capsule' => 'Form',
            'strength' => 'Strength',
            'type' => 'Type'
        ];
        
        return $displayNames[$attributeType] ?? ucfirst($attributeType);
    }
}
