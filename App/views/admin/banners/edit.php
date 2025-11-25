<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Edit Banner Ad</h1>
            <p class="text-gray-600 mt-1">Update external banner slot placement (Recommended: 1290 × 493 pixels)</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= \App\Core\View::url('admin/banners/edit/' . $banner['id']) ?>" method="POST" class="bg-white rounded-lg shadow-md p-8">
            <div class="mb-6">
                <label for="banner_image" class="block text-sm font-medium text-gray-700 mb-2">
                    Banner Image URL <span class="text-red-500">*</span>
                </label>
                <input type="url" 
                       id="banner_image" 
                       name="banner_image" 
                       value="<?= htmlspecialchars($data['banner_image'] ?? $banner['banner_image'] ?? '') ?>"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                       placeholder="https://example.com/banner.jpg">
                <p class="mt-1 text-sm text-gray-500">Recommended size: 1290 × 493 pixels (aspect ratio ~2.6:1)</p>
                <?php if (!empty($errors['banner_image'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['banner_image']) ?></p>
                <?php endif; ?>
                <?php if (!empty($banner['banner_image'])): ?>
                    <div class="mt-4">
                        <img src="<?= htmlspecialchars($banner['banner_image']) ?>" 
                             alt="Banner Preview" 
                             class="max-w-full h-auto rounded-lg border"
                             onerror="this.style.display='none';">
                    </div>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="banner_link" class="block text-sm font-medium text-gray-700 mb-2">
                    Banner Link URL (Optional)
                </label>
                <input type="url" 
                       id="banner_link" 
                       name="banner_link" 
                       value="<?= htmlspecialchars($data['banner_link'] ?? $banner['banner_link'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                       placeholder="https://example.com/page">
                <p class="mt-1 text-sm text-gray-500">URL to redirect when banner is clicked</p>
                <?php if (!empty($errors['banner_link'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['banner_link']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                    Start Date <span class="text-red-500">*</span>
                </label>
                <input type="date" 
                       id="start_date" 
                       name="start_date" 
                       value="<?= htmlspecialchars($data['start_date'] ?? $banner['start_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                       required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <p class="mt-1 text-sm text-gray-500">End date will be automatically set to 7 days after start date (1 week duration)</p>
                <input type="hidden" 
                       id="end_date" 
                       name="end_date" 
                       value="">
                <?php if (!empty($errors['start_date'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['start_date']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="slot_key" class="block text-sm font-medium text-gray-700 mb-2">
                    Placement Slot <span class="text-red-500">*</span>
                </label>
                <select id="slot_key"
                        name="slot_key"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    <option value="">Select a slot</option>
                    <?php if (!empty($slotOptions)): ?>
                        <?php foreach ($slotOptions as $tier => $slots): ?>
                            <?php $tierMeta = \App\Config\BannerSlotConfig::TIERS[$tier] ?? null; ?>
                            <optgroup label="<?= htmlspecialchars(($tierMeta['label'] ?? strtoupper($tier)) . ' · Rs ' . number_format($tierMeta['price'] ?? 0, 2) . ' / week') ?>">
                                <?php foreach ($slots as $key => $slot): ?>
                                    <?php $selected = ($data['slot_key'] ?? $banner['slot_key'] ?? '') === $key ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($slot['label']) ?> — <?= htmlspecialchars($slot['description']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php if (!empty($errors['slot_key'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['slot_key']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                </label>
                <select id="status" 
                        name="status" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    <option value="active" <?= ($data['status'] ?? $banner['status']) === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($data['status'] ?? $banner['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes (Optional)
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                          placeholder="Additional notes about this banner ad"><?= htmlspecialchars($data['notes'] ?? $banner['notes'] ?? '') ?></textarea>
            </div>

            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-4">Statistics</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Reach</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($banner['reach'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Clicks</p>
                        <p class="text-2xl font-bold text-gray-900"><?= number_format($banner['click'] ?? 0) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">CTR</p>
                        <?php 
                        $reach = (int)($banner['reach'] ?? 0);
                        $clicks = (int)($banner['click'] ?? 0);
                        $ctr = $reach > 0 ? ($clicks / $reach) * 100 : 0;
                        $ctrColor = $ctr >= 2 ? 'text-green-600' : ($ctr >= 1 ? 'text-yellow-600' : 'text-gray-600');
                        ?>
                        <p class="text-2xl font-bold <?= $ctrColor ?>"><?= number_format($ctr, 2) ?>%</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <a href="<?= \App\Core\View::url('admin/banners') ?>" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">
                    Update Banner Ad
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-calculate end_date when start_date changes (start_date + 7 days)
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    if (startDateInput && endDateInput) {
        function calculateEndDate() {
            const startDate = startDateInput.value;
            if (startDate) {
                const start = new Date(startDate);
                start.setDate(start.getDate() + 7); // Add 7 days
                const endDate = start.toISOString().split('T')[0];
                endDateInput.value = endDate;
            }
        }
        
        // Calculate on page load
        calculateEndDate();
        
        // Calculate when start date changes
        startDateInput.addEventListener('change', calculateEndDate);
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

