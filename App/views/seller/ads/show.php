<?php ob_start(); ?>
<?php $page = 'ads'; ?>

<div class="space-y-6 max-w-4xl">
    <div class="mb-6">
        <a href="<?= \App\Core\View::url('seller/ads') ?>" class="text-primary hover:text-primary-dark mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Back to Ads
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Ad Details</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <!-- Ad Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Ad Type</label>
                <p class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ad['ad_type_name'] ?? 'N/A'))) ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?php
                    $status = $ad['status'] ?? 'inactive';
                    echo match($status) {
                        'active' => 'bg-green-100 text-green-800',
                        'inactive' => 'bg-gray-100 text-gray-800',
                        'suspended' => 'bg-red-100 text-red-800',
                        'expired' => 'bg-yellow-100 text-yellow-800',
                        'paused_daily_limit' => 'bg-orange-100 text-orange-800',
                        default => 'bg-gray-100 text-gray-800'
                    };
                ?>">
                    <?= ucfirst($status) ?>
                </span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Approval Status</label>
                <?php
                $approvalStatus = $ad['approval_status'] ?? 'pending';
                $approvalColors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'approved' => 'bg-green-100 text-green-800',
                    'rejected' => 'bg-red-100 text-red-800'
                ];
                $approvalColor = $approvalColors[$approvalStatus] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?= $approvalColor ?>">
                    <?= ucfirst($approvalStatus) ?>
                </span>
                <?php if ($approvalStatus === 'pending'): ?>
                    <p class="text-xs text-gray-500 mt-1">Waiting for admin approval</p>
                <?php elseif ($approvalStatus === 'rejected'): ?>
                    <p class="text-xs text-red-500 mt-1">Ad was rejected by admin</p>
                <?php endif; ?>
            </div>
            <?php if (!empty($ad['product_name'])): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Product</label>
                    <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ad['product_name']) ?></p>
                    <?php if (!empty($ad['product_slug'])): ?>
                        <a href="<?= \App\Core\View::url('products/view/' . $ad['product_slug']) ?>" target="_blank" class="text-sm text-primary hover:underline mt-1 inline-block">
                            View Product <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Duration</label>
                <p class="text-lg font-semibold text-gray-900">
                    <?= date('M d, Y', strtotime($ad['start_date'] ?? 'now')) ?> - <?= date('M d, Y', strtotime($ad['end_date'] ?? 'now')) ?>
                    <?php if (!empty($ad['duration_days'])): ?>
                        <span class="text-sm text-gray-500">(<?= $ad['duration_days'] ?> days)</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Clicks & Cost Info -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Clicks & Cost Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Total Clicks</label>
                    <p class="text-2xl font-bold text-blue-600"><?= number_format($ad['total_clicks'] ?? 0) ?></p>
                    <p class="text-xs text-gray-500 mt-1">Clicks purchased</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Remaining Clicks</label>
                    <p class="text-2xl font-bold text-green-600"><?= number_format($ad['remaining_clicks'] ?? 0) ?></p>
                    <p class="text-xs text-gray-500 mt-1">Clicks remaining</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Cost Per Click</label>
                    <p class="text-2xl font-bold text-purple-600">Rs. <?= number_format($ad['per_click_rate'] ?? 0, 2) ?></p>
                    <p class="text-xs text-gray-500 mt-1">Minimum CPC rate</p>
                </div>
            </div>
            <div class="mt-4 bg-gray-50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Estimated Total Cost:</span>
                    <span class="text-xl font-bold text-primary">Rs. <?= number_format($ad['total_cost'] ?? 0, 2) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Calculated as: CPC Rate × Total Clicks (charged per click)</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Performance Statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600"><?= number_format($ad['reach'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600">Total Reach</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600"><?= number_format($ad['click'] ?? 0) ?></div>
                    <div class="text-sm text-gray-600">Total Clicks</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">
                        <?= ($ad['reach'] ?? 0) > 0 ? number_format((($ad['click'] ?? 0) / $ad['reach']) * 100, 2) : 0 ?>%
                    </div>
                    <div class="text-sm text-gray-600">CTR</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?= $ad['duration_days'] ?? 0 ?></div>
                    <div class="text-sm text-gray-600">Days</div>
                </div>
            </div>
        </div>

        <!-- Start/Stop Controls -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Ad Control</h3>
            <div class="space-y-3">
                <?php
                $approvalStatus = $ad['approval_status'] ?? 'pending';
                if ($approvalStatus === 'approved'):
                    if ($ad['status'] === 'active' && empty($ad['auto_paused'])): ?>
                        <form action="<?= \App\Core\View::url('seller/ads/update-status/' . $ad['id']) ?>" method="POST">
                            <input type="hidden" name="status" value="inactive">
                            <button type="submit" class="w-full bg-red-600 text-white px-6 py-3 rounded-2xl hover:bg-red-700 transition-colors font-semibold" onclick="return confirm('Stop this ad? No charges will occur while stopped.');">
                                <i class="fas fa-stop mr-2"></i>Stop Ad
                            </button>
                        </form>
                        <p class="text-sm text-gray-500 text-center">
                            <i class="fas fa-info-circle mr-1"></i>Ad is running and charging Rs. <?= number_format($ad['per_click_rate'] ?? 0, 2) ?> per click
                        </p>
                    <?php elseif ($ad['status'] === 'inactive' || !empty($ad['auto_paused'])): ?>
                        <form action="<?= \App\Core\View::url('seller/ads/update-status/' . $ad['id']) ?>" method="POST">
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="w-full bg-green-600 text-white px-6 py-3 rounded-2xl hover:bg-green-700 transition-colors font-semibold">
                                <i class="fas fa-play mr-2"></i>Start Ad
                            </button>
                        </form>
                        <?php if (!empty($ad['auto_paused'])): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <p class="text-sm text-yellow-800 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Auto-paused: <?= !empty($ad['notes']) ? htmlspecialchars(substr($ad['notes'], -100)) : 'Insufficient balance or clicks exhausted' ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-500 text-center">
                                <i class="fas fa-pause mr-1"></i>Ad is stopped - no charges will occur
                            </p>
                        <?php endif;
                    endif;
                elseif ($approvalStatus === 'pending'): ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800 flex items-center justify-center">
                            <i class="fas fa-clock mr-2"></i>
                            <span>This ad is pending admin approval. You can start/stop it after approval.</span>
                        </p>
                    </div>
                <?php elseif ($approvalStatus === 'rejected'): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-sm text-red-800 flex items-center justify-center">
                            <i class="fas fa-times-circle mr-2"></i>
                            <span>This ad was rejected by admin. Please contact support for more information.</span>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($ad['notes'])): ?>
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Notes</h3>
                <p class="text-gray-600 whitespace-pre-wrap"><?= htmlspecialchars($ad['notes']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
