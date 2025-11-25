<?php ob_start(); ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Manage Ads</h1>
        <a href="<?= \App\Core\View::url('admin/ads/costs') ?>" class="bg-primary text-white px-4 py-2 rounded-2xl hover:bg-primary-dark transition-colors">
            <i class="fas fa-dollar-sign mr-2"></i>Manage Costs
        </a>
    </div>

    <?php if (empty($ads)): ?>
        <div class="bg-white rounded-lg shadow p-8 text-center">
            <i class="fas fa-ad text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Ads Found</h3>
            <p class="text-gray-500">No advertisements have been created yet</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
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
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ad['seller_name'] ?? 'N/A') ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($ad['company_name'] ?? '') ?></div>
                                </td>
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
                                        <a href="<?= \App\Core\View::url('admin/ads/show/' . $ad['id']) ?>" class="text-primary hover:text-primary-dark" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($approvalStatus === 'pending'): ?>
                                            <form action="<?= \App\Core\View::url('admin/ads/approve/' . $ad['id']) ?>" method="POST" class="inline" onsubmit="return confirm('Approve this ad?');">
                                                <button type="submit" class="text-green-600 hover:text-green-800" title="Approve Ad">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                            <button onclick="showRejectModal(<?= $ad['id'] ?>)" class="text-red-600 hover:text-red-800" title="Reject Ad">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
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

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Ad</h3>
            <form id="rejectForm" method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason</label>
                    <textarea name="rejection_reason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Enter reason for rejection..." required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                        Reject Ad
                    </button>
                    <button type="button" onclick="closeRejectModal()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(adId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = '<?= \App\Core\View::url('admin/ads/reject/') ?>' + adId;
    modal.classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectForm').reset();
}

// Close modal when clicking outside
document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

