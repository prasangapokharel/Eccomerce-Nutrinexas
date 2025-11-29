/**
 * Image Performance & Caching Optimization
 * Prevents reloading images that are already loaded
 */

(function() {
    'use strict';

    // Track loaded images to prevent reloading
    const loadedImages = new Set();
    const imageCache = new Map();

    /**
     * Mark image as loaded
     */
    function markImageLoaded(img) {
        if (img.src) {
            loadedImages.add(img.src);
            img.setAttribute('data-loaded', 'true');
            img.classList.add('loaded');
        }
    }

    /**
     * Check if image is already loaded
     */
    function isImageLoaded(src) {
        return loadedImages.has(src);
    }

    /**
     * Preload image if not already loaded
     */
    function preloadImage(src) {
        if (isImageLoaded(src) || imageCache.has(src)) {
            return Promise.resolve();
        }

        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                imageCache.set(src, img);
                loadedImages.add(src);
                resolve(img);
            };
            img.onerror = reject;
            img.src = src;
        });
    }

    /**
     * Optimize image loading
     */
    function optimizeImage(img) {
        if (!img || img.tagName !== 'IMG') return;

        const src = img.src || img.getAttribute('src');
        if (!src) return;

        // If image is already loaded, mark it
        if (img.complete && img.naturalHeight !== 0) {
            markImageLoaded(img);
            return;
        }

        // Check if image is in cache
        if (isImageLoaded(src)) {
            img.setAttribute('data-loaded', 'true');
            img.classList.add('loaded');
        }

        // Add load event listener
        img.addEventListener('load', function() {
            markImageLoaded(this);
        }, { once: true });

        // Add error handler
        img.addEventListener('error', function() {
            this.classList.add('error');
        }, { once: true });
    }

    /**
     * Initialize image optimization for all images
     */
    function initImageOptimization() {
        // Optimize existing images
        document.querySelectorAll('img').forEach(optimizeImage);

        // Watch for new images added dynamically
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'IMG') {
                            optimizeImage(node);
                        } else {
                            node.querySelectorAll('img').forEach(optimizeImage);
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Lazy load images using Intersection Observer
     */
    function initLazyLoading() {
        if (!('IntersectionObserver' in window)) {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('img[loading="lazy"]').forEach((img) => {
                if (img.getAttribute('data-src')) {
                    img.src = img.getAttribute('data-src');
                    img.removeAttribute('data-src');
                }
            });
            return;
        }

        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Load image if using data-src
                    if (img.getAttribute('data-src')) {
                        img.src = img.getAttribute('data-src');
                        img.removeAttribute('data-src');
                    }
                    
                    // Optimize the image
                    optimizeImage(img);
                    
                    // Stop observing this image
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px' // Start loading 50px before image enters viewport
        });

        // Observe all lazy images
        document.querySelectorAll('img[loading="lazy"]').forEach((img) => {
            imageObserver.observe(img);
        });
    }

    /**
     * Preload critical images (first slider image, above-fold images)
     */
    function preloadCriticalImages() {
        // First slider image
        const firstSliderImg = document.querySelector('.app-hero__card.is-active img, .app-hero__card:first-child img');
        if (firstSliderImg && firstSliderImg.src) {
            preloadImage(firstSliderImg.src);
        }

        // Above-fold images
        const aboveFoldImages = document.querySelectorAll('img[loading="eager"], img[fetchpriority="high"]');
        aboveFoldImages.forEach((img) => {
            if (img.src) {
                preloadImage(img.src);
            }
        });
    }

    /**
     * Clear image cache (useful for memory management)
     */
    function clearImageCache() {
        imageCache.clear();
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initImageOptimization();
            initLazyLoading();
            preloadCriticalImages();
        });
    } else {
        initImageOptimization();
        initLazyLoading();
        preloadCriticalImages();
    }

    // Clear cache on page unload to free memory
    window.addEventListener('beforeunload', clearImageCache);

    // Export for global use
    window.ImagePerformance = {
        preloadImage,
        isImageLoaded,
        clearImageCache,
        optimizeImage
    };
})();

