<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Suppliers Management</h1>
            <p class="text-gray-600 mt-2">Manage your suppliers and their information</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/add-supplier') ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Supplier
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Suppliers</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_suppliers'] ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-truck text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Suppliers</p>
                    <p class="text-2xl font-bold text-green-600"><?= $stats['active_suppliers'] ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Inactive Suppliers</p>
                    <p class="text-2xl font-bold text-red-600"><?= $stats['inactive_suppliers'] ?></p>
                </div>
                <div class="bg-red-100 p-3 rounded-full">
                    <i class="fas fa-times-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">All Suppliers</h2>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" id="searchSuppliers" placeholder="Search suppliers..." 
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($suppliers)): ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="supplier-row hover:bg-gray-50" data-name="<?= strtolower(htmlspecialchars($supplier['supplier_name'])) ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($supplier['supplier_name']) ?></div>
                                        <?php if (!empty($supplier['contact_person'])): ?>
                                            <div class="text-sm text-gray-500">Contact: <?= htmlspecialchars($supplier['contact_person']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php if (!empty($supplier['phone'])): ?>
                                            <div><i class="fas fa-phone mr-2 text-gray-400"></i><?= htmlspecialchars($supplier['phone']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($supplier['email'])): ?>
                                            <div><i class="fas fa-envelope mr-2 text-gray-400"></i><?= htmlspecialchars($supplier['email']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        <?= htmlspecialchars($supplier['address'] ?? 'No address provided') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $supplier['status'];
                                    $statusConfig = [
                                        'active' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                        'inactive' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                                    ];
                                    $statusStyle = $statusConfig[$status] ?? $statusConfig['active'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusStyle[0] ?>">
                                        <i class="<?= $statusStyle[1] ?> mr-1"></i>
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/inventory/edit-supplier/' . $supplier['supplier_id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="toggleSupplierStatus(<?= $supplier['supplier_id'] ?>)" 
                                                class="text-<?= $status === 'active' ? 'red' : 'green' ?>-600 hover:text-<?= $status === 'active' ? 'red' : 'green' ?>-900 transition-colors">
                                            <i class="fas fa-<?= $status === 'active' ? 'ban' : 'check' ?>"></i>
                                        </button>
                                        <button onclick="deleteSupplier(<?= $supplier['supplier_id'] ?>)" 
                                                class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-truck text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No suppliers found</p>
                                <p class="text-sm">Get started by adding your first supplier</p>
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
                        <h3 class="text-lg font-semibold text-gray-900">Delete Supplier</h3>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-6">Are you sure you want to delete this supplier? All associated products and purchases will also be deleted.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="inline">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
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
document.getElementById('searchSuppliers').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.supplier-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Toggle supplier status
function toggleSupplierStatus(supplierId) {
    if (confirm('Are you sure you want to change the supplier status?')) {
        fetch(`<?= \App\Core\View::url('admin/inventory/toggle-supplier-status/') ?>${supplierId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to update supplier status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating supplier status');
        });
    }
}

// Delete supplier
function deleteSupplier(supplierId) {
    document.getElementById('deleteForm').action = `<?= \App\Core\View::url('admin/inventory/delete-supplier/') ?>${supplierId}`;
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

