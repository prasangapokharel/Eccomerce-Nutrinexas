<?php ob_start(); ?>

<?php
include __DIR__ . '/../components/pricing-helper.php';

// Helper function to clean and format product descriptions
function formatProductDescription($description, $maxLength = 120) {
    if (empty($description)) {
        return 'Premium Quality';
    }
    
    // Remove HTML tags but preserve line breaks and basic formatting
    $cleanDescription = strip_tags($description);
    
    // Remove extra whitespace and normalize
    $cleanDescription = preg_replace('/\s+/', ' ', trim($cleanDescription));
    
    // Truncate if too long
    if (strlen($cleanDescription) > $maxLength) {
        $cleanDescription = substr($cleanDescription, 0, $maxLength) . '...';
    }
    
    return $cleanDescription;
}

// Helper function to clean product names
function formatProductName($name) {
    if (empty($name)) {
        return 'Product Name';
    }
    
    // Remove HTML tags
    $cleanName = strip_tags($name);
    
    // Remove extra whitespace
    $cleanName = preg_replace('/\s+/', ' ', trim($cleanName));
    
    return $cleanName;
}
?>

<div class="bg-neutral-50 min-h-screen">
    <div class="container category-clean mx-auto px-3 py-4 sm:px-4 sm:py-8">
        
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="bg-success/10 border-l-4 border-success text-success px-3 py-2 rounded-lg mb-4 shadow-sm">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium text-xs"><?= $_SESSION['flash_message'] ?></span>
                </div>
            </div>
            <?php unset($_SESSION['flash_message']); ?>
        <?php endif; ?>

        <?php
        $activeSort = $_GET['sort'] ?? '';
        $quickFilters = [
            ['label' => 'Popular', 'sort' => 'popular'],
            ['label' => 'Low Price', 'sort' => 'price-low'],
            ['label' => 'Small Fishes', 'sort' => 'newest'],
            ['label' => 'Big', 'sort' => 'price-high'],
        ];
        ?>

        <!-- Filter Tags -->
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <button onclick="openFilterModal()" class="btn px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"></path>
                </svg>
                FILTER
            </button>
            <?php foreach ($quickFilters as $filter): ?>
                <?php 
                $isActive = $activeSort === $filter['sort'];
                $buttonClasses = $isActive 
                    ? 'text-xs px-3.5 py-1.5 rounded-full border whitespace-nowrap transition-colors bg-primary text-white border-primary' 
                    : 'text-xs px-3.5 py-1.5 rounded-full border whitespace-nowrap transition-colors bg-neutral-100 text-neutral-700 border-transparent hover:bg-neutral-200';
                ?>
                <button type="button"
                        class="<?= $buttonClasses ?>"
                        onclick="applyQuickFilter('<?= $filter['sort'] ?>')">
                    <?= htmlspecialchars($filter['label']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-neutral-100 p-8 text-center">
                <div class="w-14 h-14 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4-8-4V7m16 0L12 3 4 7"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-neutral-700 mb-1">No Products Available</h3>
                <p class="text-sm text-neutral-500">Check back soon for new arrivals!</p>
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
                    // Insert banner ad after every 10 products
                    if ($productIndex % 10 === 0 && $productIndex < count($products)): 
                        include dirname(__DIR__) . '/components/ProductGridBanner.php';
                    endif; 
                    ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="mt-8 border-t border-gray-200 pt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600">
                    <div class="flex items-center gap-4">
                        <span class="font-medium">
                            Showing <?= (($currentPage - 1) * 12 + 1) ?> to 
                            <?= min($currentPage * 12, $totalProducts ?? count($products)) ?> of 
                            <?= $totalProducts ?? count($products) ?> products
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= \App\Core\View::url('products?page=' . ($currentPage - 1) . (!empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '')) ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </span>
                        <?php endif; ?>
                        
                        <div class="flex items-center gap-1">
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="<?= \App\Core\View::url('products?page=1' . (!empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '')) ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $currentPage): ?>
                                    <span class="px-3 py-2 border border-primary bg-primary text-white rounded-lg font-medium"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= \App\Core\View::url('products?page=' . $i . (!empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '')) ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                                <a href="<?= \App\Core\View::url('products?page=' . $totalPages . (!empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '')) ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= \App\Core\View::url('products?page=' . ($currentPage + 1) . (!empty($_GET['sort']) ? '&sort=' . urlencode($_GET['sort']) : '')) ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- Filter Drawer -->
<div id="filterModal" class="fixed inset-0 hidden z-50">
    <div class="absolute inset-0 bg-black/40" data-filter-overlay="true"></div>
    <div id="filterDrawer" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl p-4 shadow-xl">
        <div class="w-12 h-1.5 bg-neutral-200 rounded-full mx-auto mb-3"></div>
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-base font-medium text-gray-900">Filter Products</h3>
            <button onclick="closeFilterModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="filterForm" class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Price Range</label>
                <div class="flex gap-2">
                    <input type="number" name="min_price" placeholder="Min" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                    <input type="number" name="max_price" placeholder="Max" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Sort By</label>
                <div class="relative">
                    <select name="sort" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-700 bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors appearance-none cursor-pointer pr-8">
                        <option value="newest" <?= $activeSort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="price_low" <?= $activeSort === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= $activeSort === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= $activeSort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                        <option value="popular" <?= $activeSort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Availability</label>
                <div class="space-y-1">
                    <label class="flex items-center">
                        <input type="checkbox" name="in_stock" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-xs text-gray-700">In Stock Only</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="low_stock" class="rounded border-gray-300 text-primary focus:ring-primary">
                        <span class="ml-2 text-xs text-gray-700">Low Stock</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-2 pt-3">
                <button type="button" onclick="resetFilters()" class="flex-1 px-3 py-2 border border-gray-300 rounded text-xs text-gray-700 hover:bg-gray-50">
                    Reset
                </button>
                <button type="submit" class="flex-1 px-3 py-2 bg-primary text-white rounded text-xs">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>


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

function applyQuickFilter(sortValue) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sortValue);
    window.location.href = currentUrl.toString();
}

const filterModal = document.getElementById('filterModal');
const filterDrawer = document.getElementById('filterDrawer');

function openFilterModal() {
    if (!filterModal) return;
    filterModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeFilterModal() {
    if (!filterModal) return;
    filterModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function resetFilters() {
    document.getElementById('filterForm').reset();
}

const filterForm = document.getElementById('filterForm');
if (filterForm) {
filterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams();
    
    // Add filter parameters
    if (formData.get('min_price')) params.set('min_price', formData.get('min_price'));
    if (formData.get('max_price')) params.set('max_price', formData.get('max_price'));
    if (formData.get('sort')) params.set('sort', formData.get('sort'));
    if (formData.get('in_stock')) params.set('in_stock', '1');
    if (formData.get('low_stock')) params.set('low_stock', '1');
    
    // Preserve existing page parameter if exists
    const currentPage = new URLSearchParams(window.location.search).get('page');
    if (currentPage) params.set('page', currentPage);
    
    window.location.href = '<?= \App\Core\View::url('products') ?>?' + params.toString();
});
}

if (filterModal) {
    filterModal.addEventListener('click', function(e) {
        if (e.target.hasAttribute('data-filter-overlay')) {
            closeFilterModal();
        }
    });
}

// Wishlist functions
if (typeof addToWishlist === 'undefined') {
    function addToWishlist(productId) {
        const button = document.querySelector('[data-product-id="' + productId + '"]');
        if (button) {
            button.classList.add('wishlist-active');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('far', 'text-gray-600');
                icon.classList.add('fas', 'text-red-500');
            }
            button.setAttribute('onclick', 'event.stopPropagation(); removeFromWishlist(' + productId + ')');
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
                if (button) {
                    button.classList.remove('wishlist-active');
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fas', 'text-red-500');
                        icon.classList.add('far', 'text-gray-600');
                    }
                    button.setAttribute('onclick', 'event.stopPropagation(); addToWishlist(' + productId + ')');
                }
                if (data.error === 'Please login to add items to your wishlist') {
                    window.location.href = '<?= \App\Core\View::url('auth/login') ?>';
                }
            }
        })
        .catch(() => {
            if (button) {
                button.classList.remove('wishlist-active');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('fas', 'text-red-500');
                    icon.classList.add('far', 'text-gray-600');
                }
                button.setAttribute('onclick', 'event.stopPropagation(); addToWishlist(' + productId + ')');
            }
        });
    }
}

if (typeof removeFromWishlist === 'undefined') {
    function removeFromWishlist(productId) {
        const button = document.querySelector('[data-product-id="' + productId + '"]');
        if (button) {
            button.classList.remove('wishlist-active');
            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.remove('fas', 'text-red-500');
                icon.classList.add('far', 'text-gray-600');
            }
            button.setAttribute('onclick', 'event.stopPropagation(); addToWishlist(' + productId + ')');
        }

        fetch('<?= ASSETS_URL ?>/wishlist/remove/' + productId, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                if (button) {
                    button.classList.add('wishlist-active');
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.classList.remove('far', 'text-gray-600');
                        icon.classList.add('fas', 'text-red-500');
                    }
                    button.setAttribute('onclick', 'event.stopPropagation(); removeFromWishlist(' + productId + ')');
                }
            }
        })
        .catch(() => {
            if (button) {
                button.classList.add('wishlist-active');
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('far', 'text-gray-600');
                    icon.classList.add('fas', 'text-red-500');
                }
                button.setAttribute('onclick', 'event.stopPropagation(); removeFromWishlist(' + productId + ')');
            }
        });
    }
}
</script>
<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

