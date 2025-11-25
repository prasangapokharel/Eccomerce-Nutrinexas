/**
 * Client-Side Cache Helper
 * 
 * Provides client-side caching functionality using localStorage and sessionStorage
 * for better performance and reduced server requests
 */

class CacheHelper {
    constructor() {
        this.prefix = 'nutrinexus_';
        this.defaultTTL = 30 * 60 * 1000; // 30 minutes in milliseconds
    }
    
    /**
     * Generate cache key
     */
    generateKey(key) {
        return this.prefix + key;
    }
    
    /**
     * Get cached data from localStorage
     */
    get(key, useSession = false) {
        const storage = useSession ? sessionStorage : localStorage;
        const cacheKey = this.generateKey(key);
        
        try {
            const cached = storage.getItem(cacheKey);
            if (!cached) return null;
            
            const data = JSON.parse(cached);
            
            // Check if data is expired
            if (data.expires && Date.now() > data.expires) {
                this.delete(key, useSession);
                return null;
            }
            
            return data.value;
        } catch (error) {
            console.error('Cache get error:', error);
            return null;
        }
    }
    
    /**
     * Set cached data in localStorage
     */
    set(key, value, ttl = null, useSession = false) {
        const storage = useSession ? sessionStorage : localStorage;
        const cacheKey = this.generateKey(key);
        const expires = Date.now() + (ttl || this.defaultTTL);
        
        try {
            const data = {
                value: value,
                expires: expires,
                timestamp: Date.now()
            };
            
            storage.setItem(cacheKey, JSON.stringify(data));
            return true;
        } catch (error) {
            console.error('Cache set error:', error);
            return false;
        }
    }
    
    /**
     * Delete cached data
     */
    delete(key, useSession = false) {
        const storage = useSession ? sessionStorage : localStorage;
        const cacheKey = this.generateKey(key);
        
        try {
            storage.removeItem(cacheKey);
            return true;
        } catch (error) {
            console.error('Cache delete error:', error);
            return false;
        }
    }
    
    /**
     * Clear all cache
     */
    clear(useSession = false) {
        const storage = useSession ? sessionStorage : localStorage;
        
        try {
            const keys = Object.keys(storage);
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    storage.removeItem(key);
                }
            });
            return true;
        } catch (error) {
            console.error('Cache clear error:', error);
            return false;
        }
    }
    
    /**
     * Cache product data
     */
    cacheProduct(productId, productData, ttl = null) {
        const key = `product_${productId}`;
        const productTTL = ttl || 60 * 60 * 1000; // 1 hour for products
        return this.set(key, productData, productTTL);
    }
    
    /**
     * Get cached product data
     */
    getCachedProduct(productId) {
        const key = `product_${productId}`;
        return this.get(key);
    }
    
    /**
     * Cache product list
     */
    cacheProductList(category, page, sort, products, ttl = null) {
        const key = `products_${category}_${page}_${sort}`;
        const listTTL = ttl || 15 * 60 * 1000; // 15 minutes for product lists
        return this.set(key, products, listTTL);
    }
    
    /**
     * Get cached product list
     */
    getCachedProductList(category, page, sort) {
        const key = `products_${category}_${page}_${sort}`;
        return this.get(key);
    }
    
    /**
     * Cache search results
     */
    cacheSearchResults(query, results, ttl = null) {
        const key = `search_${btoa(query)}`;
        const searchTTL = ttl || 10 * 60 * 1000; // 10 minutes for search results
        return this.set(key, results, searchTTL);
    }
    
    /**
     * Get cached search results
     */
    getCachedSearchResults(query) {
        const key = `search_${btoa(query)}`;
        return this.get(key);
    }
    
    /**
     * Cache user preferences
     */
    cacheUserPreferences(preferences, ttl = null) {
        const key = 'user_preferences';
        const prefsTTL = ttl || 24 * 60 * 60 * 1000; // 24 hours for user preferences
        return this.set(key, preferences, prefsTTL);
    }
    
    /**
     * Get cached user preferences
     */
    getCachedUserPreferences() {
        const key = 'user_preferences';
        return this.get(key);
    }
    
    /**
     * Cache cart data
     */
    cacheCart(cartData, ttl = null) {
        const key = 'cart_data';
        const cartTTL = ttl || 5 * 60 * 1000; // 5 minutes for cart data
        return this.set(key, cartData, cartTTL, true); // Use session storage for cart
    }
    
    /**
     * Get cached cart data
     */
    getCachedCart() {
        const key = 'cart_data';
        return this.get(key, true); // Use session storage for cart
    }
    
    /**
     * Optimize cache by removing expired entries
     */
    optimize() {
        const storages = [localStorage, sessionStorage];
        let optimized = 0;
        
        storages.forEach(storage => {
            const keys = Object.keys(storage);
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    try {
                        const cached = storage.getItem(key);
                        if (cached) {
                            const data = JSON.parse(cached);
                            if (data.expires && Date.now() > data.expires) {
                                storage.removeItem(key);
                                optimized++;
                            }
                        }
                    } catch (error) {
                        // Remove corrupted cache entries
                        storage.removeItem(key);
                        optimized++;
                    }
                }
            });
        });
        
        return optimized;
    }
    
    /**
     * Get cache statistics
     */
    getStats() {
        const stats = {
            localStorage: { count: 0, size: 0 },
            sessionStorage: { count: 0, size: 0 }
        };
        
        [localStorage, sessionStorage].forEach((storage, index) => {
            const storageName = index === 0 ? 'localStorage' : 'sessionStorage';
            const keys = Object.keys(storage);
            
            keys.forEach(key => {
                if (key.startsWith(this.prefix)) {
                    stats[storageName].count++;
                    stats[storageName].size += storage.getItem(key).length;
                }
            });
        });
        
        return stats;
    }
}

// Create global instance
window.cacheHelper = new CacheHelper();

// Auto-optimize cache on page load
document.addEventListener('DOMContentLoaded', function() {
    const optimized = window.cacheHelper.optimize();
    if (optimized > 0) {
        console.log(`Cache optimized: ${optimized} expired entries removed`);
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CacheHelper;
}
