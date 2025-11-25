<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Seller Management</h1>
            <p class="text-gray-600">Manage sellers and their documents</p>
        </div>
        <div>
            <a href="<?= \App\Core\View::url('admin/seller/create') ?>" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark">
                <i class="fas fa-plus mr-2"></i>
                Create Seller
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-store text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Sellers</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['approved'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['pending'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Inactive</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow flex gap-4">
        <select id="statusFilter" onchange="window.location.href='?status=' + this.value + '&approval=<?= $approvalFilter ?>'" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
        <select id="approvalFilter" onchange="window.location.href='?status=<?= $statusFilter ?>&approval=' + this.value" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Approval Status</option>
            <option value="approved" <?= $approvalFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="pending" <?= $approvalFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
        </select>
    </div>

    <!-- Sellers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">All Sellers</h3>
        </div>
        
        <?php if (empty($sellers)): ?>
            <div class="text-center py-12">
                <i class="fas fa-store text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No sellers found</h3>
                <p class="text-gray-500">No sellers match your filters</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Seller</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sellers as $seller): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if (!empty($seller['logo_url'])): ?>
                                                <img src="<?= htmlspecialchars($seller['logo_url']) ?>" alt="<?= htmlspecialchars($seller['name']) ?>" class="h-10 w-10 rounded-full object-cover">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                                    <?= strtoupper(substr($seller['name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($seller['name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($seller['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($seller['company_name'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($seller['total_products'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= number_format($seller['total_orders'] ?? 0) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    रु <?= number_format($seller['total_revenue'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($seller['is_approved']): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-clock mr-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $seller['status'] === 'active' ? 'bg-green-100 text-green-800' : ($seller['status'] === 'suspended' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= ucfirst($seller['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/seller/details/' . $seller['id']) ?>" 
                                           class="text-indigo-600 hover:text-indigo-900" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= \App\Core\View::url('admin/seller/edit/' . $seller['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900" title="Edit">
                                            <i class="fas fa-edit"></i>
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
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

