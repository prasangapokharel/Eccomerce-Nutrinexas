<?php 
ob_start(); 
use App\Helpers\CurrencyHelper;
include __DIR__ . '/../components/pricing-helper.php';
?>
<div class="container category-clean mx-auto px-3 py-4 sm:px-4 sm:py-8 md:py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 border-b border-gray-200 pb-4">
        <h1 class="text-xl md:text-2xl font-bold text-primary mb-3 md:mb-0"><?= htmlspecialchars($category) ?> Products</h1>
        
        <div class="flex items-center">
            <label for="sort" class="mr-2 text-sm text-gray-600">Sort:</label>
            <div class="relative">
                <select id="sort" class="appearance-none border border-gray-300 px-3 py-2 pr-8 bg-white text-sm rounded-lg focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary" onchange="sortProducts(this.value)">
                    <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="price-low" <?= ($_GET['sort'] ?? '') === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price-high" <?= ($_GET['sort'] ?? '') === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="popular" <?= ($_GET['sort'] ?? '') === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Banner Ad - Tier 1 (Above Product Grid) -->
    <?php
    use App\Services\BannerAdDisplayService;
    use App\Helpers\AdTrackingHelper;
    
    $bannerService = new BannerAdDisplayService();
    $banner = $bannerService->getCategoryBanner(); // Tier 1 for category pages
    ?>
    
    <?php if (!empty($banner)): ?>
        <div class="mb-6">
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden relative">
                <!-- Ads Label - Top Right -->
                <div class="absolute top-2 right-2 z-20 bg-black/80 text-white px-2.5 py-1 rounded-md text-xs font-bold backdrop-blur-sm shadow-lg" style="letter-spacing: 0.5px;">
                    Ads
                </div>
                
                <a href="<?= htmlspecialchars($banner['banner_link'] ?? '#') ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   onclick="<?= AdTrackingHelper::getClickTrackingJS($banner['id']) ?>"
                   class="block relative">
                    <img src="<?= htmlspecialchars($banner['banner_image']) ?>" 
                         alt="Advertisement" 
                         class="w-full h-auto object-cover rounded-2xl"
                         style="max-width: 100%; height: auto; display: block;"
                         loading="lazy"
                         onload="<?= AdTrackingHelper::getReachTrackingJS($banner['id']) ?>"
                         onerror="this.style.display='none'">
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($products)): ?>
        <div class="bg-white border border-gray-100 shadow-sm p-8 text-center">
            <div class="text-gray-500 mb-4">
                <i class="fas fa-th-large text-5xl text-gray-300"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">Browse Categories</h2>
            <p class="text-gray-600 mb-6">Select a category to explore products.</p>
            <a href="<?= \App\Core\View::url('products/categories') ?>" class="btn-primary">
                View Categories
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3">
            <?php 
            $productIndex = 0;
            foreach ($products as $product): 
                $productIndex++;
            ?>
                <div class="w-full">
                    <?php
                    $badge = null;
                    if (!empty($product['is_new'])) {
                        $badge = ['label' => 'New'];
                    } elseif (!empty($product['is_best_seller'])) {
                        $badge = ['label' => 'Best'];
                    }

                    $cardOptions = [
                        'theme' => 'light',
                        'showCta' => false,
                        'cardClass' => 'w-full h-full',
                    ];

                    if ($badge) {
                        $cardOptions['topRightBadge'] = $badge;
                    }

                    include dirname(__DIR__) . '/home/sections/shared/product-card.php';
                    ?>
                </div>
                
                <?php 
                // Insert product grid banner ad after every 10 products
                if ($productIndex % 10 === 0 && $productIndex < count($products)): 
                    include dirname(__DIR__) . '/components/ProductGridBanner.php';
                endif; 
                
                // Insert category mid banner after 5 products (mid-section)
                if ($productIndex === 5 && $productIndex < count($products)): 
                    $slotKey = 'slot_category_mid';
                    include dirname(__DIR__) . '/components/MidBanner.php';
                endif; 
                ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="inline-flex shadow-sm">
                    <?php if (isset($currentPage) && $currentPage > 1): ?>
                        <a href="<?= \App\Core\View::url('products/category/' . urlencode($category) . '?page=' . ($currentPage - 1)) ?>"
                           class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-1"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Show limited page numbers with ellipsis
                    $startPage = max(1, ($currentPage ?? 1) - 2);
                    $endPage = min($totalPages, ($currentPage ?? 1) + 2);
                    
                    if ($startPage > 1) {
                        echo '<a href="' . \App\Core\View::url('products/category/' . urlencode($category) . '?page=1') . '"
                                class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                1
                            </a>';
                        if ($startPage > 2) {
                            echo '<span class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="<?= \App\Core\View::url('products/category/' . urlencode($category) . '?page=' . $i) ?>"
                           class="px-4 py-2 border border-gray-300 <?= $i === ($currentPage ?? 1) ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium">
                            <?= $i ?>
                        </a>
                    <?php
                    endfor;
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                        }
                        echo '<a href="' . \App\Core\View::url('products/category/' . urlencode($category) . '?page=' . $totalPages) . '"
                                class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                ' . $totalPages . '
                            </a>';
                    }
                    ?>
                    
                    <?php if (isset($currentPage) && $currentPage < $totalPages): ?>
                        <a href="<?= \App\Core\View::url('products/category/' . urlencode($category) . '?page=' . ($currentPage + 1)) ?>"
                           class="px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next <i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Quick View Modal -->
<div id="quickViewModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75" id="quickViewBackdrop"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="absolute top-0 right-0 pt-4 pr-4">
                <button type="button" class="text-gray-400 hover:text-gray-500" id="closeQuickViewBtn">
                    <span class="sr-only">Close</span>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="p-6 flex items-center justify-center bg-gray-50">
                    <img src="<?= ASSETS_URL ?>/placeholder.svg" alt="Product" id="quickViewImage" class="max-h-96 object-contain">
                </div>
                
                <div class="p-6">
                    <div class="text-sm text-accent font-medium mb-1" id="quickViewCategory"></div>
                    <h3 class="text-xl font-bold text-primary mb-2" id="quickViewName"></h3>
                    
                    <div class="mb-4">
                        <span class="text-2xl font-bold text-primary" id="quickViewPrice"></span>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-gray-600" id="quickViewDescription"></p>
                    </div>
                    
                    <div class="mb-6" id="quickViewStockContainer">
                        <span class="text-green-600 font-medium" id="quickViewStock"></span>
                    </div>
                    
                    <form action="<?= ASSETS_URL ?>/cart/add" method="post" id="quickViewForm" class="space-y-4">
                        <input type="hidden" name="product_id" id="quickViewProductId" value="">
                        
                        <div>
                            <label for="quickViewQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <div class="flex w-full max-w-[180px] h-10 border border-gray-300">
                                <button type="button" class="w-10 flex items-center justify-center bg-gray-100 text-gray-600" id="quickViewDecrement">
                                    <i class="fas fa-minus text-xs"></i>
                                </button>
                                <input type="number" name="quantity" id="quickViewQuantity" value="1" min="1"
                                       class="flex-1 h-full text-center border-0 focus:ring-0" readonly>
                                <button type="button" class="w-10 flex items-center justify-center bg-gray-100 text-gray-600" id="quickViewIncrement">
                                    <i class="fas fa-plus text-xs"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 py-2 bg-primary  text-white font-medium hover:bg-primary-dark transition-colors" id="quickViewAddToCart">
                                Add to Cart
                            </button>
                            
                            <a href="" id="quickViewDetailsLink" class="py-2 px-4 border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                View Details
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Notification -->
<div id="addToCartNotification" class="fixed top-4 right-4 z-50 bg-white shadow-md p-3 max-w-xs w-full transform translate-y-[-150%] opacity-0 transition-all duration-300">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-check-circle text-green-500"></i>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm font-medium text-gray-900">Added to cart</p>
        </div>
        <button type="button" class="ml-auto text-gray-400 hover:text-gray-500" onclick="hideNotification()">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<style>
/* Remove focus outline and any borders on click */
a:focus, button:focus {
    outline: none !important;
}

a:active, a:focus, button:active, button:focus {
    outline: none !important;
  outline-style: none !important;
    border: none !important;
    -moz-outline-style: none !important;
}

/* Ensure consistent card heights */
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
  line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.aspect-square {
    aspect-ratio: 1 / 1;
}
</style>

<script>
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

if (typeof redirectToProduct === 'undefined') {
    function redirectToProduct(url, adId) {
        // Track ad click if product is sponsored
        if (adId) {
            trackAdClick(adId);
        }
        window.location.href = url;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Quick View Functionality
    const quickViewBtns = document.querySelectorAll('.quick-view-btn');
    const quickViewModal = document.getElementById('quickViewModal');
    const quickViewBackdrop = document.getElementById('quickViewBackdrop');
    const closeQuickViewBtn = document.getElementById('closeQuickViewBtn');
    
    // Quick View Elements
    const quickViewImage = document.getElementById('quickViewImage');
    const quickViewCategory = document.getElementById('quickViewCategory');
    const quickViewName = document.getElementById('quickViewName');
    const quickViewPrice = document.getElementById('quickViewPrice');
    const quickViewDescription = document.getElementById('quickViewDescription');
    const quickViewStock = document.getElementById('quickViewStock');
    const quickViewStockContainer = document.getElementById('quickViewStockContainer');
    const quickViewProductId = document.getElementById('quickViewProductId');
    const quickViewQuantity = document.getElementById('quickViewQuantity');
    const quickViewAddToCart = document.getElementById('quickViewAddToCart');
    const quickViewDetailsLink = document.getElementById('quickViewDetailsLink');
    const quickViewDecrement = document.getElementById('quickViewDecrement');
    const quickViewIncrement = document.getElementById('quickViewIncrement');
    const quickViewForm = document.getElementById('quickViewForm');
    
    // Open Quick View Modal
    quickViewBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const productPrice = this.dataset.productPrice;
            const productImage = this.dataset.productImage;
            const productDescription = this.dataset.productDescription;
            const productCategory = this.dataset.productCategory;
            const productStock = parseInt(this.dataset.productStock);
            
            // Set Quick View content
            quickViewImage.src = productImage;
            quickViewImage.alt = productName;
            quickViewCategory.textContent = productCategory;
            quickViewName.textContent = productName;
                            quickViewPrice.textContent = `<?= CurrencyHelper::getSymbol() ?>${productPrice}`;
            quickViewDescription.textContent = productDescription;
            quickViewProductId.value = productId;
            quickViewDetailsLink.href = `<?= \App\Core\View::url('products/view/') ?>${productId}`;
            
            // Set stock status
            if (productStock > 0) {
                quickViewStock.textContent = 'In Stock';
                quickViewStock.classList.remove('text-red-600');
                quickViewStock.classList.add('text-green-600');
                quickViewAddToCart.disabled = false;
                quickViewAddToCart.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
                quickViewAddToCart.classList.add('bg-primary', 'text-white');
                
                // Set max quantity
                quickViewQuantity.max = productStock;
                quickViewQuantity.value = 1;
            } else {
                quickViewStock.textContent = 'Out of Stock';
                quickViewStock.classList.remove('text-green-600');
                quickViewStock.classList.add('text-red-600');
                quickViewAddToCart.disabled = true;
                quickViewAddToCart.classList.remove('bg-primary', 'text-white');
                quickViewAddToCart.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            }
            
            // Show modal
            quickViewModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        });
    });
    
    // Close Quick View Modal
    function closeQuickView() {
        quickViewModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    
    if (closeQuickViewBtn) {
        closeQuickViewBtn.addEventListener('click', closeQuickView);
    }
    
    if (quickViewBackdrop) {
        quickViewBackdrop.addEventListener('click', closeQuickView);
    }
    
    // Quantity controls for Quick View
    if (quickViewDecrement) {
        quickViewDecrement.addEventListener('click', function() {
            const currentValue = parseInt(quickViewQuantity.value);
            if (currentValue > 1) {
                quickViewQuantity.value = currentValue - 1;
            }
        });
    }
    
    if (quickViewIncrement) {
        quickViewIncrement.addEventListener('click', function() {
            const currentValue = parseInt(quickViewQuantity.value);
            const maxValue = parseInt(quickViewQuantity.getAttribute('max'));
            if (currentValue < maxValue) {
                quickViewQuantity.value = currentValue + 1;
            }
        });
    }
    
    // Handle Add to Cart forms via AJAX
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification();
                    
                    // Update cart count if you have a cart counter element
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement && data.cart_count) {
                        cartCountElement.textContent = data.cart_count;
                    }
                } else {
                    showNotification(data.error || 'An error occurred while adding the product to cart.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    // Handle Quick View form submission
    if (quickViewForm) {
        quickViewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(quickViewForm);
            
            fetch(quickViewForm.action, {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeQuickView();
                    showNotification();
                    
                    // Update cart count if you have a cart counter element
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement && data.cart_count) {
                        cartCountElement.textContent = data.cart_count;
                    }
                } else {
                    showNotification(data.error || 'An error occurred while adding the product to cart.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    }
    
    // Sort functionality
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const sortValue = this.value;
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('sort', sortValue);
            window.location.href = currentUrl.toString();
        });
        
        // Set the sort dropdown to the current sort value from URL
        const urlParams = new URLSearchParams(window.location.search);
        const sortParam = urlParams.get('sort');
        if (sortParam) {
            sortSelect.value = sortParam;
        }
    }
});

// Wishlist cache management functions
function updateWishlistCache(productId, isInWishlist) {
    try {
        let wishlistCache = JSON.parse(localStorage.getItem('wishlist_cache') || '{}');
        wishlistCache[productId] = isInWishlist;
        localStorage.setItem('wishlist_cache', JSON.stringify(wishlistCache));
    } catch (error) {
        console.error('Error updating wishlist cache:', error);
    }
}

function getWishlistCache() {
    try {
        return JSON.parse(localStorage.getItem('wishlist_cache') || '{}');
    } catch (error) {
        console.error('Error reading wishlist cache:', error);
        return {};
    }
}

function initializeWishlistFromCache() {
    const wishlistCache = getWishlistCache();
    const wishlistButtons = document.querySelectorAll('button[onclick*="addToWishlist"], button[onclick*="removeFromWishlist"]');
    
    wishlistButtons.forEach(button => {
        const onclick = button.getAttribute('onclick');
        const productIdMatch = onclick.match(/(\d+)/);
        if (productIdMatch) {
            const productId = productIdMatch[1];
            const isInWishlist = wishlistCache[productId] || false;
            
            if (isInWishlist) {
                button.classList.remove('text-gray-400', 'hover:text-red-500');
                button.classList.add('text-red-500', 'hover:text-red-700');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                button.setAttribute('onclick', 'removeFromWishlist(' + productId + ')');
            } else {
                button.classList.add('text-gray-400', 'hover:text-red-500');
                button.classList.remove('text-red-500', 'hover:text-red-700');
                button.innerHTML = '<i class="far fa-heart"></i>';
                button.setAttribute('onclick', 'addToWishlist(' + productId + ')');
            }
        }
    });
}

// Initialize wishlist state from cache on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeWishlistFromCache();
});

function addToWishlist(productId) {
    // Instant visual feedback
    var button = document.querySelector(`button[onclick*="${productId}"]`);
    if (button) {
        button.classList.remove('text-gray-400', 'hover:text-red-500');
        button.classList.add('text-red-500', 'hover:text-red-700');
        button.innerHTML = '<i class="fas fa-heart"></i>';
        button.setAttribute('onclick', 'removeFromWishlist(' + productId + ')');
        
        // Update localStorage cache immediately
        updateWishlistCache(productId, true);
    }
    
    fetch('<?= ASSETS_URL ?>/wishlist/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success - keep the visual state and update cache
            updateWishlistCache(productId, true);
        } else {
            // Revert on failure
            if (button) {
                button.classList.add('text-gray-400', 'hover:text-red-500');
                button.classList.remove('text-red-500', 'hover:text-red-700');
                button.innerHTML = '<i class="far fa-heart"></i>';
                button.setAttribute('onclick', 'addToWishlist(' + productId + ')');
                updateWishlistCache(productId, false);
            }
            if (data.error === 'Please login to add items to your wishlist') {
                window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert on error
        if (button) {
            button.classList.add('text-gray-400', 'hover:text-red-500');
            button.classList.remove('text-red-500', 'hover:text-red-700');
            button.innerHTML = '<i class="far fa-heart"></i>';
            button.setAttribute('onclick', 'addToWishlist(' + productId + ')');
            updateWishlistCache(productId, false);
        }
    });
}

function removeFromWishlist(productId) {
    // Instant visual feedback
    var button = document.querySelector(`button[onclick*="${productId}"]`);
    if (button) {
        button.classList.add('text-gray-400', 'hover:text-red-500');
        button.classList.remove('text-red-500', 'hover:text-red-700');
        button.innerHTML = '<i class="far fa-heart"></i>';
        button.setAttribute('onclick', 'addToWishlist(' + productId + ')');
        
        // Update localStorage cache immediately
        updateWishlistCache(productId, false);
    }
    
    fetch('<?= ASSETS_URL ?>/wishlist/remove/' + productId, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            // Success - keep the visual state and update cache
            updateWishlistCache(productId, false);
        } else if (data && data.error) {
            // Revert on error
            if (button) {
                button.classList.remove('text-gray-400', 'hover:text-red-500');
                button.classList.add('text-red-500', 'hover:text-red-700');
                button.innerHTML = '<i class="fas fa-heart"></i>';
                button.setAttribute('onclick', 'removeFromWishlist(' + productId + ')');
                updateWishlistCache(productId, true);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        // Revert on error
        if (button) {
            button.classList.remove('text-gray-400', 'hover:text-red-500');
            button.classList.add('text-red-500', 'hover:text-red-700');
            button.innerHTML = '<i class="fas fa-heart"></i>';
            button.setAttribute('onclick', 'removeFromWishlist(' + productId + ')');
            updateWishlistCache(productId, true);
        }
    });
}

function showNotification() {
    const notification = document.getElementById('addToCartNotification');
    if (notification) {
        notification.classList.remove('translate-y-[-150%]', 'opacity-0');
        notification.classList.add('translate-y-0', 'opacity-100');
        
        // Auto hide after 3 seconds
        setTimeout(hideNotification, 3000);
    }
}

function hideNotification() {
    const notification = document.getElementById('addToCartNotification');
    if (notification) {
        notification.classList.remove('translate-y-0', 'opacity-100');
        notification.classList.add('translate-y-[-150%]', 'opacity-0');
    }
}

// Enhanced notification function
function showNotification(message, type = 'success') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-3 rounded-lg shadow-lg max-w-xs transform translate-x-full transition-transform duration-300 ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            </div>
            <div class="ml-2 flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button type="button" class="ml-2 text-gray-400 hover:text-gray-600" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
        notification.classList.add('translate-x-0');
    }, 100);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 300);
    }, 3000);
}

// Sort Products Function
function sortProducts(sortValue) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('sort', sortValue);
    window.location.href = currentUrl.toString();
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
