<?php ob_start(); ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Staff Member</h1>
        <p class="text-gray-600">Add a new staff member to your team</p>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form method="POST" class="p-6 space-y-6" id="staffForm" novalidate>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name *
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
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
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    Password *
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       minlength="6"
                       aria-describedby="password-error password-help"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Enter password (minimum 6 characters)">
                <p id="password-help" class="text-sm text-gray-500 mt-1">Password must be at least 6 characters long</p>
                <div id="password-error" class="text-red-500 text-sm mt-1 hidden" role="alert"></div>
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
                              placeholder="Enter address"></textarea>
                </div>

                <!-- Assignment Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Type</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="assignment_type" value="city" class="mr-2" checked>
                            <span class="text-sm">City-based Assignment</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="assignment_type" value="product" class="mr-2">
                            <span class="text-sm">Product-based Assignment</span>
                        </label>
                    </div>
                </div>

                <!-- Assigned Cities -->
                <div id="cityAssignment">
                    <label for="assigned_cities" class="block text-sm font-medium text-gray-700 mb-2">
                        Assigned Cities
                    </label>
                    <select id="assigned_cities" 
                            name="assigned_cities[]" 
                            multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <!-- Cities will be loaded dynamically -->
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Orders from these cities will be automatically assigned to this staff member.</p>
                </div>

                <!-- Assigned Products -->
                <div id="productAssignment" class="hidden">
                    <label for="assigned_products" class="block text-sm font-medium text-gray-700 mb-2">
                        Assigned Products
                    </label>
                    <select id="assigned_products" 
                            name="assigned_products[]" 
                            multiple
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        <!-- Products will be loaded via AJAX -->
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Orders containing these products will be automatically assigned to this staff member.</p>
                </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="<?= \App\Core\View::url('admin/staff') ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        id="submitBtn"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i><span class="btn-text">Create Staff Member</span>
                    <span class="btn-loading hidden">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Creating...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification System -->
<div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<script>
// Toast notification system
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
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

// Form validation and assignment type switching
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const assignmentType = document.querySelectorAll('input[name="assignment_type"]');
    const cityAssignment = document.getElementById('cityAssignment');
    const productAssignment = document.getElementById('productAssignment');
    
    // Load cities and products on page load
    loadCities();
    
    // Handle assignment type switching
    assignmentType.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'city') {
                cityAssignment.classList.remove('hidden');
                productAssignment.classList.add('hidden');
            } else {
                cityAssignment.classList.add('hidden');
                productAssignment.classList.remove('hidden');
                loadProducts();
            }
        });
    });
    
    // Enhanced form validation
    function validateField(field, errorId) {
        const value = field.value.trim();
        const errorElement = document.getElementById(errorId);
        let isValid = true;
        let errorMessage = '';
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        } else if (field.type === 'email' && value && !isValidEmail(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        } else if (field.id === 'password' && value && value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters long';
        }
        
        if (isValid) {
            field.classList.remove('border-red-500');
            field.classList.add('border-gray-300');
            errorElement.classList.add('hidden');
        } else {
            field.classList.remove('border-gray-300');
            field.classList.add('border-red-500');
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('hidden');
        }
        
        return isValid;
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Real-time validation
    const requiredFields = ['name', 'email', 'password'];
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', () => validateField(field, `${fieldId}-error`));
            field.addEventListener('input', () => {
                if (field.classList.contains('border-red-500')) {
                    validateField(field, `${fieldId}-error`);
                }
            });
        }
    });
    
    // Form submission with enhanced validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all required fields
        let isFormValid = true;
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field && !validateField(field, `${fieldId}-error`)) {
                isFormValid = false;
            }
        });
        
        if (!isFormValid) {
            showToast('Please fix the errors before submitting', 'error');
            return;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoading = submitBtn.querySelector('.btn-loading');
        
        submitBtn.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
        
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
            // Reset button state
            submitBtn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
        });
    });
    
    // Load cities from database
    function loadCities() {
        console.log('Loading cities...');
        fetch('<?= \App\Core\View::url('admin/staff/getCities') ?>')
            .then(response => {
                console.log('Cities response:', response);
                return response.json();
            })
            .then(data => {
                console.log('Cities data:', data);
                if (data.success) {
                    const select = document.getElementById('assigned_cities');
                    select.innerHTML = '';
                    data.cities.forEach(city => {
                        const option = document.createElement('option');
                        option.value = city.city;
                        option.textContent = city.city;
                        select.appendChild(option);
                    });
                    console.log('Cities loaded successfully');
                } else {
                    console.error('Failed to load cities:', data.message);
                }
            })
            .catch(error => console.error('Error loading cities:', error));
    }
    
    // Load products for product assignment
    function loadProducts() {
        console.log('Loading products...');
        if (document.getElementById('assigned_products').children.length <= 1) {
            fetch('<?= \App\Core\View::url('admin/staff/getProducts') ?>')
                .then(response => {
                    console.log('Products response:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Products data:', data);
                    if (data.success) {
                        const select = document.getElementById('assigned_products');
                        select.innerHTML = '';
                        data.products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.name;
                            select.appendChild(option);
                        });
                        console.log('Products loaded successfully');
                    } else {
                        console.error('Failed to load products:', data.message);
                    }
                })
                .catch(error => console.error('Error loading products:', error));
        }
    }
    
    form.addEventListener('submit', function(e) {
        if (password.value.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters long');
            password.focus();
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
