<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Courier Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage couriers and their accounts</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/curior/create') ?>" 
           class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
            <i class="fas fa-plus mr-2"></i>Add Courier
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Couriers</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active'] ?? 0 ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-pause-circle text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($curiors as $curior) {
        $tableData[] = [
            'id' => $curior['id'],
            'curior' => $curior,
            'name' => $curior['name'],
            'phone' => $curior['phone'],
            'email' => $curior['email'] ?? 'No email',
            'address' => $curior['address'] ?? '',
            'status' => $curior['status'],
            'created_at' => $curior['created_at']
        ];
    }

    $tableConfig = [
        'id' => 'curiorsTable',
        'title' => 'Couriers',
        'description' => 'Manage courier accounts and permissions',
        'search' => true,
        'columns' => [
            [
                'key' => 'curior',
                'label' => 'Name',
                'type' => 'custom',
                'render' => function($row) {
                    $curior = $row['curior'];
                    ob_start();
                    ?>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                <span class="text-white font-medium text-sm">
                                    <?= strtoupper(substr($curior['name'], 0, 2)) ?>
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($curior['name']) ?></div>
                        </div>
                    </div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'phone',
                'label' => 'Contact',
                'type' => 'custom',
                'render' => function($row) {
                    $curior = $row['curior'];
                    ob_start();
                    ?>
                    <div class="text-sm text-gray-900"><?= htmlspecialchars($curior['phone']) ?></div>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($curior['email'] ?? 'No email') ?></div>
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'address',
                'label' => 'Address',
                'type' => 'custom',
                'render' => function($row) {
                    $address = $row['address'];
                    return !empty($address) 
                        ? '<div class="text-sm text-gray-900">' . htmlspecialchars($address) . '</div>'
                        : '<span class="text-sm text-gray-400 italic">No address</span>';
                }
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'custom',
                'render' => function($row) {
                    $curior = $row['curior'];
                    ob_start();
                    ?>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" 
                               class="sr-only peer toggle-status" 
                               data-curior-id="<?= $curior['id'] ?>"
                               <?= $curior['status'] === 'active' ? 'checked' : '' ?>>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                    </label>
                    <?php
                    return ob_get_clean();
                }
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
                    $curior = $row['curior'];
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <a href="<?= \App\Core\View::url('admin/curior/edit/' . $curior['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="sendResetLink(<?= $curior['id'] ?>)" 
                                class="text-amber-600 hover:text-amber-900 hover:bg-amber-50 transition-colors p-1 rounded" 
                                title="Send Reset Link">
                            <i class="fas fa-key"></i>
                        </button>
                        <button onclick="deleteCurior(<?= $curior['id'] ?>)" 
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
        'baseUrl' => \App\Core\View::url('admin/curior')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<script>
// Toggle courier status
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.toggle-status');
    
    toggleButtons.forEach(button => {
        button.addEventListener('change', function() {
            const curiorId = this.dataset.curiorId;
            const newStatus = this.checked ? 'active' : 'inactive';
            
            fetch('<?= \App\Core\View::url('admin/curior/toggleStatus') ?>/' + curiorId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    this.checked = !this.checked;
                    alert('Error: ' + (data.message || 'Failed to update status'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;
                alert('An error occurred while updating the status');
            });
        });
    });
});

// Delete courier
function deleteCurior(id) {
    if (confirm('Are you sure you want to delete this courier? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('_method', 'DELETE');
        
        fetch('<?= \App\Core\View::url('admin/curior/delete') ?>/' + id, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data?.message || 'Failed to delete courier'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the courier');
        });
    }
}

function sendResetLink(id) {
    if (!confirm('Send a password reset link to this courier?')) {
        return;
    }

    fetch('<?= \App\Core\View::url('admin/curior/reset-password') ?>/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message || 'Request completed.');
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send password reset link.');
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
