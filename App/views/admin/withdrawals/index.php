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
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
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
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">
                        <?= is_array(\App\Core\Session::getFlash('error')) ? implode(', ', \App\Core\Session::getFlash('error')) : \App\Core\Session::getFlash('error') ?>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Withdrawals Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Payment Method
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Request Date
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($withdrawals)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900">
                                No withdrawal requests found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $withdrawal): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars(($withdrawal['first_name'] ?? '') . ' ' . ($withdrawal['last_name'] ?? '')) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($withdrawal['email'] ?? '') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    रु<?= number_format($withdrawal['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($withdrawal['payment_method'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $withdrawal['status'] ?? 'pending';
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'completed' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colorClass ?>">
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($withdrawal['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($status === 'pending'): ?>
                                            <button onclick="updateWithdrawalStatus(<?= $withdrawal['id'] ?>, 'approved')" 
                                                    class="text-green-600 hover:text-green-900" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="updateWithdrawalStatus(<?= $withdrawal['id'] ?>, 'rejected')" 
                                                    class="text-red-600 hover:text-red-900" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="viewWithdrawalDetails(<?= $withdrawal['id'] ?>)" 
                                                class="text-primary hover:text-primary-dark" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
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
    // Open modal or redirect to details page
    window.open(`<?= \App\Core\View::url('admin/withdrawals/view') ?>/${withdrawalId}`, '_blank');
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