<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Edit User</h1>
            <p class="mt-1 text-sm text-gray-500">Update user information</p>
        </div>
        <div>
            <a href="<?= \App\Core\View::url('admin/users') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>Back to Users
            </a>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form method="POST" action="<?= \App\Core\View::url('admin/editUser/' . $user['id']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" 
                           name="first_name" 
                           value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           required>
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input type="text" 
                           name="last_name" 
                           value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           required>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" 
                           name="email" 
                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                           required>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                    <input type="tel" 
                           name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" 
                           name="username" 
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                    <select name="role" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="customer" <?= ($user['role'] ?? 'customer') === 'customer' ? 'selected' : '' ?>>Customer</option>
                        <option value="admin" <?= ($user['role'] ?? 'customer') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($user['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= ($user['status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-6 flex items-center justify-end gap-4">
                <a href="<?= \App\Core\View::url('admin/users') ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    Update User
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

