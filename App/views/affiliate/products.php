<?php ob_start(); ?>

<?php
include __DIR__ . '/../components/pricing-helper.php';
?>

<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-3 py-4">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-primary mb-2">Affiliate Products</h1>
            <p class="text-gray-600 text-sm">Earn commission on these products. Default commission: <?= number_format($defaultCommissionRate, 1) ?>%</p>
        </div>

        <!-- Filter Tags (Mobile-friendly like products/index.php) -->
        <?php
        $activeSort = $_GET['sort'] ?? '';
        $quickFilters = [
            ['label' => 'Highest Commission', 'sort' => 'commission-high'],
            ['label' => 'Low Price', 'sort' => 'price-low'],
            ['label' => 'High Price', 'sort' => 'price-high'],
            ['label' => 'Newest', 'sort' => 'newest'],
            ['label' => 'Popular', 'sort' => 'popular'],
        ];
        ?>
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <button onclick="openFilterModal()" class="bg-primary text-white px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap flex items-center gap-1">
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
                    : 'text-xs px-3.5 py-1.5 rounded-full border whitespace-nowrap transition-colors bg-gray-100 text-gray-700 border-transparent hover:bg-gray-200';
                ?>
                <button type="button"
                        class="<?= $buttonClasses ?>"
                        onclick="applyQuickFilter('<?= $filter['sort'] ?>')">
                    <?= htmlspecialchars($filter['label']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4-8-4V7m16 0L12 3 4 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-gray-700 mb-1">No Affiliate Products Available</h3>
                    <p class="text-sm text-gray-500">Check back soon for new affiliate products!</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Products Grid - Using shared product card component -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                <?php foreach ($products as $product): ?>
                    <?php
                    // Calculate commission rate
                    $commissionRate = isset($product['affiliate_commission']) && $product['affiliate_commission'] > 0 
                        ? (float)$product['affiliate_commission'] 
                        : $defaultCommissionRate;
                    
                    // Calculate final price for commission
                    $finalPrice = $product['final_price'] ?? ($product['sale_price'] > 0 && $product['sale_price'] < $product['price'] ? $product['sale_price'] : $product['price']);
                    $earnAmount = ($finalPrice * $commissionRate) / 100;
                    
                    // Setup card options - matching top-sale.php format
                    $cardOptions = [
                        'theme' => 'light',
                        'showCta' => false,
                        'topRightBadge' => [
                            'label' => number_format($commissionRate, 1) . '%',
                            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>'
                        ],
                        'bottomSection' => '<div class="flex items-center justify-between bg-primary/5 border border-primary/20 rounded-lg px-2 py-1.5 mt-2">
                            <span class="text-xs text-gray-600">Earn:</span>
                            <span class="text-xs font-semibold text-primary">Rs. ' . number_format($earnAmount, 0) . '</span>
                        </div>'
                    ];
                    ?>
                    <?php include __DIR__ . '/../home/sections/shared/product-card.php'; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center items-center gap-2 flex-wrap">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= \App\Core\View::url('affiliate/products') ?>?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                        $queryParams = array_merge($_GET, ['page' => $i]);
                    ?>
                        <a href="<?= \App\Core\View::url('affiliate/products') ?>?<?= http_build_query($queryParams) ?>" 
                           class="px-4 py-2 <?= $i === $currentPage ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-lg text-sm font-medium hover:bg-gray-50">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= \App\Core\View::url('affiliate/products') ?>?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" 
                           class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4 text-center text-sm text-gray-600">
                    Showing <?= (($currentPage - 1) * 20) + 1 ?> to <?= min($currentPage * 20, $totalProducts) ?> of <?= $totalProducts ?> products
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Filter Modal (Mobile-friendly) -->
<div id="filterModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen px-3 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeFilterModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-t-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Filter Products</h3>
                    <button onclick="closeFilterModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="filterForm" method="GET" action="<?= \App\Core\View::url('affiliate/products') ?>" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <div class="flex gap-2">
                            <input type="number" name="min_price" value="<?= $minPrice ?>" placeholder="Min" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <input type="number" name="max_price" value="<?= $maxPrice ?>" placeholder="Max" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="commission-high" <?= $sort === 'commission-high' ? 'selected' : '' ?>>Highest Commission</option>
                            <option value="price-low" <?= $sort === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price-high" <?= $sort === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2 pt-4">
                        <button type="button" onclick="resetFilters()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Reset
                        </button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg text-sm hover:bg-primary-dark">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
function openFilterModal() {
    document.getElementById('filterModal').classList.remove('hidden');
}

function closeFilterModal() {
    document.getElementById('filterModal').classList.add('hidden');
}

function applyQuickFilter(sort) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sort);
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

function resetFilters() {
    window.location.href = '<?= \App\Core\View::url("affiliate/products") ?>';
}

// Close modal on backdrop click
document.getElementById('filterModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeFilterModal();
    }
});

// Product card redirect function (if not already defined)
if (typeof redirectToProduct === 'undefined') {
    function redirectToProduct(url, adId) {
        if (adId && adId !== 'null') {
            // Track ad click if applicable
            if (typeof trackAdClick === 'function') {
                trackAdClick(adId);
            }
        }
        window.location.href = url;
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
