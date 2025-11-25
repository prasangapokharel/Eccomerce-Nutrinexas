<?php ob_start(); ?>
<?php $page = 'inventory'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Inventory Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your product stock levels</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= \App\Core\View::url('seller/inventory') ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= !$lowStockFilter ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                All Products
            </a>
            <a href="<?= \App\Core\View::url('seller/inventory?low_stock=1') ?>" 
               class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $lowStockFilter ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <i class="fas fa-exclamation-triangle mr-2"></i>Low Stock
            </a>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-900">Product Inventory</h2>
                
                <!-- Search -->
                <div class="relative max-w-md">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Search products..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                           style="appearance: none; -webkit-appearance: none; border-radius: 0.5rem; -webkit-border-radius: 0.5rem;">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Current Stock
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Price
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="inventoryTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="noProductsRow">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-warehouse text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                                    <p class="text-gray-500 mb-4">
                                        <?php if ($lowStockFilter): ?>
                                            No products with low stock.
                                        <?php else: ?>
                                            Add products to manage inventory.
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!$lowStockFilter): ?>
                                        <a href="<?= \App\Core\View::url('seller/products/create') ?>" 
                                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark">
                                            <i class="fas fa-plus mr-2"></i>
                                            Add Product
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 transition-colors inventory-row" 
                                data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <?php 
                                            $defaultImage = \App\Core\View::asset('images/product_default.jpg');
                                            $imageUrl = $defaultImage;
                                            
                                            if (!empty($product['primary_image_url'])) {
                                                $imageUrl = filter_var($product['primary_image_url'], FILTER_VALIDATE_URL) 
                                                    ? $product['primary_image_url'] 
                                                    : $product['primary_image_url'];
                                            } elseif (!empty($product['image_url'])) {
                                                $imageUrl = filter_var($product['image_url'], FILTER_VALIDATE_URL) 
                                                    ? $product['image_url'] 
                                                    : $product['image_url'];
                                            }
                                            ?>
                                            <img class="h-12 w-12 rounded-lg object-cover border border-gray-200" 
                                                 src="<?= htmlspecialchars($imageUrl) ?>" 
                                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                 onerror="this.src='<?= htmlspecialchars($defaultImage) ?>'">
                                        </div>
                                        <div class="ml-4 min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900 truncate" style="max-width: 220px;">
                                                <?= htmlspecialchars($product['product_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?= $product['id'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $stockQty = $product['stock_quantity'] ?? 0;
                                    $stockClass = 'bg-gray-100 text-gray-800';
                                    $stockIcon = 'fas fa-minus';
                                    if ($stockQty > 20) {
                                        $stockClass = 'bg-green-100 text-green-800';
                                        $stockIcon = 'fas fa-check';
                                    } elseif ($stockQty > 10) {
                                        $stockClass = 'bg-yellow-100 text-yellow-800';
                                        $stockIcon = 'fas fa-exclamation';
                                    } elseif ($stockQty > 0) {
                                        $stockClass = 'bg-red-100 text-red-800';
                                        $stockIcon = 'fas fa-exclamation-triangle';
                                    } else {
                                        $stockClass = 'bg-red-200 text-red-900';
                                        $stockIcon = 'fas fa-times';
                                    }
                                    ?>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $stockClass ?>">
                                            <i class="<?= $stockIcon ?> mr-1"></i>
                                            <?= number_format($stockQty) ?>
                                        </span>
                                        <?php if ($stockQty < 10 && $stockQty > 0): ?>
                                            <span class="text-xs text-red-600 font-medium">Low Stock</span>
                                        <?php elseif ($stockQty == 0): ?>
                                            <span class="text-xs text-red-800 font-medium">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-green-600">Rs<?= number_format($product['sale_price'], 2) ?></span>
                                                <span class="text-xs text-gray-500 line-through">Rs<?= number_format($product['price'], 2) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-medium">Rs<?= number_format($product['price'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : ($product['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="openStockModal(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['product_name'])) ?>', <?= $product['stock_quantity'] ?? 0 ?>)" 
                                            class="text-primary hover:text-primary-dark transition-colors"
                                            title="Update Stock">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Stock Update Modal -->
<div id="stockModal" style="position: fixed; inset: 0; z-index: 50; display: none;">
    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);" onclick="closeStockModal()"></div>
    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 1rem;">
        <div class="bg-white rounded-xl shadow-lg p-6" style="max-width: 28rem; width: 100%;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Update Stock</h3>
                <button onclick="closeStockModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="stockForm" method="POST">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                    <p id="modalProductName" class="font-medium text-gray-900"></p>
                </div>
                <div class="mb-4">
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                    <input type="number" 
                           id="stock_quantity" 
                           name="stock_quantity" 
                           min="0" 
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div class="flex gap-3">
                    <button type="button" 
                            onclick="closeStockModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark">
                        Update Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const inventoryRows = document.querySelectorAll('.inventory-row');
    const noProductsRow = document.getElementById('noProductsRow');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            let visibleCount = 0;

            inventoryRows.forEach(row => {
                const name = row.dataset.name || '';
                
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (visibleCount === 0 && inventoryRows.length > 0) {
                noProductsRow.style.display = '';
            } else {
                noProductsRow.style.display = 'none';
            }
        });
    }
});

function openStockModal(productId, productName, currentStock) {
    document.getElementById('modalProductName').textContent = productName;
    document.getElementById('stock_quantity').value = currentStock;
    document.getElementById('stockForm').action = '<?= \App\Core\View::url('seller/inventory/update-stock/') ?>' + productId;
    document.getElementById('stockModal').style.display = 'block';
}

function closeStockModal() {
    document.getElementById('stockModal').style.display = 'none';
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
