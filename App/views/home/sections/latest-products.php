<?php
/**
 * Latest Products Grid
 *
 * Uses $products and $pricingHelper to render recently added items.
 */

$latestProducts = array_slice($products ?? [], 0, 8);

if (empty($latestProducts)) {
    return;
}
?>

<div class="bg-white mx-2 rounded-xl shadow-sm mb-4">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <h3 class="text-lg font-bold text-gray-900">Latest Products</h3>
        <a href="<?= \App\Core\View::url('products') ?>" class="text-blue-900 font-medium text-sm">View All ></a>
    </div>

    <div class="p-0 sm:p-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-2">
            <?php foreach ($latestProducts as $product):
                $cardOptions = ['theme' => 'light', 'showCta' => false];
                include __DIR__ . '/shared/product-card.php';
            endforeach; ?>
        </div>
    </div>
</div>

