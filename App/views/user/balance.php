<?php ob_start(); ?>
<?php
$title = 'Balance & Earnings - My Account';
$description = 'View your earnings, balance, and withdrawal history';
?>

<div class="min-h-screen bg-gray-50">
    <!-- Header Section -->
    <div class="bg-primary text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="h3-bold">Balance & Earnings</h1>
                    <p class="body1-regular text-white/80">Track your referral earnings and withdrawals</p>
                </div>
                <a href="<?= \App\Core\View::url('user/account') ?>" class="text-white/80 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        <!-- Balance Overview -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="body2-medium text-gray-600">Available Balance</p>
                        <p class="h3-bold text-primary">रु<?= number_format($availableBalance ?? 0, 2) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-success/10 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="body2-medium text-gray-600">Total Earned</p>
                        <p class="h3-bold text-info">रु<?= number_format($totalEarnings ?? 0, 2) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-info/10 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-warning">रु<?= number_format($pendingEarnings ?? 0, 2) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-warning/10 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Withdrawn</p>
                        <p class="text-2xl font-bold text-gray-600">रु<?= number_format($withdrawnAmount ?? 0, 2) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border mb-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Quick Actions</h2>
            <div class="space-y-4">
                <!-- Primary Withdraw Button -->
                <a href="<?= \App\Core\View::url('user/withdraw') ?>" 
                   class="btn w-full justify-center text-lg <?= ($availableBalance ?? 0) < ($minWithdrawal ?? 100) ? 'opacity-50 cursor-not-allowed' : '' ?>"
                   <?= ($availableBalance ?? 0) < ($minWithdrawal ?? 100) ? 'onclick="return false;"' : '' ?>>
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    WITHDRAW FUNDS
                </a>
                
                <!-- Secondary Actions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="<?= \App\Core\View::url('user/invite') ?>" class="btn btn-outline w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Invite Friends
                    </a>
                    
                    <a href="<?= \App\Core\View::url('user/transactions') ?>" class="btn btn-outline w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        View Transactions
                    </a>
                </div>
            </div>
            
            <?php if (($availableBalance ?? 0) < ($minWithdrawal ?? 100)): ?>
                <div class="mt-4 p-4 bg-warning/10 border border-warning rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-warning mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <p class="text-sm text-warning">
                            Minimum withdrawal amount is रु<?= number_format($minWithdrawal ?? 100) ?>. 
                            You need रु<?= number_format(($minWithdrawal ?? 100) - ($availableBalance ?? 0), 2) ?> more to withdraw.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Earnings History -->
        <div class="bg-white rounded-2xl shadow-sm border mb-8">
            <div class="p-6 border-b">
                <h2 class="text-xl font-bold text-gray-900">Recent Earnings</h2>
                <p class="text-gray-600">Your latest referral commissions and activities</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($recentActivities)): ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3 <?= $activity['type'] === 'earning' ? 'bg-success/10' : 'bg-info/10' ?>">
                                                <svg class="w-4 h-4 <?= $activity['type'] === 'earning' ? 'text-success' : 'text-info' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <?php if ($activity['type'] === 'earning'): ?>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                    <?php else: ?>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                                    <?php endif; ?>
                                                </svg>
                                            </div>
                                            <span class="text-sm font-medium text-gray-900"><?= ucfirst($activity['type']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($activity['description']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-semibold <?= $activity['amount'] >= 0 ? 'text-success' : 'text-error' ?>">
                                            <?= $activity['amount'] >= 0 ? '+' : '' ?>रु<?= number_format(abs($activity['amount']), 2) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">
                                            Completed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                    <p>No earnings history found</p>
                                    <p class="text-sm mt-1">Start referring friends to earn commissions!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Commission Information -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Commission Information</h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Current Rates</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Commission Rate</span>
                            <span class="font-semibold text-primary"><?= $commissionRate ?? 5 ?>%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Minimum Withdrawal</span>
                            <span class="font-semibold text-gray-900">रु<?= number_format($minWithdrawal ?? 100) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Auto Approve</span>
                            <span class="text-sm font-medium <?= ($autoApprove ?? 'true') === 'true' ? 'text-success' : 'text-error' ?>">
                                <?= ($autoApprove ?? 'true') === 'true' ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">How It Works</h3>
                    <div class="space-y-2 text-sm text-gray-600">
                        <p>• Earn <?= $commissionRate ?? 5 ?>% commission on every order placed by your referrals</p>
                        <p>• Commissions are credited to your account immediately</p>
                        <p>• Withdraw your earnings when you reach the minimum amount</p>
                        <p>• No limit on how much you can earn through referrals</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>