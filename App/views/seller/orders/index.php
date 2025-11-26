<?php ob_start(); ?>
<?php $page = 'orders'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Orders</h1>
            <p class="mt-1 text-sm text-gray-500">Track and manage all customer orders</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Order List</h2>
                    <button id="bulkPrintBtn" 
                            class="hidden px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors text-sm font-medium"
                            onclick="bulkPrintLabels()">
                        <i class="fas fa-print mr-2"></i>Print Shipping Labels (<span id="selectedCount">0</span>)
                    </button>
                </div>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('seller/orders') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($statusFilter) || $statusFilter === '' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All Orders
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?payment_type=cod') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($_GET['payment_type']) && $_GET['payment_type'] === 'cod' ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        COD
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?payment_type=prepaid') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($_GET['payment_type']) && $_GET['payment_type'] === 'prepaid' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Prepaid
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=pending') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Pending
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=confirmed') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'confirmed' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Confirmed
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=processing') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Processing
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=shipped') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'shipped' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Shipped
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=delivered') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'delivered' ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Delivered
                    </a>
                    <a href="<?= \App\Core\View::url('seller/orders?status=cancelled') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Cancelled
                    </a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="mt-4">
                <div class="relative max-w-md">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search orders by invoice, customer name..." 
                           class="input native-input"
                           style="padding-left: 2.5rem;">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto -mx-4 px-4">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 40px;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order Details
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Time
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Payment
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="ordersTableBody">
                    <?php if (empty($orders)): ?>
                        <tr id="noOrdersRow">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                    <p class="text-gray-500">
                                        <?php if (isset($statusFilter) && $statusFilter): ?>
                                            No orders with status "<?= ucfirst($statusFilter) ?>" found.
                                        <?php else: ?>
                                            Orders will appear here once customers place orders.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors order-row" 
                                data-invoice="<?= strtolower(htmlspecialchars($order['invoice'] ?? '')) ?>"
                                data-customer="<?= strtolower(htmlspecialchars($order['customer_name'] ?? ($order['first_name'] . ' ' . $order['last_name']))) ?>"
                                data-order-id="<?= $order['id'] ?>">
                                <td class="px-6 py-4">
                                    <input type="checkbox" 
                                           class="order-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" 
                                           value="<?= $order['id'] ?>"
                                           onchange="updateBulkPrintButton()">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-receipt text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                #<?= htmlspecialchars($order['invoice'] ?? 'N/A') ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Order ID: <?= $order['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($order['customer_name'] ?? ($order['first_name'] . ' ' . $order['last_name']) ?? 'Unknown Customer') ?>
                                    </div>
                                    <?php if (!empty($order['customer_email'] ?? $order['email'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($order['customer_email'] ?? $order['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($order['contact_no'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($order['contact_no']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('g:i A', strtotime($order['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        रु <?= number_format($order['seller_total'] ?? 0, 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= ($order['payment_status'] ?? '') === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                        <?= ucfirst($order['payment_status'] ?? 'Pending') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusBadges = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'confirmed' => 'bg-blue-100 text-blue-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-purple-100 text-purple-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusBadge = $statusBadges[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= \App\Core\View::url('seller/orders/detail/' . $order['id']) ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors"
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

        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?= ($currentPage - 1) * 20 + 1 ?> to <?= min($currentPage * 20, $total) ?> of <?= $total ?> orders
                </div>
                <div class="flex gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>" class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?>" class="btn btn-outline">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const orderRows = document.querySelectorAll('.order-row');
    const noOrdersRow = document.getElementById('noOrdersRow');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;

            orderRows.forEach(row => {
                const invoice = row.dataset.invoice || '';
                const customer = row.dataset.customer || '';
                
                if (invoice.includes(searchTerm) || customer.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && orderRows.length > 0) {
                noOrdersRow.style.display = '';
            } else {
                noOrdersRow.style.display = 'none';
            }
        });
    }
});

// Bulk selection functions
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkPrintButton();
}

function updateBulkPrintButton() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const btn = document.getElementById('bulkPrintBtn');
    const count = document.getElementById('selectedCount');
    
    if (checked.length > 0) {
        btn.classList.remove('hidden');
        count.textContent = checked.length;
    } else {
        btn.classList.add('hidden');
    }
}

function bulkPrintLabels() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const orderIds = Array.from(checked).map(cb => cb.value);
    
    if (orderIds.length === 0) {
        alert('Please select at least one order');
        return;
    }
    
    // Open bulk print page in new window
    const url = '<?= \App\Core\View::url('seller/orders/bulk-print-labels') ?>?ids=' + orderIds.join(',');
    window.open(url, '_blank');
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
