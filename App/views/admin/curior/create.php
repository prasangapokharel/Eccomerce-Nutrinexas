<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Add Curior</h1>
            <p class="mt-1 text-sm text-gray-500">Create a new curior account</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/curior') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Curiors
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="<?= \App\Core\View::url('admin/curior/create') ?>" method="POST" id="curiorForm">
            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Curior Information</h2>
                <p class="text-sm text-gray-600 mt-1">Fill in the details to create a new curior account</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                    
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="<?= htmlspecialchars($data['name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter full name"
                               required>
                        <?php if (isset($errors['name'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['name']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($data['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter email address"
                               required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['email']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?= htmlspecialchars($data['phone'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter phone number"
                               required>
                        <?php if (isset($errors['phone'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['phone']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Address <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea id="address" 
                                  name="address" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                  placeholder="Enter curior address"><?= htmlspecialchars($data['address'] ?? '') ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['address']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm pr-10"
                                   placeholder="Enter password"
                                   required>
                            <button type="button" 
                                    onclick="togglePassword('password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600" id="password-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                        <?php if (isset($errors['password'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['password']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <select id="status" 
                                name="status" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <option value="active" <?= (isset($data['status']) && $data['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (isset($data['status']) && $data['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Set the initial status for this curior</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="<?= \App\Core\View::url('admin/curior') ?>" 
                       class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Create Curior
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}
</script>

<style>
/* Primary colors */
:root {
    --primary-color: #0A3167;
    --primary-dark: #082A5A;
}

.bg-primary {
    background-color: var(--primary-color);
}

.bg-primary-dark {
    background-color: var(--primary-dark);
}

.text-primary {
    color: var(--primary-color);
}

.border-primary {
    border-color: var(--primary-color);
}

.focus\:ring-primary:focus {
    --tw-ring-color: var(--primary-color);
}

.focus\:border-primary:focus {
    border-color: var(--primary-color);
}

.hover\:bg-primary-dark:hover {
    background-color: var(--primary-dark);
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
