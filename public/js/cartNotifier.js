/**
 * Cart Notifier System (Cookie-Based)
 * Manages cart count across all pages using cookies
 */
(function() {
    'use strict';

    const COOKIE_NAME = 'cart_count';
    const COOKIE_MAX_AGE = 86400 * 30; // 30 days
    const COOKIE_PATH = '/';

    /**
     * Get cart count from cookie
     */
    function getCartCount() {
        const cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].trim();
            if (cookie.indexOf(COOKIE_NAME + '=') === 0) {
                const count = parseInt(cookie.substring(COOKIE_NAME.length + 1), 10);
                return isNaN(count) ? 0 : Math.max(0, count);
            }
        }
        return 0;
    }

    /**
     * Set cart count in cookie
     */
    function setCartCount(count) {
        const value = Math.max(0, parseInt(count, 10) || 0);
        const expires = new Date();
        expires.setTime(expires.getTime() + (COOKIE_MAX_AGE * 1000));
        document.cookie = `${COOKIE_NAME}=${value}; expires=${expires.toUTCString()}; path=${COOKIE_PATH}`;
        updateCartBadges(value);
        return value;
    }

    /**
     * Increase cart count by 1
     */
    function increaseCartCount() {
        const current = getCartCount();
        return setCartCount(current + 1);
    }

    /**
     * Decrease cart count by 1 (never below 0)
     */
    function decreaseCartCount() {
        const current = getCartCount();
        return setCartCount(Math.max(0, current - 1));
    }

    /**
     * Update all cart count badges on the page
     */
    function updateCartBadges(count) {
        const badges = document.querySelectorAll('.cart-count');
        badges.forEach(badge => {
            badge.textContent = count || 0;
            if (count > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        });
    }

    /**
     * Initialize cart count from cookie or session
     */
    function initCartCount() {
        let count = getCartCount();
        
        // Try to get from server if cookie is 0 or missing
        if (count === 0 && typeof fetch !== 'undefined') {
            const baseUrl = window.location.origin;
            const cartCountUrl = baseUrl + '/cart/count';
            fetch(cartCountUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-cache'
            })
            .then(response => response.json())
            .then(data => {
                if (data && typeof data.count !== 'undefined') {
                    setCartCount(data.count);
                } else {
                    updateCartBadges(count);
                }
            })
            .catch(() => {
                // Silent fail, use cookie value
                updateCartBadges(count);
            });
        } else {
            updateCartBadges(count);
        }
    }

    // Expose API
    window.CartNotifier = {
        getCount: getCartCount,
        setCount: setCartCount,
        increase: increaseCartCount,
        decrease: decreaseCartCount,
        update: updateCartBadges,
        init: initCartCount
    };

    // Listen for cart events
    document.addEventListener('cart:added', function() {
        increaseCartCount();
    });

    document.addEventListener('cart:removed', function() {
        decreaseCartCount();
    });

    document.addEventListener('cart:updated', function(e) {
        if (e.detail && typeof e.detail.count !== 'undefined') {
            setCartCount(e.detail.count);
        }
    });

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCartCount);
    } else {
        initCartCount();
    }
})();

