<?php
/**
 * Top Selling Section
 *
 * Highlights best-selling products with a bold layout.
 * Uses $popular_products and $pricingHelper from parent scope.
 */

$topSaleProducts = array_slice($popular_products ?? [], 0, 8);

if (empty($topSaleProducts)) {
    return;
}
?>

<div class="bg-white mx-2 rounded-xl shadow-sm mb-4 border border-primary/10">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Most Sold</h3>
        <a href="<?= \App\Core\View::url('products?sort=bestseller') ?>"
           class="inline-flex items-center gap-1 text-accent font-semibold text-sm hover:text-accent/80 transition-colors">
            <span>View All</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="p-0 sm:p-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2 sm:gap-3">
            <?php foreach ($topSaleProducts as $product):
                $cardOptions = [
                    'theme' => 'primary',
                    'ctaStyle' => 'primary',
                    'showCta' => false,
                    'topRightBadge' => [
                        'label' => 'Most Sold'
                    ],
                ];
                include __DIR__ . '/shared/product-card.php';
            endforeach; ?>
        </div>
    </div>
</div>

