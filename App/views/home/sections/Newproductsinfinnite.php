<?php
/**
 * New Products Infinite Scroll Section
 * 
 * Daraz-style infinite scroll for mobile
 * Loads products as user scrolls with no limit
 * Uses same format as top-sale.php
 */
?>

<div id="infinite-products-section">
    <div class="bg-white mx-2 rounded-xl shadow-sm mb-4 border border-primary/10">
        <div class="flex items-center justify-between p-3 border-b border-gray-100 bg-primary/5">
            <h3 class="text-lg font-semibold text-gray-900">More Products</h3>
            <a href="<?= \App\Core\View::url('products') ?>"
               class="inline-flex items-center gap-1 text-accent font-semibold text-sm hover:text-accent/80 transition-colors">
                <span>View All</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="p-0 sm:p-4">
            <div id="infinite-products-container" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
                <!-- Products will be loaded here via infinite scroll -->
            </div>
            <div id="infinite-loading" class="text-center py-8 hidden">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                <p class="mt-2 text-gray-600">Loading more products...</p>
            </div>
            <div id="infinite-end" class="text-center py-8 hidden">
                <p class="text-gray-600">No more products to load</p>
            </div>
        </div>
    </div>
</div>

