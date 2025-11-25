<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Merchant Payment Gateways</h1>
        <a href="<?= \App\Core\View::url('admin/payment/create') ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Add New Gateway
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($gateways as $gateway): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($gateway['name']) ?></h3>
                        <div class="flex space-x-2">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $gateway['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $gateway['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?= $gateway['is_test_mode'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $gateway['is_test_mode'] ? 'Test' : 'Live' ?>
                            </span>
                        </div>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($gateway['description']) ?></p>
                    
                    <?php 
                    $parameters = json_decode($gateway['parameters'], true);
                    if ($parameters): 
                    ?>
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Configuration:</h4>
                            <div class="space-y-1">
                                <?php foreach ($parameters as $key => $value): ?>
                                    <?php if (!empty($value)): ?>
                                        <div class="text-xs text-gray-600">
                                            <span class="font-medium"><?= ucfirst(str_replace('_', ' ', $key)) ?>:</span>
                                            <span class="text-green-600">✓ Configured</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-xs text-gray-600">
                                            <span class="font-medium"><?= ucfirst(str_replace('_', ' ', $key)) ?>:</span>
                                            <span class="text-red-600">✗ Not configured</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex space-x-2">
                        <a href="<?= \App\Core\View::url('admin/payment/edit/' . $gateway['id']) ?>" 
                           class="flex-1 bg-blue-600 text-white text-center py-2 px-4 rounded text-sm hover:bg-blue-700">
                            Configure
                        </a>
                        <button onclick="toggleStatus(<?= $gateway['id'] ?>)" 
                                class="px-3 py-2 border border-gray-300 rounded text-sm hover:bg-gray-50">
                            <?= $gateway['is_active'] ? 'Disable' : 'Enable' ?>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function toggleStatus(id) {
    fetch(`<?= ASSETS_URL ?>/admin/payment/toggleStatus/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>