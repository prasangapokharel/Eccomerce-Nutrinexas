<?php ob_start(); ?>

<?php
// Pricing helper function (same as home page)
$pricingHelper = function($product) {
    $price = (float)($product['price'] ?? 0);
    $salePrice = !empty($product['sale_price']) ? (float)$product['sale_price'] : null;
    
    $hasSale = $salePrice !== null && $salePrice < $price && $salePrice > 0;
    $currentPrice = $hasSale ? $salePrice : $price;
    $originalPrice = $price;
    $discountPercent = $hasSale ? round((($price - $salePrice) / $price) * 100) : 0;
    
    return [
        'hasSale' => $hasSale,
        'current' => $currentPrice,
        'original' => $originalPrice,
        'discountPercent' => $discountPercent
    ];
};
?>

<div class="min-h-screen bg-gray-50">
    <!-- Cover Banner -->
    <div class="relative w-full h-64 md:h-80 bg-gradient-to-r from-primary to-primary-dark overflow-hidden rounded-2xl mx-4 mt-4 mb-6">
        <?php if (!empty($seller['cover_banner_url'])): ?>
            <img src="<?= htmlspecialchars($seller['cover_banner_url']) ?>" 
                 alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                 class="absolute inset-0 w-full h-full object-cover rounded-2xl"
                 onerror="this.style.display='none';">
        <?php endif; ?>
        
        <!-- Seller Info Overlay -->
        <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8 bg-gradient-to-t from-black/60 via-black/40 to-transparent">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-start md:items-end gap-4">
                <!-- Logo/Avatar -->
                <div class="relative">
                    <div class="w-24 h-24 md:w-32 md:h-32 rounded-full border-4 border-white bg-white shadow-lg overflow-hidden">
                        <?php 
                        $defaultLogo = \App\Core\View::asset('images/graphics/store.png');
                        $logoUrl = !empty($seller['logo_url']) ? $seller['logo_url'] : $defaultLogo;
                        ?>
                        <img src="<?= htmlspecialchars($logoUrl) ?>" 
                             alt="<?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>"
                             class="w-full h-full object-cover"
                             onerror="this.src='<?= htmlspecialchars($defaultLogo) ?>'; this.onerror=null;">
                    </div>
                </div>
                
                <!-- Company Info -->
                <div class="flex-1 text-white drop-shadow-lg">
                    <h1 class="text-2xl md:text-4xl font-bold mb-2 drop-shadow-md">
                        <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                    </h1>
                    <?php if (!empty($seller['description'])): ?>
                        <p class="text-white text-sm md:text-base mb-3 line-clamp-2 drop-shadow-md">
                            <?= htmlspecialchars($seller['description']) ?>
                        </p>
                    <?php endif; ?>
                    
                    <!-- Social Media Links -->
                    <?php if (!empty($socialMedia)): ?>
                        <div class="flex items-center gap-3">
                            <?php if (!empty($socialMedia['facebook'])): ?>
                                <a href="<?= htmlspecialchars($socialMedia['facebook']) ?>" target="_blank" 
                                   class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors" aria-label="Facebook">
                                    <i class="fab fa-facebook-f text-sm"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socialMedia['instagram'])): ?>
                                <a href="<?= htmlspecialchars($socialMedia['instagram']) ?>" target="_blank"
                                   class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors" aria-label="Instagram">
                                    <i class="fab fa-instagram text-sm"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socialMedia['twitter'])): ?>
                                <a href="<?= htmlspecialchars($socialMedia['twitter']) ?>" target="_blank"
                                   class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors" aria-label="Twitter">
                                    <i class="fab fa-twitter text-sm"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socialMedia['linkedin'])): ?>
                                <a href="<?= htmlspecialchars($socialMedia['linkedin']) ?>" target="_blank"
                                   class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors" aria-label="LinkedIn">
                                    <i class="fab fa-linkedin-in text-sm"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($socialMedia['youtube'])): ?>
                                <a href="<?= htmlspecialchars($socialMedia['youtube']) ?>" target="_blank"
                                   class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center transition-colors" aria-label="YouTube">
                                    <i class="fab fa-youtube text-sm"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Search Bar -->
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6">
            <form method="GET" action="" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Search products in this store..."
                           class="input native-input"
                           style="padding-left: 2.5rem;">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                </div>
                <button type="submit" 
                        class="btn btn-primary">
                    <i class="fas fa-search mr-2"></i>Search
                </button>
                <?php if (!empty($search)): ?>
                    <a href="<?= \App\Core\View::url('seller/' . urlencode($seller['company_name'] ?? $seller['name'])) ?>" 
                       class="btn btn-outline">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Section -->
        <div class="bg-white rounded-xl shadow-sm p-4 md:p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl md:text-2xl font-bold text-gray-900">
                        <?= !empty($search) ? 'Search Results' : 'All Products' ?>
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        <?php if (!empty($search)): ?>
                            Found <?= $total ?> product(s) for "<?= htmlspecialchars($search) ?>"
                        <?php else: ?>
                            <?= $total ?> product(s) available
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        <?= !empty($search) ? 'No products found' : 'No products available' ?>
                    </h3>
                    <p class="text-gray-500">
                        <?= !empty($search) ? 'Try a different search term' : 'This store has no products yet' ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- Products Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 md:gap-4">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $badge = null;
                        if (!empty($product['is_new'])) {
                            $badge = ['label' => 'New'];
                        } elseif (!empty($product['is_best_seller'])) {
                            $badge = ['label' => 'Best'];
                        } elseif (!empty($product['is_featured'])) {
                            $badge = ['label' => 'Featured'];
                        }

                        $cardOptions = [
                            'theme' => 'light',
                            'showCta' => false,
                            'cardClass' => 'w-full h-full',
                        ];

                        if ($badge) {
                            $cardOptions['topRightBadge'] = $badge;
                        }

                        include dirname(dirname(__DIR__)) . '/home/sections/shared/product-card.php';
                        ?>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-8 flex items-center justify-center gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="px-4 py-2 rounded-lg text-sm font-medium <?= $i === $currentPage ? 'bg-primary text-white' : 'border border-gray-300 text-gray-700 bg-white hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function redirectToProduct(url) {
    window.location.href = url;
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(dirname(__FILE__))) . '/layouts/main.php'; ?>

