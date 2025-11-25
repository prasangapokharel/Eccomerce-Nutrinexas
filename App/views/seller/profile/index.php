<?php ob_start(); ?>
<?php $page = 'profile'; ?>

<?php
$socialMedia = !empty($seller['social_media']) ? json_decode($seller['social_media'], true) : [];
$workingHours = !empty($seller['working_hours']) ? json_decode($seller['working_hours'], true) : [];
$days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">My Profile</h1>
        <p class="text-gray-600">Update your profile information and password</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="<?= \App\Core\View::url('seller/profile') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($seller['name'] ?? '') ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($seller['email'] ?? '') ?>" disabled
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($seller['company_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea id="address" name="address" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($seller['address'] ?? '') ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Store Description</label>
                    <textarea id="description" name="description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                              placeholder="Describe your store, products, and services..."><?= htmlspecialchars($seller['description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">This description will be visible to customers</p>
                </div>

                <div class="md:col-span-2">
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-2">Logo URL (CDN)</label>
                    <input type="url" id="logo_url" name="logo_url" value="<?= htmlspecialchars($seller['logo_url'] ?? '') ?>" 
                           placeholder="https://example.com/logo.png"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Enter the full CDN URL for your logo image</p>
                </div>

                <div class="md:col-span-2">
                    <label for="cover_banner_url" class="block text-sm font-medium text-gray-700 mb-2">Cover Banner URL (CDN)</label>
                    <input type="url" id="cover_banner_url" name="cover_banner_url" value="<?= htmlspecialchars($seller['cover_banner_url'] ?? '') ?>" 
                           placeholder="https://example.com/banner.jpg"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Enter the full CDN URL for your store cover banner (recommended: 1200x300px)</p>
                </div>

                <div class="md:col-span-2 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Social Media Links</h3>
                </div>

                <div>
                    <label for="facebook" class="block text-sm font-medium text-gray-700 mb-2">Facebook</label>
                    <input type="url" id="facebook" name="facebook" value="<?= htmlspecialchars($socialMedia['facebook'] ?? '') ?>"
                           placeholder="https://facebook.com/yourpage"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="instagram" class="block text-sm font-medium text-gray-700 mb-2">Instagram</label>
                    <input type="url" id="instagram" name="instagram" value="<?= htmlspecialchars($socialMedia['instagram'] ?? '') ?>"
                           placeholder="https://instagram.com/yourpage"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="twitter" class="block text-sm font-medium text-gray-700 mb-2">Twitter</label>
                    <input type="url" id="twitter" name="twitter" value="<?= htmlspecialchars($socialMedia['twitter'] ?? '') ?>"
                           placeholder="https://twitter.com/yourpage"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="linkedin" class="block text-sm font-medium text-gray-700 mb-2">LinkedIn</label>
                    <input type="url" id="linkedin" name="linkedin" value="<?= htmlspecialchars($socialMedia['linkedin'] ?? '') ?>"
                           placeholder="https://linkedin.com/company/yourpage"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="youtube" class="block text-sm font-medium text-gray-700 mb-2">YouTube</label>
                    <input type="url" id="youtube" name="youtube" value="<?= htmlspecialchars($socialMedia['youtube'] ?? '') ?>"
                           placeholder="https://youtube.com/yourchannel"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="md:col-span-2 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Working Hours</h3>
                    <div class="space-y-3">
                        <?php foreach ($days as $key => $day): ?>
                            <?php $hours = $workingHours[$key] ?? ['open' => '09:00', 'close' => '18:00', 'closed' => 0]; ?>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg">
                                <div class="w-24">
                                    <label class="block text-sm font-medium text-gray-700"><?= $day ?></label>
                                </div>
                                <input type="checkbox" 
                                       id="working_hours_<?= $key ?>_checkbox"
                                       <?= !$hours['closed'] ? 'checked' : '' ?>
                                       onchange="toggleDayHours('<?= $key ?>')"
                                       class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                <span class="text-sm text-gray-600">Open</span>
                                <input type="time" 
                                       name="working_hours_<?= $key ?>_open" 
                                       value="<?= $hours['open'] ?? '09:00' ?>"
                                       id="hours_<?= $key ?>_open"
                                       class="px-3 py-1 border border-gray-300 rounded-lg text-sm"
                                       <?= $hours['closed'] ? 'disabled' : '' ?>>
                                <span class="text-gray-500">to</span>
                                <input type="time" 
                                       name="working_hours_<?= $key ?>_close" 
                                       value="<?= $hours['close'] ?? '18:00' ?>"
                                       id="hours_<?= $key ?>_close"
                                       class="px-3 py-1 border border-gray-300 rounded-lg text-sm"
                                       <?= $hours['closed'] ? 'disabled' : '' ?>>
                                <input type="hidden" 
                                       name="working_hours_<?= $key ?>_closed" 
                                       value="<?= $hours['closed'] ? '1' : '0' ?>"
                                       id="hours_<?= $key ?>_closed">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="md:col-span-2 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h3>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Leave empty to keep current password"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= \App\Core\View::url('seller/dashboard') ?>" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDayHours(day) {
    const checkbox = document.getElementById(`working_hours_${day}_checkbox`);
    const openInput = document.getElementById(`hours_${day}_open`);
    const closeInput = document.getElementById(`hours_${day}_close`);
    const closedInput = document.getElementById(`hours_${day}_closed`);
    
    if (checkbox.checked) {
        openInput.disabled = false;
        closeInput.disabled = false;
        closedInput.value = '0';
    } else {
        openInput.disabled = true;
        closeInput.disabled = true;
        closedInput.value = '1';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php foreach ($days as $key => $day): ?>
        toggleDayHours('<?= $key ?>');
    <?php endforeach; ?>
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
