<?php ob_start(); ?>
<?php $page = 'ads'; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Ads</h1>
        <a href="<?= \App\Core\View::url('seller/ads/create') ?>" class="bg-primary text-white px-4 py-2 rounded-2xl hover:bg-primary-dark transition-colors">
            <i class="fas fa-plus mr-2"></i>Create New Ad
        </a>
    </div>

    <?php if (empty($ads)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-ad text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Ads Yet</h3>
            <p class="text-gray-500 mb-4">Create your first ad to promote your products</p>
            <a href="<?= \App\Core\View::url('seller/ads/create') ?>" class="inline-block bg-primary text-white px-6 py-2 rounded-2xl hover:bg-primary-dark transition-colors">
                Create Ad
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product/Banner</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reach</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $ad['ad_type_name'] === 'banner_external' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= htmlspecialchars($ad['ad_type_name'] === 'banner_external' ? 'Banner' : 'Product') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($ad['product_name']): ?>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ad['product_name']) ?></div>
                                    <?php elseif ($ad['banner_image']): ?>
                                        <div class="text-sm text-gray-500">External Banner</div>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-400">-</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($ad['start_date'])) ?> - <?= date('M d, Y', strtotime($ad['end_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($ad['reach'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($ad['click'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $approvalStatus = $ad['approval_status'] ?? 'pending';
                                    $approvalColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $approvalColor = $approvalColors[$approvalStatus] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $approvalColor ?>">
                                        <?= ucfirst($approvalStatus) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                        echo match($ad['status']) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'inactive' => 'bg-gray-100 text-gray-800',
                                            'suspended' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>">
                                        <?= ucfirst($ad['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <a href="<?= \App\Core\View::url('seller/ads/show/' . $ad['id']) ?>" class="text-primary hover:text-primary-dark">View</a>
                                        <?php
                                        $approvalStatus = $ad['approval_status'] ?? 'pending';
                                        if ($approvalStatus === 'approved'):
                                            if ($ad['status'] === 'active'): ?>
                                                <form action="<?= \App\Core\View::url('seller/ads/update-status/' . $ad['id']) ?>" method="POST" class="inline">
                                                    <input type="hidden" name="status" value="inactive">
                                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Stop this ad? No charges will occur while stopped.');">
                                                        <i class="fas fa-stop"></i> Stop
                                                    </button>
                                                </form>
                                            <?php elseif ($ad['status'] === 'inactive' || ($ad['status'] === 'active' && $ad['auto_paused'])): ?>
                                                <form action="<?= \App\Core\View::url('seller/ads/update-status/' . $ad['id']) ?>" method="POST" class="inline">
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" class="text-green-600 hover:text-green-800">
                                                        <i class="fas fa-play"></i> Start
                                                    </button>
                                                </form>
                                            <?php endif;
                                        elseif ($approvalStatus === 'pending'): ?>
                                            <span class="text-yellow-600 text-xs" title="Waiting for admin approval">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php elseif ($approvalStatus === 'rejected'): ?>
                                            <span class="text-red-600 text-xs" title="Ad was rejected by admin">
                                                <i class="fas fa-times-circle"></i> Rejected
                                            </span>
                                        <?php endif; ?>
                                    </div>
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


