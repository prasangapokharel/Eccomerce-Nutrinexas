<?php 
ob_start(); 
include __DIR__ . '/../components/pricing-helper.php';
$activeSort = $_GET['sort'] ?? '';
?>
<div class="container mx-auto px-4 py-8 md:py-12">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 border-b border-gray-200 pb-4">
        <h1 class="text-xl md:text-2xl font-bold text-primary mb-3 md:mb-0">Search Results: <?= htmlspecialchars($keyword) ?></h1>
        
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
    $banner = $bannerService->getSearchBanner(); // Tier 2 for search results top
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
                <i class="fas fa-search text-5xl text-gray-300"></i>
            </div>
            <h2 class="text-xl font-semibold mb-2">No products found</h2>
            <p class="text-gray-600 mb-6">We couldn't find any products matching your search.</p>
            <a href="<?= \App\Core\View::url('products') ?>" class="inline-block bg-primary text-white px-6 py-2 hover:bg-primary-dark transition-colors">
                Browse All Products
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
                    // Setup badge
                    $badge = null;
                    if (!empty($product['is_new'])) {
                        $badge = ['label' => 'New'];
                    } elseif (!empty($product['is_best_seller'])) {
                        $badge = ['label' => 'Best'];
                    }

                    // Setup card options
                    $cardOptions = [
                        'theme' => 'light',
                        'showCta' => false,
                        'cardClass' => 'w-full h-full',
                    ];

                    if ($badge) {
                        $cardOptions['topRightBadge'] = $badge;
                    }

                    // Include product card (Sponsored badge is now inside the card component)
                    include dirname(__DIR__) . '/home/sections/shared/product-card.php';
                    ?>
                </div>
                
                <?php 
                // Insert product grid banner ad after every 10 products
                if ($productIndex % 10 === 0 && $productIndex < count($products)): 
                    include dirname(__DIR__) . '/components/ProductGridBanner.php';
                endif; 
                
                // Insert search mid banner after 5 products (mid-section)
                if ($productIndex === 5 && $productIndex < count($products)): 
                    $slotKey = 'slot_search_mid';
                    include dirname(__DIR__) . '/components/MidBanner.php';
                endif; 
                ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="mt-8 border-t border-gray-200 pt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600">
                    <div class="flex items-center gap-4">
                        <span class="font-medium">
                            Showing <?= (($currentPage - 1) * 20 + 1) ?> to 
                            <?= min($currentPage * 20, $totalCount ?? count($products)) ?> of 
                            <?= $totalCount ?? count($products) ?> products
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= \App\Core\View::url('products/search?q=' . urlencode($keyword) . '&sort=' . urlencode($sort) . '&page=' . ($currentPage - 1)) ?>" 
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
                                <a href="<?= \App\Core\View::url('products/search?q=' . urlencode($keyword) . '&sort=' . urlencode($sort) . '&page=1') ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $currentPage): ?>
                                    <span class="px-3 py-2 border border-primary bg-primary text-white rounded-lg font-medium"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= \App\Core\View::url('products/search?q=' . urlencode($keyword) . '&sort=' . urlencode($sort) . '&page=' . $i) ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                                <a href="<?= \App\Core\View::url('products/search?q=' . urlencode($keyword) . '&sort=' . urlencode($sort) . '&page=' . $totalPages) ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= \App\Core\View::url('products/search?q=' . urlencode($keyword) . '&sort=' . urlencode($sort) . '&page=' . ($currentPage + 1)) ?>" 
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
    <?php endif; ?>
</div>

<!-- Search Bottom Banner (Tier 3) -->
<section class="mt-10">
    <?php 
    $slotKey = 'slot_search_bottom';
    include dirname(__DIR__) . '/components/MidBanner.php';
    ?>
</section>

<!-- Quick View removed for compact search results -->

<!-- Cart notifications removed on search page -->

<style>
/* Remove focus outline and any borders on click */
a:focus, button:focus {
  outline: none !important;
}
a:active, a:focus, button:active, button:focus {
  outline: none !important;
  border: none !important;
  -moz-outline-style: none !important;
}
/* Ensure consistent card heights */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
/* Single-line clamp for compact titles */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Compact filter chips */
.chip { 
  font-size: 12px; 
  padding: 6px 10px; 
  border: 1px solid #e5e7eb; 
  background: #fff; 
  border-radius: 9999px; 
  white-space: nowrap;
}
.chip-active {
  background-color: #0A3167;
  color: #fff;
  border-color: #0A3167;
}
.chip-secondary {
  font-size: 12px; 
  padding: 6px 10px; 
  border-radius: 9999px; 
  background: #f3f4f6; 
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

function applyQuickFilter(sortValue) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('sort', sortValue);
    window.location.href = currentUrl.toString();
}

const sortDropdown = document.getElementById('sort');
if (sortDropdown) {
    sortDropdown.addEventListener('change', function() {
        const sortValue = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('sort', sortValue);
        window.location.href = currentUrl.toString();
    });

    const urlParams = new URLSearchParams(window.location.search);
    const sortParam = urlParams.get('sort');
    if (sortParam) {
        sortDropdown.value = sortParam;
    }
}
</script>
<style>
/* Improve rendering performance on large lists */
.product-card {
    content-visibility: auto;
    contain-intrinsic-size: 340px 420px; /* approximate card size */
}
</style>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>