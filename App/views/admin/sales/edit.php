<?php ob_start(); ?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Edit Site-Wide Sale</h1>
            <p class="mt-1 text-sm text-gray-500">Update sale details and discount percentage</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/sales') ?>" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Sales
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= \App\Core\View::url('admin/sales/update/' . $sale['id']) ?>" class="space-y-6">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <!-- Sale Name -->
            <div>
                <label for="sale_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Sale Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="sale_name" id="sale_name" required
                       value="<?= htmlspecialchars($sale['sale_name'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                       placeholder="e.g., Dashain Sale 2025, New Year Sale">
            </div>

            <!-- Discount Percent -->
            <div>
                <label for="discount_percent" class="block text-sm font-medium text-gray-700 mb-2">
                    Discount Percentage <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" name="discount_percent" id="discount_percent" required
                           min="1" max="99" step="0.01"
                           value="<?= htmlspecialchars($sale['discount_percent'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           placeholder="10">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Enter discount percentage (1-99%)</p>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date & Time <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="start_date" id="start_date" required
                       value="<?= date('Y-m-d\TH:i', strtotime($sale['start_date'])) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                    End Date & Time <span class="text-red-500">*</span>
                </label>
                <input type="datetime-local" name="end_date" id="end_date" required
                       value="<?= date('Y-m-d\TH:i', strtotime($sale['end_date'])) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <!-- Note -->
            <div>
                <label for="note" class="block text-sm font-medium text-gray-700 mb-2">
                    Note (Optional)
                </label>
                <textarea name="note" id="note" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                          placeholder="Additional notes about this sale..."><?= htmlspecialchars($sale['note'] ?? '') ?></textarea>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" 
                       <?= !empty($sale['is_active']) ? 'checked' : '' ?>
                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                    Activate sale
                </label>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Note:</strong> Updating this sale will reapply the discount to all active products.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                <a href="<?= \App\Core\View::url('admin/sales') ?>" class="btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn">
                    <i class="fas fa-save mr-2"></i>
                    Update Sale
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Validate end date is after start date
document.getElementById('end_date').addEventListener('change', function() {
    const startDate = document.getElementById('start_date').value;
    const endDate = this.value;
    
    if (startDate && endDate && endDate <= startDate) {
        alert('End date must be after start date');
        this.value = '';
    }
});

document.getElementById('start_date').addEventListener('change', function() {
    const startDate = this.value;
    const endDate = document.getElementById('end_date').value;
    
    if (startDate && endDate && endDate <= startDate) {
        document.getElementById('end_date').value = '';
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

