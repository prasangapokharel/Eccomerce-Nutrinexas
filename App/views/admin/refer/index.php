<?php ob_start(); ?>
<?php
$title = 'Referral Management - Admin Panel';
$description = 'Manage referral commission rates and settings';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Referral Management</h1>
                    <p class="text-gray-600">Manage commission rates and referral settings</p>
                </div>
                <div class="flex items-center space-x-4">
            <a href="<?= \App\Core\View::url('admin') ?>" class="btn">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Admin
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Commission Settings -->
        <div class="bg-white rounded-xl shadow-sm border mb-6">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-900">Commission Settings</h2>
                <p class="text-gray-600">Configure referral commission rates and withdrawal settings</p>
            </div>
            
            <div class="p-6">
                <form id="commissionForm" class="space-y-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Commission Rate -->
                        <div>
                            <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                Commission Rate (%)
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       id="commission_rate" 
                                       name="commission_rate" 
                                       value="<?= $commissionRate ?? 5 ?>" 
                                       min="0" 
                                       max="100" 
                                       step="0.1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 text-sm">%</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Percentage of order total that referrers earn</p>
                        </div>

                        <!-- Minimum Withdrawal -->
                        <div>
                            <label for="min_withdrawal" class="block text-sm font-medium text-gray-700 mb-2">
                                Minimum Withdrawal (रु)
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       id="min_withdrawal" 
                                       name="min_withdrawal" 
                                       value="<?= $minWithdrawal ?? 100 ?>" 
                                       min="0" 
                                       step="1"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span class="text-gray-500 text-sm">रु</span>
                                </div>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Minimum amount required for withdrawal</p>
                        </div>
                    </div>

                    <!-- Auto Approve -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Auto Approve Withdrawals</h3>
                            <p class="text-sm text-gray-500">Automatically approve withdrawal requests</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   id="auto_approve" 
                                   name="auto_approve" 
                                   <?= ($autoApprove ?? 'true') === 'true' ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
            class="btn">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Referral Statistics -->
        <div class="grid md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Referrals</p>
                        <p class="text-2xl font-bold text-primary"><?= $totalReferrals ?? 0 ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Commissions Paid</p>
                        <p class="text-2xl font-bold text-green-600">रु<?= number_format($totalCommissionsPaid ?? 0) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending Withdrawals</p>
                        <p class="text-2xl font-bold text-yellow-600">रु<?= number_format($pendingWithdrawals ?? 0) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Referrals -->
        <div class="bg-white rounded-xl shadow-sm border">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-900">Recent Referrals</h2>
                <p class="text-gray-600">Latest referral activities and earnings</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referrer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referred User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($recentReferrals)): ?>
                            <?php foreach ($recentReferrals as $referral): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-sm font-medium text-primary">
                                                    <?= strtoupper(substr($referral['referrer_name'] ?? 'U', 0, 1)) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($referral['referrer_name'] ?? 'Unknown') ?></div>
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($referral['referrer_email'] ?? '') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($referral['referred_name'] ?? 'Unknown') ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($referral['referred_email'] ?? '') ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        रु<?= number_format($referral['order_amount'] ?? 0, 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        रु<?= number_format($referral['commission_amount'] ?? 0, 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?= $referral['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                                                ($referral['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                            <?= ucfirst($referral['status'] ?? 'pending') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($referral['created_at'] ?? 'now')) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p>No referrals found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('commissionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Convert checkbox to boolean
    data.auto_approve = document.getElementById('auto_approve').checked;
    
    fetch('<?= ASSETS_URL ?>/admin/refer/save-settings', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            alert.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Settings saved successfully!
                </div>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        } else {
            alert('Error saving settings: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving settings');
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../../includes/layout.php'; ?>








