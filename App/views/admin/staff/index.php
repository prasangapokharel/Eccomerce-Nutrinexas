<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Staff Management</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your staff members and assign orders</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/staff/create') ?>" 
           class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium">
            <i class="fas fa-plus mr-2"></i>Add Staff Member
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
                    <p class="text-sm font-medium text-gray-600">Total Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['active_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive Staff</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['inactive_staff'] ?? 0 ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Prepare data for Table component -->
    <?php
    $tableData = [];
    foreach ($staff as $member) {
        $tableData[] = [
            'id' => $member['id'],
            'member' => $member,
            'name' => $member['name'],
            'email' => $member['email'],
            'phone' => $member['phone'] ?? 'N/A',
            'status' => $member['status'],
            'created_at' => $member['created_at']
        ];
    }

    $tableConfig = [
        'id' => 'staffTable',
        'title' => 'Staff Members',
        'description' => 'Manage staff accounts and permissions',
        'search' => true,
        'columns' => [
            [
                'key' => 'member',
                'label' => 'Name',
                'type' => 'custom',
                'render' => function($row) {
                    $member = $row['member'];
                    ob_start();
                    ?>
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
                    <?php
                    return ob_get_clean();
                }
            ],
            [
                'key' => 'email',
                'label' => 'Email',
                'type' => 'text'
            ],
            [
                'key' => 'phone',
                'label' => 'Phone',
                'type' => 'text'
            ],
            [
                'key' => 'status',
                'label' => 'Status',
                'type' => 'badge',
                'badgeConfig' => [
                    'active' => 'success',
                    'inactive' => 'danger'
                ]
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
                    ob_start();
                    ?>
                    <div class="flex items-center space-x-2">
                        <a href="<?= \App\Core\View::url('admin/staff/edit/' . $row['id']) ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors p-1 rounded" 
                           title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button onclick="deleteStaff(<?= $row['id'] ?>)" 
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
        'baseUrl' => \App\Core\View::url('admin/staff')
    ];
    ?>

    <?php include __DIR__ . '/../../components/Table.php'; ?>
</div>

<!-- Order Assignment Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-2">Order Assignment</h3>
    <p class="text-sm text-gray-500 mb-4">Assign unassigned orders to staff members</p>
    <div class="flex justify-between items-center mb-4">
        <button onclick="loadUnassignedOrders()" 
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="fas fa-refresh mr-2"></i>Load Unassigned Orders
        </button>
        
        <div id="bulkActions" class="hidden items-center space-x-3">
            <button onclick="selectAllOrders()" 
                    class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors text-sm">
                <i class="fas fa-check-square mr-1"></i>Select All
            </button>
            <button onclick="deselectAllOrders()" 
                    class="px-3 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-sm">
                <i class="fas fa-square mr-1"></i>Deselect All
            </button>
            <select id="bulkStaffSelect" class="border rounded px-3 py-2">
                <option value="">Select Staff for Bulk Assignment</option>
                <?php foreach ($staff as $member): ?>
                    <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button onclick="bulkAssignOrders()" 
                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-users mr-2"></i>Bulk Assign
            </button>
        </div>
    </div>
    
    <div id="unassignedOrders" class="hidden">
        <!-- Unassigned orders will be loaded here -->
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
        bulkActions.classList.remove('flex');
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
                                            class="px-3 py-1 bg-primary text-white rounded text-sm hover:bg-primary/90">
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
        bulkActions.classList.add('flex');
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
            loadUnassignedOrders();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning the order');
    });
}

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
    const bulkActions = document.getElementById('bulkActions');
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
        bulkActions.classList.add('flex');
    } else {
        bulkActions.classList.add('hidden');
        bulkActions.classList.remove('flex');
    }
}

function bulkAssignOrders() {
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    const staffId = document.getElementById('bulkStaffSelect').value;
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one order');
        return;
    }
    
    if (!staffId) {
        alert('Please select a staff member');
        return;
    }
    
    const orderIds = Array.from(checkedBoxes).map(cb => cb.dataset.orderId);
    
    const formData = new FormData();
    formData.append('order_ids', JSON.stringify(orderIds));
    formData.append('staff_id', staffId);
    
    fetch('<?= \App\Core\View::url('admin/staff/bulkAssign') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`Successfully assigned ${orderIds.length} orders`);
            loadUnassignedOrders();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while assigning orders');
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
