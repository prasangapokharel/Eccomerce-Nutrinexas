<?php ob_start(); ?>
<?php $page = 'cancellations'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Cancellation Requests</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage order cancellation requests</p>
        </div>
    </div>

    <!-- Cancellations Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header with Filters -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-900">Cancellation List</h2>
                
                <!-- Status Filter Pills -->
                <div class="flex flex-wrap gap-2">
                    <a href="<?= \App\Core\View::url('seller/cancellations') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !isset($statusFilter) || $statusFilter === '' ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        All
                    </a>
                    <a href="<?= \App\Core\View::url('seller/cancellations?status=processing') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'processing' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Processing
                    </a>
                    <a href="<?= \App\Core\View::url('seller/cancellations?status=refunded') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'refunded' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Refunded
                    </a>
                    <a href="<?= \App\Core\View::url('seller/cancellations?status=failed') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= isset($statusFilter) && $statusFilter === 'failed' ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        Failed
                    </a>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Order Details
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reason
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($cancels)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-times-circle text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No cancellation requests found</h3>
                                    <p class="text-gray-500">
                                        <?php if (isset($statusFilter) && $statusFilter): ?>
                                            No cancellation requests with status "<?= ucfirst($statusFilter) ?>" found.
                                        <?php else: ?>
                                            No cancellation requests have been submitted yet.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cancels as $cancel): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        Order #<?= $cancel['order_id'] ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        Invoice: <?= htmlspecialchars($cancel['invoice'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($cancel['customer_name'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($cancel['customer_email'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($cancel['customer_email']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        Rs <?= number_format($cancel['total_amount'] ?? 0, 2) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($cancel['reason']) ?>">
                                        <?= htmlspecialchars($cancel['reason']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'processing' => 'bg-yellow-100 text-yellow-800',
                                        'refunded' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800'
                                    ];
                                    $statusColor = $statusColors[$cancel['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst($cancel['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($cancel['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('g:i A', strtotime($cancel['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="<?= \App\Core\View::url('seller/cancellations/detail/' . $cancel['id']) ?>" 
                                       class="text-primary hover:text-primary-dark transition-colors"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

