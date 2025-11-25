<?php
namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected $table = 'products';

    public function all()
    {
        $query = "SELECT DISTINCT category, COUNT(*) as product_count
                  FROM {$this->table}
                  WHERE category IS NOT NULL AND category != ''
                  GROUP BY category
                  ORDER BY category ASC";
        return $this->db->query($query)->all();
    }

    /**
     * Get active categories with dynamic images from database
     */
    public function getActiveCategories($limit = null)
    {
        $query = "SELECT DISTINCT 
                         p.category as name, 
                         p.category as slug,
                         COUNT(*) as product_count,
                         MIN(p.created_at) as created_at,
                         (SELECT pi.image_url 
                          FROM product_images pi 
                          JOIN products p2 ON pi.product_id = p2.id 
                          WHERE p2.category = p.category 
                          AND pi.is_primary = 1 
                          ORDER BY RAND() 
                          LIMIT 1) as image_url
                  FROM {$this->table} p
                  WHERE p.category IS NOT NULL AND p.category != ''
                  GROUP BY p.category
                  ORDER BY product_count DESC, p.category ASC";
        
        if ($limit) {
            $query .= " LIMIT :limit";
        }
        
        $params = [];
        if ($limit) {
            $params['limit'] = (int)$limit;
        }
        
        $categories = $this->db->query($query, $params)->all();
        
        // Add additional data and fallback images
        foreach ($categories as &$category) {
            // If no image found from database, use fallback
            if (empty($category['image_url'])) {
                $category['image_url'] = $this->getFallbackCategoryImage($category['name']);
            }
            
            $category['description'] = 'Shop ' . $category['name'] . ' products';
            $category['is_active'] = 1;
            $category['sort_order'] = 0;
        }
        
        return $categories;
    }

    /**
     * Get fallback category image if no database image found
     */
    private function getFallbackCategoryImage($categoryName)
    {
        $fallbackImages = [
            'Protein' => 'https://m.media-amazon.com/images/I/716ruiQM3mL._AC_SL1500_.jpg',
            'Creatine' => 'https://nutriride.com/cdn/shop/files/486.webp?v=1733311938&width=600',
            'Pre-Workout' => 'https://img.drz.lazcdn.com/g/kf/S4761ff3e570b47fa9165a119e37edc2dx.jpg_720x720q80.jpg',
            'Vitamins' => 'https://asitisnutrition.com/cdn/shop/products/ProductImage.jpg?v=1639026431&width=600',
            'Mass Gainer' => 'https://m.media-amazon.com/images/I/71wQzKQ2SQL._AC_SL1500_.jpg',
            'BCAA' => 'https://wellversed.in/cdn/shop/files/Packof2-MiamiThunder_Electrolytes_Listing_773x773.png?v=1730139354',
            'Fat Burner' => 'https://img10.hkrtcdn.com/39889/prd_3988809-HK-Vitals-Skin-Radiance-Collagen-Marine-Collagen-200-g-Orange_o.jpg',
            'Multivitamin' => 'https://asitisnutrition.com/cdn/shop/files/Mango_Delight.jpg?v=1749638404&width=600'
        ];
        
        return $fallbackImages[$categoryName] ?? 'https://m.media-amazon.com/images/I/716ruiQM3mL._AC_SL1500_.jpg';
    }

    /**
     * Get all categories - alias for compatibility
     */
    public function getAllCategories()
    {
        return $this->getActiveCategories();
    }

    /**
     * Get supplement subcategories dynamically with caching
     */
    public function getSupplementSubcategories()
    {
        // Check cache first (15 minutes cache)
        $cacheKey = 'supplement_subcategories';
        $cached = $this->getFromCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Get from CategoryHelper first
        $helperSubtypes = \App\Helpers\CategoryHelper::SUBTYPES['Supplements'] ?? [];
        
        // Get actual categories from database that match supplements
        $query = "SELECT DISTINCT category, COUNT(*) as product_count
                  FROM {$this->table}
                  WHERE category IS NOT NULL AND category != ''
                  AND (category LIKE '%Protein%' OR category LIKE '%Creatine%' OR category LIKE '%Pre%' OR 
                       category LIKE '%BCAA%' OR category LIKE '%Mass%' OR category LIKE '%Vitamin%' OR 
                       category LIKE '%Mineral%' OR category LIKE '%Fat%' OR category LIKE '%Amino%' OR 
                       category LIKE '%Post%')
                  GROUP BY category
                  ORDER BY product_count DESC, category ASC";
        
        $dbCategories = $this->db->query($query)->all();
        
        // Merge helper categories with database categories
        $subcategories = [];
        
        // Add helper categories first
        foreach ($helperSubtypes as $key => $value) {
            $subcategories[] = [
                'name' => $value,
                'slug' => $key,
                'url' => '/products/category/' . urlencode($key),
                'product_count' => 0
            ];
        }
        
        // Add database categories that aren't in helper
        foreach ($dbCategories as $dbCat) {
            $found = false;
            foreach ($subcategories as &$subcat) {
                if (strtolower($subcat['name']) === strtolower($dbCat['category']) || 
                    strtolower($subcat['slug']) === strtolower($dbCat['category'])) {
                    $subcat['product_count'] = $dbCat['product_count'];
                    $subcat['url'] = '/products/category/' . urlencode($dbCat['category']);
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $subcategories[] = [
                    'name' => $dbCat['category'],
                    'slug' => $dbCat['category'],
                    'url' => '/products/category/' . urlencode($dbCat['category']),
                    'product_count' => $dbCat['product_count']
                ];
            }
        }
        
        // Filter out categories with 0 products and limit to top 8
        $subcategories = array_filter($subcategories, function($cat) {
            return $cat['product_count'] > 0;
        });
        
        // Sort by product count and limit
        usort($subcategories, function($a, $b) {
            return $b['product_count'] - $a['product_count'];
        });
        
        $result = array_slice($subcategories, 0, 8);
        
        // Cache the result for 15 minutes
        $this->setCache($cacheKey, $result, 900);
        
        return $result;
    }
    
    /**
     * Simple file-based cache getter
     */
    private function getFromCache($key)
    {
        $cacheFile = sys_get_temp_dir() . '/nutrinexus_cache_' . md5($key) . '.json';
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && $data['expires'] > time()) {
                return $data['value'];
            }
            unlink($cacheFile); // Remove expired cache
        }
        return null;
    }
    
    /**
     * Simple file-based cache setter
     */
    private function setCache($key, $value, $ttl = 900)
    {
        $cacheFile = sys_get_temp_dir() . '/nutrinexus_cache_' . md5($key) . '.json';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        file_put_contents($cacheFile, json_encode($data));
    }

    // ... rest of your existing methods remain the same
}
