<?php ob_start(); ?>
<?php $page = 'reviews'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Product Reviews</h1>
    </div>

    <!-- Stats -->
    <div class="flex flex-wrap gap-4">
        <div class="bg-white rounded-lg shadow p-6 flex-1 min-w-[180px]">
            <p class="text-sm text-gray-600">Total Reviews</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex-1 min-w-[180px]">
            <p class="text-sm text-gray-600">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pending'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex-1 min-w-[180px]">
            <p class="text-sm text-gray-600">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['approved'] ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 flex-1 min-w-[180px]">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Latest Reviews</h3>
                    <p class="text-sm text-gray-500">Track customer sentiment at a glance</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Review</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($reviews as $review): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php 
                                            $productImage = $review['product_image'] ?? \App\Core\View::asset('images/placeholder-product.jpg');
                                        ?>
                                        <img src="<?= htmlspecialchars($productImage) ?>" 
                                             alt="<?= htmlspecialchars($review['product_name'] ?? 'Product') ?>"
                                             class="w-12 h-12 rounded-lg object-cover border border-gray-200"
                                             onerror="this.src='<?= \App\Core\View::asset('images/placeholder-product.jpg') ?>'">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($review['product_name'] ?? 'N/A') ?></p>
                                            <p class="text-xs text-gray-500">#<?= $review['product_id'] ?? '—' ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="flex flex-col">
                                        <span class="font-medium"><?= htmlspecialchars(trim(($review['first_name'] ?? 'Guest') . ' ' . ($review['last_name'] ?? ''))) ?></span>
                                        <?php if (!empty($review['email'])): ?>
                                            <span class="text-xs text-gray-500"><?= htmlspecialchars($review['email']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= ($review['rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php if (!empty($review['review'])): ?>
                                        <p class="line-clamp-3"><?= htmlspecialchars($review['review']) ?></p>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">No written review</span>
                                    <?php endif; ?>
                                    <?php if (!empty($review['image_path'])): ?>
                                        <a href="<?= \App\Core\View::asset('uploads/reviews/' . $review['image_path']) ?>" target="_blank" class="text-xs text-primary font-medium mt-2 inline-flex items-center gap-1">
                                            <i class="fas fa-image"></i> View image
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= date('M j, Y g:i A', strtotime($review['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php 
                                        $status = strtolower($review['status'] ?? 'pending');
                                        $statusClasses = [
                                            'approved' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'rejected' => 'bg-red-100 text-red-700'
                                        ];
                                        $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-700';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

