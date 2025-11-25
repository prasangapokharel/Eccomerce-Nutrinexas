<?php
$title = 'Manage Coupons';
ob_start();
use App\Helpers\CurrencyHelper;
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Manage Coupons</h1>
                <p class="text-gray-600 mt-1">Create and manage discount coupons</p>
            </div>
            <div class="flex gap-3">
                <a href="<?= \App\Core\View::url('admin/coupons/addStatusField') ?>" 
                   class="btn-accent"
                   onclick="return confirm('This will add a status field to the coupons table. Continue?')">
                    <i class="fas fa-database mr-2"></i>Add Status Field
                </a>
                <a href="<?= \App\Core\View::url('admin/coupons/create') ?>" 
                   class="btn-normal">
                    <i class="fas fa-plus mr-2"></i>Create Coupon
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
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
            
            <div class="bg-white rounded-lg shadow-md p-6">
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
            
            <div class="bg-white rounded-lg shadow-md p-6">
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
            
            <div class="bg-white rounded-lg shadow-md p-6">
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

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Coupons Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">All Coupons</h2>
            </div>
            
            <?php if (empty($coupons)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-ticket-alt text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No coupons found</h3>
                    <p class="text-gray-600 mb-6">Get started by creating your first coupon</p>
                    <a href="<?= \App\Core\View::url('admin/coupons/create') ?>" 
                       class="btn-normal">
                        Create Coupon
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visibility</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($coupons as $coupon): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($coupon['code']) ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
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
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= $coupon['used_count'] ?>
                                            <?php if ($coupon['usage_limit_global']): ?>
                                                / <?= $coupon['usage_limit_global'] ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($coupon['usage_limit_per_user']): ?>
                                            <div class="text-xs text-gray-500">Per user: <?= $coupon['usage_limit_per_user'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($coupon['expires_at']): ?>
                                            <?php
                                            $expiryTime = strtotime($coupon['expires_at']);
                                            $isExpired = $expiryTime <= time();
                                            $isExpiringSoon = $expiryTime <= time() + (7 * 24 * 60 * 60) && !$isExpired;
                                            ?>
                                            <div class="text-sm <?= $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-yellow-600' : 'text-gray-900') ?>">
                                                <?= date('M j, Y', $expiryTime) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?= date('g:i A', $expiryTime) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">Never</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) <= time();
                                        $isActive = $coupon['is_active'] && !$isExpired;
                                        ?>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer" 
                                                   <?= $isActive ? 'checked' : '' ?>
                                                   onchange="toggleCouponStatus(<?= $coupon['id'] ?>, this.checked)"
                                                   <?= $isExpired ? 'disabled' : '' ?>>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer" 
                                                   <?= ($coupon['status'] ?? 'private') === 'public' ? 'checked' : '' ?>
                                                   onchange="toggleCouponVisibility(<?= $coupon['id'] ?>, this.checked)">
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="<?= \App\Core\View::url('admin/coupons/edit/' . $coupon['id']) ?>" 
                                               class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= \App\Core\View::url('admin/coupons/stats/' . $coupon['id']) ?>" 
                                               class="text-blue-600 hover:text-blue-900 transition-colors">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <button onclick="deleteCoupon(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code']) ?>')" 
                                                    class="text-red-600 hover:text-red-900 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=<?= $currentPage - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=<?= $currentPage + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?= ($currentPage - 1) * 20 + 1 ?></span> to 
                                    <span class="font-medium"><?= min($currentPage * 20, $totalCoupons) ?></span> of 
                                    <span class="font-medium"><?= $totalCoupons ?></span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?= $i ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                                  <?= $i === $currentPage 
                                                      ? 'z-10 bg-primary border-primary text-white' 
                                                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
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
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full mx-4">
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-900">Confirm Deletion</h3>
                <p class="text-gray-600" id="confirmMessage"></p>
            </div>
        </div>
        <div class="flex justify-end space-x-3">
            <button onclick="closeConfirmModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button onclick="confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                Delete
            </button>
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
    
    fetch(`<?= ASSETS_URL ?>/admin/coupons/delete/${deleteId}`, {
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
            location.reload();
        } else {
            alert('Error: ' + data.message);
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
    
    fetch(`<?= ASSETS_URL ?>/admin/coupons/toggle/${id}`, {
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
            location.reload();
        } else {
            alert('Error: ' + data.message);
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
    
    fetch(`<?= ASSETS_URL ?>/admin/coupons/toggleVisibility/${id}`, {
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
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
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
