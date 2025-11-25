<?php ob_start(); ?>
<?php $page = 'reviews'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Product Reviews</h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Reviews</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pending'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['approved'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Average Rating</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
                <?= $stats['average_rating'] ?>
                <span class="text-yellow-500"><i class="fas fa-star"></i></span>
            </p>
        </div>
    </div>


    <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <i class="fas fa-star empty-state-icon"></i>
            <h3 class="empty-state-title">No reviews yet</h3>
            <p class="empty-state-text">Reviews from customers will appear here</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start flex-1">
                            <?php 
                            $productImage = $review['product_image'] ?? null;
                            if (!$productImage && !empty($review['product_name'])) {
                                $productImage = \App\Core\View::asset('images/placeholder-product.jpg');
                            }
                            ?>
                            <?php if ($productImage): ?>
                                <img src="<?= htmlspecialchars($productImage) ?>" 
                                     alt="<?= htmlspecialchars($review['product_name']) ?>"
                                     class="w-16 h-16 object-cover rounded-lg mr-4"
                                     onerror="this.src='<?= \App\Core\View::asset('images/placeholder-product.jpg') ?>'">
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <?= htmlspecialchars($review['product_name'] ?? 'N/A') ?>
                                    </h3>
                                    <div class="flex items-center">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 mb-2">
                                    by <?= htmlspecialchars(($review['first_name'] ?? 'Guest') . ' ' . ($review['last_name'] ?? '')) ?>
                                    <?php if (!empty($review['email'])): ?>
                                        <span class="text-gray-400">(<?= htmlspecialchars($review['email']) ?>)</span>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($review['review'])): ?>
                                    <p class="text-gray-700 mb-3"><?= htmlspecialchars($review['review']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($review['image_path'])): ?>
                                    <div class="mb-3">
                                        <img src="<?= \App\Core\View::asset('uploads/reviews/' . $review['image_path']) ?>" 
                                             alt="Review image" 
                                             class="max-w-xs rounded-lg border border-gray-200">
                                    </div>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500 mt-3">
                                    <?= date('M j, Y g:i A', strtotime($review['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

