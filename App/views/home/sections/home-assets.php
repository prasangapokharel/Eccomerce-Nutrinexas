<?php
/**
 * Shared styles & scripts for the home page.
 *
 * Keeps the main view lean while still loading required behaviour.
 */
?>

<style>
html { scroll-behavior: smooth; }
@keyframes marquee { 
    0% { transform: translateX(0); } 
    100% { transform: translateX(-50%); } 
}
.animate-marquee { 
    animation: marquee 40s linear infinite; 
    will-change: transform;
}
.animate-marquee:hover { 
    animation-play-state: paused; 
}
.categories-marquee { 
    gap: 1.25rem; 
}
@media (min-width: 640px) {
    .categories-marquee { 
        gap: 1.5rem; 
    }
}
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.btn-wishlist { background: rgba(255,255,255,.9); border: 1px solid #e5e7eb; border-radius: 50%; width: 28px; height: 28px; min-width: 28px; min-height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; outline: none; }
.btn-wishlist:focus { outline: none; }
.btn-wishlist i { width: 14px; height: 14px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; }
.wishlist-active { background: rgba(239,68,68,.1)!important; border-color: #ef4444!important; }
.wishlist-active i { color: #ef4444!important; }
.product-card, .productclip { content-visibility: auto; contain-intrinsic-size: 220px 300px; cursor: pointer; }
@media (min-width: 768px) {
    .add-to-cart-form { display: flex; flex: 1; min-width: 0; }
    .add-to-cart-form button { width: 100%; }
    .btn-wishlist { flex-shrink: 0; }
}
</style>

<script>
// Lightweight Notyf bootstrap
var notyf = null;
if (typeof window !== 'undefined' && typeof window.Notyf !== 'undefined') {
    notyf = new Notyf({
        duration: 3000,
        position: { x: 'right', y: 'top' }
    });
}

// Ad tracking functions
if (typeof trackAdReach === 'undefined') {
    function trackAdReach(adId) {
        if (!adId) return;
        fetch('<?= \App\Core\View::url("ads/reach") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ads_id: adId, ip_address: '<?= $_SERVER["REMOTE_ADDR"] ?? "" ?>'}),
            keepalive: true
        }).catch(() => {});
    }
}

if (typeof trackAdClick === 'undefined') {
    function trackAdClick(adId) {
        if (!adId) return;
        fetch('<?= \App\Core\View::url("ads/click") ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ads_id: adId, ip_address: '<?= $_SERVER["REMOTE_ADDR"] ?? "" ?>'}),
            keepalive: true
        }).catch(() => {});
    }
}

function redirectToProduct(url, adId) {
    // Track ad click if product is sponsored
    if (adId) {
        trackAdClick(adId);
    }
    window.location.href = url;
}

function addToWishlist(productId) {
    const button = document.querySelector('[data-product-id="' + productId + '"]');
    if (button) {
        button.classList.add('wishlist-active');
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.remove('far', 'text-gray-600');
            icon.classList.add('fas', 'text-red-500');
        } else {
            button.innerHTML = '<i class="fas fa-heart text-sm w-3.5 h-3.5 flex items-center justify-center text-red-500"></i>';
        }
        button.setAttribute('onclick', 'event.stopPropagation(); removeFromWishlist(' + productId + ')');
        updateWishlistCache(productId, true);
    }

    fetch('<?= ASSETS_URL ?>/wishlist/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            revertWishlistState(productId);
            if (data.error === 'Please login to add items to your wishlist') {
                window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
            }
        }
    })
    .catch(() => revertWishlistState(productId));
}

function removeFromWishlist(productId) {
    const button = document.querySelector('[data-product-id="' + productId + '"]');
    if (button) {
        button.classList.remove('wishlist-active');
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.remove('fas', 'text-red-500');
            icon.classList.add('far', 'text-gray-600');
        } else {
            button.innerHTML = '<i class="far fa-heart text-sm w-3.5 h-3.5 flex items-center justify-center text-gray-600"></i>';
        }
        button.setAttribute('onclick', 'event.stopPropagation(); addToWishlist(' + productId + ')');
        updateWishlistCache(productId, false);
    }

    fetch('<?= ASSETS_URL ?>/wishlist/remove/' + productId, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            setTimeout(() => addToWishlist(productId), 2000);
        }
    })
    .catch(() => setTimeout(() => addToWishlist(productId), 2000));
}

function revertWishlistState(productId) {
    const button = document.querySelector('[data-product-id="' + productId + '"]');
    if (!button) return;
    button.classList.remove('wishlist-active');
    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.remove('fas', 'text-red-500');
        icon.classList.add('far', 'text-gray-600');
    } else {
        button.innerHTML = '<i class="far fa-heart text-sm w-3.5 h-3.5 flex items-center justify-center text-gray-600"></i>';
    }
    button.setAttribute('onclick', 'event.stopPropagation(); addToWishlist(' + productId + ')');
    updateWishlistCache(productId, false);
}

function updateWishlistCache(productId, add) {
    if (typeof window === 'undefined') return;
    const cache = JSON.parse(localStorage.getItem('nn_wishlist') || '[]');
    const exists = cache.includes(productId);

    if (add && !exists) cache.push(productId);
    if (!add && exists) cache.splice(cache.indexOf(productId), 1);

    localStorage.setItem('nn_wishlist', JSON.stringify(cache));
}

// Infinite Scroll Implementation (Flipkart-style lazy loading)
(function() {
    'use strict';
    
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    const container = document.getElementById('infinite-products-container');
    const loadingEl = document.getElementById('infinite-loading');
    const endEl = document.getElementById('infinite-end');
    
    if (!container) return;
    
    // Intersection Observer for lazy loading
    const observerOptions = {
        root: null,
        rootMargin: '200px',
        threshold: 0.1
    };
    
    const loadMoreProducts = function() {
        if (isLoading || !hasMore) return;
        
        isLoading = true;
        loadingEl.classList.remove('hidden');
        
        fetch('<?= \App\Core\View::url("products/infinite") ?>?page=' + currentPage, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.products && Array.isArray(data.products) && data.products.length > 0) {
                // Render products
                data.products.forEach(product => {
                    const productCard = createProductCard(product);
                    if (productCard) {
                        container.appendChild(productCard);
                    }
                });
                
                currentPage++;
                // No limit - continue loading until hasMore is false (Daraz-style)
                hasMore = data.hasMore !== false;
                
                if (!hasMore) {
                    endEl.classList.remove('hidden');
                    if (loadingEl) {
                        observer.unobserve(loadingEl);
                    }
                    if (endEl) {
                        observer.unobserve(endEl);
                    }
                } else {
                    // If we have more, keep end element hidden for continuous loading
                    endEl.classList.add('hidden');
                }
            } else {
                hasMore = false;
                endEl.classList.remove('hidden');
                if (loadingEl) {
                    observer.unobserve(loadingEl);
                }
                if (endEl) {
                    observer.unobserve(endEl);
                }
            }
        })
        .catch(error => {
            console.error('Error loading products:', error);
            hasMore = false;
        })
        .finally(() => {
            isLoading = false;
            loadingEl.classList.add('hidden');
        });
    };
    
    const createProductCard = function(product) {
        if (!product || !product.id) {
            return null;
        }
        
        const card = document.createElement('div');
        // Use primary theme like "Most Sold" section
        card.className = 'block bg-primary text-white border border-primary/20 rounded-2xl overflow-hidden relative product-card cursor-pointer';
        
        const productUrl = '<?= \App\Core\View::url("products/view") ?>/' + (product.slug || product.id);
        card.onclick = function(e) {
            if (e.target.closest('.btn-wishlist')) {
                return;
            }
            window.location.href = productUrl;
        };
        
        const discount = (product.sale_price > 0 && product.sale_price < product.price)
            ? Math.round(((product.price - product.sale_price) / product.price) * 100)
            : 0;
        const finalPrice = product.final_price || (product.sale_price > 0 && product.sale_price < product.price ? product.sale_price : product.price) || 0;
        const originalPrice = product.price || 0;
        const productName = (product.product_name || product.name || 'Product').substring(0, 50);
        const imageUrl = product.image_url || product.image || '<?= ASSETS_URL ?>/images/products/default.jpg';
        const avgRating = product.avg_rating || 0;
        const reviewCount = product.review_count || 0;
        const inWishlist = product.in_wishlist || false;
        
        card.innerHTML = `
            <div class="relative aspect-square bg-primary/30 p-2 rounded-2xl overflow-hidden">
                <img src="${imageUrl}" 
                     alt="${productName}" 
                     class="w-full h-full object-cover rounded-2xl"
                     loading="lazy"
                     onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                <div class="absolute top-1.5 left-1.5 z-10 flex flex-col gap-1">
                    ${discount > 0 ? `<span class="bg-white text-primary px-1.5 py-0.5 rounded-full text-xs font-semibold shadow-sm">-${discount}%</span>` : ''}
                </div>
                <button onclick="event.stopPropagation(); ${inWishlist ? 'removeFromWishlist' : 'addToWishlist'}(${product.id})" 
                        class="absolute top-2 right-2 btn-wishlist ${inWishlist ? 'wishlist-active' : ''} z-10 bg-white/80 hover:bg-white rounded-full w-7 h-7 flex items-center justify-center transition-colors"
                        data-product-id="${product.id}">
                    <i class="${inWishlist ? 'fas' : 'far'} fa-heart text-sm w-3.5 h-3.5 flex items-center justify-center ${inWishlist ? 'text-red-500' : 'text-gray-600'}"></i>
                </button>
            </div>
            <div class="p-3">
                <h4 class="text-sm font-semibold text-white mb-2 truncate">${productName}</h4>
                ${avgRating > 0 ? `
                <div class="flex items-center gap-1 mb-2">
                    <div class="flex items-center">
                        ${Array.from({length: 5}, (_, i) => {
                            const starClass = i + 1 <= avgRating ? 'text-yellow-400' : (i + 0.5 <= avgRating ? 'text-yellow-300' : 'text-white/30');
                            return `<i class="fas fa-star text-xs ${starClass}"></i>`;
                        }).join('')}
                    </div>
                    <span class="text-xs text-white/70">(${reviewCount})</span>
                </div>
                ` : ''}
                <div class="mb-2">
                    ${discount > 0 ? `
                        <div class="flex items-baseline gap-2 mb-1">
                            <span class="text-lg font-bold text-white">रु${finalPrice.toLocaleString('en-US')}</span>
                            <span class="text-xs text-white/70 line-through">रु${originalPrice.toLocaleString('en-US')}</span>
                        </div>
                        <div class="text-xs text-green-200 font-semibold">
                            Save रु${(originalPrice - finalPrice).toLocaleString('en-US')} (${discount}% OFF)
                        </div>
                    ` : `
                        <span class="text-lg font-bold text-white">रु${finalPrice.toLocaleString('en-US')}</span>
                    `}
                </div>
            </div>
        `;
        
        return card;
    };
    
    // Intersection Observer for scroll detection
    let observer = null;
    
    function initInfiniteScroll() {
        if (!container || !loadingEl) {
            console.warn('Infinite scroll elements not found');
            return;
        }
        
        // Create observer
        observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                // If loading element is visible, load more
                if (entry.target === loadingEl && entry.isIntersecting && hasMore && !isLoading) {
                    loadMoreProducts();
                }
                // If end element is visible and we still have more, load more (continuous loading)
                if (entry.target === endEl && entry.isIntersecting && hasMore && !isLoading) {
                    // Hide end message and load more
                    endEl.classList.add('hidden');
                    loadMoreProducts();
                }
            });
        }, observerOptions);
        
        // Load first page immediately
        setTimeout(function() {
            loadMoreProducts();
        }, 100);
        
        // Observe both loading and end elements for continuous loading
        observer.observe(loadingEl);
        if (endEl) {
            observer.observe(endEl);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initInfiniteScroll);
    } else {
        setTimeout(initInfiniteScroll, 50);
    }
})();
</script>

