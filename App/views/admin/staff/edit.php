<?php ob_start(); ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Staff Member</h1>
        <p class="text-gray-600">Update staff member information</p>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form method="POST" class="p-6 space-y-6" id="staffEditForm" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name *
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="<?= htmlspecialchars($staff['name']) ?>"
                           required
                           aria-describedby="name-error"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter full name">
                    <div id="name-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= htmlspecialchars($staff['email']) ?>"
                           required
                           aria-describedby="email-error"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter email address">
                    <div id="email-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number
                    </label>
                    <input type="tel" 
                           id="phone" 
                           name="phone"
                           value="<?= htmlspecialchars($staff['phone'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                           placeholder="Enter phone number">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select id="status" 
                            name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="active" <?= $staff['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $staff['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    New Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password"
                       minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter new password (leave blank to keep current)">
                <p class="text-sm text-gray-500 mt-1">Leave blank to keep current password. If changing, must be at least 6 characters long</p>
            </div>

            <!-- Address -->
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                    Address
                </label>
                <textarea id="address" 
                          name="address" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Enter address"><?= htmlspecialchars($staff['address'] ?? '') ?></textarea>
            </div>

            <!-- Assigned Cities -->
            <div>
                <label for="assigned_cities" class="block text-sm font-medium text-gray-700 mb-2">
                    Assigned Cities
                </label>
                <select id="assigned_cities" 
                        name="assigned_cities[]" 
                        multiple
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <?php 
                    $assignedCities = json_decode($staff['assigned_cities'] ?? '[]', true);
                    $cities = [
                        'Kathmandu', 'Lalitpur', 'Bhaktapur', 'Pokhara', 'Bharatpur', 
                        'Biratnagar', 'Birgunj', 'Dharan', 'Butwal', 'Hetauda',
                        'Nepalgunj', 'Itahari', 'Tulsipur', 'Kalaiya', 'Jitpur',
                        'Madhyapur Thimi', 'Birendranagar', 'Ghorahi', 'Tikapur', 'Kirtipur'
                    ];
                    foreach ($cities as $city): ?>
                        <option value="<?= $city ?>" <?= in_array($city, $assignedCities) ? 'selected' : '' ?>>
                            <?= $city ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Hold Ctrl/Cmd to select multiple cities. Orders from these cities will be automatically assigned to this staff member.</p>
            </div>

            <!-- Staff Info -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Staff Information</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Created:</span>
                        <span class="font-medium"><?= date('M d, Y', strtotime($staff['created_at'])) ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Last Updated:</span>
                        <span class="font-medium"><?= date('M d, Y', strtotime($staff['updated_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="<?= \App\Core\View::url('admin/staff') ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>Update Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    
    form.addEventListener('submit', function(e) {
        if (password.value && password.value.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
            password.focus();
        }
    });
    
    // Enhanced form submission with AJAX
    const form = document.getElementById('staffEditForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate required fields
            const requiredFields = ['name', 'email'];
            let isValid = true;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const errorElement = document.getElementById(`${fieldId}-error`);
                if (field && !field.value.trim()) {
                    field.classList.add('border-red-500');
                    if (errorElement) {
                        errorElement.textContent = 'This field is required';
                        errorElement.classList.remove('hidden');
                    }
                    isValid = false;
                } else if (field) {
                    field.classList.remove('border-red-500');
                    if (errorElement) {
                        errorElement.classList.add('hidden');
                    }
                }
            });
            
            if (!isValid) {
                showToast('Please fix the errors before submitting', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            }
            
            // Submit form
            const formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = '<?= \App\Core\View::url('admin/staff') ?>';
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update Staff Member';
                }
            });
        });
    }
});

// Toast notification system
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const icon = type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
    
    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform transition-all duration-300 translate-x-full`;
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
