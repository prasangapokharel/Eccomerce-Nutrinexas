<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Curior Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage curiors and their accounts</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/curior/create') ?>" 
               class="btn">
                <i class="fas fa-plus mr-2"></i>
                Add Curior
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Curiors</dt>
                            <dd class="text-lg font-medium text-gray-900"><?= $stats['total'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active</dt>
                            <dd class="text-lg font-medium text-gray-900"><?= $stats['active'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-pause-circle text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Inactive</dt>
                            <dd class="text-lg font-medium text-gray-900"><?= $stats['inactive'] ?? 0 ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Curior Table -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Curiors</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage curior accounts and permissions</p>
        </div>
        
        <?php if (empty($curiors)): ?>
            <div class="text-center py-12">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No curiors found</h3>
                <p class="text-gray-500 mb-6">Get started by adding your first curior.</p>
                <a href="<?= \App\Core\View::url('admin/curior/create') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark">
                    <i class="fas fa-plus mr-2"></i>
                    Add Curior
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($curiors as $curior): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary flex items-center justify-center">
                                                <span class="text-white font-medium text-sm">
                                                    <?= strtoupper(substr($curior['name'], 0, 2)) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($curior['name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($curior['phone']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($curior['email'] ?? 'No email') ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= !empty($curior['address']) ? htmlspecialchars($curior['address']) : '<span class="text-gray-400 italic">No address</span>' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="sr-only peer toggle-status" 
                                               data-curior-id="<?= $curior['id'] ?>"
                                               <?= $curior['status'] === 'active' ? 'checked' : '' ?>>
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($curior['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/curior/edit/' . $curior['id']) ?>" 
                                           class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                onclick="sendResetLink(<?= $curior['id'] ?>)"
                                                class="text-amber-600 hover:text-amber-800"
                                                title="Send reset link">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button onclick="deleteCurior(<?= $curior['id'] ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle curior status
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
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // No status badge to update - only toggle switch shows
                } else {
                    // Revert the toggle
                    this.checked = !this.checked;
                    alert('Error: ' + data.message);
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

// Delete curior
function deleteCurior(id) {
    if (confirm('Are you sure you want to delete this curior? This action cannot be undone.')) {
        fetch('<?= \App\Core\View::url('admin/curior/delete') ?>/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
                alert('An error occurred while deleting the curior');
        });
    }
}

function sendResetLink(id) {
    if (!confirm('Send a password reset link to this curior?')) {
        return;
    }

    fetch('<?= \App\Core\View::url('admin/curior/reset-password') ?>/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
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

<style>
/* Toggle switch styling */
.toggle-status {
    position: relative;
}

.toggle-status + div {
    position: relative;
}

.toggle-status:checked + div {
    background-color: var(--primary-color, #0A3167);
}

.toggle-status:checked + div + span {
    transform: translateX(1.25rem);
}

.dot {
    position: absolute;
    left: 0.25rem;
    top: 0.25rem;
    width: 1.25rem;
    height: 1.25rem;
    background-color: white;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Status badges */
.status-active {
    background-color: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.status-inactive {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

/* Primary colors */
:root {
    --primary-color: #0A3167;
    --primary-dark: #082A5A;
}

.bg-primary {
    background-color: var(--primary-color);
}

.bg-primary-dark {
    background-color: var(--primary-dark);
}

.text-primary {
    color: var(--primary-color);
}

.text-primary-dark {
    color: var(--primary-dark);
}

.hover\:text-primary-dark:hover {
    color: var(--primary-dark);
}

.focus\:ring-primary:focus {
    --tw-ring-color: var(--primary-color);
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
