<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Payment Gateway - <?= htmlspecialchars($gateway['name']) ?></h1>
        <a href="<?= \App\Core\View::url('admin/payment') ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Back to Gateways
        </a>
    </div>

    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="md:col-span-2">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Gateway Name *</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($gateway['name']) ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug *</label>
                <input type="text" name="slug" id="slug" value="<?= htmlspecialchars($gateway['slug']) ?>" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Used in URLs and code (lowercase, no spaces)</p>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Gateway Type *</label>
                <select name="type" id="type" required onchange="toggleParameterFields()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="manual" <?= $gateway['type'] === 'manual' ? 'selected' : '' ?>>Manual Payment</option>
                    <option value="digital" <?= $gateway['type'] === 'digital' ? 'selected' : '' ?>>Digital Wallet</option>
                    <option value="cod" <?= $gateway['type'] === 'cod' ? 'selected' : '' ?>>Cash on Delivery</option>
                </select>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="sort_order" value="<?= $gateway['sort_order'] ?>" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($gateway['description']) ?></textarea>
            </div>

            <div class="md:col-span-2">
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Logo URL</label>
                <input type="url" name="logo" id="logo" value="<?= htmlspecialchars($gateway['logo'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://example.com/logo.png">
                <p class="text-xs text-gray-500 mt-1">URL to the payment gateway logo image</p>
            </div>

            <!-- Status Settings -->
            <div class="md:col-span-2 mt-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Status Settings</h2>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= $gateway['is_active'] ? 'checked' : '' ?>
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-900">Enable Gateway</label>
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_test_mode" id="is_test_mode" value="1" <?= $gateway['is_test_mode'] ? 'checked' : '' ?>
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_test_mode" class="ml-2 block text-sm text-gray-900">Test Environment</label>
                <p class="ml-2 text-xs text-gray-500">Enable for testing purposes</p>
            </div>
        </div>

        <!-- Gateway Parameters -->
        <?php 
        $parameters = json_decode($gateway['parameters'], true) ?? [];
        ?>

        <!-- Manual Payment Parameters -->
        <div id="manual-params" class="mt-8" style="display: <?= $gateway['type'] === 'manual' ? 'block' : 'none' ?>">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Bank Transfer Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" value="<?= htmlspecialchars($parameters['bank_name'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                    <input type="text" name="account_number" id="account_number" value="<?= htmlspecialchars($parameters['account_number'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="account_name" class="block text-sm font-medium text-gray-700 mb-2">Account Name</label>
                    <input type="text" name="account_name" id="account_name" value="<?= htmlspecialchars($parameters['account_name'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="branch" class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                    <input type="text" name="branch" id="branch" value="<?= htmlspecialchars($parameters['branch'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="md:col-span-2">
                    <label for="swift_code" class="block text-sm font-medium text-gray-700 mb-2">SWIFT Code</label>
                    <input type="text" name="swift_code" id="swift_code" value="<?= htmlspecialchars($parameters['swift_code'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Digital Wallet Parameters -->
        <div id="digital-params" class="mt-8" style="display: <?= $gateway['type'] === 'digital' ? 'block' : 'none' ?>">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">API Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Khalti Parameters -->
                <div class="khalti-params" style="display: <?= $gateway['slug'] === 'khalti' ? 'block' : 'none' ?>">
                    <div class="md:col-span-2 mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Khalti Configuration</h3>
                        <p class="text-sm text-gray-600">Configure Khalti payment gateway settings</p>
                    </div>

                    <div class="md:col-span-2 mb-4">
                        <h4 class="text-md font-medium text-gray-800">Test Environment</h4>
                        <p class="text-xs text-gray-600">Credentials for testing payments</p>
                    </div>

                    <div>
                        <label for="test_secret_key" class="block text-sm font-medium text-gray-700 mb-2">Test Secret Key</label>
                        <div class="relative">
                            <input type="password" name="test_secret_key" id="test_secret_key" value="<?= htmlspecialchars($parameters['test_secret_key'] ?? '') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter test secret key">
                            <button type="button" onclick="togglePasswordVisibility('test_secret_key')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="test_api_url" class="block text-sm font-medium text-gray-700 mb-2">Test API URL</label>
                        <input type="url" name="test_api_url" id="test_api_url" value="<?= htmlspecialchars($parameters['test_api_url'] ?? 'https://a.khalti.com/api/v2/epayment/initiate/') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="https://a.khalti.com/api/v2/epayment/initiate/">
                    </div>

                    <div class="md:col-span-2 mt-4 mb-4">
                        <h4 class="text-md font-medium text-gray-800">Live Environment</h4>
                        <p class="text-xs text-gray-600">Credentials for production payments</p>
                    </div>

                    <div>
                        <label for="secret_key" class="block text-sm font-medium text-gray-700 mb-2">Live Secret Key</label>
                        <div class="relative">
                            <input type="password" name="secret_key" id="secret_key" value="<?= htmlspecialchars($parameters['secret_key'] ?? '') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter live secret key">
                            <button type="button" onclick="togglePasswordVisibility('secret_key')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="live_api_url" class="block text-sm font-medium text-gray-700 mb-2">Live API URL</label>
                        <input type="url" name="live_api_url" id="live_api_url" value="<?= htmlspecialchars($parameters['live_api_url'] ?? 'https://khalti.com/api/v2/epayment/initiate/') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="https://khalti.com/api/v2/epayment/initiate/">
                    </div>

                </div>

                <!-- eSewa Parameters -->
                <div class="esewa-params" style="display: <?= $gateway['slug'] === 'esewa' ? 'block' : 'none' ?>">
                    <div class="md:col-span-2 mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Test Environment Credentials</h3>
                        <p class="text-sm text-gray-600">Use these credentials for testing eSewa payments</p>
                    </div>
                    
                    <div>
                        <label for="test_merchant_id" class="block text-sm font-medium text-gray-700 mb-2">Test Merchant ID</label>
                        <input type="text" name="test_merchant_id" id="test_merchant_id" value="<?= htmlspecialchars($parameters['test_merchant_id'] ?? 'EPAYTEST') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="test_secret_key" class="block text-sm font-medium text-gray-700 mb-2">Test Secret Key</label>
                        <div class="relative">
                            <input type="password" name="test_secret_key" id="test_secret_key" value="<?= htmlspecialchars($parameters['test_secret_key'] ?? '8gBm/:&EnhH.1/q') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('test_secret_key')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="test_token" class="block text-sm font-medium text-gray-700 mb-2">Test Token</label>
                        <input type="text" name="test_token" id="test_token" value="<?= htmlspecialchars($parameters['test_token'] ?? '123456') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="test_client_id" class="block text-sm font-medium text-gray-700 mb-2">Test Client ID</label>
                        <input type="text" name="test_client_id" id="test_client_id" value="<?= htmlspecialchars($parameters['test_client_id'] ?? 'JB0BBQ4aD0UqIThFJwAKBgAXEUkEGQUBBAwdOgABHD4DChwUAB0R') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="test_client_secret" class="block text-sm font-medium text-gray-700 mb-2">Test Client Secret</label>
                        <div class="relative">
                            <input type="password" name="test_client_secret" id="test_client_secret" value="<?= htmlspecialchars($parameters['test_client_secret'] ?? 'BhwIWQQADhIYSxILExMcAgFXFhcOBwAKBgAXEQ==') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('test_client_secret')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2 mt-6 mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Live Environment Credentials</h3>
                        <p class="text-sm text-gray-600">Use these credentials for production eSewa payments</p>
                    </div>

                    <div>
                        <label for="live_merchant_id" class="block text-sm font-medium text-gray-700 mb-2">Live Merchant ID</label>
                        <input type="text" name="live_merchant_id" id="live_merchant_id" value="<?= htmlspecialchars($parameters['live_merchant_id'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="live_secret_key" class="block text-sm font-medium text-gray-700 mb-2">Live Secret Key</label>
                        <div class="relative">
                            <input type="password" name="live_secret_key" id="live_secret_key" value="<?= htmlspecialchars($parameters['live_secret_key'] ?? '') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('live_secret_key')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="live_token" class="block text-sm font-medium text-gray-700 mb-2">Live Token</label>
                        <input type="text" name="live_token" id="live_token" value="<?= htmlspecialchars($parameters['live_token'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="live_client_id" class="block text-sm font-medium text-gray-700 mb-2">Live Client ID</label>
                        <input type="text" name="live_client_id" id="live_client_id" value="<?= htmlspecialchars($parameters['live_client_id'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="live_client_secret" class="block text-sm font-medium text-gray-700 mb-2">Live Client Secret</label>
                        <div class="relative">
                            <input type="password" name="live_client_secret" id="live_client_secret" value="<?= htmlspecialchars($parameters['live_client_secret'] ?? '') ?>"
                                   class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="button" onclick="togglePasswordVisibility('live_client_secret')" 
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-2 mt-6 mb-4">
                        <h3 class="text-lg font-medium text-gray-900">API URLs</h3>
                        <p class="text-sm text-gray-600">Configure the API endpoints for test and live environments</p>
                    </div>

                    <div>
                        <label for="test_payment_url" class="block text-sm font-medium text-gray-700 mb-2">Test Payment URL</label>
                        <input type="url" name="test_payment_url" id="test_payment_url" value="<?= htmlspecialchars($parameters['test_payment_url'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="test_status_url" class="block text-sm font-medium text-gray-700 mb-2">Test Status URL</label>
                        <input type="url" name="test_status_url" id="test_status_url" value="<?= htmlspecialchars($parameters['test_status_url'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="live_payment_url" class="block text-sm font-medium text-gray-700 mb-2">Live Payment URL</label>
                        <input type="url" name="live_payment_url" id="live_payment_url" value="<?= htmlspecialchars($parameters['live_payment_url'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="live_status_url" class="block text-sm font-medium text-gray-700 mb-2">Live Status URL</label>
                        <input type="url" name="live_status_url" id="live_status_url" value="<?= htmlspecialchars($parameters['live_status_url'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                </div>

                <!-- MyPay Parameters -->
                <div class="mypay-params" style="display: <?= $gateway['slug'] === 'mypay' ? 'block' : 'none' ?>">
                    <div>
                        <label for="merchant_username" class="block text-sm font-medium text-gray-700 mb-2">Merchant Username</label>
                        <input type="text" name="merchant_username" id="merchant_username" value="<?= htmlspecialchars($parameters['merchant_username'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="merchant_password" class="block text-sm font-medium text-gray-700 mb-2">Merchant API Password</label>
                        <input type="password" name="merchant_password" id="merchant_password" value="<?= htmlspecialchars($parameters['merchant_password'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="merchant_id" class="block text-sm font-medium text-gray-700 mb-2">Merchant ID</label>
                        <input type="text" name="merchant_id" id="merchant_id" value="<?= htmlspecialchars($parameters['merchant_id'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="api_key" class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                        <input type="password" name="api_key" id="api_key" value="<?= htmlspecialchars($parameters['api_key'] ?? '') ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Currency Configuration -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Supported Currencies</h2>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Currency</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Min Limit</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Max Limit</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">% Charge</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fixed Charge</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $currencies = $gateway['currencies'] ?? [];
                            if (empty($currencies)) {
                                $currencies = [['currency_code' => 'NPR', 'currency_symbol' => '₹', 'conversion_rate' => 1.0000, 'min_limit' => null, 'max_limit' => null, 'percentage_charge' => 0, 'fixed_charge' => 0]];
                            }
                            ?>
                            <?php foreach ($currencies as $index => $currency): ?>
                                <tr>
                                    <td class="px-4 py-2">
                                        <select name="currencies[<?= $index ?>][currency_code]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                            <option value="NPR" <?= ($currency['currency_code'] ?? '') === 'NPR' ? 'selected' : '' ?>>NPR - Nepalese Rupee</option>
                                            <option value="USD" <?= ($currency['currency_code'] ?? '') === 'USD' ? 'selected' : '' ?>>USD - US Dollar</option>
                                            <option value="EUR" <?= ($currency['currency_code'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR - Euro</option>
                                            <option value="INR" <?= ($currency['currency_code'] ?? '') === 'INR' ? 'selected' : '' ?>>INR - Indian Rupee</option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="currencies[<?= $index ?>][currency_symbol]" value="<?= htmlspecialchars($currency['currency_symbol'] ?? '₹') ?>"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="currencies[<?= $index ?>][conversion_rate]" value="<?= $currency['conversion_rate'] ?? 1.0000 ?>" step="0.0001"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="currencies[<?= $index ?>][min_limit]" value="<?= $currency['min_limit'] ?? '' ?>" step="0.01"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="currencies[<?= $index ?>][max_limit]" value="<?= $currency['max_limit'] ?? '' ?>" step="0.01"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="currencies[<?= $index ?>][percentage_charge]" value="<?= $currency['percentage_charge'] ?? 0 ?>" step="0.01" min="0" max="100"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="currencies[<?= $index ?>][fixed_charge]" value="<?= $currency['fixed_charge'] ?? 0 ?>" step="0.01" min="0"
                                               class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Logo Upload -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Gateway Logo</h2>
            <div class="flex items-center space-x-4">
                <?php if (!empty($gateway['logo'])): ?>
                    <img src="<?= htmlspecialchars($gateway['logo']) ?>" alt="Current Logo" class="w-16 h-16 object-contain border border-gray-300 rounded" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="w-16 h-16 bg-gray-200 border border-gray-300 rounded flex items-center justify-center text-gray-500 text-xs" style="display: none;">
                        No Logo
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 bg-gray-200 border border-gray-300 rounded flex items-center justify-center text-gray-500 text-xs">
                        No Logo
                    </div>
                <?php endif; ?>
                <div>
                    <input type="file" name="logo_file" id="logo_file" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">Upload a logo for this payment gateway (optional)</p>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="mt-8 flex justify-between">
            <a href="<?= \App\Core\View::url('admin/payment') ?>" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Update Gateway
            </button>
        </div>
    </form>
</div>

<script>
function toggleParameterFields() {
    const type = document.getElementById('type').value;
    const slug = '<?= $gateway['slug'] ?>';
    
    // Hide all parameter sections
    document.getElementById('manual-params').style.display = 'none';
    document.getElementById('digital-params').style.display = 'none';
    
    // Show relevant section
    if (type === 'manual') {
        document.getElementById('manual-params').style.display = 'block';
    } else if (type === 'digital') {
        document.getElementById('digital-params').style.display = 'block';
        
        // Show specific digital wallet params
        document.querySelectorAll('.khalti-params, .esewa-params, .mypay-params').forEach(el => {
            el.style.display = 'none';
        });
        
        if (slug === 'khalti') {
            document.querySelector('.khalti-params').style.display = 'block';
        } else if (slug === 'esewa') {
            document.querySelector('.esewa-params').style.display = 'block';
        } else if (slug === 'mypay') {
            document.querySelector('.mypay-params').style.display = 'block';
        }
    }
}

// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    document.getElementById('slug').value = slug;
});

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const svg = button.querySelector('svg');
    
    if (input.type === 'password') {
        input.type = 'text';
        // Change to eye-slash icon
        svg.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
        `;
    } else {
        input.type = 'password';
        // Change back to eye icon
        svg.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        `;
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>