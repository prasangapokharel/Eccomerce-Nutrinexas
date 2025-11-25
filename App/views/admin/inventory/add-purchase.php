<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Add Purchase Order</h1>
            <p class="text-gray-600 mt-2">Create a new purchase order for wholesale products</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/purchases') ?>" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Purchases
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
        <form method="POST" action="<?= \App\Core\View::url('admin/inventory/add-purchase') ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Product -->
                <div>
                    <label for="product_id" class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                    <select id="product_id" name="product_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= $product['product_id'] ?>" 
                                    data-cost="<?= $product['cost_amount'] ?>"
                                    data-supplier="<?= $product['supplier_id'] ?>"
                                    <?= (($_POST['product_id'] ?? '') == $product['product_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($product['product_name']) ?> - <?= htmlspecialchars($product['supplier_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Supplier (Auto-filled) -->
                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                    <input type="text" id="supplier_display" readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                    <input type="hidden" id="supplier_id" name="supplier_id">
                </div>

                <!-- Quantity -->
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" min="1" required
                           value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Unit Cost -->
                <div>
                    <label for="unit_cost" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost (Rs) *</label>
                    <input type="number" id="unit_cost" name="unit_cost" step="0.01" min="0" required
                           value="<?= htmlspecialchars($_POST['unit_cost'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Total Amount (Auto-calculated) -->
                <div>
                    <label for="total_amount" class="block text-sm font-medium text-gray-700 mb-2">Total Amount (Rs)</label>
                    <input type="number" id="total_amount" name="total_amount" step="0.01" readonly
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-600">
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select id="payment_method" name="payment_method"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="cod" <?= (($_POST['payment_method'] ?? 'cod') == 'cod') ? 'selected' : '' ?>>Cash on Delivery</option>
                        <option value="paid" <?= (($_POST['payment_method'] ?? '') == 'paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="partial" <?= (($_POST['payment_method'] ?? '') == 'partial') ? 'selected' : '' ?>>Partial Payment</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="pending" <?= (($_POST['status'] ?? 'pending') == 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= (($_POST['status'] ?? '') == 'paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="partial" <?= (($_POST['status'] ?? '') == 'partial') ? 'selected' : '' ?>>Partial</option>
                        <option value="remaining" <?= (($_POST['status'] ?? '') == 'remaining') ? 'selected' : '' ?>>Remaining</option>
                    </select>
                </div>

                <!-- Purchase Date -->
                <div>
                    <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                    <input type="date" id="purchase_date" name="purchase_date"
                           value="<?= htmlspecialchars($_POST['purchase_date'] ?? date('Y-m-d')) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Expected Delivery -->
                <div>
                    <label for="expected_delivery" class="block text-sm font-medium text-gray-700 mb-2">Expected Delivery</label>
                    <input type="date" id="expected_delivery" name="expected_delivery"
                           value="<?= htmlspecialchars($_POST['expected_delivery'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              placeholder="Additional notes about this purchase..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="<?= \App\Core\View::url('admin/inventory/purchases') ?>" 
                   class="px-6 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Create Purchase
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-fill supplier and unit cost when product is selected
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const supplierId = selectedOption.getAttribute('data-supplier');
    const unitCost = selectedOption.getAttribute('data-cost');
    
    if (supplierId && unitCost) {
        document.getElementById('supplier_id').value = supplierId;
        document.getElementById('supplier_display').value = selectedOption.text.split(' - ')[1] || '';
        document.getElementById('unit_cost').value = unitCost;
        
        // Recalculate total amount
        calculateTotalAmount();
    }
});

// Calculate total amount when quantity or unit cost changes
function calculateTotalAmount() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const totalAmount = quantity * unitCost;
    
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
}

document.getElementById('quantity').addEventListener('input', calculateTotalAmount);
document.getElementById('unit_cost').addEventListener('input', calculateTotalAmount);

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
    
    if (quantity <= 0) {
        e.preventDefault();
        alert('Quantity must be greater than 0');
        return;
    }
    
    if (unitCost <= 0) {
        e.preventDefault();
        alert('Unit cost must be greater than 0');
        return;
    }
    
    if (totalAmount <= 0) {
        e.preventDefault();
        alert('Total amount must be greater than 0');
        return;
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
