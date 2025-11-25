<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Create New Customer</h1>
            <p class="mt-1 text-sm text-gray-500">Add a new customer to the system</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/customers') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Customers
            </a>
        </div>
    </div>

    <!-- Customer Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
        </div>
        
        <form method="POST" action="<?= \App\Core\View::url('admin/customers/create') ?>" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Customer Name -->
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Customer Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="customer_name" 
                           name="customer_name" 
                           required
                           placeholder="Enter customer full name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                           value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>">
                </div>
                
                <!-- Contact Number -->
                <div>
                    <label for="contact_no" class="block text-sm font-medium text-gray-700 mb-2">
                        Contact Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" 
                           id="contact_no" 
                           name="contact_no" 
                           required
                           placeholder="+977-98XXXXXXXX" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                           value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>">
                    <p class="mt-1 text-xs text-gray-500">Format: +977-98XXXXXXXX or 98XXXXXXXX</p>
                </div>
                
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="customer@example.com" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <p class="mt-1 text-xs text-gray-500">Optional - for communication purposes</p>
                </div>
                
                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                        Address <span class="text-red-500">*</span>
                    </label>
                    <textarea id="address" 
                              name="address" 
                              required
                              rows="3"
                              placeholder="Enter complete address including city, state, and postal code" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-100">
                <a href="<?= \App\Core\View::url('admin/customers') ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Create Customer
                </button>
            </div>
        </form>
    </div>
    
    <!-- Help Section -->
    <div class="bg-blue-50 rounded-xl p-6 border border-blue-200">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-600 text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Customer Information Guidelines</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li><strong>Customer Name:</strong> Use the full legal name as it appears on official documents</li>
                        <li><strong>Contact Number:</strong> Enter the primary phone number in Nepal format (+977-98XXXXXXXX)</li>
                        <li><strong>Address:</strong> Include complete address details for delivery purposes</li>
                        <li><strong>Email:</strong> Optional but recommended for order confirmations and updates</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const contactInput = document.getElementById('contact_no');
    
    // Format contact number as user types
    contactInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
        
        // Format as +977-98XXXXXXXX
        if (value.length >= 10) {
            if (value.startsWith('977')) {
                value = '+' + value.substring(0, 3) + '-' + value.substring(3);
            } else if (value.startsWith('98')) {
                value = '+977-' + value;
            } else if (value.startsWith('09')) {
                value = '+977-' + value.substring(1);
            }
        }
        
        e.target.value = value;
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const customerName = document.getElementById('customer_name').value.trim();
        const contactNo = contactInput.value.trim();
        const address = document.getElementById('address').value.trim();
        
        if (!customerName) {
            e.preventDefault();
            alert('Please enter customer name');
            document.getElementById('customer_name').focus();
            return;
        }
        
        if (!contactNo) {
            e.preventDefault();
            alert('Please enter contact number');
            contactInput.focus();
            return;
        }
        
        if (!address) {
            e.preventDefault();
            alert('Please enter address');
            document.getElementById('address').focus();
            return;
        }
        
        // Validate contact number format
        const phoneRegex = /^\+977-[78]\d{8}$|^9[78]\d{8}$|^0?9[78]\d{8}$/;
        if (!phoneRegex.test(contactNo)) {
            e.preventDefault();
            alert('Please enter a valid Nepal phone number format');
            contactInput.focus();
            return;
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
