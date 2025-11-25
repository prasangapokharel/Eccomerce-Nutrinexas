<?php ob_start(); ?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Ad Cost Plan</h1>
        <a href="<?= \App\Core\View::url('admin/ads/costs') ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-2xl hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Costs
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <form action="<?= \App\Core\View::url('admin/ads/costs/update/' . $cost['id']) ?>" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ad Type</label>
                <input type="text" value="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $cost['ad_type_name']))) ?>" disabled class="w-full px-3 py-2 border border-gray-300 rounded-2xl bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Ad type cannot be changed</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
                <input type="number" name="duration_days" required min="1" value="<?= $cost['duration_days'] ?>" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary" placeholder="e.g., 7, 14, 30">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cost Amount (Rs.) <span class="text-red-500">*</span></label>
                <input type="number" name="cost_amount" required step="0.01" min="0" value="<?= number_format($cost['cost_amount'], 2, '.', '') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary" placeholder="0.00">
                <p class="text-xs text-gray-500 mt-1">Recommended: Rs. 500-2000 for Nepal market</p>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-primary text-white px-6 py-3 rounded-2xl font-semibold hover:bg-primary-dark transition-colors">
                    Update Cost Plan
                </button>
                <a href="<?= \App\Core\View::url('admin/ads/costs') ?>" class="flex-1 bg-gray-200 text-gray-800 px-6 py-3 rounded-2xl font-semibold hover:bg-gray-300 transition-colors text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

