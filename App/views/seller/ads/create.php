<?php ob_start(); ?>
<?php $page = 'ads'; ?>

<div class="space-y-6 max-w-4xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Create New Ad</h1>
        <p class="text-gray-600 mt-1">Promote your products with targeted advertisements</p>
    </div>

    <form action="<?= \App\Core\View::url('seller/ads/create') ?>" method="POST" class="bg-white rounded-lg shadow p-6 space-y-6">
        <!-- Ad Type (Hidden - Only Product Internal for Sellers) -->
        <input type="hidden" name="ads_type_id" id="ads_type_id" value="<?= $adTypes[0]['id'] ?? '' ?>">
        
        <!-- Product Selection -->
        <div id="product_section">
            <label class="block text-sm font-medium text-gray-700 mb-2">Product <span class="text-red-500">*</span></label>
            <select name="product_id" id="product_id" required class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" 
                            data-name="<?= htmlspecialchars($product['product_name']) ?>"
                            data-image="<?= htmlspecialchars($product['image_url'] ?? '') ?>"
                            data-price="<?= isset($product['sale_price']) && $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'] ?? '0' ?>">
                        <?= htmlspecialchars($product['product_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Product Preview -->
        <div id="product_preview" class="hidden mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Product Preview</h4>
            <div id="preview_content" class="flex items-center gap-3">
                <img id="preview_image" src="" alt="" class="w-16 h-16 object-cover rounded-lg" style="display: none;">
                <div>
                    <p id="preview_name" class="font-medium text-gray-900"></p>
                    <p id="preview_price" class="text-sm text-gray-600"></p>
                </div>
            </div>
        </div>

        <!-- Duration (Simple days input) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Duration (Days) <span class="text-red-500">*</span></label>
            <input type="number" name="duration_days" id="duration_days" required min="1" max="365" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Enter number of days (e.g., 7)">
            <p class="text-sm text-gray-500 mt-1">How many days you want your ad to run</p>
        </div>

        <!-- Start Date -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date <span class="text-red-500">*</span></label>
            <input type="date" name="start_date" id="start_date" required min="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary">
        </div>

        <!-- Total Clicks -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Total Clicks <span class="text-red-500">*</span></label>
            <input type="number" name="total_clicks" id="total_clicks" required min="1" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Enter total number of clicks (e.g., 100)">
            <p class="text-sm text-gray-500 mt-1">Total number of clicks you want for this ad</p>
        </div>

        <!-- Cost Estimation -->
        <div id="cost_estimation" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Cost Estimation</h4>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Minimum CPC Rate:</span>
                    <span class="font-semibold" id="min_cpc_display">Rs. <span id="min_cpc_rate"><?= number_format($minCpcRate ?? 2, 2) ?></span></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Clicks:</span>
                    <span class="font-semibold" id="total_clicks_display">0</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-blue-200">
                    <span class="text-gray-700 font-semibold">Required Balance:</span>
                    <span class="text-primary font-bold text-lg" id="required_balance">Rs. 0.00</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-primary focus:border-primary" placeholder="Additional notes..."></textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="flex gap-3 pt-4">
            <button type="submit" class="flex-1 bg-primary text-white px-6 py-3 rounded-2xl font-semibold hover:bg-primary-dark transition-colors">
                Create Ad
            </button>
            <a href="<?= \App\Core\View::url('seller/ads') ?>" class="flex-1 bg-gray-200 text-gray-800 px-6 py-3 rounded-2xl font-semibold hover:bg-gray-300 transition-colors text-center">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const productPreview = document.getElementById('product_preview');
    const previewImage = document.getElementById('preview_image');
    const previewName = document.getElementById('preview_name');
    const previewPrice = document.getElementById('preview_price');
    const startDate = document.getElementById('start_date');
    const durationDays = document.getElementById('duration_days');
    const totalClicks = document.getElementById('total_clicks');
    const minCpcRate = parseFloat(document.getElementById('min_cpc_rate').textContent);
    const requiredBalance = document.getElementById('required_balance');
    const totalClicksDisplay = document.getElementById('total_clicks_display');

    // Calculate end date when start date or duration changes
    function calculateEndDate() {
        if (startDate.value && durationDays.value) {
            const start = new Date(startDate.value);
            start.setDate(start.getDate() + parseInt(durationDays.value));
            const endDateInput = document.createElement('input');
            endDateInput.type = 'hidden';
            endDateInput.name = 'end_date';
            endDateInput.id = 'end_date';
            endDateInput.value = start.toISOString().split('T')[0];
            const existing = document.getElementById('end_date');
            if (existing) {
                existing.value = endDateInput.value;
            } else {
                document.querySelector('form').appendChild(endDateInput);
            }
        }
    }

    // Calculate cost estimation
    function calculateCost() {
        const clicks = parseInt(totalClicks.value) || 0;
        totalClicksDisplay.textContent = clicks.toLocaleString();
        const cost = clicks * minCpcRate;
        requiredBalance.textContent = 'Rs. ' + cost.toFixed(2);
    }

    // Handle product selection - show preview
    productSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const name = selectedOption.dataset.name;
            const image = selectedOption.dataset.image;
            const price = selectedOption.dataset.price;
            
            previewName.textContent = name;
            previewPrice.textContent = 'Rs. ' + parseFloat(price).toFixed(2);
            
            if (image) {
                previewImage.src = image;
                previewImage.style.display = 'block';
            } else {
                previewImage.style.display = 'none';
            }
            
            productPreview.classList.remove('hidden');
        } else {
            productPreview.classList.add('hidden');
        }
    });

    // Calculate end date and cost on input changes
    startDate.addEventListener('change', calculateEndDate);
    durationDays.addEventListener('change', calculateEndDate);
    durationDays.addEventListener('input', calculateEndDate);
    totalClicks.addEventListener('input', calculateCost);
    totalClicks.addEventListener('change', calculateCost);

    // Initial calculation
    calculateCost();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
