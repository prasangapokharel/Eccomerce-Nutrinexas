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

<div class="bg-white mx-2 rounded-xl shadow-sm mb-4 mt-2">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <div class="flex items-center">
            <img src="https://qkjsnpejxzujoaktpgpq.supabase.co/storage/v1/object/public/nutrinexas/Live%20static/sale.gif"
                 alt="Flash Sale"
                 class="h-12 w-12 mr-3"
                 loading="lazy" decoding="async" fetchpriority="low" width="48" height="48">
            <div class="bg-primary/10 text-primary px-2 py-1 rounded-full text-xs font-medium">
                Limited Time
            </div>
        </div>
        <a href="<?= \App\Core\View::url('products') ?>" class="text-blue-900 font-medium text-sm">SHOP MORE ></a>
    </div>

    <div class="p-0 sm:p-3">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <?php foreach ($flashProducts as $product):
                $cardOptions = ['theme' => 'flash', 'showCta' => false];
                include __DIR__ . '/shared/product-card.php';
            endforeach; ?>
        </div>
    </div>
</div>

