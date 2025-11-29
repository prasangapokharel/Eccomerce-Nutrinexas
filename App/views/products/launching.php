<?php ob_start(); ?>

<?php
include __DIR__ . '/../components/pricing-helper.php';
?>

<div class="bg-neutral-50 min-h-screen">
    <div class="container category-clean mx-auto px-3 py-4 sm:px-4 sm:py-8">
        <div class="mb-4 sm:mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Launching Soon</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    Upcoming products that are scheduled to launch soon.
                </p>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-neutral-100 p-8 text-center">
                <div class="w-14 h-14 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-neutral-700 mb-1">No upcoming launches</h3>
                <p class="text-sm text-neutral-500">Check back soon for scheduled product launches.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-3">
                <?php foreach ($products as $product): ?>
                    <div class="w-full">
                        <?php
                        $cardOptions = [
                            'theme' => 'light',
                            'showCta' => false,
                            'cardClass' => 'w-full h-full',
                        ];

                        // Optional badge for scheduled / countdown days
                        if (!empty($product['is_scheduled']) && !empty($product['remaining_days'])) {
                            $days = (int)$product['remaining_days'];
                            $label = $days === 0 ? 'Today' : "D-{$days}";
                            $cardOptions['topRightBadge'] = ['label' => $label];
                        }

                        include __DIR__ . '/../home/sections/shared/product-card.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="mt-8 border-t border-gray-200 pt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600">
                    <div class="flex items-center gap-4">
                        <span class="font-medium">
                            Showing <?= (($currentPage - 1) * 12 + 1) ?> to
                            <?= min($currentPage * 12, $totalProducts ?? count($products)) ?>
                            of <?= $totalProducts ?? count($products) ?> launches
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= \App\Core\View::url('products/launching?page=' . ($currentPage - 1)) ?>"
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                                <i class="fas fa-chevron-left mr-1"></i>Previous
                            </span>
                        <?php endif; ?>

                        <div class="flex items-center gap-1">
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);

                            if ($startPage > 1): ?>
                                <a href="<?= \App\Core\View::url('products/launching?page=1') ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $currentPage): ?>
                                    <span class="px-3 py-2 border border-primary bg-primary text-white rounded-lg font-medium"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="<?= \App\Core\View::url('products/launching?page=' . $i) ?>"
                                       class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="px-2 text-gray-500">...</span>
                                <?php endif; ?>
                                <a href="<?= \App\Core\View::url('products/launching?page=' . $totalPages) ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                            <?php endif; ?>
                        </div>

                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= \App\Core\View::url('products/launching?page=' . ($currentPage + 1)) ?>"
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                                Next<i class="fas fa-chevron-right ml-1"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
if (typeof redirectToProduct === 'undefined') {
    function redirectToProduct(url, adId) {
        window.location.href = url;
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>


