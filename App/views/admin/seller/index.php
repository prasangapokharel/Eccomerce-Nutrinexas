<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Seller Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage sellers and their documents</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/seller/create') ?>" 
           class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
            <i class="fas fa-plus mr-2"></i>Create Seller
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-store text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Sellers</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['approved'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['pending'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($sellers as $seller) {
        $tableData[] = [
            'id' => $seller['id'],
            'seller' => $seller,
            'name' => $seller['name'],
            'email' => $seller['email'],
            'company_name' => $seller['company_name'] ?? 'N/A',
            'total_products' => $seller['total_products'] ?? 0,
            'total_orders' => $seller['total_orders'] ?? 0,
            'total_revenue' => $seller['total_revenue'] ?? 0,
            'is_approved' => $seller['is_approved'] ?? false,
            'status' => $seller['status']
        ];
    }

    $tableConfig = [
        'id' => 'sellersTable',
        'title' => 'All Sellers',
        'description' => 'Manage seller accounts and approvals',
        'search' => true,
        'filters' => [
            [
                'label' => 'All Status',
                'url' => \App\Core\View::url('admin/seller'),
                'active' => empty($statusFilter)
            ],
            [
                'label' => 'Active',
                'url' => \App\Core\View::url('admin/seller?status=active'),
                'active' => $statusFilter === 'active'
            ],
            [
                'label' => 'Inactive',
                'url' => \App\Core\View::url('admin/seller?status=inactive'),
                'active' => $statusFilter === 'inactive'
            ],
            [
                'label' => 'Suspended',
                'url' => \App\Core\View::url('admin/seller?status=suspended'),
                'active' => $statusFilter === 'suspended'
            ]
        ],
        'columns' => [
            [
                'key' => 'seller',
                'label' => 'Seller',
                'type' => 'custom',
                'render' => function($row) {
                    $seller = $row['seller'];
                    ob_start();
                    ?>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <?php if (!empty($seller['logo_url'])): ?>
                                <img src="<?= htmlspecialchars($seller['logo_url']) ?>" 
                                     alt="<?= htmlspecialchars($seller['name']) ?>" 
                                     class="h-10 w-10 rounded-full object-cover">
                            <?php else: ?>
                                <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                    <?= strtoupper(substr($seller['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($seller['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($seller['email']) ?></div>
                        </div>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'company_name',
                'label' => 'Company',
                'type' => 'text'
            ],
            [
                'key' => 'total_products',
                'label' => 'Products',
                'type' => 'text'
            ],
            [
                'key' => 'total_orders',
                'label' => 'Orders',
                'type' => 'text'
            ],
            [
                'key' => 'total_revenue',
                'label' => 'Revenue',
                'type' => 'currency'
            ],
            [
                'key' => 'is_approved',
                'label' => 'Approval',
                'type' => 'custom',
                'render' => function($row) {
                    $seller = $row['seller'];
                    $isApproved = $seller['is_approved'] ?? false;
                    ob_start();
                    ?>
                    <?php if ($isApproved): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>Approved
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                    <?php endif; ?>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'badge',
                'badgeConfig' => [
                    'active' => 'success',
                    'inactive' => 'secondary',
                    'suspended' => 'danger'
                ]
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <a href="<?= \App\Core\View::url('admin/seller/details/' . $row['id']) ?>" 
                           class="text-primary hover:text-primary-dark hover:bg-primary-50 transition-colors p-1 rounded" 
                           title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?= \App\Core\View::url('admin/seller/edit/' . $row['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/seller')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
