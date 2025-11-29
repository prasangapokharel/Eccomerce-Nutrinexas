<?php
$page = 'orders';
ob_start();
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Assigned Orders</h1>
            <p class="mt-1 text-sm text-gray-500">All orders assigned to you for delivery</p>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Order List</h2>
                </div>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('curior/orders') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($status) || $status === '' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All Orders
                    </a>
                    <a href="<?= \App\Core\View::url('curior/orders?status=pending') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Pending
                    </a>
                    <a href="<?= \App\Core\View::url('curior/orders?status=processing') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Processing
                    </a>
                    <a href="<?= \App\Core\View::url('curior/orders?status=picked_up') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'picked_up' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Picked Up
                    </a>
                    <a href="<?= \App\Core\View::url('curior/orders?status=in_transit') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'in_transit' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        In Transit
                    </a>
                    <a href="<?= \App\Core\View::url('curior/orders?status=delivered') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'delivered' ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Delivered
                    </a>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="mt-4">
                <div class="relative max-w-md">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search orders by invoice, customer name..." 
                           class="input native-input pr-10">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
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
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                                    <p class="text-gray-500">
                                        <?php if (isset($status) && $status): ?>
                                            No orders with status "<?= ucfirst(str_replace('_', ' ', $status)) ?>" found.
                                        <?php else: ?>
                                            Orders will appear here once they are assigned to you.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors order-row" 
                                data-invoice="<?= strtolower(htmlspecialchars($order['invoice'] ?? '')) ?>"
                                data-customer="<?= strtolower(htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? '')) ?>"
                                data-order-id="<?= $order['id'] ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-receipt text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                #<?= htmlspecialchars($order['invoice'] ?? $order['id']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Order ID: <?= $order['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($order['customer_name'] ?? $order['order_customer_name'] ?? $order['user_full_name'] ?? 'Unknown Customer') ?>
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
                                        रु <?= number_format($order['total_amount'] ?? 0, 2) ?>
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
                                        'ready_for_pickup' => 'bg-orange-100 text-orange-800',
                                        'picked_up' => 'bg-primary-50 text-primary-700',
                                        'in_transit' => 'bg-primary-50 text-primary-700',
                                        'shipped' => 'bg-purple-100 text-purple-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusBadge = $statusBadges[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusBadge ?>">
                                        <?= ucfirst(str_replace('_', ' ', $order['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
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
    </div>
</div>

<script>
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.order-row');
    
    rows.forEach(row => {
        const invoice = row.getAttribute('data-invoice') || '';
        const customer = row.getAttribute('data-customer') || '';
        
        if (invoice.includes(searchTerm) || customer.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>
