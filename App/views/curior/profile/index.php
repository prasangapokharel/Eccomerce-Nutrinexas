<?php
$page = 'profile';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Profile</h1>
    <p class="text-gray-600 mt-2">Manage your courier profile and settings</p>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="<?= \App\Core\View::url('curior/profile/update') ?>" method="POST">
        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($curior['name'] ?? '') ?>" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($curior['phone'] ?? '') ?>" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($curior['email'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                <textarea name="address" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"><?= htmlspecialchars($curior['address'] ?? '') ?></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City <span class="text-red-500">*</span></label>
                <select name="city" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select City</option>
                    <?php
                    $cities = [
                        'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Bharatpur', 
                        'Biratnagar', 'Birgunj', 'Dharan', 'Butwal', 'Hetauda',
                        'Nepalgunj', 'Itahari', 'Tulsipur', 'Kalaiya', 'Jitpur',
                        'Inaruwa', 'Janakpur', 'Bhimdatta', 'Dhangadhi', 'Birendranagar',
                        'Ghorahi', 'Tikapur', 'Tansen', 'Baglung', 'Gulariya',
                        'Rajbiraj', 'Lahan', 'Siddharthanagar', 'Bhadrapur', 'Damak',
                        'Bardibas', 'Malangwa', 'Banepa', 'Panauti', 'Dhankuta',
                        'Ilam', 'Phidim', 'Bhojpur', 'Diktel', 'Okhaldhunga',
                        'Ramechhap', 'Manthali', 'Charikot', 'Jiri', 'Sindhuli',
                        'Jaleshwar', 'Siraha', 'Mechinagar', 'Birtamod', 'Kakarbhitta'
                    ];
                    sort($cities);
                    $currentCity = trim($curior['city'] ?? '');
                    foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>" <?= (strcasecmp($currentCity, $city) === 0) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($currentCity)): ?>
                    <p class="text-xs text-green-600 mt-1">Current city: <?= htmlspecialchars($currentCity) ?></p>
                <?php else: ?>
                    <p class="text-xs text-gray-500 mt-1">Select your operating city for auto-assignment</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Branch (Optional)</label>
                <input type="text" name="branch" value="<?= htmlspecialchars($curior['branch'] ?? '') ?>"
                       placeholder="e.g., Main Branch, North Branch"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-save mr-2"></i>Update Profile
            </button>
        </div>
    </form>
</div>

<!-- Change Password -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Change Password</h2>
    <form id="changePasswordForm">
        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                <input type="password" name="current_password" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                <input type="password" name="new_password" required minlength="6"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="6"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-key mr-2"></i>Change Password
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        alert('New passwords do not match');
        return;
    }
    
    fetch('<?= \App\Core\View::url('curior/profile/change-password') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password changed successfully!');
            this.reset();
        } else {
            alert('Error: ' + (data.message || 'Failed to change password'));
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

