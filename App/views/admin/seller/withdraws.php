<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Seller Withdrawals</h1>
            <p class="mt-1 text-sm text-gray-500">
                <?php if (isset($seller)): ?>
                    <?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?>
                <?php else: ?>
                    All seller withdrawal requests
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-3">
            <?php if (isset($seller)): ?>
                <a href="<?= \App\Core\View::url('admin/seller/details/' . $seller['id']) ?>" 
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Seller
                </a>
            <?php endif; ?>
            <a href="<?= \App\Core\View::url('admin/seller/withdraws') ?>" 
               class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-list mr-2"></i>All Withdrawals
            </a>
        </div>
    </div>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($withdraws as $withdraw) {
        $tableData[] = [
            'id' => $withdraw['id'],
            'withdraw' => $withdraw,
            'amount' => $withdraw['amount'],
            'account_holder_name' => $withdraw['account_holder_name'] ?? 'N/A',
            'bank_name' => $withdraw['bank_name'] ?? '',
            'status' => $withdraw['status'],
            'requested_at' => $withdraw['requested_at']
        ];
    }

    $tableConfig = [
        'id' => 'sellerWithdrawsTable',
        'title' => 'Withdrawal Requests',
        'description' => 'Manage seller withdrawal requests',
        'search' => true,
        'columns' => [
            [
                'key' => 'id',
                'label' => 'ID',
                'type' => 'text'
            ],
            [
                'key' => 'amount',
                'label' => 'Amount',
                'type' => 'currency'
            ],
            [
                'key' => 'account_holder_name',
                'label' => 'Bank Account',
                'type' => 'custom',
                'render' => function($row) {
                    $withdraw = $row['withdraw'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900"><?= htmlspecialchars($withdraw['account_holder_name'] ?? 'N/A') ?></div>
                    <?php if (!empty($withdraw['bank_name'])): ?>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($withdraw['bank_name']) ?></div>
                    <?php endif; ?>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'badge',
                'badgeConfig' => [
                    'pending' => 'warning',
                    'approved' => 'info',
                    'rejected' => 'danger',
                    'completed' => 'success'
                ]
            ],
            [
                'key' => 'requested_at',
                'label' => 'Requested',
                'type' => 'date'
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    $withdraw = $row['withdraw'];
                    $status = $withdraw['status'];
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <?php if ($status === 'pending'): ?>
                            <button onclick="showApproveModal(<?= $withdraw['id'] ?>)" 
                                    class="text-green-600 hover:text-green-900 hover:bg-green-50 transition-colors p-1 rounded" 
                                    title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="showRejectModal(<?= $withdraw['id'] ?>)" 
                                    class="text-red-600 hover:text-red-900 hover:bg-red-50 transition-colors p-1 rounded" 
                                    title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php elseif ($status === 'approved'): ?>
                            <button onclick="showCompleteModal(<?= $withdraw['id'] ?>)" 
                                    class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                                    title="Mark Complete">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/seller/withdraws')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<!-- Approve Modal -->
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

<!-- Reject Modal -->
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

<!-- Complete Modal -->
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
let currentWithdrawId = null;

function showApproveModal(id) {
    currentWithdrawId = id;
    const form = document.getElementById('approveForm');
    form.action = '<?= \App\Core\View::url('admin/seller/withdraws/approve') ?>/' + id;
    document.getElementById('approveModal').style.display = 'flex';
}

function hideApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
    document.getElementById('approveForm').reset();
}

function showRejectModal(id) {
    currentWithdrawId = id;
    const form = document.getElementById('rejectForm');
    form.action = '<?= \App\Core\View::url('admin/seller/withdraws/reject') ?>/' + id;
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectForm').reset();
}

function showCompleteModal(id) {
    currentWithdrawId = id;
    const form = document.getElementById('completeForm');
    form.action = '<?= \App\Core\View::url('admin/seller/withdraws/complete') ?>/' + id;
    document.getElementById('completeModal').style.display = 'flex';
}

function hideCompleteModal() {
    document.getElementById('completeModal').style.display = 'none';
    document.getElementById('completeForm').reset();
}

// Close modals on outside click
document.getElementById('approveModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideApproveModal();
});
document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideRejectModal();
});
document.getElementById('completeModal')?.addEventListener('click', function(e) {
    if (e.target === this) hideCompleteModal();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
