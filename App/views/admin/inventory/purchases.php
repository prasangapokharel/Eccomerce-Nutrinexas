<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Purchases Management</h1>
            <p class="text-gray-600 mt-2">Track and manage your wholesale purchases</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/add-purchase') ?>" class="btn">
            <i class="fas fa-plus mr-2"></i>Add Purchase
        </a>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Purchases</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_purchases'] ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-orange-600"><?= $stats['pending_purchases'] ?></p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Paid</p>
                    <p class="text-2xl font-bold text-green-600"><?= $stats['paid_purchases'] ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Outstanding</p>
                    <p class="text-2xl font-bold text-red-600">Rs <?= number_format($stats['outstanding_amount'] ?? 0, 2) ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">All Purchases</h2>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" id="searchPurchases" placeholder="Search purchases..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($purchases)): ?>
                        <?php foreach ($purchases as $purchase): ?>
                            <tr class="purchase-row hover:bg-gray-50" data-name="<?= strtolower(htmlspecialchars($purchase['product_name'])) ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($purchase['product_name']) ?></div>
                                        <?php if (!empty($purchase['sku'])): ?>
                                            <div class="text-sm text-gray-500">SKU: <?= htmlspecialchars($purchase['sku']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($purchase['supplier_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= number_format($purchase['quantity']) ?> units</div>
                                    <div class="text-sm text-gray-500">@ Rs <?= number_format($purchase['unit_cost'], 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium">Rs <?= number_format($purchase['total_amount'], 2) ?></div>
                                        <?php if ($purchase['paid_amount'] > 0): ?>
                                            <div class="text-xs text-green-600">Paid: Rs <?= number_format($purchase['paid_amount'], 2) ?></div>
                                        <?php endif; ?>
                                        <?php if ($purchase['remaining_amount'] > 0): ?>
                                            <div class="text-xs text-red-600">Due: Rs <?= number_format($purchase['remaining_amount'], 2) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $purchase['status'];
                                    $statusConfig = [
                                        'pending' => ['bg-orange-100 text-orange-800', 'fas fa-clock'],
                                        'paid' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                        'partial' => ['bg-yellow-100 text-yellow-800', 'fas fa-exclamation-circle'],
                                        'remaining' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                                    ];
                                    $statusStyle = $statusConfig[$status] ?? $statusConfig['pending'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusStyle[0] ?>">
                                        <i class="<?= $statusStyle[1] ?> mr-1"></i>
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= date('M d, Y', strtotime($purchase['purchase_date'])) ?></div>
                                    <?php if (!empty($purchase['expected_delivery'])): ?>
                                        <div class="text-xs text-gray-500">Expected: <?= date('M d, Y', strtotime($purchase['expected_delivery'])) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/inventory/edit-purchase/' . $purchase['purchase_id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deletePurchase(<?= $purchase['purchase_id'] ?>)" 
                                                class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No purchases found</p>
                                <p class="text-sm">Get started by adding your first purchase</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Delete Purchase</h3>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-6">Are you sure you want to delete this purchase? All associated payments will also be deleted.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="btn">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="inline">
                        <button type="submit" class="btn">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchPurchases').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.purchase-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete purchase
function deletePurchase(purchaseId) {
    document.getElementById('deleteForm').action = `<?= \App\Core\View::url('admin/inventory/delete-purchase/') ?>${purchaseId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>