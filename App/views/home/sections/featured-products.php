<?php
/**
 * Featured Products Carousel/Grid
 *
 * Filters $products for featured entries and displays them in either a grid or marquee.
 */

$featuredProducts = array_values(array_filter($products ?? [], function ($p) {
    return !empty($p['is_featured']) || !empty($p['featured']);
}));

if (empty($featuredProducts)) {
    return;
}

$useMarquee = count($featuredProducts) > 6;
?>

<div id="featured-section" class="bg-white mx-2 rounded-xl shadow-sm mb-2 border border-primary/10 opacity-0 transition-opacity duration-700">
    <div class="flex items-center justify-between p-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-primary">Featured Products</h3>
        <a href="<?= \App\Core\View::url('products?featured=1') ?>" class="inline-flex items-center gap-1 text-accent font-semibold text-sm hover:text-accent/80 transition-colors">
            <span>View All</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
    <div class="p-0 sm:p-4">
        <div class="<?= $useMarquee ? 'overflow-hidden' : '' ?>">
            <div class="<?= $useMarquee ? 'flex gap-2 sm:gap-3 animate-marquee' : 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-2' ?>">
                <?php foreach ($featuredProducts as $product):
                    $cardOptions = [
                        'theme' => 'light',
                        'showCta' => false,
                        'cardClass' => $useMarquee ? 'flex-shrink-0 w-40 sm:w-48' : '',
                    ];
                    include __DIR__ . '/shared/product-card.php';
                endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const featured = document.getElementById('featured-section');
    if (!featured) return;
    setTimeout(() => { featured.classList.remove('opacity-0'); }, 600);
});
</script>

