/* Optimized JavaScript for NutriNexus */
/* This file contains optimized JavaScript for better performance */

// Performance optimizations
(function() {
    'use strict';
    
    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Throttle function for performance
    function throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    // Optimized scroll handler
    const optimizedScrollHandler = throttle(function() {
        // Handle scroll events efficiently
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add scroll-based optimizations here
        if (scrollTop > 100) {
            document.body.classList.add('scrolled');
        } else {
            document.body.classList.remove('scrolled');
        }
    }, 16); // ~60fps
    
    // Optimized resize handler
    const optimizedResizeHandler = debounce(function() {
        // Handle resize events efficiently
        const width = window.innerWidth;
        
        // Add resize-based optimizations here
        if (width < 768) {
            document.body.classList.add('mobile');
        } else {
            document.body.classList.remove('mobile');
        }
    }, 250);
    
    // Add event listeners
    window.addEventListener('scroll', optimizedScrollHandler, { passive: true });
    window.addEventListener('resize', optimizedResizeHandler, { passive: true });
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Optimized JavaScript loaded');
        });
    } else {
        console.log('Optimized JavaScript loaded');
    }
    
})();