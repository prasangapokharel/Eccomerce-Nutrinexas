<?php ob_start(); ?>
<?php $page = 'dashboard'; ?>

<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="flex flex-wrap gap-4">
        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Orders</p>
                    <p class="stats-value"><?= number_format($stats['total_orders'] ?? 0) ?></p>
                </div>
                <div class="stats-icon stats-icon-blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>

        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Pending Orders</p>
                    <p class="stats-value"><?= number_format($stats['pending_orders'] ?? 0) ?></p>
                </div>
                <div class="stats-icon stats-icon-yellow">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Completed Orders</p>
                    <p class="stats-value"><?= number_format($stats['completed_orders'] ?? 0) ?></p>
                </div>
                <div class="stats-icon stats-icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Earnings</p>
                    <p class="stats-value">रु <?= number_format($stats['total_earnings'] ?? $stats['total_revenue'] ?? 0, 2) ?></p>
                </div>
                <div class="stats-icon stats-icon-purple">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>

        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Wallet Balance</p>
                    <p class="stats-value">रु <?= number_format($stats['wallet_balance'] ?? 0, 2) ?></p>
                </div>
                <div class="stats-icon stats-icon-blue">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
        </div>

        <div class="card flex-1 min-w-[220px]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Products Count</p>
                    <p class="stats-value"><?= number_format($stats['products_count'] ?? $stats['total_products'] ?? 0) ?></p>
                </div>
                <div class="stats-icon stats-icon-purple">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="flex flex-col lg:flex-row gap-6">
        <div class="card flex-1">
            <h3 class="card-title">Top Products</h3>
            <div class="space-y-4">
                <?php if (empty($topProducts)): ?>
                    <p class="text-gray-600" style="text-align: center; padding: 2rem 0;">No sales data available</p>
                <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                        <div class="flex items-center justify-between p-3" style="background-color: var(--gray-50); border-radius: 0.5rem;">
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></p>
                                <p class="text-sm text-gray-600"><?= number_format($product['total_sold'] ?? 0) ?> sold</p>
                            </div>
                            <div style="text-align: right;">
                                <p class="font-semibold text-gray-900">रु <?= number_format($product['total_revenue'] ?? 0, 2) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders & Products -->
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Recent Orders -->
        <div class="card flex-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="card-title" style="margin: 0;">Recent Orders</h3>
                <a href="<?= \App\Core\View::url('seller/orders') ?>" class="link-primary text-sm">View All</a>
            </div>
            <div class="space-y-3">
                <?php if (empty($recentOrders)): ?>
                    <p class="text-gray-600" style="text-align: center; padding: 2rem 0;">No orders yet</p>
                <?php else: ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="flex items-center justify-between p-3" style="border: 1px solid var(--gray-200); border-radius: 0.5rem;">
                            <div>
                                <p class="font-medium text-gray-900">Order #<?= $order['id'] ?></p>
                                <p class="text-sm text-gray-600"><?= date('M j, Y', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">रु <?= number_format($order['total_amount'] ?? 0, 2) ?></p>
                                <span class="badge <?= $order['status'] === 'delivered' ? 'badge-success' : ($order['status'] === 'pending' ? 'badge-warning' : 'badge-info') ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Products -->
        <div class="card flex-1">
            <div class="flex items-center justify-between mb-4">
                <h3 class="card-title" style="margin: 0;">Recent Products</h3>
                <a href="<?= \App\Core\View::url('seller/products') ?>" class="link-primary text-sm">View All</a>
            </div>
            <div class="space-y-3">
                <?php if (empty($recentProducts)): ?>
                    <p class="text-gray-600" style="text-align: center; padding: 2rem 0;">No products yet</p>
                <?php else: ?>
                    <?php foreach ($recentProducts as $product): ?>
                        <div class="flex items-center justify-between p-3" style="border: 1px solid var(--gray-200); border-radius: 0.5rem;">
                            <div>
                                <p class="font-medium text-gray-900"><?= htmlspecialchars($product['product_name'] ?? 'N/A') ?></p>
                                <p class="text-sm text-gray-600">Stock: <?= $product['stock_quantity'] ?? 0 ?></p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">रु <?= number_format($product['price'] ?? 0, 2) ?></p>
                                <span class="badge <?= $product['status'] === 'active' ? 'badge-success' : 'badge-gray' ?>">
                                    <?= ucfirst($product['status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
