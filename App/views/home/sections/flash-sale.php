<?php
/**
 * Flash Sale Grid
 *
 * Shows up to six fast-moving products with aggressive styling.
 * Relies on $popular_products (array) and $pricingHelper (callable).
 */

$flashProducts = array_slice($popular_products ?? [], 0, 6);

if (empty($flashProducts)) {
    return;
}
?>

<div class="bg-white mx-2 rounded-xl shadow-sm mb-0 border border-primary/10">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-primary">Limited Time</h3>
        <a href="<?= \App\Core\View::url('products') ?>" class="inline-flex items-center gap-1 text-accent font-semibold text-sm hover:text-accent/80 transition-colors">
            <span>View All</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <div class="p-0 sm:p-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <?php foreach ($flashProducts as $product):
                $cardOptions = ['theme' => 'flash', 'showCta' => false];
                include __DIR__ . '/shared/product-card.php';
            endforeach; ?>
        </div>
    </div>
</div>

