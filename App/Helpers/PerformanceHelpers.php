<?php

namespace App\Helpers;

use App\Core\Cache;
use App\Models\Product;
use App\Models\User;
use App\Models\Cart;

/**
 * Performance Helper Functions
 * 
 * Easy-to-use functions for accessing optimized models and cache management
 * These functions provide static caching for model instances and common operations
 */
class PerformanceHelpers
{
    // Static model instances for performance
    private static $productModel = null;
    private static $userModel = null;
    private static $cartModel = null;
    private static $cache = null;

    /**
     * Get Product Model instance (singleton pattern)
     */
    private static function getProductModel()
    {
        if (self::$productModel === null) {
            self::$productModel = new Product();
        }
        return self::$productModel;
    }

    /**
     * Get User Model instance (singleton pattern)
     */
    private static function getUserModel()
    {
        if (self::$userModel === null) {
            self::$userModel = new User();
        }
        return self::$userModel;
    }

    /**
     * Get Cart Model instance (singleton pattern)
     */
    private static function getCartModel()
    {
        if (self::$cartModel === null) {
            self::$cartModel = new Cart();
        }
        return self::$cartModel;
    }

    /**
     * Get Cache instance (singleton pattern)
     */
    private static function getCache()
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
        }
        return self::$cache;
    }

    // ========================================
    // PRODUCT HELPERS
    // ========================================

    /**
     * Get product by ID with caching
     */
    public static function getProduct($id)
    {
        return self::getProductModel()->findById($id);
    }

    /**
     * Get product by slug with caching
     */
    public static function getProductBySlug($slug)
    {
        return self::getProductModel()->findBySlug($slug);
    }

    /**
     * Get products by category with caching
     */
    public static function getProductsByCategory($category, $limit = 20, $offset = 0)
    {
        return self::getProductModel()->getProductsByCategory($category, $limit, $offset);
    }

    /**
     * Get featured products with caching
     */
    public static function getFeaturedProducts($limit = 8)
    {
        return self::getProductModel()->getFeaturedProducts($limit);
    }

    /**
     * Search products with caching
     */
    public static function searchProducts($query, $limit = 20, $offset = 0)
    {
        return self::getProductModel()->searchProducts($query, $limit, $offset);
    }

    /**
     * Get all products with pagination and caching
     */
    public static function getAllProducts($limit = 20, $offset = 0, $filters = [])
    {
        return self::getProductModel()->getAllProducts($limit, $offset, $filters);
    }

    /**
     * Get product count by category
     */
    public static function getProductCountByCategory($category)
    {
        return self::getProductModel()->getProductCountByCategory($category);
    }

    /**
     * Get total product count
     */
    public static function getTotalProducts()
    {
        return self::getProductModel()->getTotalProducts();
    }

    // ========================================
    // USER HELPERS
    // ========================================

    /**
     * Get user by ID with caching
     */
    public static function getUser($id)
    {
        return self::getUserModel()->findById($id);
    }

    /**
     * Get user by email with caching
     */
    public static function getUserByEmail($email)
    {
        return self::getUserModel()->findByEmail($email);
    }

    /**
     * Get user by username with caching
     */
    public static function getUserByUsername($username)
    {
        return self::getUserModel()->findByUsername($username);
    }

    /**
     * Get user by phone with caching
     */
    public static function getUserByPhone($phone)
    {
        return self::getUserModel()->findByPhone($phone);
    }

    /**
     * Get all users with pagination and caching
     */
    public static function getAllUsers($limit = 20, $offset = 0, $filters = [])
    {
        return self::getUserModel()->getAllUsers($limit, $offset, $filters);
    }

    /**
     * Get users by role with caching
     */
    public static function getUsersByRole($role, $limit = 100)
    {
        return self::getUserModel()->getUsersByRole($role, $limit);
    }

    /**
     * Get user count with filters
     */
    public static function getUserCount($filters = [])
    {
        return self::getUserModel()->getUserCount($filters);
    }

    // ========================================
    // CART HELPERS
    // ========================================

    /**
     * Get cart items with caching
     */
    public static function getCartItems()
    {
        return self::getCartModel()->getItems();
    }

    /**
     * Get cart item count with caching
     */
    public static function getCartCount()
    {
        return self::getCartModel()->getItemCount();
    }

    /**
     * Get cart total with caching
     */
    public static function getCartTotal()
    {
        return self::getCartModel()->getTotal();
    }

    /**
     * Get cart with product details
     */
    public static function getCartWithProducts()
    {
        return self::getCartModel()->getCartWithProducts(self::getProductModel());
    }

    /**
     * Get cart summary
     */
    public static function getCartSummary()
    {
        return self::getCartModel()->getSummary();
    }

    /**
     * Get item quantity in cart
     */
    public static function getCartItemQuantity($productId)
    {
        return self::getCartModel()->getItemQuantity($productId);
    }

    // ========================================
    // CACHE MANAGEMENT HELPERS
    // ========================================

    /**
     * Clear product cache
     */
    public static function clearProductCache($productId = null)
    {
        $cache = self::getCache();
        if ($productId) {
            $cache->delete('product_id_' . $productId);
            $cache->delete('product_slug_' . md5($productId));
        } else {
            $cache->deletePattern('product_*');
        }
    }

    /**
     * Clear user cache
     */
    public static function clearUserCache($userId = null)
    {
        $cache = self::getCache();
        if ($userId) {
            $cache->delete('user_id_' . $userId);
            $cache->delete('user_email_' . md5($userId));
            $cache->delete('user_username_' . md5($userId));
        } else {
            $cache->deletePattern('user_*');
        }
    }

    /**
     * Clear cart cache
     */
    public static function clearCartCache($userId = null)
    {
        $cache = self::getCache();
        $userKey = $userId ?: 'guest';
        $cache->deletePattern('cart_' . $userKey . '_*');
    }

    /**
     * Clear all cache
     */
    public static function clearAllCache()
    {
        $cache = self::getCache();
        $cache->clear();
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats()
    {
        return self::getCache()->getStats();
    }

    /**
     * Clean expired cache
     */
    public static function cleanExpiredCache()
    {
        return self::getCache()->clean();
    }

    /**
     * Check if cache key exists
     */
    public static function hasCache($key)
    {
        return self::getCache()->has($key);
    }

    /**
     * Get cache value
     */
    public static function getCacheValue($key)
    {
        return self::getCache()->get($key);
    }

    /**
     * Set cache value
     */
    public static function setCache($key, $value, $ttl = null)
    {
        return self::getCache()->set($key, $value, $ttl);
    }

    // ========================================
    // PERFORMANCE MONITORING HELPERS
    // ========================================

    /**
     * Measure execution time of a callback
     */
    public static function measureTime(callable $callback, $description = 'Operation')
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = ($endTime - $startTime) * 1000; // ms
        $memoryUsed = $endMemory - $startMemory; // bytes
        
        $metrics = [
            'description' => $description,
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 4),
            'result' => $result
        ];
        
        // Log performance metrics
        error_log("Performance: {$description} - {$executionTime}ms, Memory: {$memoryUsed} bytes");
        
        return $metrics;
    }

    /**
     * Get current memory usage
     */
    public static function getMemoryUsage()
    {
        return [
            'current' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'current_mb' => round(memory_get_usage() / 1024 / 1024, 4),
            'peak_mb' => round(memory_get_peak_usage() / 1024 / 1024, 4)
        ];
    }

    /**
     * Get system performance metrics
     */
    public static function getSystemMetrics()
    {
        return [
            'memory' => self::getMemoryUsage(),
            'cache' => self::getCacheStats(),
            'time' => microtime(true),
            'load_time' => defined('APP_START_TIME') ? (microtime(true) - APP_START_TIME) * 1000 : 0
        ];
    }

    // ========================================
    // UTILITY HELPERS
    // ========================================

    /**
     * Format bytes to human readable format
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format milliseconds to human readable format
     */
    public static function formatMilliseconds($ms, $precision = 2)
    {
        if ($ms < 1) {
            return round($ms * 1000, $precision) . ' μs';
        } elseif ($ms < 1000) {
            return round($ms, $precision) . ' ms';
        } else {
            return round($ms / 1000, $precision) . ' s';
        }
    }

    /**
     * Generate cache key from parameters
     */
    public static function generateCacheKey($prefix, $params = [])
    {
        $key = $prefix;
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        return $key;
    }

    /**
     * Check if system is in debug mode
     */
    public static function isDebugMode()
    {
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }

    /**
     * Log debug information
     */
    public static function debugLog($message, $data = null)
    {
        if (self::isDebugMode()) {
            $logData = [
                'message' => $message,
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s'),
                'memory' => self::getMemoryUsage()
            ];
            error_log('DEBUG: ' . json_encode($logData));
        }
    }

    // ========================================
    // BATCH OPERATION HELPERS
    // ========================================

    /**
     * Batch get multiple products
     */
    public static function batchGetProducts($productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        
        $products = [];
        foreach ($productIds as $id) {
            $products[$id] = self::getProduct($id);
        }
        
        return array_filter($products); // Remove false values
    }

    /**
     * Batch get multiple users
     */
    public static function batchGetUsers($userIds)
    {
        if (empty($userIds)) {
            return [];
        }
        
        $users = [];
        foreach ($userIds as $id) {
            $users[$id] = self::getUser($id);
        }
        
        return array_filter($users); // Remove false values
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public static function warmUpCache()
    {
        $cache = self::getCache();
        
        // Warm up featured products
        self::getFeaturedProducts(8);
        
        // Warm up user count
        self::getUserCount();
        
        // Warm up product count
        self::getTotalProducts();
        
        return true;
    }

    /**
     * Get cache hit rate statistics
     */
    public static function getCacheHitRate()
    {
        $stats = self::getCacheStats();
        $total = $stats['total_files'] ?? 0;
        $valid = $stats['valid_files'] ?? 0;
        
        if ($total === 0) {
            return 0;
        }
        
        return round(($valid / $total) * 100, 2);
    }
}

// ========================================
// GLOBAL HELPER FUNCTIONS
// ========================================

/**
 * Global helper functions for easy access
 * These can be used anywhere in the application
 */

if (!function_exists('getProduct')) {
    function getProduct($id) {
        return PerformanceHelpers::getProduct($id);
    }
}

if (!function_exists('getProductsByCategory')) {
    function getProductsByCategory($category, $limit = 20) {
        return PerformanceHelpers::getProductsByCategory($category, $limit);
    }
}

if (!function_exists('getUser')) {
    function getUser($id) {
        return PerformanceHelpers::getUser($id);
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount() {
        return PerformanceHelpers::getCartCount();
    }
}

if (!function_exists('getCartTotal')) {
    function getCartTotal() {
        return PerformanceHelpers::getCartTotal();
    }
}

if (!function_exists('clearCache')) {
    function clearCache($type = null) {
        switch ($type) {
            case 'product':
                return PerformanceHelpers::clearProductCache();
            case 'user':
                return PerformanceHelpers::clearUserCache();
            case 'cart':
                return PerformanceHelpers::clearCartCache();
            default:
                return PerformanceHelpers::clearAllCache();
        }
    }
}

if (!function_exists('measurePerformance')) {
    function measurePerformance(callable $callback, $description = 'Operation') {
        return PerformanceHelpers::measureTime($callback, $description);
    }
}
