<?php ob_start(); ?>
<?php $page = 'seller-products'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Seller Products - Approval</h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Total Products</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Pending Approval</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pending'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['approved'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <p class="text-sm text-gray-600">Rejected</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['rejected'] ?? 0 ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow flex gap-4">
        <select onchange="window.location.href='?approval=' + this.value + '&status=<?= $statusFilter ?>'" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="pending" <?= $approvalFilter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
            <option value="approved" <?= $approvalFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $approvalFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            <option value="all" <?= $approvalFilter === 'all' ? 'selected' : '' ?>>All</option>
        </select>
        <select onchange="window.location.href='?approval=<?= $approvalFilter ?>&status=' + this.value" 
                class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open empty-state-icon"></i>
            <h3 class="empty-state-title">No products found</h3>
            <p class="empty-state-text">No seller products match the current filters</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php if (!empty($product['primary_image'])): ?>
                                            <img src="<?= htmlspecialchars($product['primary_image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                 class="w-12 h-12 object-cover rounded-lg mr-3">
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($product['seller_name'] ?? 'N/A') ?>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($product['seller_email'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($product['price'], 2) ?>
                                    <?php if ($product['sale_price']): ?>
                                        <div class="text-xs text-red-600">
                                            Sale: रु <?= number_format($product['sale_price'], 2) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : ($product['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= ucfirst($product['status']) ?>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($product['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/seller/products/detail/' . $product['id']) ?>" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye"></i> Review
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(dirname(__FILE__))) . '/layouts/admin.php'; ?>

