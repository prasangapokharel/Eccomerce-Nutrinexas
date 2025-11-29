<?php
$title = 'Manage Coupons';
ob_start();
use App\Helpers\CurrencyHelper;
?>

<div class="space-y-6">
    <!-- Standard Action Row: Title Left, Search/Filter/Add Button Right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Coupons</h1>
            <p class="mt-1 text-sm text-gray-500">Create and manage discount coupons</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Add Button -->
            <a href="<?= \App\Core\View::url('admin/coupons/addStatusField') ?>" 
               class="btn btn-outline"
               onclick="return confirm('This will add a status field to the coupons table. Continue?')">
                <i class="fas fa-database mr-2"></i>Add Status Field
            </a>
            <a href="<?= \App\Core\View::url('admin/coupons/create') ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>Create Coupon
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-ticket-alt text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Coupons</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $totalCoupons ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Coupons</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?= count(array_filter($coupons, function($c) { return $c['is_active']; })) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <i class="fas fa-clock text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Expiring Soon</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php
                        $expiringSoon = 0;
                        $oneWeekFromNow = time() + (7 * 24 * 60 * 60);
                        foreach ($coupons as $coupon) {
                            if ($coupon['expires_at'] && strtotime($coupon['expires_at']) <= $oneWeekFromNow && strtotime($coupon['expires_at']) > time()) {
                                $expiringSoon++;
                            }
                        }
                        echo $expiringSoon;
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <i class="fas fa-times-circle text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Expired</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php
                        $expired = 0;
                        foreach ($coupons as $coupon) {
                            if ($coupon['expires_at'] && strtotime($coupon['expires_at']) <= time()) {
                                $expired++;
                            }
                        }
                        echo $expired;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($coupons as $coupon) {
        $tableData[] = [
            'id' => $coupon['id'],
            'coupon' => $coupon,
            'code' => $coupon['code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
            'min_order_amount' => $coupon['min_order_amount'] ?? null,
            'used_count' => $coupon['used_count'] ?? 0,
            'usage_limit_global' => $coupon['usage_limit_global'] ?? null,
            'usage_limit_per_user' => $coupon['usage_limit_per_user'] ?? null,
            'expires_at' => $coupon['expires_at'] ?? null,
            'is_active' => $coupon['is_active'] ?? false,
            'status' => $coupon['status'] ?? 'private'
        ];
    }

    $tableConfig = [
        'id' => 'couponsTable',
        'title' => 'All Coupons',
        'description' => 'Manage discount coupons and their settings',
        'search' => true,
        'columns' => [
            [
                'key' => 'code',
                'label' => 'Code',
                'type' => 'text'
            ],
            [
                'key' => 'discount_value',
                'label' => 'Discount',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900">
                        <?php if ($coupon['discount_type'] === 'percentage'): ?>
                            <?= $coupon['discount_value'] ?>%
                        <?php else: ?>
                            <?= CurrencyHelper::format($coupon['discount_value']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($coupon['min_order_amount']): ?>
                        <div class="text-xs text-gray-500">Min: <?= CurrencyHelper::format($coupon['min_order_amount']) ?></div>
                    <?php endif; ?>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'used_count',
                'label' => 'Usage',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900">
                        <?= $coupon['used_count'] ?? 0 ?>
                        <?php if ($coupon['usage_limit_global']): ?>
                            / <?= $coupon['usage_limit_global'] ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($coupon['usage_limit_per_user']): ?>
                        <div class="text-xs text-gray-500">Per user: <?= $coupon['usage_limit_per_user'] ?></div>
                    <?php endif; ?>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'expires_at',
                'label' => 'Expires',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    if (!$coupon['expires_at']) {
                        return '<span class="text-sm text-gray-500">Never</span>';
                    }
                    $expiryTime = strtotime($coupon['expires_at']);
                    $isExpired = $expiryTime <= time();
                    $isExpiringSoon = $expiryTime <= time() + (7 * 24 * 60 * 60) && !$isExpired;
                    $colorClass = $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-yellow-600' : 'text-gray-900');
                    ob_start();
                    ?>
                    <div class="text-sm <?= $colorClass ?>">
                        <?= date('M j, Y', $expiryTime) ?>
                    </div>
                    <div class="text-xs text-gray-500">
                        <?= date('g:i A', $expiryTime) ?>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'is_active',
                'label' => 'Status',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                    $isActive = $coupon['is_active'] && !$isExpired;
                    ob_start();
                    ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               class="sr-only peer toggle-status" 
                               data-coupon-id="<?= $coupon['id'] ?>"
                               <?= $isActive ? 'checked' : '' ?>
                               onchange="toggleCouponStatus(<?= $coupon['id'] ?>, this.checked)"
                               <?= $isExpired ? 'disabled' : '' ?>>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'status',
                'label' => 'Visibility',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    $isPublic = ($coupon['status'] ?? 'private') === 'public';
                    ob_start();
                    ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               class="sr-only peer toggle-visibility" 
                               data-coupon-id="<?= $coupon['id'] ?>"
                               <?= $isPublic ? 'checked' : '' ?>
                               onchange="toggleCouponVisibility(<?= $coupon['id'] ?>, this.checked)">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    $coupon = $row['coupon'];
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <a href="<?= \App\Core\View::url('admin/coupons/edit/' . $coupon['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="<?= \App\Core\View::url('admin/coupons/stats/' . $coupon['id']) ?>" 
                           class="text-primary hover:text-primary-dark hover:bg-primary-50 transition-colors p-1 rounded" 
                           title="Stats">
                            <i class="fas fa-chart-bar"></i>
                        </a>
                        <button onclick="deleteCoupon(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code']) ?>')" 
                                class="text-red-600 hover:text-red-900 hover:bg-red-50 transition-colors p-1 rounded" 
                                title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/coupons')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            <span class="text-gray-700">Processing...</span>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-overlay hidden">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Deletion</h3>
            <button onclick="closeConfirmModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <p class="text-sm text-gray-500 text-center" id="confirmMessage"></p>
        </div>
        <div class="modal-footer">
            <button onclick="closeConfirmModal()" class="btn btn-outline">Cancel</button>
            <button onclick="confirmDelete()" class="btn btn-delete">Delete</button>
        </div>
    </div>
</div>

<script>
let deleteId = null;

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

function deleteCoupon(id, code) {
    deleteId = id;
    document.getElementById('confirmMessage').textContent = `Are you sure you want to delete the coupon "${code}"? This action cannot be undone.`;
    document.getElementById('confirmModal').classList.remove('hidden');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    deleteId = null;
}

function confirmDelete() {
    if (!deleteId) return;
    
    showLoading();
    closeConfirmModal();
    
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    
    fetch(`<?= \App\Core\View::url('admin/coupons/delete') ?>/${deleteId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data && data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data?.message || 'Failed to delete coupon'));
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while deleting the coupon');
    });
}

function toggleCouponStatus(id, isChecked) {
    showLoading();
    
    fetch(`<?= \App\Core\View::url('admin/coupons/toggle') ?>/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data && data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data?.message || 'Failed to update coupon status'));
            location.reload();
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while updating the coupon status');
        location.reload();
    });
}

function toggleCouponVisibility(id, isChecked) {
    const newStatus = isChecked ? 'public' : 'private';
    showLoading();
    
    fetch(`<?= \App\Core\View::url('admin/coupons/toggleVisibility') ?>/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data && data.success) {
            location.reload();
        } else {
            alert('Error: ' + (data?.message || 'Failed to update coupon visibility'));
            location.reload();
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while updating the coupon visibility');
        location.reload();
    });
}
</script>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/admin.php';
?>
