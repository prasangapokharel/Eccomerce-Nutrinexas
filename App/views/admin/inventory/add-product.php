<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add Wholesale Product</h1>
            <p class="text-gray-600 mt-2">Add a new product to your wholesale inventory</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/products') ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Products
        </a>
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

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form method="POST" action="<?= \App\Core\View::url('admin/inventory/add-product') ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product Name -->
                <div class="md:col-span-2">
                    <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" required
                           value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Supplier -->
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                    <select id="supplier_id" name="supplier_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier_id'] ?>" 
                                    <?= (($_POST['supplier_id'] ?? '') == $supplier['supplier_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($supplier['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Product Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Product Type</label>
                    <input type="text" id="type" name="type"
                           value="<?= htmlspecialchars($_POST['type'] ?? '') ?>"
                           placeholder="e.g., Supplements, Vitamins, Protein"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Cost Amount -->
                <div>
                    <label for="cost_amount" class="block text-sm font-medium text-gray-700 mb-2">Cost Amount (Rs) *</label>
                    <input type="number" id="cost_amount" name="cost_amount" step="0.01" min="0" required
                           value="<?= htmlspecialchars($_POST['cost_amount'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Selling Price -->
                <div>
                    <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-2">Selling Price (Rs)</label>
                    <input type="number" id="selling_price" name="selling_price" step="0.01" min="0"
                           value="<?= htmlspecialchars($_POST['selling_price'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Initial Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="0"
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Min Stock Level -->
                <div>
                    <label for="min_stock_level" class="block text-sm font-medium text-gray-700 mb-2">Min Stock Level</label>
                    <input type="number" id="min_stock_level" name="min_stock_level" min="0"
                           value="<?= htmlspecialchars($_POST['min_stock_level'] ?? '10') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- SKU -->
                <div>
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                    <input type="text" id="sku" name="sku"
                           value="<?= htmlspecialchars($_POST['sku'] ?? '') ?>"
                           placeholder="Product SKU or barcode"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="active" <?= (($_POST['status'] ?? 'active') == 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($_POST['status'] ?? '') == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="discontinued" <?= (($_POST['status'] ?? '') == 'discontinued') ? 'selected' : '' ?>>Discontinued</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Product description, ingredients, benefits, etc."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="<?= \App\Core\View::url('admin/inventory/products') ?>" 
                   class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Add Product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-calculate selling price based on cost amount (optional markup)
document.getElementById('cost_amount').addEventListener('input', function() {
    const costAmount = parseFloat(this.value) || 0;
    const sellingPriceField = document.getElementById('selling_price');
    
    // If selling price is empty, suggest a 30% markup
    if (!sellingPriceField.value && costAmount > 0) {
        sellingPriceField.value = (costAmount * 1.3).toFixed(2);
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const costAmount = parseFloat(document.getElementById('cost_amount').value) || 0;
    const sellingPrice = parseFloat(document.getElementById('selling_price').value) || 0;
    
    if (costAmount <= 0) {
        e.preventDefault();
        alert('Cost amount must be greater than 0');
        return;
    }
    
    if (sellingPrice > 0 && sellingPrice < costAmount) {
        if (!confirm('Selling price is less than cost amount. Are you sure you want to continue?')) {
            e.preventDefault();
            return;
        }
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
