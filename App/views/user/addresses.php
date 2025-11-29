<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">My Addresses</h1>
        
        <div class="mb-6 flex justify-end">
            <a href="<?= \App\Core\View::url('user/address') ?>" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i> Add New Address
            </a>
        </div>
        
        <?php if (empty($addresses)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-map-marker-alt text-5xl"></i>
                </div>
                <h2 class="text-xl font-semibold mb-2">No addresses found</h2>
                <p class="text-gray-600 mb-6">You haven't added any addresses yet.</p>
                <a href="<?= \App\Core\View::url('user/address') ?>" class="btn btn-primary">
                    Add Your First Address
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($addresses as $address): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-900">
                                <?= htmlspecialchars($address['recipient_name']) ?>
                                <?php if ($address['is_default']): ?>
                                    <span class="ml-2 px-2 py-1 bg-success/10 text-success text-xs rounded-full">Default</span>
                                <?php endif; ?>
                            </h2>
                            <div class="flex space-x-2">
                                <a href="<?= \App\Core\View::url('user/address/' . $address['id']) ?>" class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= \App\Core\View::url('user/deleteAddress/' . $address['id']) ?>" 
                                   onclick="return confirm('Are you sure you want to delete this address?')" 
                                   class="text-error hover:text-error-dark">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-600 mb-2"><?= htmlspecialchars($address['phone']) ?></p>
                            <p class="text-gray-600">
                                <?= htmlspecialchars($address['address_line1']) ?><br>
                                <?php if (!empty($address['address_line2'])): ?>
                                    <?= htmlspecialchars($address['address_line2']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($address['city']) ?>, <?= htmlspecialchars($address['state']) ?><?= !empty($address['postal_code']) ? ' ' . htmlspecialchars($address['postal_code']) : '' ?><br>
                                <?= htmlspecialchars($address['country']) ?>
                            </p>
                            
                            <?php if (!$address['is_default']): ?>
                                <div class="mt-4">
                                    <a href="<?= \App\Core\View::url('user/setDefaultAddress/' . $address['id']) ?>" 
                                       class="text-sm text-primary hover:text-primary-dark">
                                        Set as Default
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
