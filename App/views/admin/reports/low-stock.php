<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Low Stock Alerts</h1>
            <p class="text-gray-600 mt-2">Monitor products that need restocking</p>
        </div>
        <button onclick="location.reload()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-sync-alt mr-2"></i>Refresh
        </button>
    </div>

    <!-- Alert Summary -->
    <?php if (isset($alertResults)): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-3"></i>
                <div>
                    <p class="text-sm font-medium text-blue-900">
                        <?= $alertResults['low_stock_count'] ?> products with low stock, 
                        <?= $alertResults['out_of_stock_count'] ?> products out of stock
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Out of Stock Products -->
    <?php if (!empty($outOfStockProducts)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-red-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-red-200 bg-red-50">
                <h2 class="text-lg font-semibold text-red-900 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Out of Stock (<?= count($outOfStockProducts) ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($outOfStockProducts as $product): ?>
                            <tr class="hover:bg-red-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['category'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rs <?= number_format($product['price'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Out of Stock
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/editProduct/' . $product['id']) ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i> Update Stock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Low Stock Products -->
    <?php if (!empty($lowStockProducts)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-yellow-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-yellow-200 bg-yellow-50">
                <h2 class="text-lg font-semibold text-yellow-900 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Low Stock (<?= count($lowStockProducts) ?>)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr class="hover:bg-yellow-50">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($product['category'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    Rs <?= number_format($product['price'] ?? 0, 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        <?= number_format($product['stock_quantity']) ?> units
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('admin/editProduct/' . $product['id']) ?>" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i> Update Stock
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($lowStockProducts) && empty($outOfStockProducts)): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-check-circle text-6xl text-green-300 mb-4"></i>
            <p class="text-lg font-medium text-gray-900">All products are well stocked!</p>
            <p class="text-sm text-gray-500 mt-2">No low stock or out of stock products</p>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>


