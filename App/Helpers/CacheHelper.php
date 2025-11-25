<?php

namespace App\Helpers;

use App\Core\Cache;

/**
* Cache Helper
 * 
 * Provides enhanced caching functionality with cookies support
 * and automatic cache invalidation for better performance
*/
class CacheHelper
{
    private static $instance = null;
    private $cache;
    private $cookiePrefix = 'nutrinexus_';
    private $defaultTTL = 3600; // 1 hour
    
    public function __construct()
    {
        $this->cache = new Cache();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generate cache key
     */
    public function generateKey($prefix, $params = [])
    {
        $key = $prefix;
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        return $key;
    }
    
    /**
     * Get cached data with fallback to cookies
     */
    public function get($key, $useCookies = false)
    {
        // Try cache first
        $cached = $this->cache->get($key);
        if ($cached !== false) {
            return $cached;
        }
        
        // Fallback to cookies if enabled
        if ($useCookies && isset($_COOKIE[$this->cookiePrefix . $key])) {
            $cookieData = $_COOKIE[$this->cookiePrefix . $key];
            $data = json_decode($cookieData, true);
            
            // Check if cookie data is still valid (not expired)
            if ($data && isset($data['expires']) && $data['expires'] > time()) {
                // Store in cache for faster access
                $this->cache->set($key, $data['value'], $this->defaultTTL);
                return $data['value'];
            }
        }
        
        return false;
    }
    
    /**
     * Set cached data with optional cookie storage
     */
    public function set($key, $value, $ttl = null, $useCookies = false)
    {
        $ttl = $ttl ?? $this->defaultTTL;
        
        // Store in cache
        $result = $this->cache->set($key, $value, $ttl);
        
        // Store in cookies if enabled
        if ($useCookies) {
            $cookieData = [
                'value' => $value,
                'expires' => time() + $ttl
            ];
            
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie(
                $this->cookiePrefix . $key,
                json_encode($cookieData),
                [
                    'expires' => time() + $ttl,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $secure,
                    'httponly' => false, // Allow JavaScript access for client-side caching
                    'samesite' => 'Lax'
                ]
            );
        }
        
        return $result;
    }
    
    /**
     * Delete cached data and cookies
     */
    public function delete($key)
    {
        // Delete from cache
        $this->cache->delete($key);
        
        // Delete from cookies
        if (isset($_COOKIE[$this->cookiePrefix . $key])) {
            setcookie($this->cookiePrefix . $key, '', time() - 3600, '/');
        }
    }
    
    /**
     * Clear all cache and cookies
     */
    public function clearAll()
    {
        // Clear cache
        $this->cache->clear();
        
        // Clear all our cookies
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, $this->cookiePrefix) === 0) {
                setcookie($name, '', time() - 3600, '/');
            }
        }
    }
    
    /**
     * Cache product data with enhanced features
     */
    public function cacheProduct($productId, $productData, $ttl = null)
    {
        $key = "product_{$productId}";
        $ttl = $ttl ?? 7200; // 2 hours for products
        
        return $this->set($key, $productData, $ttl, true);
    }
    
    /**
     * Get cached product data
     */
    public function getCachedProduct($productId)
    {
        $key = "product_{$productId}";
        return $this->get($key, true);
    }
    
    /**
     * Cache product list with pagination
     */
    public function cacheProductList($category, $page, $sort, $products, $ttl = null)
    {
        $key = "products_{$category}_{$page}_{$sort}";
        $ttl = $ttl ?? 1800; // 30 minutes for product lists
        
        return $this->set($key, $products, $ttl, true);
    }
    
    /**
     * Get cached product list
     */
    public function getCachedProductList($category, $page, $sort)
    {
        $key = "products_{$category}_{$page}_{$sort}";
        return $this->get($key, true);
    }
    
    /**
     * Cache user preferences
     */
    public function cacheUserPreferences($userId, $preferences, $ttl = null)
    {
        $key = "user_prefs_{$userId}";
        $ttl = $ttl ?? 86400; // 24 hours for user preferences
        
        return $this->set($key, $preferences, $ttl, true);
    }
    
    /**
     * Get cached user preferences
     */
    public function getCachedUserPreferences($userId)
    {
        $key = "user_prefs_{$userId}";
        return $this->get($key, true);
    }
    
    /**
     * Cache search results
     */
    public function cacheSearchResults($query, $results, $ttl = null)
    {
        $key = "search_" . md5($query);
        $ttl = $ttl ?? 900; // 15 minutes for search results
        
        return $this->set($key, $results, $ttl, true);
    }
    
    /**
     * Get cached search results
     */
    public function getCachedSearchResults($query)
    {
        $key = "search_" . md5($query);
        return $this->get($key, true);
    }
    
    /**
     * Invalidate product-related cache
     */
    public function invalidateProductCache($productId = null)
    {
        if ($productId) {
            // Clear specific product cache
            $this->delete("product_{$productId}");
        }
        
        // Clear product list caches
        $this->delete('products_list');
        $this->delete('featured_products');
        $this->delete('products_by_category');
        
        // Clear search caches
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, $this->cookiePrefix . 'search_') === 0) {
                setcookie($name, '', time() - 3600, '/');
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        $stats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'cookie_count' => 0,
            'total_size' => 0
        ];
        
        // Count our cookies
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, $this->cookiePrefix) === 0) {
                $stats['cookie_count']++;
                $stats['total_size'] += strlen($value);
            }
        }
        
        return $stats;
    }
    
    /**
     * Optimize cache by removing expired entries
     */
    public function optimizeCache()
    {
        $optimized = 0;
        
        foreach ($_COOKIE as $name => $value) {
            if (strpos($name, $this->cookiePrefix) === 0) {
                $data = json_decode($value, true);
                
                // Remove expired cookies
                if ($data && isset($data['expires']) && $data['expires'] <= time()) {
                    setcookie($name, '', time() - 3600, '/');
                    $optimized++;
                }
            }
        }
        
        return $optimized;
    }
}