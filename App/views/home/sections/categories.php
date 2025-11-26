<?php
/**
 * Home Categories Section
 *
 * Renders a lightweight horizontal list of categories with fallbacks.
 * Expects $categories array to be available in the parent scope.
 */
?>

<div class="bg-white mx-2 rounded-3xl shadow-sm mb-0 overflow-hidden border border-gray-100/50">
    <div class="px-4 py-3 border-b border-gray-100/60">
        <h3 class="text-sm font-semibold text-primary tracking-tight">Shop by Category</h3>
    </div>

    <div class="p-3 sm:p-4 overflow-hidden">
        <?php if (!empty($categories)): ?>
            <div class="flex items-center gap-4 sm:gap-5 animate-marquee categories-marquee">
                <?php for ($loop = 0; $loop < 2; $loop++): ?>
                    <?php foreach ($categories as $category): ?>
                        <a href="<?= \App\Core\View::url('products/category/' . urlencode($category['slug'] ?? strtolower(str_replace(' ', '-', $category['name'])))) ?>"
                           class="flex-shrink-0 text-center w-28 sm:w-32 group category-item">
                            <div class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto flex items-center justify-center bg-gradient-to-br from-gray-50 to-white rounded-3xl shadow-sm border border-gray-100/60 transition-all duration-300 ease-out group-hover:shadow-md group-hover:scale-105 group-hover:border-[#0A3167]/20 overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-br from-transparent via-transparent to-[#0A3167]/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <img src="<?= htmlspecialchars($category['image_url'] ?? 'https://m.media-amazon.com/images/I/716ruiQM3mL._AC_SL1500_.jpg') ?>"
                                     alt="<?= htmlspecialchars($category['name'] ?? 'Category') ?>"
                                     class="relative z-10 w-20 h-20 sm:w-24 sm:h-24 object-contain transition-transform duration-300 group-hover:scale-110"
                                     loading="lazy" 
                                     decoding="async" 
                                     fetchpriority="low" 
                                     width="96" 
                                     height="96"
                                     sizes="(max-width:640px) 112px, 128px"
                                     onerror="this.onerror=null; this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                            </div>
                            <span class="text-xs sm:text-sm text-gray-700 font-medium mt-2.5 block truncate transition-colors duration-200 group-hover:text-[#0A3167]">
                                <?= htmlspecialchars($category['name'] ?? 'Category') ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        <?php else: ?>
            <div class="flex items-center justify-center py-6">
                <a href="<?= \App\Core\View::url('products') ?>" class="flex-shrink-0 text-center group w-28 sm:w-32">
                    <div class="relative w-28 h-28 sm:w-32 sm:h-32 mx-auto flex items-center justify-center bg-gradient-to-br from-gray-50 to-white rounded-3xl shadow-sm border border-gray-100/60 transition-all duration-300 ease-out group-hover:shadow-md group-hover:scale-105 group-hover:border-[#0A3167]/20">
                        <i class="fas fa-dumbbell text-2xl sm:text-3xl text-[#0A3167] transition-transform duration-300 group-hover:scale-110"></i>
                    </div>
                    <span class="text-xs sm:text-sm text-gray-700 font-medium mt-2.5 block transition-colors duration-200 group-hover:text-[#0A3167]">All Products</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.category-item {
    will-change: transform;
}
.categories-marquee {
    gap: 1.25rem;
}
@media (min-width: 640px) {
    .categories-marquee {
        gap: 1.5rem;
    }
}
</style>

