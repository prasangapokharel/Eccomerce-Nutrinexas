<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Manage Orders</h1>
            <p class="mt-1 text-sm text-gray-500">Track and manage all customer orders</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/orders/create') ?>" 
               class="btn">
                <i class="fas fa-plus mr-2"></i>
                Create Order
            </a>
            
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $stats = [
            'total' => count($orders),
            'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
            'paid' => count(array_filter($orders, fn($o) => $o['payment_status'] === 'paid')),
            'delivered' => count(array_filter($orders, fn($o) => $o['status'] === 'delivered')),
        ];
        $totalRevenue = array_sum(array_map(fn($o) => $o['payment_status'] === 'paid' ? ($o['calculated_total'] ?? $o['total_amount']) : 0, $orders));
        ?>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-shopping-cart text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-clock text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['pending'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Completed</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['delivered'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-rupee-sign text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Revenue</p>
                    <h3 class="text-xl font-bold text-gray-900">रु <?= number_format($totalRevenue, 0) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center">
                    <input id="selectAllOrders" type="checkbox" class="form-checkbox h-4 w-4 text-primary">
                    <span class="ml-2 text-sm text-gray-700">Select All</span>
                </label>
                <span id="selectedOrdersCount" class="text-xs text-gray-500">0 selected</span>
            </div>
            <div class="flex flex-wrap gap-2">
                <button onclick="bulkUpdateOrdersStatus('paid')" class="px-3 py-1 bg-green-100 text-green-700 rounded text-sm hover:bg-green-200">Mark Paid</button>
                <button onclick="bulkUpdateOrdersStatus('processing')" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm hover:bg-blue-200">Processing</button>
                <button onclick="bulkUpdateOrdersStatus('shipped')" class="px-3 py-1 bg-purple-100 text-purple-700 rounded text-sm hover:bg-purple-200">Shipped</button>
                <button onclick="bulkUpdateOrdersStatus('delivered')" class="px-3 py-1 bg-green-200 text-green-800 rounded text-sm hover:bg-green-300">Delivered</button>
                <button onclick="bulkUpdateOrdersStatus('cancelled')" class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200">Cancel</button>
                <button onclick="bulkDeleteOrders()" class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">Delete Selected</button>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-900">Order List</h2>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('admin/orders') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($status) ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All Orders
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=pending') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Pending
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=processing') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'processing' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Processing
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=paid') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'paid' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Paid
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=shipped') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'shipped' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Shipped
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=delivered') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'delivered' ? 'bg-green-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Delivered
                    </a>
                    <a href="<?= \App\Core\View::url('admin/orders?status=cancelled') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($status) && $status === 'cancelled' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
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
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                           style="appearance: none; -webkit-appearance: none; border-radius: 0.5rem; -webkit-border-radius: 0.5rem;">
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
                        <th scope="col" class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAllHeader" class="h-4 w-4 text-primary">
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
                                        <?php if (isset($status)): ?>
                                            No orders with status "<?= ucfirst($status) ?>" found.
                                        <?php else: ?>
                                            No orders have been placed yet.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50 transition-colors order-row" data-order-id="<?= $order['id'] ?>" 
                                data-invoice="<?= strtolower(htmlspecialchars($order['invoice'] ?? '')) ?>"
                                data-customer="<?= strtolower(htmlspecialchars($order['customer_name'] ?? '')) ?>">
                                <td class="px-4 py-4">
                                    <input type="checkbox" class="order-checkbox h-4 w-4" value="<?= $order['id'] ?>">
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
                                        <?= htmlspecialchars($order['customer_name'] ?? 'Unknown Customer') ?>
                                    </div>
                                    <?php if (!empty($order['customer_email'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($order['customer_email']) ?>
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
                                        रु <?= number_format($order['calculated_total'] ?? $order['total_amount'], 2) ?>
                                    </div>
                                    <?php if ($order['delivery_fee'] > 0): ?>
                                        <div class="text-xs text-gray-500">
                                            (+ रु <?= number_format($order['delivery_fee'], 2) ?> delivery)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($order['payment_method'])): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-credit-card mr-1"></i>
                                            <?= htmlspecialchars($order['payment_method']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="fas fa-question mr-1"></i>
                                            Not Set
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    // Use payment_status to determine if order is paid
                                    $displayStatus = $order['payment_status'] === 'paid' ? 'Paid' : ucfirst($order['status']);
                                    $statusConfig = [
                                        'paid' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                        'processing' => ['bg-blue-100 text-blue-800', 'fas fa-cog'],
                                        'pending' => ['bg-yellow-100 text-yellow-800', 'fas fa-clock'],
                                        'unpaid' => ['bg-orange-100 text-orange-800', 'fas fa-exclamation-triangle'],
                                        'cancelled' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                                        'shipped' => ['bg-purple-100 text-purple-800', 'fas fa-shipping-fast'],
                                        'delivered' => ['bg-green-100 text-green-800', 'fas fa-check-double'],
                                        'confirmed' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                    ];
                                    
                                    // Determine config based on payment_status for paid orders, otherwise use status
                                    $statusKey = $order['payment_status'] === 'paid' ? 'paid' : $order['status'];
                                    $config = $statusConfig[$statusKey] ?? ['bg-gray-100 text-gray-800', 'fas fa-question'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $config[0] ?>">
                                        <i class="<?= $config[1] ?> mr-1"></i>
                                        <?= $displayStatus ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <!-- View Order -->
                                        <a href="<?= \App\Core\View::url('admin/viewOrder/' . $order['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-800 transition-colors p-1 rounded hover:bg-blue-50" 
                                           title="View Order Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <!-- Download Receipt -->
                                        <a href="<?= \App\Core\View::url('receipt/downloadReceipt/' . $order['id']) ?>" 
                                           class="text-green-600 hover:text-green-800 transition-colors p-1 rounded hover:bg-green-50" 
                                           title="Download Receipt"
                                           target="_blank">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        
                                        <!-- Preview Receipt -->
                                        <a href="<?= \App\Core\View::url('receipt/previewReceipt/' . $order['id']) ?>" 
                                           class="text-purple-600 hover:text-purple-800 transition-colors p-1 rounded hover:bg-purple-50" 
                                           title="Preview Receipt"
                                           target="_blank">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        
                                        <!-- Quick Actions Dropdown -->
                                        <div class="relative">
                                            <button onclick="toggleActionMenu(<?= $order['id'] ?>)" 
                                                    class="text-gray-600 hover:text-gray-800 transition-colors p-1 rounded hover:bg-gray-50" 
                                                    title="More Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div id="actionMenu<?= $order['id'] ?>" 
                                                 class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-20 border border-gray-200">
                                                <?php if (in_array($order['status'], ['pending', 'processing', 'unpaid'])): ?>
                                                    <button onclick="updateStatus(<?= $order['id'] ?>, 'paid')" 
                                                            class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 transition-colors">
                                                        <i class="fas fa-check mr-2"></i>Mark as Paid
                                                    </button>
                                                    <button onclick="updateStatus(<?= $order['id'] ?>, 'cancelled')" 
                                                            class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 transition-colors">
                                                        <i class="fas fa-times mr-2"></i>Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] === 'paid'): ?>
                                                    <button onclick="updateStatus(<?= $order['id'] ?>, 'shipped')" 
                                                            class="w-full text-left px-4 py-2 text-sm text-purple-700 hover:bg-purple-50 transition-colors">
                                                        <i class="fas fa-shipping-fast mr-2"></i>Mark as Shipped
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($order['status'] === 'shipped'): ?>
                                                    <button onclick="updateStatus(<?= $order['id'] ?>, 'delivered')" 
                                                            class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 transition-colors">
                                                        <i class="fas fa-check-circle mr-2"></i>Mark as Delivered
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Delete button for all orders -->
                                                <hr class="my-1 border-gray-200">
                                                <button onclick="confirmDelete(<?= $order['id'] ?>)" 
                                                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                    <i class="fas fa-trash mr-2"></i>Delete Order
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <?php if (!empty($orders)): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-sm text-gray-700">
                    Showing <?= count($orders) ?> orders
                    <?php if (isset($status)): ?>
                        with status "<?= ucfirst($status) ?>"
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-6 text-sm text-gray-500">
                    <span>Total Revenue: Rs<?= number_format($totalRevenue, 2) ?></span>
                    <span>•</span>
                    <span>Avg Order: Rs<?= $stats['total'] > 0 ? number_format($totalRevenue / $stats['total'], 2) : '0.00' ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <i class="fas fa-edit text-blue-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Update Order Status</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="statusModalText">
                    Are you sure you want to update this order status?
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmStatusBtn" 
                        class="px-4 py-2 bg-primary text-white text-base font-medium rounded-lg w-24 mr-2 hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-300">
                    Update
                </button>
                <button id="cancelStatusBtn" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
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
                    Are you sure you want to delete this order? This action cannot be undone.
                </p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirmDeleteBtn" 
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-lg w-24 mr-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Delete
                </button>
                <button id="cancelDeleteBtn" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let orderToUpdate = null;
let statusToUpdate = null;

function updateStatus(orderId, status) {
    const statusMessages = {
        'paid': 'mark this order as PAID',
        'cancelled': 'CANCEL this order',
        'shipped': 'mark this order as SHIPPED',
        'delivered': 'mark this order as DELIVERED',
        'processing': 'mark this order as PROCESSING'
    };
    
    orderToUpdate = orderId;
    statusToUpdate = status;
    
    const message = statusMessages[status] || `update this order status to ${status}`;
    document.getElementById('statusModalText').textContent = `Are you sure you want to ${message}?`;
    document.getElementById('statusModal').classList.remove('hidden');
    
    // Close action menu
    const actionMenu = document.getElementById(`actionMenu${orderId}`);
    if (actionMenu) {
        actionMenu.classList.add('hidden');
    }
}

function toggleActionMenu(orderId) {
    const menu = document.getElementById(`actionMenu${orderId}`);
    const allMenus = document.querySelectorAll('[id^="actionMenu"]');
    
    // Close all other menus
    allMenus.forEach(m => {
        if (m.id !== `actionMenu${orderId}`) {
            m.classList.add('hidden');
        }
    });
    
    // Toggle current menu
    menu.classList.toggle('hidden');
}

function confirmDelete(orderId) {
    document.getElementById('deleteModalText').textContent = `Are you sure you want to delete order #${orderId}? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('confirmDeleteBtn').onclick = () => {
        // Create and submit a POST form for deletion
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= \App\Core\View::url('admin/deleteOrder/') ?>' + orderId;
        document.body.appendChild(form);
        form.submit();
    };
}

function exportOrders() {
    // Implement export functionality
    alert('Export functionality will be implemented soon!');
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusModal = document.getElementById('statusModal');
    const confirmStatusBtn = document.getElementById('confirmStatusBtn');
    const cancelStatusBtn = document.getElementById('cancelStatusBtn');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.order-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const invoice = row.dataset.invoice;
            const customer = row.dataset.customer;
            
            const matches = !searchTerm || 
                           invoice.includes(searchTerm) || 
                           customer.includes(searchTerm);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show no results message
        if (visibleCount === 0 && rows.length > 0) {
            if (!document.getElementById('noResultsRow')) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No orders found</h3>
                            <p class="text-gray-500">Try adjusting your search criteria.</p>
                        </div>
                    </td>
                `;
                document.getElementById('ordersTableBody').appendChild(noResultsRow);
            }
        } else {
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    });
    
    // Modal handlers
    confirmStatusBtn.addEventListener('click', function() {
        if (orderToUpdate && statusToUpdate) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/updateOrderStatus/') ?>' + orderToUpdate;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = statusToUpdate;
            
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelStatusBtn.addEventListener('click', function() {
        statusModal.classList.add('hidden');
        orderToUpdate = null;
        statusToUpdate = null;
    });

    confirmDeleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
        orderToUpdate = null; // Clear orderToUpdate for status updates
        statusToUpdate = null; // Clear statusToUpdate for status updates
    });

    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
        orderToUpdate = null; // Clear orderToUpdate for status updates
        statusToUpdate = null; // Clear statusToUpdate for status updates
    });
    
    // Close modal on outside click
    statusModal.addEventListener('click', function(e) {
        if (e.target === statusModal) {
            statusModal.classList.add('hidden');
            orderToUpdate = null;
            statusToUpdate = null;
        }
    });

    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
            orderToUpdate = null; // Clear orderToUpdate for status updates
            statusToUpdate = null; // Clear statusToUpdate for status updates
        }
    });
    
    // Close action menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[onclick*="toggleActionMenu"]') && !e.target.closest('[id^="actionMenu"]')) {
            document.querySelectorAll('[id^="actionMenu"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            statusModal.classList.add('hidden');
            orderToUpdate = null;
            statusToUpdate = null;
            deleteModal.classList.add('hidden');
            document.querySelectorAll('[id^="actionMenu"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });
});
</script>

<style>
/* iOS Safari specific fixes */
input[type="text"], 
input[type="search"], 
select, 
textarea {
    appearance: none;
    -webkit-appearance: none;
    border-radius: 0.5rem;
    -webkit-border-radius: 0.5rem;
    font-size: 16px;
}

/* Smooth transitions */
.order-row {
    transition: background-color 0.15s ease-in-out;
}

/* Mobile responsive table */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}

/* Action menu positioning */
.relative {
    position: relative;
}

/* Loading states */
.loading {
    pointer-events: none;
    opacity: 0.6;
}
</style>

<script>
// Notification function
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Bulk selection and actions for Orders
function bulkUpdateOrdersStatus(status) {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showNotification('Please select at least one order', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to set ${checkedBoxes.length} order${checkedBoxes.length > 1 ? 's' : ''} to "${status}"?`)) {
        return;
    }

    const orderIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    fetch('<?= \App\Core\View::url('admin/orders/bulkUpdate') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            order_ids: orderIds,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Failed to update orders', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating orders', 'error');
    });
}

// Bulk delete selected orders
function bulkDeleteOrders() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showNotification('Please select at least one order', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} order${checkedBoxes.length > 1 ? 's' : ''}? This action cannot be undone.`)) {
        return;
    }

    const orderIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    fetch('<?= \App\Core\View::url('admin/orders/bulkUpdate') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            order_ids: orderIds,
            action: 'delete'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Remove deleted rows from table
            orderIds.forEach(id => {
                const row = document.querySelector(`tr[data-order-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        const remainingRows = document.querySelectorAll('.order-row');
                        if (remainingRows.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            });
        } else {
            showNotification(data.message || 'Failed to delete orders', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while deleting orders', 'error');
    });
}

// Keep selected count updated
function updateOrderBulkActionState() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const countEl = document.getElementById('selectedOrdersCount');
    if (countEl) countEl.textContent = `${checkedBoxes.length} selected`;
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.order-checkbox').forEach(cb => {
        cb.addEventListener('change', updateOrderBulkActionState);
    });

    const selectAllOrders = document.getElementById('selectAllOrders');
    if (selectAllOrders) {
        selectAllOrders.addEventListener('change', function() {
            document.querySelectorAll('.order-checkbox').forEach(cb => { cb.checked = selectAllOrders.checked; });
            updateOrderBulkActionState();
        });
    }

    const selectAllHeader = document.getElementById('selectAllHeader');
    if (selectAllHeader) {
        selectAllHeader.addEventListener('change', function() {
            document.querySelectorAll('.order-checkbox').forEach(cb => { cb.checked = selectAllHeader.checked; });
            if (selectAllOrders) { selectAllOrders.checked = selectAllHeader.checked; }
            updateOrderBulkActionState();
        });
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>