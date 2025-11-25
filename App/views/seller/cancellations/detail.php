<?php ob_start(); ?>
<?php $page = 'cancellations'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Cancellation Request #<?= $cancel['id'] ?></h1>
        <a href="<?= \App\Core\View::url('seller/cancellations') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Cancellations
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Cancellation Details -->
        <div class="card">
            <h2 class="card-title">Cancellation Details</h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">Order ID</label>
                    <p class="text-gray-900">#<?= $cancel['order_id'] ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Invoice</label>
                    <p class="text-gray-900"><?= htmlspecialchars($cancel['invoice'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Reason</label>
                    <p class="text-gray-900"><?= htmlspecialchars($cancel['reason']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <?php
                    $statusColors = [
                        'processing' => 'badge-warning',
                        'refunded' => 'badge-success',
                        'failed' => 'badge-danger'
                    ];
                    $statusBadge = $statusColors[$cancel['status']] ?? 'badge-gray';
                    ?>
                    <span class="badge <?= $statusBadge ?>">
                        <?= ucfirst($cancel['status']) ?>
                    </span>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Created At</label>
                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($cancel['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Information -->
        <div class="card">
            <h2 class="card-title">Order Information</h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">Customer</label>
                    <p class="text-gray-900"><?= htmlspecialchars($cancel['customer_name'] ?? 'N/A') ?></p>
                    <?php if (!empty($cancel['customer_email'])): ?>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($cancel['customer_email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Order Amount</label>
                    <p class="text-gray-900 font-semibold">Rs <?= number_format($cancel['total_amount'] ?? 0, 2) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Order Status</label>
                    <span class="badge badge-warning">
                        <?= ucfirst($cancel['order_status'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Section -->
    <div class="card">
        <h2 class="card-title">Update Status</h2>
        <form method="POST" action="<?= \App\Core\View::url('seller/cancellations/update/' . $cancel['id']) ?>">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="space-y-4">
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="processing" <?= $cancel['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="refunded" <?= $cancel['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        <option value="failed" <?= $cancel['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-save mr-2"></i>Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

