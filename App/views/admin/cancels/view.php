<?php ob_start(); ?>

<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cancel Request Details</h1>
            <p class="text-gray-600">Order #<?= $cancel['order_id'] ?></p>
        </div>
        <a href="<?= \App\Core\View::url('admin/cancels') ?>" 
           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
            <i class="fas fa-arrow-left mr-2"></i>Back to Cancellations
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Cancel Request Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Cancellation Request</h2>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">Request ID</label>
                    <p class="text-gray-900 font-semibold">#<?= $cancel['id'] ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Order ID</label>
                    <p class="text-gray-900">#<?= $cancel['order_id'] ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Invoice Number</label>
                    <p class="text-gray-900"><?= htmlspecialchars($cancel['invoice'] ?? 'N/A') ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Cancellation Reason</label>
                    <p class="text-gray-900 bg-gray-50 p-3 rounded-lg"><?= htmlspecialchars($cancel['reason']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <div class="mt-1">
                        <?php
                        $statusColors = [
                            'processing' => 'bg-yellow-100 text-yellow-800',
                            'refunded' => 'bg-green-100 text-green-800',
                            'failed' => 'bg-red-100 text-red-800'
                        ];
                        $statusColor = $statusColors[$cancel['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColor ?>">
                            <?= ucfirst($cancel['status']) ?>
                        </span>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Requested At</label>
                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($cancel['created_at'])) ?></p>
                </div>
                <?php if (!empty($cancel['updated_at']) && $cancel['updated_at'] !== $cancel['created_at']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Last Updated</label>
                        <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($cancel['updated_at'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Order & Customer Information -->
        <div class="space-y-6">
            <!-- Order Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Order Amount</label>
                        <p class="text-gray-900 font-semibold text-lg">Rs <?= number_format($cancel['total_amount'] ?? 0, 2) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Order Status</label>
                        <p class="text-gray-900"><?= ucfirst($cancel['order_status'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Customer Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cancel['customer_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900"><?= htmlspecialchars($cancel['customer_email'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>

            <!-- Seller Information -->
            <?php if (!empty($cancel['seller_id'])): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Seller Information</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Seller Name</label>
                            <p class="text-gray-900"><?= htmlspecialchars($cancel['seller_name'] ?? 'N/A') ?></p>
                            <?php if (!empty($cancel['company_name'])): ?>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($cancel['company_name']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($cancel['seller_email'])): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Email</label>
                                <p class="text-gray-900"><?= htmlspecialchars($cancel['seller_email']) ?></p>
                            </div>
                        <?php endif; ?>
                        <div>
                            <a href="<?= \App\Core\View::url('admin/seller/details/' . $cancel['seller_id']) ?>" 
                               class="text-primary hover:text-primary-dark text-sm font-medium">
                                <i class="fas fa-external-link-alt mr-1"></i>View Seller Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Status Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h2>
        <form id="updateStatusForm" method="POST" action="<?= \App\Core\View::url('admin/cancels/update/' . $cancel['id']) ?>">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="processing" <?= $cancel['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="refunded" <?= $cancel['status'] === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                        <option value="failed" <?= $cancel['status'] === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <div class="pt-6">
                    <button type="submit" 
                            class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

