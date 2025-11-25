<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Seller</h1>
            <p class="text-gray-600">Add a new seller account</p>
        </div>
        <div class="flex gap-3">
            <a href="<?= \App\Core\View::url('admin/seller') ?>" 
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="<?= \App\Core\View::url('admin/seller/create') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                            <input type="text" id="name" name="name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="email" name="email" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" id="password" name="password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                            <input type="tel" id="phone" name="phone"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                            <input type="text" id="company_name" name="company_name"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea id="address" name="address" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select id="status" name="status" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="active">Active</option>
                                <option value="inactive" selected>Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div>
                            <label for="is_approved" class="block text-sm font-medium text-gray-700 mb-2">Approval Status</label>
                            <select id="is_approved" name="is_approved"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approved</option>
                            </select>
                        </div>
                        <div>
                            <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">Commission Rate (%)</label>
                            <input type="number" id="commission_rate" name="commission_rate" step="0.01" min="0" max="100" 
                                   value="10.00"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Documents</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-2">Business Logo URL (CDN)</label>
                            <input type="url" id="logo_url" name="logo_url"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="https://example.com/logo.png">
                        </div>
                        <div>
                            <label for="citizenship_document_url" class="block text-sm font-medium text-gray-700 mb-2">Citizenship Document URL</label>
                            <input type="url" id="citizenship_document_url" name="citizenship_document_url"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="https://example.com/citizenship.pdf">
                        </div>
                        <div>
                            <label for="pan_vat_type" class="block text-sm font-medium text-gray-700 mb-2">PAN/VAT Type</label>
                            <select id="pan_vat_type" name="pan_vat_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Select Type</option>
                                <option value="PAN">PAN</option>
                                <option value="VAT">VAT</option>
                                <option value="Both">Both</option>
                                <option value="None">None</option>
                            </select>
                        </div>
                        <div>
                            <label for="pan_vat_number" class="block text-sm font-medium text-gray-700 mb-2">PAN/VAT Number</label>
                            <input type="text" id="pan_vat_number" name="pan_vat_number"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>
                        <div>
                            <label for="pan_vat_document_url" class="block text-sm font-medium text-gray-700 mb-2">PAN/VAT Document URL</label>
                            <input type="url" id="pan_vat_document_url" name="pan_vat_document_url"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="https://example.com/pan-vat.pdf">
                        </div>
                        <div>
                            <label for="cheque_qr_url" class="block text-sm font-medium text-gray-700 mb-2">Cheque/QR Code URL</label>
                            <input type="url" id="cheque_qr_url" name="cheque_qr_url"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                   placeholder="https://example.com/cheque_qr.png">
                        </div>
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                            <select id="payment_method" name="payment_method"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Select Method</option>
                                <option value="Cheque">Cheque</option>
                                <option value="QR">QR Code</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="payment_details" class="block text-sm font-medium text-gray-700 mb-2">Payment Details</label>
                            <textarea id="payment_details" name="payment_details" rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary"
                                      placeholder="Enter payment details (e.g., bank account, QR code details, etc.)"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= \App\Core\View::url('admin/seller') ?>" 
                   class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                    Create Seller
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

