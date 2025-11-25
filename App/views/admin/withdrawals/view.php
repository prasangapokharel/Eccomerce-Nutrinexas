<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-center space-x-4">
            <a href="<?= \App\Core\View::url('admin/withdrawals') ?>" 
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Withdrawals
            </a>
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
                    Withdrawal Details #<?= $withdrawal['id'] ?>
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Complete withdrawal information and user details
                </p>
            </div>
        </div>
        
        <div class="flex items-center space-x-3">
            <button onclick="window.print()" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-print mr-2"></i>
                Print
            </button>
            <button onclick="openStatusModal(<?= $withdrawal['id'] ?>)" 
                    class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-300 transition-colors">
                <i class="fas fa-edit mr-2"></i>
                Update Status
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Withdrawal Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Withdrawal Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-money-bill-wave text-primary mr-3"></i>
                        Withdrawal Information
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Withdrawal ID</label>
                            <p class="text-lg font-semibold text-gray-900">#<?= $withdrawal['id'] ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Amount</label>
                            <p class="text-2xl font-bold text-green-600">₹<?= number_format($withdrawal['amount'], 2) ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Payment Method</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-credit-card mr-2"></i>
                                <?= ucfirst(str_replace('_', ' ', $withdrawal['payment_method'])) ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                            <?php
                            $statusConfig = [
                                'pending' => ['bg-yellow-100 text-yellow-800', 'fas fa-clock'],
                                'processing' => ['bg-blue-100 text-blue-800', 'fas fa-cog'],
                                'completed' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                'rejected' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                            ];
                            $config = $statusConfig[$withdrawal['status']] ?? ['bg-gray-100 text-gray-800', 'fas fa-question'];
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $config[0] ?>">
                                <i class="<?= $config[1] ?> mr-2"></i>
                                <?= ucfirst($withdrawal['status']) ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Request Date</label>
                            <p class="text-sm text-gray-900">
                                <?= date('F j, Y \a\t g:i A', strtotime($withdrawal['created_at'])) ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Last Updated</label>
                            <p class="text-sm text-gray-900">
                                <?= date('F j, Y \a\t g:i A', strtotime($withdrawal['updated_at'])) ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($withdrawal['notes'])): ?>
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <label class="block text-sm font-medium text-gray-500 mb-2">Admin Notes</label>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($withdrawal['notes'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-credit-card text-primary mr-3"></i>
                        Payment Details
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($paymentDetails)): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($paymentDetails as $key => $value): ?>
                                <?php if (!empty($value)): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <label class="block text-sm font-medium text-gray-500 mb-1">
                                        <?= ucfirst(str_replace('_', ' ', $key)) ?>
                                    </label>
                                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($value) ?></p>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-info-circle text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No Payment Details</h3>
                            <p class="text-gray-500">No additional payment information is available for this withdrawal.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Withdrawals -->
            <?php if (!empty($recentWithdrawals) && count($recentWithdrawals) > 1): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-history text-primary mr-3"></i>
                        Recent Withdrawals by This User
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php foreach ($recentWithdrawals as $recent): ?>
                            <tr class="<?= $recent['id'] == $withdrawal['id'] ? 'bg-blue-50' : 'hover:bg-gray-50' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= $recent['id'] ?>
                                    <?php if ($recent['id'] == $withdrawal['id']): ?>
                                        <span class="ml-2 text-xs text-blue-600">(Current)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ₹<?= number_format($recent['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= ucfirst(str_replace('_', ' ', $recent['payment_method'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $config = $statusConfig[$recent['status']] ?? ['bg-gray-100 text-gray-800', 'fas fa-question'];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $config[0] ?>">
                                        <?= ucfirst($recent['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($recent['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- User Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-user text-primary mr-3"></i>
                        User Information
                    </h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-bold text-lg">
                            <?php 
                            $firstName = $withdrawal['first_name'] ?? '';
                            $lastName = $withdrawal['last_name'] ?? '';
                            $initials = '';
                            if ($firstName) $initials .= strtoupper(substr($firstName, 0, 1));
                            if ($lastName) $initials .= strtoupper(substr($lastName, 0, 1));
                            if (!$initials && isset($withdrawal['email'])) {
                                $initials = strtoupper(substr($withdrawal['email'], 0, 2));
                            }
                            ?>
                            <?= $initials ?: 'U' ?>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php 
                                $fullName = trim(($withdrawal['first_name'] ?? '') . ' ' . ($withdrawal['last_name'] ?? ''));
                                if (!$fullName && isset($withdrawal['username'])) {
                                    $fullName = $withdrawal['username'];
                                }
                                ?>
                                <?= htmlspecialchars($fullName ?: 'Unknown User') ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?= htmlspecialchars($withdrawal['email'] ?? 'No email') ?></p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">User ID</label>
                            <p class="text-sm font-semibold text-gray-900">#<?= $withdrawal['user_id'] ?></p>
                        </div>
                        
                        <?php if (!empty($withdrawal['username'])): ?>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Username</label>
                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($withdrawal['username']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($withdrawal['phone'])): ?>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Phone</label>
                            <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($withdrawal['phone']) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-green-50 rounded-lg p-3">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Current Balance</label>
                            <p class="text-lg font-bold text-green-600">₹<?= number_format($withdrawal['referral_earnings'] ?? 0, 2) ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <a href="<?= \App\Core\View::url('admin/viewUser/' . $withdrawal['user_id']) ?>" 
                           class="inline-flex items-center text-sm text-primary hover:text-primary-dark transition-colors">
                            <i class="fas fa-external-link-alt mr-2"></i>
                            View Full Profile
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Statistics -->
            <?php if (!empty($userStats)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-chart-bar text-primary mr-3"></i>
                        Withdrawal Statistics
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-blue-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-blue-600"><?= $userStats['total_requests'] ?? 0 ?></p>
                            <p class="text-xs text-blue-600 font-medium">Total Requests</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-green-600"><?= $userStats['completed_count'] ?? 0 ?></p>
                            <p class="text-xs text-green-600 font-medium">Completed</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-yellow-600"><?= $userStats['pending_count'] ?? 0 ?></p>
                            <p class="text-xs text-yellow-600 font-medium">Pending</p>
                        </div>
                        <div class="bg-red-50 rounded-lg p-3 text-center">
                            <p class="text-2xl font-bold text-red-600"><?= $userStats['rejected_count'] ?? 0 ?></p>
                            <p class="text-xs text-red-600 font-medium">Rejected</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Total Amount</span>
                            <span class="text-sm font-semibold text-gray-900">₹<?= number_format($userStats['total_completed_amount'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="text-sm text-gray-600">Pending Amount</span>
                            <span class="text-sm font-semibold text-yellow-600">₹<?= number_format($userStats['total_pending_amount'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-600">Average Withdrawal</span>
                            <span class="text-sm font-semibold text-gray-900">₹<?= number_format($userStats['avg_withdrawal_amount'] ?? 0, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-bolt text-primary mr-3"></i>
                        Quick Actions
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <button onclick="openStatusModal(<?= $withdrawal['id'] ?>)" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-300 transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Update Status
                    </button>
                    
                    <button onclick="sendEmail()" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <i class="fas fa-envelope mr-2"></i>
                        Send Email
                    </button>
                    
                    <button onclick="addNote()" 
                            class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                        <i class="fas fa-sticky-note mr-2"></i>
                        Add Note
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
                <i class="fas fa-edit text-blue-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 text-center mb-4">Update Withdrawal Status</h3>
            
            <form id="statusForm" method="POST" action="<?= \App\Core\View::url('admin/updateWithdrawalStatus/' . $withdrawal['id']) ?>">
                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                style="-webkit-appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 4 5\'><path fill=\'%23666\' d=\'M2 0L0 2h4zm0 5L0 3h4z\'/></svg>'); background-repeat: no-repeat; background-position: right 0.7rem center; background-size: 0.65rem auto; padding-right: 2.5rem;" required>
                            <option value="pending" <?= $withdrawal['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="processing" <?= $withdrawal['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="completed" <?= $withdrawal['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="rejected" <?= $withdrawal['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                  style="-webkit-appearance: none; -webkit-border-radius: 0.5rem; font-size: 16px;"
                                  placeholder="Add any notes about this withdrawal..."><?= htmlspecialchars($withdrawal['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Note:</strong> Rejecting a withdrawal will return the amount to the user's balance if it was previously pending or processing.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3 mt-6">
                    <button type="button" id="cancelStatusBtn"
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="confirmStatusBtn"
                            class="px-4 py-2 bg-primary text-white text-base font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-300 transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openStatusModal(withdrawalId) {
    document.getElementById('statusModal').classList.remove('hidden');
}

function sendEmail() {
    alert('Email functionality will be implemented soon!');
}

function addNote() {
    alert('Add note functionality will be implemented soon!');
}

document.addEventListener('DOMContentLoaded', function() {
    const statusModal = document.getElementById('statusModal');
    const statusForm = document.getElementById('statusForm');
    const confirmStatusBtn = document.getElementById('confirmStatusBtn');
    const cancelStatusBtn = document.getElementById('cancelStatusBtn');
    
    // Modal handlers
    statusForm.addEventListener('submit', function(e) {
        confirmStatusBtn.disabled = true;
        confirmStatusBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
    });
    
    cancelStatusBtn.addEventListener('click', function() {
        statusModal.classList.add('hidden');
        confirmStatusBtn.disabled = false;
        confirmStatusBtn.innerHTML = 'Update Status';
    });
    
    // Close modal on outside click
    statusModal.addEventListener('click', function(e) {
        if (e.target === statusModal) {
            statusModal.classList.add('hidden');
            confirmStatusBtn.disabled = false;
            confirmStatusBtn.innerHTML = 'Update Status';
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            statusModal.classList.add('hidden');
            confirmStatusBtn.disabled = false;
            confirmStatusBtn.innerHTML = 'Update Status';
        }
    });
});
</script>

<style>
/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        font-size: 12px;
    }
    
    .bg-white {
        background: white !important;
    }
    
    .shadow-sm {
        box-shadow: none !important;
    }
    
    .border {
        border: 1px solid #e5e7eb !important;
    }
}

/* iOS Safari specific fixes */
input[type="text"], 
input[type="search"], 
select, 
textarea {
    -webkit-appearance: none;
    -webkit-border-radius: 0.5rem;
    border-radius: 0.5rem;
    font-size: 16px;
}

/* Custom select arrow */
select {
    background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%23666' d='M2 0L0 2h4zm0 5L0 3h4z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 0.7rem center;
    background-size: 0.65rem auto;
    padding-right: 2.5rem;
}

/* Enhanced payment details styling */
.payment-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.payment-detail-item {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1rem;
    transition: all 0.2s ease-in-out;
}

.payment-detail-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Responsive  improvements */
@media (max-width: 768px) {
    .grid {
        grid-template-columns: 1fr;
    }
    
    .lg\:col-span-2 {
        grid-column: span 1;
    }
}

/* Loading states */
.loading {
    pointer-events: none;
    opacity: 0.6;
}

/* Smooth transitions */
.transition-colors {
    transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
