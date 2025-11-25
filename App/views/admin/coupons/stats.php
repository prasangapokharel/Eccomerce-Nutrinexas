<?php
$title = 'Coupon Statistics';
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Coupon Statistics</h1>
                        <p class="text-gray-600 mt-1">Detailed analytics for coupon: <span class="font-semibold text-primary"><?= htmlspecialchars($stats['code']) ?></span></p>
                    </div>
                    <a href="<?= \App\Core\View::url('admin/coupons') ?>" 
                       class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Back to Coupons
                    </a>
                </div>
            </div>
        </div>

        <!-- Coupon Details Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Coupon Details</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                        <p class="text-lg font-semibold text-primary"><?= htmlspecialchars($stats['code']) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Type</label>
                        <p class="text-gray-900 capitalize"><?= htmlspecialchars($stats['discount_type']) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                        <p class="text-gray-900">
                            <?php if ($stats['discount_type'] === 'percentage'): ?>
                                <?= number_format($stats['discount_value'], 2) ?>%
                            <?php else: ?>
                                Rs<?= number_format($stats['discount_value'], 2) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $stats['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                            <?= $stats['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Created Date</label>
                        <p class="text-gray-900"><?= date('M d, Y', strtotime($stats['created_at'])) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expires</label>
                        <p class="text-gray-900">
                            <?php if ($stats['expires_at']): ?>
                                <?= date('M d, Y H:i', strtotime($stats['expires_at'])) ?>
                                <?php if (strtotime($stats['expires_at']) <= time()): ?>
                                    <span class="text-red-500 text-sm">(Expired)</span>
                                <?php endif; ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Uses -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Uses</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_uses'] ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <!-- Unique Users -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Unique Users</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['unique_users'] ?? 0) ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Discount Given -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Discount</p>
                        <p class="text-2xl font-semibold text-gray-900">Rs<?= number_format($stats['total_discount_given'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Average Discount -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg. Discount</p>
                        <p class="text-2xl font-semibold text-gray-900">Rs<?= number_format($stats['avg_discount_per_use'] ?? 0, 2) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Limits -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Usage Limits</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Global Usage Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Global Usage Limit</label>
                        <?php if ($stats['usage_limit_global']): ?>
                            <div class="flex items-center space-x-4">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <?php 
                                    $globalUsagePercent = min(100, (($stats['total_uses'] ?? 0) / $stats['usage_limit_global']) * 100);
                                    ?>
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?= $globalUsagePercent ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-600">
                                    <?= number_format($stats['total_uses'] ?? 0) ?> / <?= number_format($stats['usage_limit_global']) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500">Unlimited</p>
                        <?php endif; ?>
                    </div>

                    <!-- Per User Limit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Per User Limit</label>
                        <?php if ($stats['usage_limit_per_user']): ?>
                            <p class="text-gray-900"><?= number_format($stats['usage_limit_per_user']) ?> uses per user</p>
                        <?php else: ?>
                            <p class="text-gray-500">Unlimited</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Constraints -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Order Constraints</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Order Amount</label>
                        <p class="text-gray-900">
                            <?php if ($stats['min_order_amount']): ?>
                                Rs<?= number_format($stats['min_order_amount'], 2) ?>
                            <?php else: ?>
                                No minimum
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Maximum Discount Amount</label>
                        <p class="text-gray-900">
                            <?php if ($stats['max_discount_amount']): ?>
                                Rs<?= number_format($stats['max_discount_amount'], 2) ?>
                            <?php else: ?>
                                No maximum
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Usage Timeline -->
        <?php if ($stats['first_used'] && $stats['last_used']): ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Usage Timeline</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Used</label>
                        <p class="text-gray-900"><?= date('M d, Y H:i', strtotime($stats['first_used'])) ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Used</label>
                        <p class="text-gray-900"><?= date('M d, Y H:i', strtotime($stats['last_used'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Applicable Products -->
        <?php if ($stats['applicable_products']): ?>
        <?php 
        $applicableProducts = json_decode($stats['applicable_products'], true);
        if ($applicableProducts && !empty($applicableProducts)):
        ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Applicable Products</h2>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-4">This coupon is applicable to <?= count($applicableProducts) ?> specific products.</p>
                <div class="text-sm text-gray-500">
                    Product IDs: <?= implode(', ', $applicableProducts) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Product Applicability</h2>
            </div>
            <div class="p-6">
                <p class="text-gray-600">This coupon is applicable to all products.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Performance Summary -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Performance Summary</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600"><?= number_format($stats['total_uses'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">Total Redemptions</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-green-600">Rs<?= number_format($stats['total_discount_given'] ?? 0, 0) ?></div>
                        <div class="text-sm text-gray-600">Total Savings Provided</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-purple-600"><?= number_format($stats['unique_users'] ?? 0) ?></div>
                        <div class="text-sm text-gray-600">Customers Reached</div>
                    </div>
                </div>
                
                <?php if (($stats['total_uses'] ?? 0) > 0): ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            This coupon has been successfully used by <strong><?= number_format($stats['unique_users'] ?? 0) ?></strong> customers, 
                            providing a total discount of <strong>Rs<?= number_format($stats['total_discount_given'] ?? 0, 2) ?></strong> 
                            across <strong><?= number_format($stats['total_uses'] ?? 0) ?></strong> orders.
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="text-center">
                        <p class="text-sm text-gray-500">This coupon has not been used yet.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/admin.php';
 ?>
