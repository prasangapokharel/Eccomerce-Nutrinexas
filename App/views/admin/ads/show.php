<?php ob_start(); ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= \App\Core\View::url('admin/ads') ?>" class="text-primary hover:text-primary-dark mb-2 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to Ads
            </a>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Ad Details</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage advertisement information</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 space-y-6">
        <!-- Ad Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Seller</label>
                <p class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars($ad['seller_name'] ?? 'N/A') ?>
                    <?php if ($ad['company_name']): ?>
                        <span class="text-gray-500">(<?= htmlspecialchars($ad['company_name']) ?>)</span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Ad Type</label>
                <p class="text-lg font-semibold text-gray-900">
                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $ad['ad_type_name']))) ?>
                </p>
            </div>
            <?php if ($ad['product_name']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-1">Product</label>
                    <p class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($ad['product_name']) ?></p>
                </div>
            <?php endif; ?>
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
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?php
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
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Duration</label>
                <p class="text-lg font-semibold text-gray-900">
                    <?= !empty($ad['start_date']) ? date('M d, Y', strtotime($ad['start_date'])) : 'N/A' ?> - <?= !empty($ad['end_date']) ? date('M d, Y', strtotime($ad['end_date'])) : 'N/A' ?>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Cost</label>
                <p class="text-lg font-semibold text-gray-900">Rs. <?= number_format($ad['cost_amount'] ?? 0, 2) ?></p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
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

        <!-- Payment Info -->
        <?php if ($payment): ?>
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h3>
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <p class="text-sm text-gray-600">Amount</p>
                            <p class="text-lg font-semibold text-gray-900">Rs. <?= number_format($payment['amount'], 2) ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <span class="px-3 py-1 text-sm font-medium rounded-full <?php
                                echo match($payment['payment_status']) {
                                    'paid' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            ?>">
                                <?= ucfirst($payment['payment_status']) ?>
                            </span>
                        </div>
                    </div>
                    <form action="<?= \App\Core\View::url('admin/ads/payment/update-status/' . $payment['id']) ?>" method="POST" class="flex gap-3">
                        <select name="payment_status" class="flex-1 px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary">
                            <option value="pending" <?= $payment['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $payment['payment_status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="failed" <?= $payment['payment_status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                        <button type="submit" class="bg-primary text-white px-6 py-2 rounded-2xl hover:bg-primary-dark transition-colors">
                            Update Payment Status
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Approval Actions -->
        <?php
        $approvalStatus = $ad['approval_status'] ?? 'pending';
        if ($approvalStatus === 'pending'):
        ?>
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Actions</h3>
            <div class="flex flex-col sm:flex-row gap-3">
                <form action="<?= \App\Core\View::url('admin/ads/approve/' . $ad['id']) ?>" method="POST" class="flex-1" onsubmit="return confirm('Approve this ad? Seller will be able to start/stop it.');">
                    <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <i class="fas fa-check-circle mr-2"></i>Approve Ad
                    </button>
                </form>
                <button onclick="showRejectModal()" class="flex-1 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                    <i class="fas fa-times-circle mr-2"></i>Reject Ad
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Status Update Form -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h3>
            <form action="<?= \App\Core\View::url('admin/ads/update-status/' . $ad['id']) ?>" method="POST" class="flex flex-col sm:flex-row gap-3">
                <select name="status" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                    <option value="active" <?= $ad['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $ad['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $ad['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    <option value="expired" <?= $ad['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
                <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                    <i class="fas fa-save mr-2"></i>Update Status
                </button>
            </form>
        </div>

        <!-- Reject Modal -->
        <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reject Ad</h3>
                    <form action="<?= \App\Core\View::url('admin/ads/reject/' . $ad['id']) ?>" method="POST" id="rejectForm">
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
        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
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

        <!-- Notes -->
        <?php if ($ad['notes']): ?>
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Notes</h3>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($ad['notes']) ?></p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

