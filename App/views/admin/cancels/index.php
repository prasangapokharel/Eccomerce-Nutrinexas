<?php ob_start(); ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Order Cancellations</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p class="text-red-800 text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($cancels)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 text-center">
            <div class="text-gray-400 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">No cancellation requests</h2>
            <p class="text-gray-500">There are no order cancellation requests at the moment.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($cancels as $cancel): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?= $cancel['order_id'] ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($cancel['invoice'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($cancel['customer_name'] ?? 'N/A') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($cancel['customer_email'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cancel['seller_name'] ?? 'N/A') ?></div>
                                    <?php if (!empty($cancel['company_name'])): ?>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($cancel['company_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">Rs <?= number_format($cancel['total_amount'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($cancel['reason'] ?? '') ?>">
                                        <?= htmlspecialchars($cancel['reason'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'refunded' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$cancel['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst($cancel['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= !empty($cancel['created_at']) ? date('M j, Y H:i', strtotime($cancel['created_at'])) : 'N/A' ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <select onchange="updateCancelStatus(<?= $cancel['id'] ?>, this.value)" 
                                                class="text-xs border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-primary focus:border-primary">
                                            <option value="processing" <?= $cancel['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="refunded" <?= $cancel['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                                            <option value="failed" <?= $cancel['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                                        </select>
                                        <a href="<?= \App\Core\View::url('admin/cancels/view/' . $cancel['id']) ?>" 
                                           class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing <?= (($currentPage - 1) * $perPage) + 1 ?> to <?= min($currentPage * $perPage, $totalCount) ?> of <?= $totalCount ?> cancellations
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= \App\Core\View::url('admin/cancels?page=' . ($currentPage - 1)) ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                            <a href="<?= \App\Core\View::url('admin/cancels?page=' . $i) ?>" 
                               class="px-3 py-2 text-sm font-medium rounded-lg <?= $i === $currentPage ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= \App\Core\View::url('admin/cancels?page=' . ($currentPage + 1)) ?>" 
                               class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function updateCancelStatus(cancelId, status) {
    if (!confirm('Are you sure you want to update the status to ' + status + '?')) {
        location.reload();
        return;
    }
    
    const formData = new FormData();
    formData.append('status', status);
    
    fetch('<?= \App\Core\View::url('admin/cancels/update/') ?>' + cancelId, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        } else {
            alert('Failed to update status');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
        location.reload();
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

