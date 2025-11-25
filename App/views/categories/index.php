<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-primary mb-2">Product Categories</h1>
            <p class="text-gray-600">Browse our wide range of supplement categories</p>
        </div>

        <!-- Categories Grid -->
        <?php if (!empty($categories)): ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach ($categories as $category): ?>
                    <a href="<?= \App\Core\View::url('products/category/' . urlencode($category['slug'] ?? $category['name'])) ?>"
                       class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center hover:shadow-md transition-shadow">
                        
                        <div class="relative w-20 h-20 mx-auto mb-3 flex items-center justify-center">
                            <!-- Category Image -->
                            <img src="<?= htmlspecialchars($category['image_url'] ?? 'https://m.media-amazon.com/images/I/716ruiQM3mL._AC_SL1500_.jpg') ?>"
                                 alt="<?= htmlspecialchars($category['name']) ?>"
                                 class="w-full h-full object-contain rounded-lg bg-gray-50"
                                 loading="lazy"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/products/default.jpg'">
                        </div>

                        <h3 class="text-sm font-semibold text-gray-900 mb-1 line-clamp-2">
                            <?= htmlspecialchars($category['name']) ?>
                        </h3>
                        
                        <?php if (!empty($category['description'])): ?>
                            <p class="text-xs text-gray-500 line-clamp-2">
                                <?= htmlspecialchars(substr($category['description'], 0, 60)) ?>
                                <?= strlen($category['description']) > 60 ? '...' : '' ?>
                            </p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Categories Fallback -->
            <div class="bg-white border border-gray-100 shadow-sm p-8 text-center rounded-2xl">
                <div class="text-gray-500 mb-4">
                    <svg class="w-16 h-16 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold mb-2">No Categories Available</h2>
                <p class="text-gray-600 mb-6">Categories will be displayed here once they are added.</p>
                <a href="<?= \App\Core\View::url('products') ?>" class="btn-primary">
                    Browse All Products
                </a>
            </div>
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
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
