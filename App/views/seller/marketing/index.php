<?php ob_start(); ?>
<?php $page = 'marketing'; ?>
<?php use App\Helpers\CurrencyHelper; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Marketing Tools</h1>
            <p class="mt-1 text-sm text-gray-500">Create and manage discount coupons</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= \App\Core\View::url('seller/marketing/create-coupon') ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Create Coupon
            </a>
        </div>
    </div>

    <!-- Coupons Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">All Coupons</h2>
        </div>
        
        <?php if (empty($coupons)): ?>
            <div class="text-center py-12">
                <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No coupons found</h3>
                <p class="text-gray-600 mb-6">Get started by creating your first coupon</p>
                <a href="<?= \App\Core\View::url('seller/marketing/create-coupon') ?>" 
                   class="btn btn-primary">
                    Create Coupon
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibility</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($coupons as $coupon): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($coupon['code']) ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                            <?= $coupon['discount_value'] ?>%
                                        <?php else: ?>
                                            <?= CurrencyHelper::format($coupon['discount_value']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($coupon['min_order_amount']): ?>
                                        <div class="text-xs text-gray-500">Min: <?= CurrencyHelper::format($coupon['min_order_amount']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= $coupon['used_count'] ?? 0 ?>
                                        <?php if ($coupon['usage_limit_global']): ?>
                                            / <?= $coupon['usage_limit_global'] ?>
                                        <?php else: ?>
                                            <span class="text-gray-500">(Unlimited)</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($coupon['usage_limit_per_user']): ?>
                                        <div class="text-xs text-gray-500">Per user: <?= $coupon['usage_limit_per_user'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($coupon['expires_at']): ?>
                                        <?php
                                        $expiryTime = strtotime($coupon['expires_at']);
                                        $isExpired = $expiryTime <= time();
                                        $isExpiringSoon = $expiryTime <= time() + (7 * 24 * 60 * 60) && !$isExpired;
                                        ?>
                                        <div class="text-sm <?= $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-yellow-600' : 'text-gray-900') ?>">
                                            <?= date('M j, Y', $expiryTime) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('g:i A', $expiryTime) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                                    $isActive = $coupon['is_active'] && !$isExpired;
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($coupon['status'] ?? 'private') === 'public' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= ucfirst($coupon['status'] ?? 'Private') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="copyCouponCode('<?= htmlspecialchars($coupon['code']) ?>')" 
                                                class="text-indigo-600 hover:text-indigo-900 transition-colors"
                                                title="Copy Code">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyCouponCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Coupon code copied: ' + code);
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
