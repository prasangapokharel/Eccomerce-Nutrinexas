<?php
/**
 * Reusable Table Component
 * 
 * Usage:
 * $tableConfig = [
 *     'columns' => [
 *         ['key' => 'id', 'label' => 'ID', 'sortable' => true],
 *         ['key' => 'name', 'label' => 'Name', 'sortable' => true],
 *         ['key' => 'status', 'label' => 'Status', 'type' => 'badge', 'badgeConfig' => ['active' => 'success', 'inactive' => 'danger']],
 *         ['key' => 'actions', 'label' => 'Actions', 'type' => 'actions', 'actions' => ['edit', 'delete']]
 *     ],
 *     'data' => $dataArray,
 *     'pagination' => ['currentPage' => 1, 'totalPages' => 10, 'total' => 100],
 *     'bulkActions' => true,
 *     'search' => true,
 *     'filters' => [...],
 *     'title' => 'Table Title',
 *     'description' => 'Table description'
 * ];
 * include __DIR__ . '/Table.php';
 */

if (empty($tableConfig) || !is_array($tableConfig)) {
    return;
}

$columns = $tableConfig['columns'] ?? [];
$data = $tableConfig['data'] ?? [];
$pagination = $tableConfig['pagination'] ?? null;
$bulkActions = $tableConfig['bulkActions'] ?? false;
$search = $tableConfig['search'] ?? false;
$filters = $tableConfig['filters'] ?? [];
$tableId = $tableConfig['id'] ?? 'dataTable';
$baseUrl = $tableConfig['baseUrl'] ?? '';
$onRowClick = $tableConfig['onRowClick'] ?? null;
$title = $tableConfig['title'] ?? '';
$description = $tableConfig['description'] ?? '';

// Badge color mapping
$badgeColors = [
    'success' => 'bg-green-100 text-green-800',
    'danger' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-blue-100 text-blue-800',
    'primary' => 'bg-primary-50 text-primary-700',
    'secondary' => 'bg-gray-100 text-gray-800',
    'active' => 'bg-green-100 text-green-800',
    'inactive' => 'bg-red-100 text-red-800',
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'delivered' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800',
    'processing' => 'bg-blue-100 text-blue-800',
    'picked_up' => 'bg-primary-50 text-primary-700',
    'in_transit' => 'bg-primary-50 text-primary-700',
];
?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <!-- Table Header -->
    <?php if ($title || $filters || $search): ?>
    <div class="p-6 border-b border-gray-100">
        <?php if ($title): ?>
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($title) ?></h2>
                <?php if ($description): ?>
                    <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($description) ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($filters)): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($filters as $filter): ?>
                    <a href="<?= htmlspecialchars($filter['url'] ?? '#') ?>" 
                       class="px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $filter['active'] ?? false ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                        <?= htmlspecialchars($filter['label'] ?? '') ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($search): ?>
        <div class="mt-4">
            <div class="relative max-w-md">
                <input type="text" 
                       id="searchInput_<?= $tableId ?>" 
                       placeholder="Search..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Table Content -->
    <div class="overflow-x-auto -mx-4 px-4">
        <table class="min-w-full divide-y divide-gray-100" id="<?= $tableId ?>">
            <thead class="bg-gray-50">
                <tr>
                    <?php if ($bulkActions): ?>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 40px;">
                            <input type="checkbox" id="selectAll_<?= $tableId ?>" onchange="toggleSelectAll_<?= $tableId ?>(this)" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        </th>
                    <?php endif; ?>
                    
                    <?php foreach ($columns as $col): ?>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <div class="flex items-center">
                                <?php if (!empty($col['icon'])): ?>
                                    <?= $col['icon'] ?>
                                <?php endif; ?>
                                <?= htmlspecialchars($col['label']) ?>
                                <?php if (!empty($col['sortable'])): ?>
                                    <i class="fas fa-sort ml-2 text-gray-400 cursor-pointer hover:text-gray-600" onclick="sortTable_<?= $tableId ?>('<?= $col['key'] ?>')"></i>
                                <?php endif; ?>
                            </div>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-100">
                <?php if (empty($data)): ?>
                    <tr>
                        <td colspan="<?= count($columns) + ($bulkActions ? 1 : 0) ?>" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No data found</h3>
                                <p class="text-gray-500">No records available.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data as $index => $row): ?>
                        <tr class="hover:bg-gray-50 transition-colors <?= $onRowClick ? 'cursor-pointer' : '' ?>" 
                            <?= $onRowClick ? 'onclick="' . str_replace('{id}', $row['id'] ?? $index, $onRowClick) . '"' : '' ?>
                            data-row-index="<?= $index ?>">
                            <?php if ($bulkActions): ?>
                                <td class="px-6 py-4">
                                    <input type="checkbox" 
                                           class="row-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" 
                                           value="<?= $row['id'] ?? $index ?>"
                                           data-id="<?= $row['id'] ?? $index ?>"
                                           onchange="updateBulkActions_<?= $tableId ?>()">
                                </td>
                            <?php endif; ?>
                            
                            <?php foreach ($columns as $col): ?>
                                <td class="px-6 py-4">
                                    <?php
                                    $key = $col['key'];
                                    $value = $row[$key] ?? '';
                                    $type = $col['type'] ?? 'text';
                                    
                                    switch ($type):
                                        case 'badge':
                                            $badgeConfig = $col['badgeConfig'] ?? [];
                                            $badgeType = $badgeConfig[$value] ?? $value;
                                            $badgeClass = $badgeColors[$badgeType] ?? $badgeColors['secondary'];
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $value))) ?>
                                            </span>
                                            <?php
                                            break;
                                        
                                        case 'actions':
                                            $actions = $col['actions'] ?? [];
                                            $actionBaseUrl = $col['baseUrl'] ?? $baseUrl;
                                            ?>
                                            <div class="flex items-center space-x-2">
                                                <?php foreach ($actions as $action): ?>
                                                    <?php
                                                    $actionConfig = is_array($action) ? $action : ['type' => $action];
                                                    $actionType = $actionConfig['type'] ?? $action;
                                                    
                                                    // Replace {id} placeholder in URL
                                                    $actionUrl = $actionConfig['url'] ?? ($actionBaseUrl . '/' . $actionType . '/' . ($row['id'] ?? ''));
                                                    $actionUrl = str_replace('{id}', $row['id'] ?? '', $actionUrl);
                                                    
                                                    $actionTitle = $actionConfig['title'] ?? ucfirst($actionType);
                                                    
                                                    $actionIcons = [
                                                        'edit' => 'fas fa-edit',
                                                        'delete' => 'fas fa-trash',
                                                        'view' => 'fas fa-eye',
                                                        'view_details' => 'fas fa-eye',
                                                    ];
                                                    
                                                    $actionIcon = $actionConfig['icon'] ?? ($actionIcons[$actionType] ?? 'fas fa-circle');
                                                    
                                                    $actionColors = [
                                                        'edit' => 'text-blue-600 hover:text-blue-900 hover:bg-blue-50',
                                                        'delete' => 'text-red-600 hover:text-red-900 hover:bg-red-50',
                                                        'view' => 'text-primary hover:text-primary-dark hover:bg-primary-50',
                                                        'view_details' => 'text-primary hover:text-primary-dark hover:bg-primary-50',
                                                    ];
                                                    $actionColor = $actionConfig['color'] ?? ($actionColors[$actionType] ?? 'text-gray-600 hover:text-gray-900 hover:bg-gray-100');
                                                    
                                                    // Check condition if provided
                                                    $condition = $actionConfig['condition'] ?? null;
                                                    if ($condition && is_callable($condition) && !$condition($row)) {
                                                        continue; // Skip this action
                                                    }
                                                    
                                                    $onclick = $actionConfig['onclick'] ?? null;
                                                    ?>
                                                    <?php if ($actionType === 'delete'): ?>
                                                        <button onclick="handleDeleteAction_<?= $tableId ?>('<?= htmlspecialchars($actionUrl) ?>', <?= $row['id'] ?? $index ?>, this)" 
                                                                class="transition-colors p-1 rounded <?= $actionColor ?>" 
                                                                title="<?= htmlspecialchars($actionTitle) ?>">
                                                            <i class="<?= $actionIcon ?>"></i>
                                                        </button>
                                                    <?php elseif ($onclick || $actionType === 'custom'): ?>
                                                        <button onclick="<?= $onclick ? str_replace('{id}', $row['id'] ?? $index, $onclick) : 'void(0)' ?>" 
                                                                class="transition-colors p-1 rounded <?= $actionColor ?>" 
                                                                title="<?= htmlspecialchars($actionTitle) ?>">
                                                            <i class="<?= $actionIcon ?>"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <a href="<?= htmlspecialchars($actionUrl) ?>" 
                                                           class="transition-colors p-1 rounded <?= $actionColor ?>" 
                                                           title="<?= htmlspecialchars($actionTitle) ?>">
                                                            <i class="<?= $actionIcon ?>"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php
                                            break;
                                        
                                        case 'image':
                                            ?>
                                            <div class="flex items-center">
                                                <img src="<?= htmlspecialchars($value) ?>" 
                                                     alt="<?= htmlspecialchars($row['name'] ?? '') ?>" 
                                                     class="w-10 h-10 rounded-lg object-cover"
                                                     onerror="this.src='<?= \App\Core\View::asset('images/default-avatar.png') ?>'">
                                            </div>
                                            <?php
                                            break;
                                        
                                        case 'date':
                                            $dateValue = !empty($value) ? date('M j, Y', strtotime($value)) : '-';
                                            $timeValue = !empty($value) ? date('g:i A', strtotime($value)) : '';
                                            ?>
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($dateValue) ?></div>
                                            <?php if ($timeValue): ?>
                                                <div class="text-xs text-gray-500"><?= htmlspecialchars($timeValue) ?></div>
                                            <?php endif; ?>
                                            <?php
                                            break;
                                        
                                        case 'currency':
                                            ?>
                                            <div class="text-sm font-medium text-gray-900">
                                                रु <?= number_format((float)$value, 2) ?>
                                            </div>
                                            <?php
                                            break;
                                        
                                        case 'custom':
                                            $customRender = $col['render'] ?? null;
                                            if (is_callable($customRender)) {
                                                echo $customRender($row, $index);
                                            } else {
                                                echo htmlspecialchars($value);
                                            }
                                            break;
                                        
                                        default:
                                            ?>
                                            <div class="text-sm text-gray-900">
                                                <?= htmlspecialchars($value) ?>
                                            </div>
                                            <?php
                                            break;
                                    endswitch;
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <?= (($pagination['currentPage'] - 1) * ($pagination['perPage'] ?? 10) + 1) ?> to 
                <?= min($pagination['currentPage'] * ($pagination['perPage'] ?? 10), $pagination['total']) ?> of 
                <?= $pagination['total'] ?> entries
            </div>

            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="flex items-center gap-2">
                <?php if ($pagination['currentPage'] > 1): ?>
                    <a href="?page=<?= $pagination['currentPage'] - 1 ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Previous
                    </a>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $pagination['currentPage'] - 2);
                $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                for ($i = $startPage; $i <= $endPage; $i++):
                    $isActive = $i === $pagination['currentPage'];
                    $pageClass = $isActive 
                        ? 'bg-primary text-white border-primary' 
                        : 'border-gray-300 text-gray-700 bg-white hover:bg-gray-50';
                ?>
                    <a href="?page=<?= $i ?>" 
                       class="px-4 py-2 border rounded-lg text-sm font-medium <?= $pageClass ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                    <a href="?page=<?= $pagination['currentPage'] + 1 ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Next
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function goToPage_<?= $tableId ?>(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

function sortTable_<?= $tableId ?>(column) {
    const url = new URL(window.location.href);
    const currentSort = url.searchParams.get('sort');
    const currentOrder = url.searchParams.get('order');
    
    if (currentSort === column) {
        url.searchParams.set('order', currentOrder === 'asc' ? 'desc' : 'asc');
    } else {
        url.searchParams.set('sort', column);
        url.searchParams.set('order', 'asc');
    }
    
    window.location.href = url.toString();
}

function handleDeleteAction_<?= $tableId ?>(url, id, button) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const row = button.closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s, transform 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                    const tbody = document.querySelector('#<?= $tableId ?> tbody');
                    if (tbody && tbody.children.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="<?= count($columns) + ($bulkActions ? 1 : 0) ?>" class="px-6 py-12 text-center"><div class="flex flex-col items-center"><i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i><h3 class="text-lg font-medium text-gray-900 mb-2">No data found</h3><p class="text-gray-500">No records available.</p></div></td></tr>';
                    }
                }, 300);
            }
            
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Item deleted successfully', 'success');
            } else {
                alert(data.message || 'Item deleted successfully');
            }
        } else {
            button.disabled = false;
            button.innerHTML = originalHTML;
            alert(data.message || 'Failed to delete item');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        button.disabled = false;
        button.innerHTML = originalHTML;
        alert('An error occurred while deleting the item');
    });
}

<?php if ($bulkActions): ?>
function toggleSelectAll_<?= $tableId ?>(checkbox) {
    const rowCheckboxes = document.querySelectorAll('#<?= $tableId ?> .row-checkbox');
    rowCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateBulkActions_<?= $tableId ?>();
}

function updateBulkActions_<?= $tableId ?>() {
    const checkedBoxes = document.querySelectorAll('#<?= $tableId ?> .row-checkbox:checked');
    const selectAll = document.getElementById('selectAll_<?= $tableId ?>');
    if (selectAll) {
        selectAll.checked = checkedBoxes.length === document.querySelectorAll('#<?= $tableId ?> .row-checkbox').length;
    }
    // Trigger bulk actions update if callback exists
    if (typeof updateBulkActions === 'function') {
        updateBulkActions(Array.from(checkedBoxes).map(cb => cb.value));
    }
}
<?php endif; ?>

<?php if ($search): ?>
document.getElementById('searchInput_<?= $tableId ?>')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#<?= $tableId ?> tbody tr[data-row-index]');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
<?php endif; ?>
</script>
