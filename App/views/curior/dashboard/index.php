<?php
$page = 'dashboard';
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-day text-primary text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Today's Orders</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($todayOrders ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-truck-loading text-warning text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pending Pickups</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($pendingPickups ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shipping-fast text-primary text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($inProgress ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-accent text-2xl"></i>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                        <dd class="text-lg font-medium text-gray-900"><?= count($completed ?? []) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- COD Pending Card -->
<?php if (($codPending ?? 0) > 0): ?>
<div class="bg-gradient-to-r from-primary to-accent rounded-lg shadow-lg p-6 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-white/90 text-sm mb-1">COD Pending Collection</p>
            <p class="text-3xl font-bold">Rs <?= number_format($codPending, 2) ?></p>
        </div>
        <div class="bg-white/20 rounded-full p-4">
            <i class="fas fa-money-bill-wave text-3xl"></i>
        </div>
    </div>
    <a href="<?= \App\Core\View::url('curior/settlements') ?>" class="mt-4 inline-block text-sm underline hover:text-white/90">
        View Settlements →
    </a>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="<?= \App\Core\View::url('curior/pickup') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-warning-50 rounded-full p-3 mr-4">
                <i class="fas fa-box-open text-warning text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Pickup Management</h3>
                <p class="text-sm text-gray-500"><?= count($pendingPickups ?? []) ?> orders waiting</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \App\Core\View::url('curior/orders') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-primary-50 rounded-full p-3 mr-4">
                <i class="fas fa-list text-primary text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">All Orders</h3>
                <p class="text-sm text-gray-500">View all assigned orders</p>
            </div>
        </div>
    </a>
    
    <a href="<?= \App\Core\View::url('curior/returns') ?>" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <div class="bg-error-50 rounded-full p-3 mr-4">
                <i class="fas fa-undo text-error text-xl"></i>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Returns & RTO</h3>
                <p class="text-sm text-gray-500">Manage returns</p>
            </div>
        </div>
    </a>
</div>

<!-- Orders Snapshot -->
<?php $dashboardOrders = array_slice($orders ?? [], 0, 10); ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100" id="dashboard-orders">
    <div class="p-6 border-b border-gray-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Recent Assigned Orders</h3>
            <p class="text-sm text-gray-500">Latest orders assigned to you. Use search to filter quickly.</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
            <div class="relative flex-1 min-w-[220px]">
                <input type="text" 
                       id="dashboardOrdersSearch" 
                       placeholder="Search orders, customer, status..." 
                       class="input native-input"
                       style="padding-left: 2.5rem;">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                </div>
            </div>
            <a href="<?= \App\Core\View::url('curior/orders') ?>" class="btn btn-outline">
                View All
            </a>
        </div>
    </div>
    
    <?php if (empty($dashboardOrders)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-gray-300 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders assigned</h3>
            <p class="text-gray-500">Orders assigned to you will appear here.</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date &amp; Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="dashboardOrdersBody">
                    <?php foreach ($dashboardOrders as $order): ?>
                        <?php
                            $customerName = $order['customer_name'] ?? $order['order_customer_name'] ?? 'N/A';
                            $customerEmail = $order['customer_email'] ?? $order['contact_no'] ?? 'N/A';
                            $invoice = $order['invoice'] ?? '#' . ($order['id'] ?? '');
                            $statusLabel = ucfirst(str_replace('_', ' ', $order['status'] ?? 'pending'));
                            $paymentStatus = ucfirst($order['payment_status'] ?? 'pending');
                            $paymentMethod = strtoupper($order['payment_method'] ?? 'COD');

                            $statusClass = match ($order['status'] ?? 'pending') {
                                'delivered' => 'bg-accent/10 text-accent-dark',
                                'in_transit', 'shipped', 'dispatched', 'picked_up' => 'bg-primary/10 text-primary',
                                'ready_for_pickup', 'processing' => 'bg-warning/10 text-warning-dark',
                                'cancelled' => 'bg-error/10 text-error',
                                default => 'bg-gray-100 text-gray-700'
                            };

                            $paymentClass = ($order['payment_status'] ?? '') === 'paid'
                                ? 'text-success'
                                : 'text-warning';
                        ?>
                        <tr data-dashboard-order
                            data-order-id="<?= htmlspecialchars($order['id']) ?>"
                            data-customer="<?= htmlspecialchars(strtolower($customerName)) ?>"
                            data-status="<?= htmlspecialchars(strtolower($order['status'] ?? '')) ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($invoice) ?></div>
                                <div class="text-xs text-gray-500">#<?= $order['id'] ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($customerName) ?></div>
                                <div class="text-xs text-gray-500"><?= htmlspecialchars($customerEmail) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                Rs <?= number_format($order['total_amount'] ?? 0, 2) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="<?= $paymentClass ?> font-medium"><?= $paymentMethod ?></span>
                                <p class="text-xs text-gray-500"><?= $paymentStatus ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="<?= \App\Core\View::url('curior/order/view/' . $order['id']) ?>" 
                                       class="btn btn-outline text-xs">
                                        View
                                    </a>
                                    <a href="<?= \App\Core\View::url('receipt/previewReceipt/' . $order['id']) ?>" 
                                       target="_blank"
                                       class="btn btn-outline text-xs">
                                        Receipt
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('dashboardOrdersSearch');
    const rows = document.querySelectorAll('#dashboardOrdersBody [data-dashboard-order]');

    if (searchInput && rows.length) {
        searchInput.addEventListener('input', function (event) {
            const query = event.target.value.toLowerCase().trim();
            rows.forEach(row => {
                const orderId = row.getAttribute('data-order-id') || '';
                const customer = row.getAttribute('data-customer') || '';
                const status = row.getAttribute('data-status') || '';

                const matches = !query ||
                    orderId.includes(query) ||
                    customer.includes(query) ||
                    status.includes(query);

                row.style.display = matches ? '' : 'none';
            });
        });
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

