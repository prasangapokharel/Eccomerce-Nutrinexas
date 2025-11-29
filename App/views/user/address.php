<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="<?= \App\Core\View::url('user/addresses') ?>" class="text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-2"></i> Back to Addresses
            </a>
        </div>
        
        <!-- FIXED: Use $address instead of $user_id to determine edit mode -->
        <h1 class="text-3xl font-bold text-gray-900 mb-8"><?= isset($address) && $address ? 'Edit Address' : 'Add New Address' ?></h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-error/10 border border-error text-error px-4 py-3 rounded relative mb-6" role="alert">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-none shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Address Information</h2>
            </div>
            
            <form action="<?= \App\Core\View::url('user/address' . (isset($address) && $address ? '/' . $address['id'] : '')) ?>" method="post" class="p-6">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="recipient_name" class="block text-sm font-medium text-gray-700 mb-1">Recipient Name</label>
                        <input type="text" name="recipient_name" id="recipient_name" 
                               value="<?= isset($data['recipient_name']) ? htmlspecialchars($data['recipient_name']) : (isset($address['recipient_name']) ? htmlspecialchars($address['recipient_name']) : '') ?>" 
                               class="input native-input" required>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="phone" 
                               value="<?= isset($data['phone']) ? htmlspecialchars($data['phone']) : (isset($address['phone']) ? htmlspecialchars($address['phone']) : '') ?>" 
                               class="input native-input" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address_line1" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                        <input type="text" name="address_line1" id="address_line1" 
                               value="<?= isset($data['address_line1']) ? htmlspecialchars($data['address_line1']) : (isset($address['address_line1']) ? htmlspecialchars($address['address_line1']) : '') ?>" 
                               class="input native-input" required>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address_line2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2 (Optional)</label>
                        <input type="text" name="address_line2" id="address_line2" 
                               value="<?= isset($data['address_line2']) ? htmlspecialchars($data['address_line2']) : (isset($address['address_line2']) ? htmlspecialchars($address['address_line2']) : '') ?>" 
                               class="input native-input">
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" name="city" id="city" 
                               value="<?= isset($data['city']) ? htmlspecialchars($data['city']) : (isset($address['city']) ? htmlspecialchars($address['city']) : '') ?>" 
                               class="input native-input" required>
                    </div>
                    
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                        <input type="text" name="state" id="state" 
                               value="<?= isset($data['state']) ? htmlspecialchars($data['state']) : (isset($address['state']) ? htmlspecialchars($address['state']) : '') ?>" 
                               class="input native-input" required>
                    </div>
                    
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code <span class="text-gray-400 text-xs">(Optional)</span></label>
                        <input type="text" name="postal_code" id="postal_code" 
                               value="<?= isset($data['postal_code']) ? htmlspecialchars($data['postal_code']) : (isset($address['postal_code']) ? htmlspecialchars($address['postal_code']) : '') ?>" 
                               class="input native-input">
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <input type="text" name="country" id="country" 
                               value="<?= isset($data['country']) ? htmlspecialchars($data['country']) : (isset($address['country']) ? htmlspecialchars($address['country']) : 'Nepal') ?>" 
                               class="input native-input" required>
                    </div>
                </div>
                
                <div class="mt-6">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_default" id="is_default" 
                               <?= (isset($data['is_default']) && $data['is_default']) || (isset($address['is_default']) && $address['is_default']) ? 'checked' : '' ?> 
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_default" class="ml-2 block text-sm text-gray-700">
                            Set as default address
                        </label>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end gap-3">
                    <a href="<?= \App\Core\View::url('user/addresses') ?>" class="btn btn-outline">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <?= isset($address) && $address ? 'Update Address' : 'Save Address' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>