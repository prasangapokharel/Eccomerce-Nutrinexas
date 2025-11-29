<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?></h1>
            <p class="text-gray-600"><?= htmlspecialchars($seller['name']) ?></p>
        </div>
        <div class="flex gap-3">
            <a href="<?= \App\Core\View::url('admin/seller/withdraws/' . $seller['id']) ?>" 
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-money-bill-wave mr-2"></i>Withdrawals
            </a>
            <a href="<?= \App\Core\View::url('admin/seller/edit/' . $seller['id']) ?>" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <a href="<?= \App\Core\View::url('admin/seller') ?>" 
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <!-- Approval & Status Section -->
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($seller['company_name'] ?? $seller['name']) ?></h2>
            </div>
            <div class="flex items-center gap-3">
                <?php if (!$seller['is_approved']): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <i class="fas fa-clock mr-2"></i>Pending Approval
                    </span>
                    <form action="<?= \App\Core\View::url('admin/seller/approve/' . $seller['id']) ?>" method="POST" class="inline">
                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                        <button type="submit" class="bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-check mr-1"></i>Approve
                        </button>
                    </form>
                    <button onclick="showRejectModal()" class="bg-red-600 text-white px-4 py-1.5 rounded-lg hover:bg-red-700 text-sm">
                        <i class="fas fa-times mr-1"></i>Reject
                    </button>
                <?php else: ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-2"></i>Approved
                    </span>
                <?php endif; ?>
                <?php
                $status = $seller['status'] ?? 'active';
                $statusColors = [
                    'active' => 'bg-green-100 text-green-800',
                    'suspended' => 'bg-red-100 text-red-800',
                    'inactive' => 'bg-gray-100 text-gray-800'
                ];
                $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColor ?>">
                    <?= ucfirst($status) ?>
                </span>
            </div>
        </div>
        <?php if (!$seller['is_approved'] && !empty($seller['rejection_reason'])): ?>
            <div class="mt-3 pt-3 border-t border-gray-200">
                <p class="text-sm text-gray-600"><strong>Previous Rejection Reason:</strong> <?= htmlspecialchars($seller['rejection_reason']) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Full Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['email']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['phone'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Company Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['company_name'] ?? 'N/A') ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-500">Address</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['address'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $seller['status'] === 'active' ? 'bg-green-100 text-green-800' : ($seller['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') ?>">
                                <?= ucfirst($seller['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Commission Rate</label>
                        <p class="text-gray-900"><?= number_format($seller['commission_rate'] ?? 10.00, 2) ?>%</p>
                    </div>
                </div>
            </div>

            <!-- Documents -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Documents</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Business Logo</label>
                        <?php if (!empty($seller['logo_url'])): ?>
                            <div class="mt-2">
                                <img src="<?= htmlspecialchars($seller['logo_url']) ?>" alt="Business Logo" class="h-24 w-24 object-cover rounded-lg border border-gray-200">
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm mt-1">Not provided</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Citizenship Document</label>
                        <?php if (!empty($seller['citizenship_document_url'])): ?>
                            <div class="mt-2">
                                <a href="<?= htmlspecialchars($seller['citizenship_document_url']) ?>" target="_blank" class="text-primary hover:underline">
                                    <i class="fas fa-external-link-alt mr-2"></i>View Document
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm mt-1">Not provided</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">PAN/VAT Type</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['pan_vat_type'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">PAN/VAT Number</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['pan_vat_number'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">PAN/VAT Document</label>
                        <?php if (!empty($seller['pan_vat_document_url'])): ?>
                            <div class="mt-2">
                                <a href="<?= htmlspecialchars($seller['pan_vat_document_url']) ?>" target="_blank" class="text-primary hover:underline">
                                    <i class="fas fa-external-link-alt mr-2"></i>View Document
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm mt-1">Not provided</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Payment Method</label>
                        <p class="text-gray-900"><?= htmlspecialchars($seller['payment_method'] ?? 'N/A') ?></p>
                    </div>
                    <?php if (!empty($seller['payment_details'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Payment Details</label>
                            <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($seller['payment_details']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-6">
            <!-- Statistics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Statistics</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Total Products</label>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_products'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500"><?= number_format($stats['active_products'] ?? 0) ?> active</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Total Orders</label>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($stats['total_orders'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500"><?= number_format($stats['pending_orders'] ?? 0) ?> pending</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Total Revenue</label>
                        <p class="text-2xl font-bold text-gray-900">रु <?= number_format($stats['total_revenue'] ?? 0, 2) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Wallet Balance</label>
                        <p class="text-2xl font-bold text-green-600">रु <?= number_format($walletBalance ?? 0, 2) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Pending Withdrawals</label>
                        <p class="text-xl font-bold text-yellow-600">रु <?= number_format($withdrawStats['pending_amount'] ?? 0, 2) ?></p>
                        <p class="text-xs text-gray-500"><?= number_format($withdrawStats['pending_withdraws'] ?? 0) ?> requests</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="space-y-3">
                    <a href="<?= \App\Core\View::url('admin/seller/withdraws/' . $seller['id']) ?>" 
                       class="block w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-money-bill-wave mr-2"></i>Manage Withdrawals
                    </a>
                    <a href="<?= \App\Core\View::url('admin/seller/products?seller_id=' . $seller['id']) ?>" 
                       class="block w-full bg-green-50 hover:bg-green-100 text-green-700 px-4 py-3 rounded-lg transition-colors">
                        <i class="fas fa-box mr-2"></i>View Products
                    </a>
                </div>
            </div>

            <!-- Account Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Information</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created At</label>
                        <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($seller['created_at'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($seller['updated_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Orders</h2>
            <p class="text-sm text-gray-600 mt-1">Order management and statistics for this seller</p>
        </div>
        
        <!-- Order Statistics -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Not Delivered</p>
                    <p class="text-2xl font-bold text-orange-600"><?= number_format($orderStats['not_delivered'] ?? 0) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Delivered</p>
                    <p class="text-2xl font-bold text-green-600"><?= number_format($orderStats['delivered'] ?? 0) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">Total Orders</p>
                    <p class="text-2xl font-bold text-primary"><?= number_format($orderStats['total_orders'] ?? 0) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">This Week Sales</p>
                    <p class="text-2xl font-bold text-blue-600">रु <?= number_format($orderStats['week_sales'] ?? 0, 2) ?></p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500 mb-1">This Month Sales</p>
                    <p class="text-2xl font-bold text-purple-600">रु <?= number_format($orderStats['month_sales'] ?? 0, 2) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">#<?= $order['id'] ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($order['invoice'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars(trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?: $order['customer_name'] ?? 'Guest') ?>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($order['customer_email'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($order['item_count'] ?? 0) ?> items
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">रु <?= number_format($order['seller_order_total'] ?? 0, 2) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $paymentStatus = $order['payment_status'] ?? 'pending';
                                    $paymentColors = [
                                        'paid' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'failed' => 'bg-red-100 text-red-800'
                                    ];
                                    $paymentColor = $paymentColors[$paymentStatus] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $paymentColor ?>">
                                        <?= ucfirst($paymentStatus) ?>
                                    </span>
                                    <div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($order['payment_method_name'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $order['status'] ?? 'pending';
                                    $statusColors = [
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'in_transit' => 'bg-blue-100 text-blue-800',
                                        'picked_up' => 'bg-purple-100 text-purple-800',
                                        'confirmed' => 'bg-yellow-100 text-yellow-800',
                                        'pending' => 'bg-gray-100 text-gray-800',
                                        'cancelled' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($order['created_at'])) ?><br>
                                    <span class="text-xs"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/orders/view/' . $order['id']) ?>" 
                                       class="text-primary hover:text-primary-dark">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-2 block"></i>
                                No orders found for this seller
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Coupons Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Coupons</h2>
            <p class="text-sm text-gray-600 mt-1">Seller-created coupons</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($coupons)): ?>
                        <?php foreach ($coupons as $coupon): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($coupon['code']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : 'रु' . number_format($coupon['discount_value'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($coupon['usage_count'] ?? 0) ?> uses
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $isActive = $coupon['is_active'] == 1;
                                    $isExpired = !empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time();
                                    $statusColor = $isActive && !$isExpired ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= $isActive && !$isExpired ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $coupon['expires_at'] ? date('M j, Y', strtotime($coupon['expires_at'])) : 'No expiry' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($coupon['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                <i class="fas fa-ticket-alt text-4xl text-gray-300 mb-2 block"></i>
                                No coupons found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Withdraw Requests Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Withdraw Requests</h2>
            <p class="text-sm text-gray-600 mt-1">All withdrawal requests from this seller</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Account</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($withdrawRequests)): ?>
                        <?php foreach ($withdrawRequests as $request): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#<?= $request['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">रु <?= number_format($request['amount'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($request['payment_method'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($request['bank_name'] ?? 'N/A') ?>
                                    <?php if (!empty($request['account_number'])): ?>
                                        <div class="text-xs text-gray-400">****<?= substr($request['account_number'], -4) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $request['status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-blue-100 text-blue-800',
                                        'completed' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($request['requested_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/seller/withdraws/' . $seller['id'] . '?request=' . $request['id']) ?>" 
                                       class="text-primary hover:text-primary-dark">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                <i class="fas fa-money-bill-wave text-4xl text-gray-300 mb-2 block"></i>
                                No withdrawal requests found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bank Accounts Section -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Bank Accounts</h2>
            <p class="text-sm text-gray-600 mt-1">Registered bank accounts for withdrawals</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Holder</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Added</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($bankAccounts)): ?>
                        <?php foreach ($bankAccounts as $account): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($account['account_holder_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($account['bank_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">****<?= substr($account['account_number'] ?? '', -4) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($account['account_type'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($account['is_default'] ?? 0): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">Default</span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($account['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                <i class="fas fa-university text-4xl text-gray-300 mb-2 block"></i>
                                No bank accounts registered
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Products</h2>
            <p class="text-sm text-gray-600 mt-1">All products listed by this seller</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sales</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if (!empty($product['primary_image'])): ?>
                                            <img src="<?= htmlspecialchars($product['primary_image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                 class="w-12 h-12 object-cover rounded-lg mr-3">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-200 rounded-lg mr-3 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($product['product_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?= $product['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        रु <?= number_format($product['price'] ?? 0, 2) ?>
                                    </div>
                                    <?php if (!empty($product['discount_price']) && $product['discount_price'] < $product['price']): ?>
                                        <div class="text-xs text-green-600">
                                            रु <?= number_format($product['discount_price'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 <?= ($product['stock'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= number_format($product['stock'] ?? 0) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $product['status'] ?? 'inactive';
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-gray-100 text-gray-800',
                                        'out_of_stock' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $approvalStatus = $product['approval_status'] ?? 'pending';
                                    $approvalColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $approvalColor = $approvalColors[$approvalStatus] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $approvalColor ?>">
                                        <?= ucfirst($approvalStatus) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($product['order_count'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($product['total_sales'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($product['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/products/edit/' . $product['id']) ?>" 
                                       class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= \App\Core\View::url('products/view/' . $product['id']) ?>" 
                                       target="_blank"
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">
                                <i class="fas fa-box-open text-4xl text-gray-300 mb-2 block"></i>
                                No products found for this seller
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center" style="display: none;">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reject Seller</h3>
        <form action="<?= \App\Core\View::url('admin/seller/reject/' . $seller['id']) ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="mb-4">
                <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="hideRejectModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Reject</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').style.display = 'none';
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

