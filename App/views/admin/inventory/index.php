<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="text-gray-600 mt-2">Manage suppliers, products, purchases, and payments</p>
        </div>
        <div class="flex space-x-3">
            <a href="<?= \App\Core\View::url('admin/inventory/supplier') ?>" class="btn">
                <i class="fas fa-truck mr-2"></i>Suppliers
            </a>
            <a href="<?= \App\Core\View::url('admin/inventory/products') ?>" class="btn">
                <i class="fas fa-box mr-2"></i>Products
            </a>
            <a href="<?= \App\Core\View::url('admin/inventory/purchases') ?>" class="btn">
                <i class="fas fa-shopping-cart mr-2"></i>Purchases
            </a>
            <a href="<?= \App\Core\View::url('admin/inventory/payments') ?>" class="btn">
                <i class="fas fa-credit-card mr-2"></i>Payments
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Suppliers Stats -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Suppliers</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $supplierStats['total_suppliers'] ?></p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-check-circle mr-1"></i>
                        <?= $supplierStats['active_suppliers'] ?> Active
                    </p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-truck text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Products Stats -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Products</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $productStats['total_products'] ?></p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-check-circle mr-1"></i>
                        <?= $productStats['active_products'] ?> Active
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-box text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Purchases Stats -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Purchases</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $purchaseStats['total_purchases'] ?? 0 ?></p>
                    <p class="text-xs text-orange-600 mt-1">
                        <i class="fas fa-clock mr-1"></i>
                        <?= $purchaseStats['pending_purchases'] ?? 0 ?> Pending
                    </p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-shopping-cart text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Payments Stats -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Payments</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $paymentStats['total_payments'] ?? 0 ?></p>
                    <p class="text-xs text-green-600 mt-1">
                        <i class="fas fa-rupee-sign mr-1"></i>
                        Rs <?= number_format($paymentStats['total_payment_amount'] ?? 0, 2) ?>
                    </p>
                </div>
                <div class="bg-orange-100 p-3 rounded-full">
                    <i class="fas fa-credit-card text-orange-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Purchase Amounts -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Purchase Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Purchase Amount</span>
                    <span class="font-semibold text-gray-900">Rs <?= number_format($purchaseStats['total_purchase_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Paid Amount</span>
                    <span class="font-semibold text-green-600">Rs <?= number_format($purchaseStats['paid_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Outstanding Amount</span>
                    <span class="font-semibold text-red-600">Rs <?= number_format($purchaseStats['outstanding_amount'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Inventory Value -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Inventory Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total Quantity</span>
                    <span class="font-semibold text-gray-900"><?= number_format($productStats['total_quantity'] ?? 0) ?> units</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Inventory Value</span>
                    <span class="font-semibold text-blue-600">Rs <?= number_format($productStats['total_inventory_value'] ?? 0, 2) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Low Stock Items</span>
                    <span class="font-semibold text-orange-600"><?= count($lowStockProducts) ?> products</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Recent Purchases -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Recent Purchases</h3>
                <a href="<?= \App\Core\View::url('admin/inventory/purchases') ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All
                </a>
            </div>
            <div class="space-y-3">
                <?php if (!empty($recentPurchases)): ?>
                    <?php foreach ($recentPurchases as $purchase): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($purchase['product_name']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($purchase['supplier_name']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">Rs <?= number_format($purchase['total_amount'] ?? 0, 2) ?></p>
                                <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($purchase['purchase_date'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent purchases</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Recent Payments</h3>
                <a href="<?= \App\Core\View::url('admin/inventory/payments') ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View All
                </a>
            </div>
            <div class="space-y-3">
                <?php if (!empty($recentPayments)): ?>
                    <?php foreach ($recentPayments as $payment): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($payment['product_name']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($payment['supplier_name']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600">Rs <?= number_format($payment['amount'] ?? 0, 2) ?></p>
                                <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">No recent payments</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (!empty($lowStockProducts)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-orange-200 p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-triangle text-orange-600 text-xl mr-3"></i>
                <h3 class="text-lg font-semibold text-orange-900">Low Stock Alert</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($lowStockProducts as $product): ?>
                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium text-orange-900"><?= htmlspecialchars($product['product_name']) ?></p>
                                <p class="text-sm text-orange-700"><?= htmlspecialchars($product['supplier_name']) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-orange-900"><?= $product['quantity'] ?> units</p>
                                <p class="text-xs text-orange-600">Min: <?= $product['min_stock_level'] ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>


