<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <a href="<?= \App\Core\View::url('admin/users') ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Users
                </a>
            </div>
            
            <div class="flex items-center gap-2">
                <?php
                $roleConfig = [
                    'admin' => ['bg-purple-100 text-purple-800', 'fas fa-user-shield'],
                    'customer' => ['bg-blue-100 text-blue-800', 'fas fa-user'],
                ];
                $config = $roleConfig[$user['role']] ?? ['bg-gray-100 text-gray-800', 'fas fa-user'];
                ?>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold <?= $config[0] ?>">
                    <i class="<?= $config[1] ?> mr-1.5"></i>
                    <?= ucfirst($user['role']) ?>
                </span>
                
                <?php
                $status = $user['status'] ?? 'active';
                $statusConfig = [
                    'active' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                    'inactive' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                    'suspended' => ['bg-yellow-100 text-yellow-800', 'fas fa-exclamation-triangle'],
                ];
                $statusStyle = $statusConfig[$status] ?? $statusConfig['active'];
                ?>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold <?= $statusStyle[0] ?>">
                    <i class="<?= $statusStyle[1] ?> mr-1.5"></i>
                    <?= ucfirst($status) ?>
                </span>
                
                <?php 
                $sponsorStatus = $user['sponsor_status'] ?? 'inactive';
                $sponsorConfig = [
                    'active' => ['bg-gradient-to-r from-yellow-400 to-yellow-600 text-white', 'fas fa-star'],
                    'inactive' => ['bg-gray-100 text-gray-600', 'far fa-star'],
                ];
                $sponsorStyle = $sponsorConfig[$sponsorStatus] ?? $sponsorConfig['inactive'];
                ?>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold <?= $sponsorStyle[0] ?>">
                    <i class="<?= $sponsorStyle[1] ?> mr-1.5"></i>
                    VIP
                </span>
            </div>
        </div>
        
        <div class="border-t border-gray-100 pt-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        ID: <?= $user['id'] ?> • Joined <?= date('F j, Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <?php if ($user['id'] != $_SESSION['user_id']): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Role Management -->
            <?php if ($user['role'] !== 'admin'): ?>
                <button onclick="updateRole(<?= $user['id'] ?>, 'admin')" 
                        class="btn-primary">
                    <i class="fas fa-user-shield mr-2"></i>
                    <span>Make Admin</span>
                </button>
            <?php else: ?>
                <button onclick="updateRole(<?= $user['id'] ?>, 'customer')" 
                        class="btn-secondary">
                    <i class="fas fa-user mr-2"></i>
                    <span>Remove Admin</span>
                </button>
            <?php endif; ?>
            
            <!-- Status Management -->
            <?php if ($status === 'active'): ?>
                <button onclick="updateUserStatus(<?= $user['id'] ?>, 'inactive')" 
                        class="btn-delete">
                    <i class="fas fa-user-times mr-2"></i>
                    <span>Deactivate</span>
                </button>
            <?php else: ?>
                <button onclick="updateUserStatus(<?= $user['id'] ?>, 'active')" 
                        class="btn-primary">
                    <i class="fas fa-user-check mr-2"></i>
                    <span>Activate</span>
                </button>
            <?php endif; ?>
            
            <!-- Suspend User -->
            <button onclick="suspendUser(<?= $user['id'] ?>)" 
                    class="btn-secondary">
                <i class="fas fa-ban mr-2"></i>
                <span>Suspend</span>
            </button>
            
            <!-- Sponsor Status -->
            <?php 
            $sponsorStatus = $user['sponsor_status'] ?? 'inactive';
            $isSponsorActive = ($sponsorStatus === 'active');
            ?>
            <?php if ($isSponsorActive): ?>
                <button onclick="updateSponsorStatus(<?= $user['id'] ?>, 'inactive')" 
                        class="btn-secondary">
                    <i class="fas fa-star-half-alt mr-2"></i>
                    <span>Remove VIP</span>
                </button>
            <?php else: ?>
                <button onclick="updateSponsorStatus(<?= $user['id'] ?>, 'active')" 
                        class="btn-primary">
                    <i class="fas fa-star mr-2"></i>
                    <span>Make VIP</span>
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Profile Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-primary to-primary-dark p-6">
            <h2 class="text-xl font-semibold text-white">User Profile</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Avatar Section -->
                <div class="flex lg:flex-col items-center lg:items-start gap-4">
                    <div class="relative">
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?= ASSETS_URL ?>/profileimage/<?= htmlspecialchars($user['profile_image']) ?>" 
                                 alt="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>"
                                 class="h-24 w-24 rounded-full object-cover border-4 <?= ($user['sponsor_status'] ?? 'inactive') === 'active' ? 'border-yellow-400 ring-4 ring-yellow-100' : 'border-gray-200' ?> shadow-lg hover:shadow-xl transition-shadow"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="h-24 w-24 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-bold text-2xl shadow-lg" style="display: none;">
                                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                            </div>
                        <?php else: ?>
                            <div class="h-24 w-24 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-bold text-2xl <?= ($user['sponsor_status'] ?? 'inactive') === 'active' ? 'ring-4 ring-yellow-400' : '' ?> shadow-lg">
                                <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (($user['sponsor_status'] ?? 'inactive') === 'active'): ?>
                            <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-full p-1 shadow-lg">
                                <img src="<?= ASSETS_URL ?>/images/icons/vip.png" alt="VIP" class="w-full h-full object-contain">
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center lg:text-left">
                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h3>
                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($user['username'] ?? 'user') ?></p>
                    </div>
                </div>
                
                <!-- Account Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-100">Account Information</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Email</p>
                            <p class="text-sm text-gray-900 mt-1"><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Member Since</p>
                            <p class="text-sm text-gray-900 mt-1"><?= date('F j, Y', strtotime($user['created_at'])) ?></p>
                            <p class="text-xs text-gray-500"><?= date('g:i A', strtotime($user['created_at'])) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Referral Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-100">Referral Information</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Referral Code</p>
                            <div class="flex items-center gap-2 mt-1">
                                <input type="text" 
                                       value="<?= \App\Core\View::url('auth/register?ref=' . htmlspecialchars($user['referral_code'] ?? 'N/A')) ?>" 
                                       class="text-xs font-mono text-gray-900 bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 flex-1" 
                                       id="referralLink_<?= $user['id'] ?>" readonly>
                                <button onclick="copyReferralLink(<?= $user['id'] ?>)" 
                                        class="btn-primary"
                                        title="Copy Link">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button onclick="editReferralCode(<?= $user['id'] ?>)" 
                                        class="btn-secondary"
                                        title="Edit Code">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Earnings</p>
                            <p class="text-lg font-bold text-green-600 mt-1">Rs<?= number_format($user['referral_earnings'] ?? 0, 2) ?></p>
                        </div>
                        <?php if ($user['referred_by']): ?>
                            <?php 
                            $userModel = new \App\Models\User();
                            $referrer = $userModel->find($user['referred_by']); 
                            ?>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Referred By</p>
                                <?php if ($referrer): ?>
                                    <p class="text-sm font-medium text-gray-900 mt-1"><?= htmlspecialchars($referrer['first_name'] . ' ' . $referrer['last_name']) ?></p>
                                    <p class="text-xs text-gray-500">@<?= htmlspecialchars($referrer['username']) ?></p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500 mt-1">Unknown User</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div>
                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Referred By</p>
                                <p class="text-sm text-gray-500 mt-1">Direct Registration</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 pb-2 border-b border-gray-100">Statistics</h3>
                    <div class="space-y-3">
                        <?php 
                        $userModel = new \App\Models\User();
                        $referralCount = $userModel->getReferralCount($user['id']);
                        ?>
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Referrals</p>
                            <p class="text-xl font-bold text-blue-700"><?= $referralCount ?></p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-3">
                            <p class="text-xs font-medium text-purple-600 uppercase tracking-wide mb-1">Orders</p>
                            <p class="text-xl font-bold text-purple-700"><?= count($orders) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <!-- Orders Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="<?= \App\Core\View::url('admin/orders?user_id=' . $user['id']) ?>" 
                       class="text-sm text-primary hover:text-primary-dark transition-colors">
                        View All Orders
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-shopping-cart text-3xl text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500">No orders found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            #<?= htmlspecialchars($order['invoice']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            ID: <?= $order['id'] ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('g:i A', strtotime($order['created_at'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            Rs<?= number_format($order['total_amount'], 2) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusConfig = [
                                            'paid' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                        ];
                                        $statusClass = $statusConfig[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Referral Earnings Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Referral Earnings</h2>
                    <span class="text-sm text-gray-500">
                        Total: Rs<?= number_format($user['referral_earnings'] ?? 0, 2) ?>
                    </span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Order
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php if (empty($referralEarnings)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-coins text-3xl text-gray-300 mb-2"></i>
                                        <p class="text-sm text-gray-500">No referral earnings yet</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($referralEarnings, 0, 5) as $earning): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            #<?= htmlspecialchars($earning['invoice']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-green-600">
                                            Rs<?= number_format($earning['amount'], 2) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusConfig = [
                                            'paid' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusClass = $statusConfig[$earning['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                            <?= ucfirst($earning['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modals -->

<!-- Role Update Modal -->
<div id="roleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-purple-500 to-purple-600 mb-4">
                <i class="fas fa-user-cog text-white text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Update User Role</h3>
            <p class="text-sm text-gray-600 mb-6" id="roleModalText">
                Are you sure you want to update this user's role?
            </p>
            <div class="flex gap-3">
                <button id="cancelRoleBtn" 
                        class="flex-1 btn-secondary">
                    Cancel
                </button>
                <button id="confirmRoleBtn" 
                        class="flex-1 btn-primary">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div id="statusModalIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4">
                <!-- Icon will be set dynamically -->
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Update User Status</h3>
            <p class="text-sm text-gray-600 mb-6" id="statusModalText">
                Are you sure you want to update this user's status?
            </p>
            <div class="flex gap-3">
                <button id="cancelStatusBtn" 
                        class="flex-1 btn-secondary">
                    Cancel
                </button>
                <button id="confirmStatusBtn" 
                        class="flex-1 btn">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Suspend User Modal -->
<div id="suspendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-yellow-500 to-yellow-600 mb-4">
                <i class="fas fa-ban text-white text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Suspend User</h3>
            <p class="text-sm text-gray-600 mb-4">
                Please provide a reason for suspending this user:
            </p>
            <textarea id="suspendReason" 
                      class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent resize-none" 
                      rows="3" 
                      placeholder="Enter suspension reason..."></textarea>
            <div class="flex gap-3 mt-6">
                <button id="cancelSuspendBtn" 
                        class="flex-1 btn-secondary">
                    Cancel
                </button>
                <button id="confirmSuspendBtn" 
                        class="flex-1 btn-primary">
                    Suspend
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sponsor Status Modal -->
<div id="sponsorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
        <div class="text-center">
            <div id="sponsorModalIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4">
                <!-- Icon will be set dynamically -->
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Update VIP Status</h3>
            <p class="text-sm text-gray-600 mb-6" id="sponsorModalText">
                Are you sure you want to update this user's VIP status?
            </p>
            <div class="flex gap-3">
                <button id="cancelSponsorBtn" 
                        class="flex-1 btn-secondary">
                    Cancel
                </button>
                <button id="confirmSponsorBtn" 
                        class="flex-1 btn">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables for modal state
let userToUpdate = null;
let roleToUpdate = null;
let statusToUpdate = null;
let sponsorStatusToUpdate = null;

// Role Update Function
function updateRole(userId, role) {
    const roleMessages = {
        'admin': 'This will grant this user administrator privileges. They will have access to the admin panel and can manage users, products, and orders.',
        'customer': 'This will remove administrator privileges from this user. They will lose access to the admin panel.'
    };
    
    userToUpdate = userId;
    roleToUpdate = role;
    
    document.getElementById('roleModalText').textContent = roleMessages[role] || `Update this user's role to ${role}.`;
    document.getElementById('roleModal').classList.remove('hidden');
}

// Status Update Function
function updateUserStatus(userId, status) {
    const statusConfig = {
        'active': {
            icon: 'fas fa-check-circle',
            bgClass: 'bg-gradient-to-br from-green-500 to-green-600',
            btnClass: 'bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 focus:ring-green-500',
            message: 'This will activate the user account. They will be able to log in and use the platform.'
        },
        'inactive': {
            icon: 'fas fa-times-circle',
            bgClass: 'bg-gradient-to-br from-red-500 to-red-600',
            btnClass: 'bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 focus:ring-red-500',
            message: 'This will deactivate the user account. They will not be able to log in until reactivated.'
        }
    };
    
    userToUpdate = userId;
    statusToUpdate = status;
    
    const config = statusConfig[status];
    const iconDiv = document.getElementById('statusModalIcon');
    iconDiv.className = `mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 ${config.bgClass}`;
    iconDiv.innerHTML = `<i class="${config.icon} text-white text-2xl"></i>`;
    
    document.getElementById('statusModalText').textContent = config.message;
    
    const confirmBtn = document.getElementById('confirmStatusBtn');
    confirmBtn.className = `flex-1 px-6 py-3 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all shadow-lg hover:shadow-xl ${config.btnClass}`;
    confirmBtn.textContent = 'Confirm';
    
    document.getElementById('statusModal').classList.remove('hidden');
}

// Suspend User Function
function suspendUser(userId) {
    userToUpdate = userId;
    document.getElementById('suspendReason').value = '';
    document.getElementById('suspendModal').classList.remove('hidden');
}

// Sponsor Status Update Function
function updateSponsorStatus(userId, status) {
    const sponsorConfig = {
        'active': {
            icon: 'fas fa-star',
            bgClass: 'bg-gradient-to-br from-yellow-400 to-yellow-600',
            btnClass: 'bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 focus:ring-yellow-500',
            message: 'This will activate VIP status for this user. They will gain access to sponsor features and can earn referral commissions.'
        },
        'inactive': {
            icon: 'fas fa-star-half-alt',
            bgClass: 'bg-gradient-to-br from-gray-500 to-gray-600',
            btnClass: 'bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 focus:ring-gray-500',
            message: 'This will remove VIP status from this user. They will lose access to sponsor features and referral earnings.'
        }
    };
    
    userToUpdate = userId;
    sponsorStatusToUpdate = status;
    
    const config = sponsorConfig[status];
    const iconDiv = document.getElementById('sponsorModalIcon');
    iconDiv.className = `mx-auto flex items-center justify-center h-16 w-16 rounded-full mb-4 ${config.bgClass}`;
    iconDiv.innerHTML = `<i class="${config.icon} text-white text-2xl"></i>`;
    
    document.getElementById('sponsorModalText').textContent = config.message;
    
    const confirmBtn = document.getElementById('confirmSponsorBtn');
    confirmBtn.className = `flex-1 px-6 py-3 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all shadow-lg hover:shadow-xl ${config.btnClass}`;
    confirmBtn.textContent = 'Confirm';
    
    document.getElementById('sponsorModal').classList.remove('hidden');
}

// Modal Event Handlers
document.addEventListener('DOMContentLoaded', function() {
    // Role Modal
    const roleModal = document.getElementById('roleModal');
    const confirmRoleBtn = document.getElementById('confirmRoleBtn');
    const cancelRoleBtn = document.getElementById('cancelRoleBtn');
    
    confirmRoleBtn.addEventListener('click', function() {
        if (userToUpdate && roleToUpdate) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/updateUserRole/') ?>' + userToUpdate;
            
            const roleInput = document.createElement('input');
            roleInput.type = 'hidden';
            roleInput.name = 'role';
            roleInput.value = roleToUpdate;
            
            form.appendChild(roleInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelRoleBtn.addEventListener('click', function() {
        roleModal.classList.add('hidden');
        userToUpdate = null;
        roleToUpdate = null;
    });
    
    // Status Modal
    const statusModal = document.getElementById('statusModal');
    const confirmStatusBtn = document.getElementById('confirmStatusBtn');
    const cancelStatusBtn = document.getElementById('cancelStatusBtn');
    
    confirmStatusBtn.addEventListener('click', function() {
        if (userToUpdate && statusToUpdate) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/updateUserStatus/') ?>' + userToUpdate;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = statusToUpdate;
            
            form.appendChild(statusInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelStatusBtn.addEventListener('click', function() {
        statusModal.classList.add('hidden');
        userToUpdate = null;
        statusToUpdate = null;
    });
    
    // Suspend Modal
    const suspendModal = document.getElementById('suspendModal');
    const confirmSuspendBtn = document.getElementById('confirmSuspendBtn');
    const cancelSuspendBtn = document.getElementById('cancelSuspendBtn');
    const suspendReasonInput = document.getElementById('suspendReason');
    
    confirmSuspendBtn.addEventListener('click', function() {
        const reason = suspendReasonInput.value.trim();
        if (!reason) {
            alert('Please provide a reason for suspension');
            return;
        }
        
        if (userToUpdate) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Suspending...';
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/updateUserStatus/') ?>' + userToUpdate;
            
            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'status';
            statusInput.value = 'suspended';
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;
            
            form.appendChild(statusInput);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
    
    cancelSuspendBtn.addEventListener('click', function() {
        suspendModal.classList.add('hidden');
        userToUpdate = null;
        suspendReasonInput.value = '';
    });
    
    // Sponsor Modal
    const sponsorModal = document.getElementById('sponsorModal');
    const confirmSponsorBtn = document.getElementById('confirmSponsorBtn');
    const cancelSponsorBtn = document.getElementById('cancelSponsorBtn');
    
    confirmSponsorBtn.addEventListener('click', function() {
        if (userToUpdate && sponsorStatusToUpdate) {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
            
            fetch('<?= \App\Core\View::url('admin/updateSponsorStatus') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: parseInt(userToUpdate),
                    sponsor_status: sponsorStatusToUpdate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update VIP status'));
                    this.disabled = false;
                    this.textContent = 'Confirm';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating VIP status');
                this.disabled = false;
                this.textContent = 'Confirm';
            });
        }
    });
    
    cancelSponsorBtn.addEventListener('click', function() {
        sponsorModal.classList.add('hidden');
        userToUpdate = null;
        sponsorStatusToUpdate = null;
    });
    
    // Close modals on outside click
    [roleModal, statusModal, suspendModal, sponsorModal].forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
                userToUpdate = null;
                roleToUpdate = null;
                statusToUpdate = null;
                sponsorStatusToUpdate = null;
            }
        });
    });
    
    // Keyboard shortcuts - ESC to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            [roleModal, statusModal, suspendModal, sponsorModal].forEach(modal => {
                modal.classList.add('hidden');
            });
            userToUpdate = null;
            roleToUpdate = null;
            statusToUpdate = null;
            sponsorStatusToUpdate = null;
        }
    });
});
</script>

<script>
// Copy Referral Link Function
function copyReferralLink(userId) {
    const linkInput = document.getElementById('referralLink_' + userId);
    
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(linkInput.value).then(() => {
                showCopySuccess(event.target.closest('button'));
            }).catch(() => {
                document.execCommand('copy');
                showCopySuccess(event.target.closest('button'));
            });
        } else {
            document.execCommand('copy');
            showCopySuccess(event.target.closest('button'));
        }
    } catch (err) {
        console.error('Failed to copy link:', err);
        alert('Failed to copy link');
    }
}

// Show copy success feedback
function showCopySuccess(button) {
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check"></i>';
    button.classList.add('bg-green-600');
    button.classList.remove('bg-primary');
    
    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.classList.remove('bg-green-600');
        button.classList.add('bg-primary');
    }, 2000);
}

// Edit Referral Code Function
function editReferralCode(userId) {
    const currentCode = '<?= $user['referral_code'] ?>';
    const newCode = prompt('Enter new referral code:', currentCode);
    
    if (newCode === null || newCode === currentCode) {
        return; // User cancelled or no change
    }
    
    if (newCode.trim() === '') {
        alert('Referral code cannot be empty');
        return;
    }
    
    // Update referral code via AJAX
    fetch('<?= \App\Core\View::url('admin/updateReferralCode') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: parseInt(userId),
            referral_code: newCode.trim()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Referral code updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update referral code'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating referral code');
    });
}
</script>



<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
