<?php ob_start(); ?>
<?php $page = 'wallet'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Withdrawal Request #<?= $request['id'] ?></h1>
        <a href="<?= \App\Core\View::url('seller/withdraw-requests') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Request Details</h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-500">Amount</label>
                    <p class="text-lg font-bold text-gray-900">रु <?= number_format($request['amount'], 2) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Payment Method</label>
                    <p class="text-gray-900"><?= ucfirst(str_replace('_', ' ', $request['payment_method'])) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Status</label>
                    <p>
                        <?php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'completed' => 'bg-green-100 text-green-800'
                        ];
                        $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                            <?= ucfirst($request['status']) ?>
                        </span>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Requested At</label>
                    <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></p>
                </div>
                <?php if ($request['processed_at']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Processed At</label>
                        <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($request['processed_at'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($request['admin_comment'])): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Admin Comment</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['admin_comment']) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Account Information</h2>
            <?php if (!empty($request['account_holder_name'])): ?>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Account Holder</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['account_holder_name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Bank Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['bank_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Account Number</label>
                        <p class="text-gray-900"><?= htmlspecialchars($request['account_number'] ?? 'N/A') ?></p>
                    </div>
                    <?php if (!empty($request['ifsc_code'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-500">IFSC Code</label>
                            <p class="text-gray-900"><?= htmlspecialchars($request['ifsc_code']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($request['account_details'])): ?>
                <div>
                    <label class="text-sm font-medium text-gray-500">Account Details</label>
                    <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($request['account_details']) ?></p>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No account details provided</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

