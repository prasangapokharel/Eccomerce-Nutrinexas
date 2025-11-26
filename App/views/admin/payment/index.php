<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Payment Gateways</h1>
            <p class="mt-1 text-sm text-gray-500">Manage payment gateways and methods</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/payment/create') ?>" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
            <i class="fas fa-plus mr-2"></i>Add New Gateway
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?= $_SESSION['flash_error'] ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-primary/10 rounded-lg">
                    <i class="fas fa-credit-card text-primary text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Gateways</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= count($gateways) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Gateways</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= count(array_filter($gateways, function($g) { return $g['is_active']; })) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <i class="fas fa-wallet text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Digital Wallets</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= count(array_filter($gateways, function($g) { return $g['type'] === 'digital'; })) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-flask text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Test Mode</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= count(array_filter($gateways, function($g) { return $g['is_test_mode']; })) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($gateways as $gateway) {
        $tableData[] = [
            'id' => $gateway['id'],
            'gateway' => $gateway,
            'name' => $gateway['name'],
            'slug' => $gateway['slug'],
            'description' => $gateway['description'] ?? '',
            'type' => $gateway['type'],
            'is_active' => $gateway['is_active'],
            'is_test_mode' => $gateway['is_test_mode'],
            'sort_order' => $gateway['sort_order'],
            'created_at' => $gateway['created_at'],
            'logo' => $gateway['logo'] ?? null
        ];
    }

    $tableConfig = [
        'id' => 'gatewaysTable',
        'title' => 'Payment Gateways',
        'description' => 'Manage and configure payment gateways',
        'search' => true,
        'filters' => [
            [
                'label' => 'All Types',
                'url' => \App\Core\View::url('admin/payment'),
                'active' => true
            ],
            [
                'label' => 'Digital',
                'url' => \App\Core\View::url('admin/payment/merchant'),
                'active' => false
            ],
            [
                'label' => 'Manual',
                'url' => \App\Core\View::url('admin/payment/manual'),
                'active' => false
            ]
        ],
        'columns' => [
            [
                'key' => 'gateway',
                'label' => 'Gateway',
                'type' => 'custom',
                'render' => function($row) {
                    $gateway = $row['gateway'];
                    ob_start();
                    ?>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <?php if (!empty($gateway['logo'])): ?>
                                <img class="h-10 w-10 rounded-lg object-cover" src="<?= htmlspecialchars($gateway['logo']) ?>" alt="<?= htmlspecialchars($gateway['name']) ?>">
                            <?php else: ?>
                                <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                    <i class="fas fa-credit-card text-primary"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($gateway['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($gateway['slug']) ?></div>
                        </div>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'type',
                'label' => 'Type',
                'type' => 'custom',
                'render' => function($row) {
                    $type = $row['type'];
                    $typeLabels = [
                        'digital' => 'Digital Wallet',
                        'manual' => 'Manual Payment',
                        'cod' => 'Cash on Delivery'
                    ];
                    $typeColors = [
                        'digital' => 'bg-blue-100 text-blue-800',
                        'manual' => 'bg-yellow-100 text-yellow-800',
                        'cod' => 'bg-green-100 text-green-800'
                    ];
                    $badgeClass = $typeColors[$type] ?? 'bg-gray-100 text-gray-800';
                    $label = $typeLabels[$type] ?? ucfirst($type);
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $badgeClass . '">' . htmlspecialchars($label) . '</span>';
                }
            ],
            [
                'key' => 'is_active',
                'label' => 'Status',
                'type' => 'custom',
                'render' => function($row) {
                    $gateway = $row['gateway'];
                    ob_start();
                    ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               class="sr-only peer toggle-status" 
                               data-gateway-id="<?= $gateway['id'] ?>"
                               <?= $gateway['is_active'] ? 'checked' : '' ?>>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'is_test_mode',
                'label' => 'Environment',
                'type' => 'custom',
                'render' => function($row) {
                    $isTest = !empty($row['is_test_mode']) && $row['is_test_mode'] != '0';
                    $badgeClass = $isTest ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                    $label = $isTest ? 'Test' : 'Live';
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $badgeClass . '">' . htmlspecialchars($label) . '</span>';
                }
            ],
            [
                'key' => 'sort_order',
                'label' => 'Order',
                'type' => 'text'
            ],
            [
                'key' => 'created_at',
                'label' => 'Created',
                'type' => 'date'
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    $gateway = $row['gateway'];
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <a href="<?= \App\Core\View::url('admin/payment/edit/' . $gateway['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                           title="Edit Gateway">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if ($gateway['type'] !== 'cod'): ?>
                            <button onclick="toggleTestMode(<?= $gateway['id'] ?>)" 
                                    class="text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50 transition-colors p-1 rounded" 
                                    title="Toggle Test Mode">
                                <i class="fas fa-flask"></i>
                            </button>
                            <button onclick="deleteGateway(<?= $gateway['id'] ?>, '<?= htmlspecialchars($gateway['name']) ?>')" 
                                    class="text-red-600 hover:text-red-900 hover:bg-red-50 transition-colors p-1 rounded" 
                                    title="Delete Gateway">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/payment')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center">
            <i class="fas fa-spinner fa-spin text-primary text-xl mr-3"></i>
            <span>Processing...</span>
        </div>
    </div>
</div>

<script>
// Toggle Gateway Status
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.toggle-status');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const gatewayId = this.dataset.gatewayId;
            const isActive = this.checked;
            
            showLoading();
            
            const url = '<?= \App\Core\View::url('admin/payment/toggle') ?>/' + gatewayId;
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ is_active: isActive })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showNotification(data.message || 'Gateway status updated successfully', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to update gateway status', 'error');
                    this.checked = !isActive;
                }
            })
            .catch(error => {
                hideLoading();
                showNotification('Network error occurred', 'error');
                this.checked = !isActive;
            });
        });
    });
});

// Delete Gateway
function deleteGateway(gatewayId, gatewayName) {
    if (confirm(`Are you sure you want to delete "${gatewayName}"? This action cannot be undone.`)) {
        showLoading();
        
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        
        fetch(`<?= \App\Core\View::url('admin/payment/delete/') ?>${gatewayId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch {
                        throw new Error(text || `HTTP error! status: ${response.status}`);
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            hideLoading();
            if (data && data.success) {
                showNotification(data.message || 'Gateway deleted successfully', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data?.message || 'Failed to delete gateway', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Delete error:', error);
            showNotification('Network error occurred: ' + (error.message || 'Unknown error'), 'error');
        });
    }
}

// Toggle Test Mode
function toggleTestMode(gatewayId) {
    if (confirm('Are you sure you want to toggle test mode for this gateway?')) {
        showLoading();
        
        const url = '<?= \App\Core\View::url('admin/payment/toggleTestMode') ?>/' + gatewayId;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification(data.message || 'Failed to toggle test mode', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('Network error occurred', 'error');
        });
    }
}

// Utility Functions
function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
    document.getElementById('loadingOverlay').classList.add('flex');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
    document.getElementById('loadingOverlay').classList.remove('flex');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-400' : 'bg-red-100 text-red-800 border border-red-400'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
