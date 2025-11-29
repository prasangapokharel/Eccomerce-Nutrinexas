<?php ob_start(); ?>
<?php $page = 'customers'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Customers</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage your customers</p>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-900">Customer List</h2>
                
                <!-- Standard Top Bar: Search, Filter, Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 sm:flex-initial sm:w-64">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search customers by name, email..." 
                               class="input native-input pr-10">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Phone
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Orders
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Spent
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="customersTableBody">
                    <?php if (empty($customers)): ?>
                        <tr id="noCustomersRow">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                                    <p class="text-gray-500">Customers will appear here once they place orders</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50 customer-row" 
                                data-name="<?= strtolower(htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''))) ?>"
                                data-email="<?= strtolower(htmlspecialchars($customer['email'] ?? '')) ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-user text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['email'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= number_format($customer['total_orders'] ?? 0) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">Rs<?= number_format($customer['total_spent'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= \App\Core\View::url('seller/customers/detail/' . $customer['id']) ?>" 
                                       class="text-primary hover:text-primary-dark"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const customerRows = document.querySelectorAll('.customer-row');
    const noCustomersRow = document.getElementById('noCustomersRow');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;

            customerRows.forEach(row => {
                const name = row.dataset.name || '';
                const email = row.dataset.email || '';
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && customerRows.length > 0) {
                noCustomersRow.style.display = '';
            } else {
                noCustomersRow.style.display = 'none';
            }
        });
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
