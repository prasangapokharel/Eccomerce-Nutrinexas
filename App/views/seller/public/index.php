<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50 py-6 px-4">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-primary mb-3">Our Trusted Sellers</h1>
            <p class="text-gray-600 text-lg">Discover premium products from verified sellers</p>
        </div>

        <!-- Search Bar -->
        <div class="max-w-2xl mx-auto mb-8">
            <form method="GET" action="<?= \App\Core\View::url('sellers') ?>" class="relative">
                <input 
                    type="text" 
                    name="search" 
                    value="<?= htmlspecialchars($search ?? '') ?>"
                    placeholder="Search sellers by name or company..."
                    class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all"
                >
                <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    Search
                </button>
            </form>
        </div>

        <!-- Sellers Grid -->
        <?php if (empty($sellers)): ?>
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No sellers found</h3>
                <p class="text-gray-500"><?= !empty($search) ? 'Try a different search term' : 'Check back soon for new sellers' ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                <?php foreach ($sellers as $seller): ?>
                    <?php
                    $sellerName = htmlspecialchars($seller['company_name'] ?? $seller['name']);
                    $sellerSlug = urlencode($seller['company_name'] ?? $seller['name']);
                    $logoUrl = !empty($seller['logo_url']) ? htmlspecialchars($seller['logo_url']) : null;
                    $productCount = (int)($seller['product_count'] ?? 0);
                    $avgRating = round((float)($seller['avg_rating'] ?? 0), 1);
                    ?>
                    <a href="<?= \App\Core\View::url('seller/' . $sellerSlug) ?>" 
                       class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all duration-300 group">
                        <!-- Logo Section -->
                        <div class="bg-gradient-to-br from-primary/5 to-primary/10 p-6 flex items-center justify-center h-48">
                            <?php if ($logoUrl): ?>
                                <img 
                                    src="<?= $logoUrl ?>" 
                                    alt="<?= $sellerName ?>"
                                    class="max-w-full max-h-32 object-contain group-hover:scale-105 transition-transform duration-300"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                            <?php endif; ?>
                            <div class="<?= $logoUrl ? 'hidden' : '' ?> w-24 h-24 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold">
                                <?= strtoupper(substr($seller['name'], 0, 1)) ?>
                            </div>
                        </div>
                        
                        <!-- Content Section -->
                        <div class="p-5">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-primary transition-colors line-clamp-2">
                                <?= $sellerName ?>
                            </h3>
                            
                            <?php if (!empty($seller['description'])): ?>
                                <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                    <?= htmlspecialchars(substr($seller['description'], 0, 100)) ?><?= strlen($seller['description']) > 100 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Stats -->
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <span><?= $productCount ?> <?= $productCount === 1 ? 'Product' : 'Products' ?></span>
                                </div>
                                
                                <?php if ($avgRating > 0): ?>
                                    <div class="flex items-center text-accent">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                        <span class="font-medium"><?= $avgRating ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center gap-2 mt-8">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 <?= $i === $currentPage ? 'bg-primary text-white' : 'border border-gray-300 hover:bg-gray-50' ?> rounded-lg transition-colors">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(dirname(__FILE__))) . '/layouts/main.php'; ?>

