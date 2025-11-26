<?php ob_start(); ?>
<?php $page = 'seller-products'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Seller Products - Approval</h1>
            <p class="mt-1 text-sm text-gray-500">Review and approve seller products</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-600">Total Products</p>
            <p class="text-2xl font-bold text-gray-900 mt-1"><?= $stats['total'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-600">Pending Approval</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $stats['pending'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-600">Approved</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $stats['approved'] ?? 0 ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <p class="text-sm font-medium text-gray-600">Rejected</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= $stats['rejected'] ?? 0 ?></p>
        </div>
    </div>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($products as $product) {
        $tableData[] = [
            'id' => $product['id'],
            'product' => $product,
            'product_name' => $product['product_name'],
            'seller_name' => $product['seller_name'] ?? 'N/A',
            'seller_email' => $product['seller_email'] ?? '',
            'price' => $product['price'],
            'sale_price' => $product['sale_price'] ?? null,
            'status' => $product['status'],
            'approval_status' => $product['approval_status'] ?? 'pending',
            'created_at' => $product['created_at'],
            'primary_image' => $product['primary_image'] ?? null
        ];
    }

    $tableConfig = [
        'id' => 'sellerProductsTable',
        'title' => 'Seller Products',
        'description' => 'Review and approve seller products',
        'search' => true,
        'filters' => [
            [
                'label' => 'Pending',
                'url' => \App\Core\View::url('admin/seller/products?approval=pending'),
                'active' => $approvalFilter === 'pending'
            ],
            [
                'label' => 'Approved',
                'url' => \App\Core\View::url('admin/seller/products?approval=approved'),
                'active' => $approvalFilter === 'approved'
            ],
            [
                'label' => 'Rejected',
                'url' => \App\Core\View::url('admin/seller/products?approval=rejected'),
                'active' => $approvalFilter === 'rejected'
            ],
            [
                'label' => 'All',
                'url' => \App\Core\View::url('admin/seller/products?approval=all'),
                'active' => $approvalFilter === 'all'
            ]
        ],
        'columns' => [
            [
                'key' => 'product',
                'label' => 'Product',
                'type' => 'custom',
                'render' => function($row) {
                    $product = $row['product'];
                    ob_start();
                    ?>
                    <div class="flex items-center">
                        <?php if (!empty($product['primary_image'])): ?>
                            <img src="<?= htmlspecialchars($product['primary_image']) ?>" 
                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                 class="w-12 h-12 object-cover rounded-lg mr-3">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center mr-3">
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
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'seller_name',
                'label' => 'Seller',
                'type' => 'custom',
                'render' => function($row) {
                    $product = $row['product'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900"><?= htmlspecialchars($product['seller_name'] ?? 'N/A') ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($product['seller_email'] ?? '') ?></div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'price',
                'label' => 'Price',
                'type' => 'custom',
                'render' => function($row) {
                    $product = $row['product'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900">रु <?= number_format($product['price'], 2) ?></div>
                    <?php if ($product['sale_price']): ?>
                        <div class="text-xs text-red-600">
                            Sale: रु <?= number_format($product['sale_price'], 2) ?>
                        </div>
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
                    'pending' => 'warning',
                    'inactive' => 'secondary'
                ]
            ],
            [
                'key' => 'approval_status',
                'label' => 'Approval',
                'type' => 'badge',
                'badgeConfig' => [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger'
                ]
            ],
            [
                'key' => 'created_at',
                'label' => 'Created',
                'type' => 'date'
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    ob_start();
                    ?>
                    <a href="<?= \App\Core\View::url('admin/seller/products/detail/' . $row['id']) ?>" 
                       class="text-primary hover:text-primary-dark hover:bg-primary-50 transition-colors p-1 rounded" 
                       title="Review">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/seller/products')
    ];
    ?>

    <?php include __DIR__ . '/../../../components/Table.php'; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(dirname(__FILE__))) . '/layouts/admin.php'; ?>
