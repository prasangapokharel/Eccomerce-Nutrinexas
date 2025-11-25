<?php 
ob_start(); 
$title = $title ?? 'Delivery Charges Management';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Delivery Charges Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage delivery fees for different locations in Nepal</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <button onclick="openDefaultValueModal()" 
                    class="inline-flex items-center justify-center px-4 py-2 border border-primary text-sm font-medium rounded-lg text-primary bg-white hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-cog mr-2"></i>
                Set Default Value
            </button>
            <a href="<?= \App\Core\View::url('admin/delivery/create') ?>" 
               class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Add Location
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (\App\Core\Session::hasFlash('success')): ?>
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            <?= \App\Helpers\FlashHelper::getFlashMessage('success') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            <?= \App\Helpers\FlashHelper::getFlashMessage('error') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <!-- Delivery Charges Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Location
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Delivery Fee
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($charges)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            No delivery charges found. <a href="<?= \App\Core\View::url('admin/delivery/create') ?>" class="text-primary hover:text-primary-dark">Add your first location</a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($charges as $charge): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?= htmlspecialchars($charge['location_name']) ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?= $charge['location_name'] === 'Free' ? 'Free delivery option' : 'Delivery location' ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php if ($charge['charge'] == 0): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            Free
                                                        </span>
                                                    <?php else: ?>
                                                        रु<?= number_format($charge['charge'], 2) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="<?= \App\Core\View::url('admin/delivery/edit/' . $charge['id']) ?>" 
                                                       class="text-primary hover:text-primary-dark">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </a>
                                                    <?php if ($charge['location_name'] !== 'Free'): ?>
                                                        <a href="<?= \App\Core\View::url('admin/delivery/delete/' . $charge['id']) ?>" 
                                                           class="text-red-600 hover:text-red-900"
                                                           onclick="return confirm('Are you sure you want to delete this delivery charge?')">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </a>
                                                    <?php else: ?>
                                                        <button onclick="disableFreeDelivery(<?= $charge['id'] ?>)" 
                                                                class="text-red-600 hover:text-red-900" 
                                                                title="Disable Free Delivery">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                                                            </svg>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Free Delivery Toggle -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Free Delivery Settings</h3>
        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div>
                <h4 class="font-medium text-gray-900">Enable Free Delivery</h4>
                <p class="text-sm text-gray-500">When enabled, all locations will have free delivery</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="free-delivery-toggle" class="sr-only peer" onchange="toggleFreeDelivery()" <?= isset($isFreeDeliveryEnabled) && $isFreeDeliveryEnabled ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
        </div>
    </div>

    <!-- Popular Nepal Locations -->
    <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Popular Nepal Locations</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <?php
                    $popularLocations = [
                        'Kathmandu', 'Pokhara', 'Lalitpur', 'Bharatpur', 'Biratnagar', 'Birgunj',
                        'Butwal', 'Dharan', 'Nepalgunj', 'Itahari', 'Hetauda', 'Janakpur',
                        'Dhangadhi', 'Tulsipur', 'Kalaiya', 'Jitpur Simara', 'Kirtipur', 'Tikapur',
                        'Gulariya', 'Rajbiraj', 'Lahan', 'Patan', 'Madhyapur Thimi', 'Birendranagar'
                    ];
                    
                    $existingLocations = array_column($charges, 'location_name');
                    ?>
                    
                    <?php foreach ($popularLocations as $location): ?>
                        <?php if (!in_array($location, $existingLocations)): ?>
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                <span class="text-sm text-gray-700"><?= htmlspecialchars($location) ?></span>
                                <button onclick="quickAddLocation('<?= htmlspecialchars($location) ?>', 300)" 
                                        class="text-xs text-primary hover:text-primary-dark bg-transparent border-none cursor-pointer">
                                    Add
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Default Value Modal -->
<div id="defaultValueModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Set Default Delivery Fee</h3>
                <button onclick="closeDefaultValueModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-4">
                    Enter the default delivery fee amount that will be applied to all locations.
                </p>
                <label for="defaultFeeAmount" class="block text-sm font-medium text-gray-700 mb-2">
                    Delivery Fee Amount (रु)
                </label>
                <input type="number" 
                       id="defaultFeeAmount" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary" 
                       placeholder="Enter amount (e.g., 300)" 
                       min="0" 
                       step="0.01">
                <div id="defaultFeeError" class="text-red-600 text-sm mt-1 hidden"></div>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDefaultValueModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Cancel
                </button>
                <button onclick="saveDefaultValue()" 
                        class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary">
                    Save Default Fee
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openDefaultValueModal() {
    document.getElementById('defaultValueModal').classList.remove('hidden');
    document.getElementById('defaultFeeAmount').focus();
}

function closeDefaultValueModal() {
    document.getElementById('defaultValueModal').classList.add('hidden');
    document.getElementById('defaultFeeAmount').value = '';
    document.getElementById('defaultFeeError').classList.add('hidden');
}

function saveDefaultValue() {
    const amount = document.getElementById('defaultFeeAmount').value;
    const errorDiv = document.getElementById('defaultFeeError');
    
    // Validation
    if (!amount || amount === '') {
        errorDiv.textContent = 'Please enter a delivery fee amount';
        errorDiv.classList.remove('hidden');
        return;
    }
    
    const numAmount = parseFloat(amount);
    if (isNaN(numAmount) || numAmount < 0) {
        errorDiv.textContent = 'Please enter a valid positive number';
        errorDiv.classList.remove('hidden');
        return;
    }
    
    // Hide error if validation passes
    errorDiv.classList.add('hidden');
    
    // Confirm action
    if (!confirm(`Are you sure you want to set the default delivery fee to रु${numAmount.toFixed(2)} for all locations?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('default_fee', numAmount);
    
    fetch('<?= \App\Core\View::url('admin/delivery/setDefaultFee') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            closeDefaultValueModal();
            // Reload page to show updated delivery fees
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to set default delivery fee');
    });
}

function quickAddLocation(location, charge) {
    const formData = new FormData();
    formData.append('location_name', location);
    formData.append('charge', charge);
    
    fetch('<?= \App\Core\View::url('admin/delivery/quickAdd') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showAlert('success', data.message);
            // Reload page to show new entry
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to add delivery charge');
    });
}

function toggleFreeDelivery() {
    const toggle = document.getElementById('free-delivery-toggle');
    const isEnabled = toggle.checked;
    
    const formData = new FormData();
    formData.append('free_delivery', isEnabled ? '1' : '0');
    
    fetch('<?= \App\Core\View::url('admin/delivery/toggleFree') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Reload page to show updated delivery fees
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
            // Revert toggle state
            toggle.checked = !isEnabled;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to update free delivery setting');
        // Revert toggle state
        toggle.checked = !isEnabled;
    });
}

function disableFreeDelivery(chargeId) {
    if (!confirm('Are you sure you want to disable free delivery? This will restore default delivery charges for all locations.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('free_delivery', '0');
    
    fetch('<?= \App\Core\View::url('admin/delivery/toggleFree') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            // Reload page to show updated delivery fees
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to disable free delivery');
    });
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
    }`;
    alertDiv.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    ${type === 'success' ? 
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>' :
                        '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
                    }
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remove alert after 3 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

