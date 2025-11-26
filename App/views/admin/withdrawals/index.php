<?php 
ob_start(); 
$title = $title ?? 'Withdrawals Management';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Withdrawals Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage user withdrawal requests and payments</p>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (\App\Core\Session::hasFlash('success')): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        <?php 
                        $successFlash = \App\Core\Session::getFlash('success');
                        echo is_array($successFlash) ? implode(', ', $successFlash) : $successFlash;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        <?= is_array(\App\Core\Session::getFlash('error')) ? implode(', ', \App\Core\Session::getFlash('error')) : \App\Core\Session::getFlash('error') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($withdrawals as $withdrawal) {
        $tableData[] = [
            'id' => $withdrawal['id'],
            'user' => $withdrawal,
            'amount' => $withdrawal['amount'],
            'payment_method' => $withdrawal['payment_method'] ?? 'N/A',
            'status' => $withdrawal['status'] ?? 'pending',
            'created_at' => $withdrawal['created_at']
        ];
    }

    $tableConfig = [
        'id' => 'withdrawalsTable',
        'title' => 'Withdrawal Requests',
        'description' => 'Manage user withdrawal requests and payments',
        'search' => true,
        'columns' => [
            [
                'key' => 'user',
                'label' => 'User',
                'type' => 'custom',
                'render' => function($row) {
                    $user = $row['user'];
                    ob_start();
                    ?>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-primary-50 flex items-center justify-center">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                                <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?= htmlspecialchars($user['email'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'amount',
                'label' => 'Amount',
                'type' => 'currency'
            ],
            [
                'key' => 'payment_method',
                'label' => 'Payment Method',
                'type' => 'text'
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'badge',
                'badgeConfig' => [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'completed' => 'info'
                ]
            ],
            [
                'key' => 'created_at',
                'label' => 'Request Date',
                'type' => 'date'
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'type' => 'custom',
                'render' => function($row) {
                    $status = $row['status'];
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <?php if ($status === 'pending'): ?>
                            <button onclick="updateWithdrawalStatus(<?= $row['id'] ?>, 'approved')" 
                                    class="text-green-600 hover:text-green-900 hover:bg-green-50 transition-colors p-1 rounded" 
                                    title="Approve">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="updateWithdrawalStatus(<?= $row['id'] ?>, 'rejected')" 
                                    class="text-red-600 hover:text-red-900 hover:bg-red-50 transition-colors p-1 rounded" 
                                    title="Reject">
                                <i class="fas fa-times"></i>
                            </button>
                        <?php endif; ?>
                        <button onclick="viewWithdrawalDetails(<?= $row['id'] ?>)" 
                                class="text-primary hover:text-primary-dark hover:bg-primary-50 transition-colors p-1 rounded" 
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ]
        ],
        'data' => $tableData,
        'baseUrl' => \App\Core\View::url('admin/withdrawals')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<script>
function updateWithdrawalStatus(withdrawalId, status) {
    const action = status === 'approved' ? 'approve' : 'reject';
    const message = status === 'approved' ? 'approve this withdrawal request' : 'reject this withdrawal request';
    
    if (!confirm(`Are you sure you want to ${message}?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('status', status);
    
    fetch(`<?= \App\Core\View::url('admin/withdrawals/update') ?>/${withdrawalId}`, {
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
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'Failed to update withdrawal status');
    });
}

function viewWithdrawalDetails(withdrawalId) {
    window.open(`<?= \App\Core\View::url('admin/withdrawals/view') ?>/${withdrawalId}`, '_blank');
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'
    }`;
    alertDiv.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium">${message}</p>
            </div>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
