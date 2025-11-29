<?php ob_start(); ?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Create Site-Wide Sale</h1>
            <p class="mt-1 text-sm text-gray-500">Create a site-wide sale event. Only one active sale can exist at a time.</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/sales') ?>" class="btn btn-outline">
            <i class="fas fa-arrow-left mr-2"></i>Back to Sales
        </a>
    </div>

    <?php if (!empty($activeSale)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-yellow-800">Active Sale Exists</h3>
                    <p class="mt-1 text-sm text-yellow-700">
                        An active sale "<?= htmlspecialchars($activeSale['sale_name']) ?>" is currently running. 
                        You must end it before creating a new sale.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="<?= \App\Core\View::url('admin/sales/create') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="p-6 space-y-6">
                <div>
                    <label for="sale_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Sale Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="sale_name" 
                           name="sale_name" 
                           value="Site-Wide Sale"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                           required>
                    <p class="text-xs text-gray-500 mt-1">e.g., "11.11 Sale", "Big Sale", "Flash Sale"</p>
                </div>

                <div>
                    <label for="sale_percent" class="block text-sm font-medium text-gray-700 mb-2">
                        Sale Percent <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" 
                               id="sale_percent" 
                               name="sale_percent" 
                               min="1" 
                               max="99" 
                               step="0.1"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Enter discount percentage"
                               required>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Discount percentage (1-99%). Products with sale='on' will show this discount.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date & Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="start_date" 
                               name="start_date" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               required>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            End Date & Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="end_date" 
                               name="end_date" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                               required>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>How It Works
                    </h3>
                    <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                        <li>Only products with <code class="bg-blue-100 px-1 rounded">sale='on'</code> will be affected by this sale</li>
                        <li>If product has a <code class="bg-blue-100 px-1 rounded">sale_price</code>, it will be used</li>
                        <li>Otherwise, discount will be calculated: <code class="bg-blue-100 px-1 rounded">price - (price × sale_percent / 100)</code></li>
                        <li>Only one active sale can exist at a time</li>
                    </ul>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end space-x-3">
                <a href="<?= \App\Core\View::url('admin/sales') ?>" class="btn btn-outline">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary" <?= !empty($activeSale) ? 'disabled' : '' ?>>
                    <i class="fas fa-fire mr-2"></i>Create Sale
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
