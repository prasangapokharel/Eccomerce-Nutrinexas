<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create Payment Gateway</h1>
        <a href="<?= \App\Core\View::url('admin/payment') ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Back to Gateways
        </a>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['flash_error'] ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="md:col-span-2">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Gateway Name *</label>
                <input type="text" name="name" id="name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
                <input type="text" name="slug" id="slug" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Used in URLs and code (lowercase, no spaces)</p>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Gateway Type *</label>
                <select name="type" id="type" required onchange="toggleParameterFields()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Select Type</option>
                    <option value="manual">Manual Payment</option>
                    <option value="digital">Digital Wallet</option>
                    <option value="cod">Cash on Delivery</option>
                </select>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="sort_order" value="0" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Status Settings -->
            <div class="md:col-span-2 mt-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Status Settings</h2>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">Enable Gateway</label>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_test_mode" id="is_test_mode" value="1"
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_test_mode" class="ml-2 block text-sm text-gray-900">Test Environment</label>
                <p class="ml-2 text-xs text-gray-500">Enable for testing purposes</p>
            </div>
        </div>

        <!-- Manual Payment Parameters -->
        <div id="manual-params" class="mt-8" style="display: none">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Bank Transfer Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                    <input type="text" name="account_number" id="account_number"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" name="account_name" id="account_name"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <input type="text" name="branch" id="branch"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label for="swift_code" class="block text-sm font-medium text-gray-700 mb-2">SWIFT Code</label>
                    <input type="text" name="swift_code" id="swift_code"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Digital Wallet Parameters -->
        <div id="digital-params" class="mt-8" style="display: none">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">API Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="secret_key" class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                    <input type="password" name="secret_key" id="secret_key"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="merchant_id" class="block text-sm font-medium text-gray-700 mb-2">Merchant ID</label>
                    <input type="text" name="merchant_id" id="merchant_id"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="api_key" class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                    <input type="password" name="api_key" id="api_key"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                    <input type="url" name="webhook_url" id="webhook_url"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Gateway Logo</h2>
            <div>
                <input type="file" name="logo" id="logo" accept="image/*"
                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-xs text-gray-500 mt-1">Upload a logo for this payment gateway (optional)</p>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="mt-8 flex justify-between">
            <a href="<?= \App\Core\View::url('admin/payment') ?>" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Create Gateway
            </button>
        </div>
    </form>
</div>

<script>
function toggleParameterFields() {
    const type = document.getElementById('type').value;
    
    // Hide all parameter sections
    document.getElementById('manual-params').style.display = 'none';
    document.getElementById('digital-params').style.display = 'none';
    
    // Show relevant section
    if (type === 'manual') {
        document.getElementById('manual-params').style.display = 'block';
    } else if (type === 'digital') {
        document.getElementById('digital-params').style.display = 'block';
    }
}

// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    document.getElementById('slug').value = slug;
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>