<?php ob_start(); ?>
<?php
use App\Helpers\CurrencyHelper;
$userId = \App\Core\Session::get('user_id');
?>

<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Digital Products Download</h1>
            <p class="text-gray-600">Order #<?= htmlspecialchars($order['invoice'] ?? $order['id']) ?></p>
        </div>

        <!-- Digital Products List -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden mb-6">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Digital Products</h2>
                <div class="space-y-4">
                    <?php foreach ($digitalProducts as $product): ?>
                        <div class="border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <h3 class="text-base font-semibold text-gray-900 mb-2">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </h3>
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                                            </svg>
                                            Size: <?= htmlspecialchars($product['file_size']) ?>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Downloads: <?= $product['download_count'] ?> / <?= $product['max_download'] ?>
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Expires: <?= date('M d, Y', strtotime($product['expire_date'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="<?= \App\Core\View::url('products/digital/download/' . $product['product_id']) ?>" 
                                   class="flex-shrink-0 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-dark transition-colors <?= $product['remaining_downloads'] <= 0 ? 'opacity-50 cursor-not-allowed' : '' ?>"
                                   <?= $product['remaining_downloads'] <= 0 ? 'onclick="return false;"' : '' ?>>
                                    <?= $product['remaining_downloads'] > 0 ? 'Download' : 'Limit Reached' ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= \App\Core\View::url('orders') ?>" 
               class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-xl font-semibold text-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                View All Orders
            </a>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>

