<?php ob_start(); ?>

<div class="p-6 space-y-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Ad Cost Settings</h1>
        <a href="<?= \App\Core\View::url('admin/ads') ?>" class="bg-gray-200 text-gray-800 px-4 py-2 rounded-2xl hover:bg-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Ads
        </a>
    </div>

    <!-- Minimum CPC Rate Setting -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Minimum Cost-Per-Click Rate</h2>
        <p class="text-sm text-gray-600 mb-4">Set the minimum cost-per-click rate for all ads. Sellers will be charged this rate multiplied by the total number of clicks they request.</p>
        
        <form action="<?= \App\Core\View::url('admin/ads/settings/update') ?>" method="POST" class="space-y-4">
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum CPC Rate (Rs.) <span class="text-red-500">*</span>
                </label>
                <input 
                    type="number" 
                    name="min_cpc_rate" 
                    step="0.01" 
                    min="0.01" 
                    value="<?= number_format($minCpcRate ?? 2.00, 2) ?>" 
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary text-lg"
                    placeholder="2.00"
                >
                <p class="text-xs text-gray-500 mt-2">
                    This rate will be used to calculate: Required Balance = Minimum CPC Rate × Total Clicks
                </p>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-2">How it works:</h3>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Seller enters total number of clicks they want (e.g., 100 clicks)</li>
                    <li>• System calculates: Required Balance = Min CPC Rate × Total Clicks</li>
                    <li>• Example: If Min CPC = Rs. 2.50 and Total Clicks = 100, Required Balance = Rs. 250.00</li>
                    <li>• Balance is locked when ad is activated</li>
                    <li>• Seller is charged this rate per click until clicks are exhausted</li>
                </ul>
            </div>
            
            <div class="flex justify-end pt-4">
                <button type="submit" class="bg-primary text-white px-6 py-3 rounded-2xl font-semibold hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Minimum CPC Rate
                </button>
            </div>
        </form>
    </div>

    <!-- Current Setting Display -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
        <h3 class="font-semibold text-gray-700 mb-3">Current Setting</h3>
        <div class="flex items-center gap-4">
            <div class="bg-white rounded-lg px-6 py-4 border border-green-300">
                <p class="text-sm text-gray-600 mb-1">Minimum CPC Rate</p>
                <p class="text-3xl font-bold text-primary">Rs. <?= number_format($minCpcRate ?? 2.00, 2) ?></p>
            </div>
            <div class="flex-1">
                <p class="text-sm text-gray-600">
                    This rate applies to all product ads. Sellers cannot set a rate lower than this minimum.
                </p>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
