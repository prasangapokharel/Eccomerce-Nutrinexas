<?php ob_start(); ?>

<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Become a Seller
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Register your business and start selling on NutriNexus
            </p>
        </div>

        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="list-disc list-inside text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6 bg-white p-8 rounded-lg shadow" action="<?= \App\Core\View::url('seller/register') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="md:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Personal Information</h3>
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" id="phone" name="phone" required
                           value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Company Name
                    </label>
                    <input type="text" id="company_name" name="company_name"
                           value="<?= htmlspecialchars($formData['company_name'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Address
                    </label>
                    <textarea id="address" name="address" rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"><?= htmlspecialchars($formData['address'] ?? '') ?></textarea>
                </div>

                <!-- Documents Section -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Documents (CDN Links)</h3>
                    <p class="text-sm text-gray-600 mb-4">Upload your documents to a CDN and paste the URLs here</p>
                </div>

                <div>
                    <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Business Logo URL
                    </label>
                    <input type="url" id="logo_url" name="logo_url"
                           value="<?= htmlspecialchars($formData['logo_url'] ?? '') ?>"
                           placeholder="https://cdn.example.com/logo.png"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Upload your logo to a CDN and paste the URL</p>
                </div>

                <div>
                    <label for="citizenship_document_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Citizenship Document URL <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="citizenship_document_url" name="citizenship_document_url" required
                           value="<?= htmlspecialchars($formData['citizenship_document_url'] ?? '') ?>"
                           placeholder="https://cdn.example.com/citizenship.pdf"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-gray-500 mt-1">Upload citizenship document to CDN and paste URL</p>
                </div>

                <div>
                    <label for="pan_vat_type" class="block text-sm font-medium text-gray-700 mb-2">
                        PAN/VAT Type
                    </label>
                    <select id="pan_vat_type" name="pan_vat_type"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Select Type</option>
                        <option value="PAN" <?= ($formData['pan_vat_type'] ?? '') === 'PAN' ? 'selected' : '' ?>>PAN</option>
                        <option value="VAT" <?= ($formData['pan_vat_type'] ?? '') === 'VAT' ? 'selected' : '' ?>>VAT</option>
                        <option value="Both" <?= ($formData['pan_vat_type'] ?? '') === 'Both' ? 'selected' : '' ?>>Both</option>
                        <option value="None" <?= ($formData['pan_vat_type'] ?? '') === 'None' ? 'selected' : '' ?>>None</option>
                    </select>
                </div>

                <div>
                    <label for="pan_vat_number" class="block text-sm font-medium text-gray-700 mb-2">
                        PAN/VAT Number
                    </label>
                    <input type="text" id="pan_vat_number" name="pan_vat_number"
                           value="<?= htmlspecialchars($formData['pan_vat_number'] ?? '') ?>"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label for="pan_vat_document_url" class="block text-sm font-medium text-gray-700 mb-2">
                        PAN/VAT Document URL
                    </label>
                    <input type="url" id="pan_vat_document_url" name="pan_vat_document_url"
                           value="<?= htmlspecialchars($formData['pan_vat_document_url'] ?? '') ?>"
                           placeholder="https://cdn.example.com/pan-vat.pdf"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Payment Information -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Information</h3>
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Method
                    </label>
                    <select id="payment_method" name="payment_method"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Select Method</option>
                        <option value="bank_transfer" <?= ($formData['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                        <option value="esewa" <?= ($formData['payment_method'] ?? '') === 'esewa' ? 'selected' : '' ?>>eSewa</option>
                        <option value="khalti" <?= ($formData['payment_method'] ?? '') === 'khalti' ? 'selected' : '' ?>>Khalti</option>
                        <option value="upi" <?= ($formData['payment_method'] ?? '') === 'upi' ? 'selected' : '' ?>>UPI</option>
                    </select>
                </div>

                <div>
                    <label for="payment_details" class="block text-sm font-medium text-gray-700 mb-2">
                        Payment Details
                    </label>
                    <input type="text" id="payment_details" name="payment_details"
                           value="<?= htmlspecialchars($formData['payment_details'] ?? '') ?>"
                           placeholder="Account number, eSewa ID, Khalti ID, or UPI ID"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <!-- Password Section -->
                <div class="md:col-span-2 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Account Security</h3>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Register as Seller
                </button>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="<?= \App\Core\View::url('seller/login') ?>" class="font-medium text-primary hover:text-primary-dark">
                        Login here
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/../layouts/main.php'; ?>

