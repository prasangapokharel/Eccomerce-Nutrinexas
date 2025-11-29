<?php ob_start(); ?>
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Site-Wide Sales Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage site-wide sales campaigns and apply discounts to all products</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/sales/create') ?>" class="btn">
            <i class="fas fa-plus mr-2"></i>
            Create New Sale
        </a>
    </div>

    <!-- Active Sale Alert -->
    <?php if (!empty($activeSale)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-fire text-green-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-green-800">
                        Active Sale: <?= htmlspecialchars($activeSale['sale_name']) ?>
                    </h3>
                    <p class="mt-1 text-sm text-green-700">
                        <?= number_format($activeSale['sale_percent'] ?? $activeSale['discount_percent'] ?? 0, 0) ?>% OFF - 
                        Ends: <?= date('M j, Y g:i A', strtotime($activeSale['end_date'])) ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Sales Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sale Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-tags text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No sales created yet</h3>
                                    <p class="text-gray-500 mb-4">Create your first site-wide sale to apply discounts to all products</p>
                                    <a href="<?= \App\Core\View::url('admin/sales/create') ?>" class="btn">
                                        <i class="fas fa-plus mr-2"></i>
                                        Create Sale
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($sale['sale_name']) ?></div>
                                    <?php if (!empty($sale['note'])): ?>
                                        <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($sale['note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-red-100 text-red-800 text-sm font-medium rounded">
                                        <?= number_format($sale['sale_percent'] ?? $sale['discount_percent'] ?? 0, 0) ?>% OFF
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= date('M j, Y g:i A', strtotime($sale['start_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    <?= date('M j, Y g:i A', strtotime($sale['end_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $now = date('Y-m-d H:i:s');
                                    $isActive = !empty($sale['is_active']) && 
                                               !empty($sale['start_date']) &&
                                               !empty($sale['end_date']) &&
                                               $sale['start_date'] <= $now && 
                                               $sale['end_date'] >= $now;
                                    $isExpired = !empty($sale['end_date']) && $sale['end_date'] < $now;
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                        $isActive ? 'bg-green-100 text-green-800' : 
                                        ($isExpired ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800')
                                    ?>">
                                        <?= $isActive ? 'Active' : ($isExpired ? 'Expired' : 'Scheduled') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/sales/edit/' . $sale['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="toggleSaleStatus(<?= $sale['id'] ?>)" 
                                                class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-toggle-<?= $sale['is_active'] ? 'on' : 'off' ?>"></i>
                                        </button>
                                        <form method="POST" action="<?= \App\Core\View::url('admin/sales/delete/' . $sale['id']) ?>" 
                                              onsubmit="return confirm('Are you sure you want to delete this sale? This will remove the discount from all products.')" 
                                              class="inline">
                                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<script>
function toggleSaleStatus(saleId) {
    fetch('<?= \App\Core\View::url('admin/sales/toggle/') ?>' + saleId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _csrf_token: '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update sale status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating sale status');
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

