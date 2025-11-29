<?php ob_start(); ?>
<?php $page = 'stock-movement'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Stock Movement Log</h1>
            <p class="mt-1 text-sm text-gray-500">Track all stock changes for your products</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Stock Movement Filters</h2>
                
                <!-- Standard Top Bar: Search, Filter, Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 sm:flex-initial sm:w-64">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search by product name..." 
                               class="input native-input pr-10">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <select id="product_id" name="product_id" class="input native-input sm:w-48">
                    <option value="">All Products</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product['id'] ?>" <?= $productFilter == $product['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($product['product_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select id="type" name="type" class="input native-input sm:w-48">
                    <option value="">All Types</option>
                    <option value="in" <?= $typeFilter === 'in' ? 'selected' : '' ?>>Stock In</option>
                    <option value="out" <?= $typeFilter === 'out' ? 'selected' : '' ?>>Stock Out</option>
                    <option value="sale" <?= $typeFilter === 'sale' ? 'selected' : '' ?>>Sale</option>
                    <option value="return" <?= $typeFilter === 'return' ? 'selected' : '' ?>>Return</option>
                    <option value="cancellation" <?= $typeFilter === 'cancellation' ? 'selected' : '' ?>>Cancellation</option>
                    <option value="adjustment" <?= $typeFilter === 'adjustment' ? 'selected' : '' ?>>Adjustment</option>
                </select>
                
                <button type="submit" class="btn btn-sm btn-primary">
                    Filter
                </button>
            </form>
        </div>
    </div>

    <!-- Stock Movements Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Previous Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                No stock movements found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movements as $movement): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('M j, Y H:i', strtotime($movement['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($movement['product_name'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                        echo match($movement['movement_type']) {
                                            'in' => 'bg-green-100 text-green-800',
                                            'out', 'sale' => 'bg-red-100 text-red-800',
                                            'return' => 'bg-blue-100 text-blue-800',
                                            'cancellation' => 'bg-orange-100 text-orange-800',
                                            'adjustment' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>">
                                        <?= ucfirst($movement['movement_type']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?= in_array($movement['movement_type'], ['out', 'sale']) ? 'text-red-600' : 'text-green-600' ?>">
                                    <?= in_array($movement['movement_type'], ['out', 'sale']) ? '-' : '+' ?><?= $movement['quantity'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?= $movement['previous_stock'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= $movement['new_stock'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php if ($movement['reference_type'] && $movement['reference_id']): ?>
                                        <?= ucfirst($movement['reference_type']) ?> #<?= $movement['reference_id'] ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?= htmlspecialchars($movement['notes'] ?? '-') ?>
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

