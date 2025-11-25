<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Staff Management</h1>
            <p class="text-gray-600">Manage your staff members and assign orders</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/staff/create') ?>" 
           class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Staff Member
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Total Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Active Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-600">Inactive Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Staff Members</h3>
        </div>
        
        <?php if (empty($staff)): ?>
            <div class="text-center py-12">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No staff members</h3>
                <p class="text-gray-500 mb-4">Get started by adding your first staff member</p>
                <a href="<?= \App\Core\View::url('admin/staff/create') ?>" 
                   class="btn">
                    <i class="fas fa-plus mr-2"></i>Add Staff Member
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($staff as $member): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center">
                                                <?= strtoupper(substr($member['name'], 0, 1)) ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($member['name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($member['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($member['phone'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $member['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ucfirst($member['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M d, Y', strtotime($member['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/staff/edit/' . $member['id']) ?>" 
                                           class="text-primary hover:text-primary-dark">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteStaff(<?= $member['id'] ?>)" 
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

    <!-- Order Assignment Section -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Order Assignment</h3>
            <p class="text-sm text-gray-600">Assign unassigned orders to staff members</p>
        </div>
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <button onclick="loadUnassignedOrders()" 
                        class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-refresh mr-2"></i>Load Unassigned Orders
                </button>
                
                <div id="bulkActions" class="hidden flex items-center space-x-3">
                    <button onclick="selectAllOrders()" 
                            class="bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-sm">
                        <i class="fas fa-check-square mr-1"></i>Select All
                    </button>
                    <button onclick="deselectAllOrders()" 
                            class="bg-gray-500 text-white px-3 py-2 rounded-lg hover:bg-gray-600 transition-colors text-sm">
                        <i class="fas fa-square mr-1"></i>Deselect All
                    </button>
                    <select id="bulkStaffSelect" class="border rounded px-3 py-2">
                        <option value="">Select Staff for Bulk Assignment</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="bulkAssignOrders()" 
                            class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-users mr-2"></i>Bulk Assign
                    </button>
                </div>
            </div>
            
            <div id="unassignedOrders" class="hidden">
                <!-- Unassigned orders will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function deleteStaff(staffId) {
    if (confirm('Are you sure you want to delete this staff member?')) {
        window.location.href = '<?= \App\Core\View::url('admin/staff/delete') ?>/' + staffId;
    }
}

function loadUnassignedOrders() {
    fetch('<?= \App\Core\View::url('admin/staff/getUnassignedOrders') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUnassignedOrders(data.orders);
            } else {
                alert('Failed to load unassigned orders');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading orders');
        });
}

function displayUnassignedOrders(orders) {
    const container = document.getElementById('unassignedOrders');
    const bulkActions = document.getElementById('bulkActions');
    
    if (orders.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No unassigned orders found.</p>';
        bulkActions.classList.add('hidden');
    } else {
        let html = '<div class="space-y-4">';
        orders.forEach(order => {
            html += `
                <div class="border rounded-lg p-4 order-item" data-order-id="${order.id}">
                    <div class="flex items-start space-x-3">
                        <input type="checkbox" 
                               class="order-checkbox mt-1" 
                               data-order-id="${order.id}"
                               onchange="updateBulkActions()">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-lg">Order #${order.invoice}</h4>
                                    <p class="text-sm text-gray-600">${order.customer_name || 'Guest'}</p>
                                    <p class="text-sm text-gray-600">${order.payment_method}</p>
                                    <p class="text-sm text-gray-600">रु${parseFloat(order.total_amount).toFixed(2)}</p>
                                    <p class="text-xs text-gray-500">${new Date(order.created_at).toLocaleDateString()}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <select id="staffSelect_${order.id}" class="border rounded px-3 py-1">
                                        <option value="">Select Staff</option>
                                        <?php foreach ($staff as $member): ?>
                                            <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button onclick="assignOrder(${order.id})" 
                                            class="bg-primary text-white px-3 py-1 rounded text-sm hover:bg-primary-dark">
                                        Assign
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
        bulkActions.classList.remove('hidden');
    }
    
    container.classList.remove('hidden');
}

function assignOrder(orderId) {
    const staffSelect = document.getElementById(`staffSelect_${orderId}`);
    const staffId = staffSelect.value;
    
    if (!staffId) {
        alert('Please select a staff member');
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('staff_id', staffId);
    
    fetch('<?= \App\Core\View::url('admin/staff/assignOrder') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order assigned successfully');
            loadUnassignedOrders(); // Refresh the list
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning the order');
    });
}

// Bulk assignment functions
function selectAllOrders() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateBulkActions();
}

function deselectAllOrders() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const bulkStaffSelect = document.getElementById('bulkStaffSelect');
    
    if (checkedBoxes.length > 0) {
        bulkStaffSelect.disabled = false;
    } else {
        bulkStaffSelect.disabled = true;
    }
}

function bulkAssignOrders() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const staffId = document.getElementById('bulkStaffSelect').value;
    
    if (checkedBoxes.length === 0) {
        showToast('Please select at least one order', 'error');
        return;
    }
    
    if (!staffId) {
        showToast('Please select a staff member', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to assign ${checkedBoxes.length} order(s) to the selected staff member?`)) {
        return;
    }
    
    const orderIds = Array.from(checkedBoxes).map(checkbox => checkbox.dataset.orderId);
    
    const formData = new FormData();
    formData.append('order_ids', JSON.stringify(orderIds));
    formData.append('staff_id', staffId);
    
    fetch('<?= \App\Core\View::url('admin/staff/bulkAssignOrders') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Successfully assigned ${data.assigned_count} order(s)`, 'success');
            loadUnassignedOrders(); // Refresh the list
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while bulk assigning orders', 'error');
    });
}

// Toast notification system
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container') || createToastContainer();
    const toast = document.createElement('div');
    
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const icon = type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle';
    
    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 transform transition-all duration-300 translate-x-full`;
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
