<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Seller Withdrawals</h1>
            <p class="text-gray-600">Manage all seller withdrawal requests</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/seller') ?>" 
           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i>Back to Sellers
        </a>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-600"><?= number_format($stats['pending'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Approved</p>
            <p class="text-2xl font-bold text-blue-600"><?= number_format($stats['approved'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Completed</p>
            <p class="text-2xl font-bold text-green-600"><?= number_format($stats['completed'] ?? 0) ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Amount</p>
            <p class="text-2xl font-bold text-gray-900">रु <?= number_format($stats['total_amount'] ?? 0, 2) ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" class="flex gap-3">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">All Status</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark">
                Filter
            </button>
        </form>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bank Account</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($withdraws)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No withdrawal requests found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdraws as $withdraw): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?= $withdraw['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <a href="<?= \App\Core\View::url('admin/seller/details/' . $withdraw['seller_id']) ?>" 
                                       class="text-primary hover:underline">
                                        <?= htmlspecialchars($withdraw['company_name'] ?? $withdraw['seller_name'] ?? 'N/A') ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">रु <?= number_format($withdraw['amount'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($withdraw['account_holder_name'] ?? 'N/A') ?><br>
                                    <span class="text-xs"><?= htmlspecialchars($withdraw['bank_name'] ?? '') ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'completed' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusColor = $statusColors[$withdraw['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusColor ?>">
                                        <?= ucfirst($withdraw['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($withdraw['requested_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <a href="<?= \App\Core\View::url('admin/seller/withdraws/' . $withdraw['seller_id']) ?>" 
                                           class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($withdraw['status'] === 'pending'): ?>
                                            <button onclick="showApproveModal(<?= $withdraw['id'] ?>)" 
                                                    class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button onclick="showRejectModal(<?= $withdraw['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php elseif ($withdraw['status'] === 'approved'): ?>
                                            <button onclick="showCompleteModal(<?= $withdraw['id'] ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-check-circle"></i> Complete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals (same as withdraws.php) -->
<div id="approveModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Approve Withdrawal</h3>
        <form id="approveForm" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="mb-4">
                <label for="admin_notes" class="block text-sm font-medium text-gray-700 mb-2">Admin Notes (Optional)</label>
                <textarea id="admin_notes" name="admin_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideApproveModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Approve</button>
            </div>
        </form>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Withdrawal</h3>
        <form id="rejectForm" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideRejectModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>

<div id="completeModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Mark as Completed</h3>
        <form id="completeForm" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="mb-4">
                <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">Payment Notes (Optional)</label>
                <textarea id="payment_notes" name="payment_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideCompleteModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Mark Complete</button>
            </div>
        </form>
    </div>
</div>

<script>
function showApproveModal(id) {
    document.getElementById('approveForm').action = '<?= \App\Core\View::url('admin/seller/withdraws/approve/') ?>' + id;
    document.getElementById('approveModal').classList.remove('hidden');
    document.getElementById('approveModal').style.display = 'flex';
}

function hideApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
    document.getElementById('approveModal').style.display = 'none';
}

function showRejectModal(id) {
    document.getElementById('rejectForm').action = '<?= \App\Core\View::url('admin/seller/withdraws/reject/') ?>' + id;
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').style.display = 'none';
}

function showCompleteModal(id) {
    document.getElementById('completeForm').action = '<?= \App\Core\View::url('admin/seller/withdraws/complete/') ?>' + id;
    document.getElementById('completeModal').classList.remove('hidden');
    document.getElementById('completeModal').style.display = 'flex';
}

function hideCompleteModal() {
    document.getElementById('completeModal').classList.add('hidden');
    document.getElementById('completeModal').style.display = 'none';
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

