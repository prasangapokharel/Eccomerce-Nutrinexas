<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Customers</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage customer information</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/customers/create') ?>" 
               class="btn">
                <i class="fas fa-plus mr-2"></i>
                Add Customer
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Customers</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $totalCustomers ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-phone text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">With Phone</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= count(array_filter($customers, fn($c) => !empty($c['contact_no']))) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-envelope text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">With Email</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= count(array_filter($customers, fn($c) => !empty($c['email']))) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="GET" action="<?= \App\Core\View::url('admin/customers') ?>" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                           placeholder="Search by name, contact, or email..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <div>
                    <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input type="text" 
                           id="contact_no" 
                           name="contact_no" 
                           value="<?= htmlspecialchars($filters['contact_no'] ?? '') ?>"
                           placeholder="Filter by contact number..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" 
                            class="w-full px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-300 transition-colors">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Address
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                                    <p class="text-gray-500">
                                        <?php if (!empty($filters['search']) || !empty($filters['contact_no'])): ?>
                                            No customers match your search criteria.
                                        <?php else: ?>
                                            No customers have been added yet.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-user text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </div>
                                            <?php if (!empty($customer['email'])): ?>
                                                <div class="text-xs text-gray-500">
                                                    <?= htmlspecialchars($customer['email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($customer['contact_no']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($customer['address']) ?>">
                                        <?= htmlspecialchars($customer['address']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($customer['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('g:i A', strtotime($customer['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/customers/view/' . $customer['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-800 transition-colors p-1 rounded hover:bg-blue-50" 
                                           title="View Customer">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <a href="<?= \App\Core\View::url('admin/customers/edit/' . $customer['id']) ?>" 
                                           class="text-green-600 hover:text-green-800 transition-colors p-1 rounded hover:bg-green-50" 
                                           title="Edit Customer">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <button onclick="confirmDelete(<?= $customer['id'] ?>)" 
                                                class="text-red-600 hover:text-red-800 transition-colors p-1 rounded hover:bg-red-50" 
                                                title="Delete Customer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <?= (($currentPage - 1) * 20) + 1 ?> to <?= min($currentPage * 20, $totalCustomers) ?> of <?= $totalCustomers ?> customers
                </div>
                
                <div class="flex items-center space-x-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= \App\Core\View::url('admin/customers?page=' . ($currentPage - 1) . '&search=' . urlencode($filters['search'] ?? '') . '&contact_no=' . urlencode($filters['contact_no'] ?? '')) ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                        <a href="<?= \App\Core\View::url('admin/customers?page=' . $i . '&search=' . urlencode($filters['search'] ?? '') . '&contact_no=' . urlencode($filters['contact_no'] ?? '')) ?>" 
                           class="px-3 py-2 text-sm font-medium rounded-lg <?= $i === $currentPage ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= \App\Core\View::url('admin/customers?page=' . ($currentPage + 1) . '&search=' . urlencode($filters['search'] ?? '') . '&contact_no=' . urlencode($filters['contact_no'] ?? '')) ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Confirm Deletion</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="deleteModalText">
                    Are you sure you want to delete this customer? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <form id="deleteForm" method="POST" style="display: inline;">
                    <button type="submit" 
                            class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-lg w-24 mr-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Delete
                    </button>
                </form>
                <button id="cancelDeleteBtn" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(customerId) {
    document.getElementById('deleteModalText').textContent = `Are you sure you want to delete this customer? This action cannot be undone.`;
    document.getElementById('deleteForm').action = '<?= \App\Core\View::url('admin/customers/delete/') ?>' + customerId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
    
    // Close modal on outside click
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            deleteModal.classList.add('hidden');
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
