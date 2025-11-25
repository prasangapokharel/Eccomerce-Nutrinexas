<?php ob_start(); ?>
<?php $page = 'customers'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Customer Details</h1>
        <a href="<?= \App\Core\View::url('seller/customers') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Customers
        </a>
    </div>

    <div class="grid grid-cols-1 grid-cols-3">
        <!-- Customer Info -->
        <div class="card">
            <h2 class="card-title">Customer Information</h2>
            <div class="space-y-3">
                <div>
                    <p class="text-sm text-gray-600">Name</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium text-gray-900"><?= htmlspecialchars($customer['email'] ?? '') ?></p>
                </div>
                <?php if (!empty($customer['phone'])): ?>
                    <div>
                        <p class="text-sm text-gray-600">Phone</p>
                        <p class="font-medium text-gray-900"><?= htmlspecialchars($customer['phone']) ?></p>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="text-sm text-gray-600">Total Orders</p>
                    <p class="font-medium text-gray-900"><?= number_format($customer['total_orders'] ?? 0) ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total Spent</p>
                    <p class="font-medium text-gray-900 text-lg">रु <?= number_format($customer['total_spent'] ?? 0, 2) ?></p>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div style="grid-column: span 2;" class="card">
            <h2 class="card-title">Order History</h2>
            <?php if (empty($orders)): ?>
                <p class="text-gray-600" style="text-align: center; padding: 2rem 0;">No orders found</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($orders as $order): ?>
                        <div style="border: 1px solid var(--gray-200); border-radius: 0.5rem; padding: 1rem;">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium text-gray-900">Order #<?= $order['id'] ?></p>
                                    <p class="text-sm text-gray-600"><?= date('M j, Y h:i A', strtotime($order['created_at'])) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">रु <?= number_format($order['total_amount'], 2) ?></p>
                                    <span class="badge <?= $order['status'] === 'delivered' ? 'badge-success' : ($order['status'] === 'pending' ? 'badge-warning' : 'badge-info') ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <a href="<?= \App\Core\View::url('seller/orders/detail/' . $order['id']) ?>" class="link-primary text-sm">
                                View Details →
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/../layouts/main.php'; ?>
