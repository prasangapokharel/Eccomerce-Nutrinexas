<?php ob_start(); ?>
<?php
$title = 'My Reviews - NutriNexus';
?>

<div class="min-h-screen bg-neutral-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-foreground mb-2">My Reviews</h1>
            <p class="text-sm text-neutral-600">Manage and view all your product reviews</p>
        </div>

        <?php if (empty($reviews)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-12 text-center">
                <div class="w-16 h-16 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-foreground mb-2">No Reviews Yet</h3>
                <p class="text-sm text-neutral-600 mb-6">You haven't reviewed any products yet.</p>
                <a href="<?= \App\Core\View::url('products') ?>" class="bg-primary text-white px-6 py-2.5 rounded-2xl font-medium hover:bg-primary-dark inline-block">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex flex-col md:flex-row gap-4">
                            <!-- Product Image -->
                            <div class="flex-shrink-0">
                                <a href="<?= \App\Core\View::url('products/view/' . ($review['slug'] ?? $review['product_id'])) ?>" class="block">
                                    <div class="w-24 h-24 rounded-lg overflow-hidden border border-neutral-200 bg-neutral-100">
                                        <img src="<?= htmlspecialchars($review['product_image']) ?>" 
                                             alt="<?= htmlspecialchars($review['product_name']) ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                    </div>
                                </a>
                            </div>

                            <!-- Review Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <a href="<?= \App\Core\View::url('products/view/' . ($review['slug'] ?? $review['product_id'])) ?>" class="text-lg font-semibold text-foreground hover:text-primary">
                                            <?= htmlspecialchars($review['product_name']) ?>
                                        </a>
                                        <p class="text-xs text-neutral-500 mt-1">
                                            Reviewed on <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                        </p>
                                    </div>
                                    <button type="button" 
                                            onclick="deleteReview(<?= $review['id'] ?>)"
                                            class="text-error hover:text-error-dark p-2 hover:bg-error/10 rounded-lg transition-colors"
                                            title="Delete Review">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Rating -->
                                <div class="flex items-center gap-1 mb-3">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <svg class="w-5 h-5 <?= $i <= (int)($review['rating'] ?? 0) ? 'text-warning fill-current' : 'text-neutral-300' ?>" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    <?php endfor; ?>
                                </div>

                                <!-- Review Text -->
                                <?php if (!empty($review['review'])): ?>
                                    <p class="text-sm text-neutral-700 leading-relaxed">
                                        <?= htmlspecialchars($review['review']) ?>
                                    </p>
                                <?php else: ?>
                                    <p class="text-sm text-neutral-500 italic">No review text provided.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review?')) {
        return;
    }

    fetch('<?= \App\Core\View::url('reviews/delete/' . $reviewId) ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'review_id=' + reviewId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

