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
 *     'filters' => [...]
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

// Badge color mapping
$badgeColors = [
    'success' => 'bg-green-100 text-green-800',
    'danger' => 'bg-red-100 text-red-800',
    'warning' => 'bg-yellow-100 text-yellow-800',
    'info' => 'bg-blue-100 text-blue-800',
    'primary' => 'bg-primary text-white',
    'secondary' => 'bg-gray-100 text-gray-800',
    'active' => 'bg-green-100 text-green-800',
    'inactive' => 'bg-red-100 text-red-800',
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
];
?>

<div class="overflow-x-auto">
    <table class="min-w-full bg-white" id="<?= $tableId ?>">
        <thead class="bg-gray-50 whitespace-nowrap">
            <tr>
                <?php if ($bulkActions): ?>
                    <th class="pl-4 w-8">
                        <input id="selectAll" type="checkbox" class="hidden peer" />
                        <label for="selectAll"
                            class="relative flex items-center justify-center p-0.5 peer-checked:before:hidden before:block before:absolute before:w-full before:h-full before:bg-white w-5 h-5 cursor-pointer bg-primary border border-gray-400 rounded overflow-hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-full fill-white" viewBox="0 0 520 520">
                                <path
                                    d="M79.423 240.755a47.529 47.529 0 0 0-36.737 77.522l120.73 147.894a43.136 43.136 0 0 0 36.066 16.009c14.654-.787 27.884-8.626 36.319-21.515L486.588 56.773a6.13 6.13 0 0 1 .128-.2c2.353-3.613 1.59-10.773-3.267-15.271a13.321 13.321 0 0 0-19.362 1.343q-.135.166-.278.327L210.887 328.736a10.961 10.961 0 0 1-15.585.843l-83.94-76.386a47.319 47.319 0 0 0-31.939-12.438z"
                                    data-name="7-Check" data-original="#000000" />
                            </svg>
                        </label>
                    </th>
                <?php endif; ?>
                
                <?php foreach ($columns as $col): ?>
                    <th class="px-4 py-3 text-left text-sm font-medium text-slate-600">
                        <div class="flex items-center">
                            <?php if (!empty($col['icon'])): ?>
                                <?= $col['icon'] ?>
                            <?php endif; ?>
                            <?= htmlspecialchars($col['label']) ?>
                            <?php if (!empty($col['sortable'])): ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline ml-2 cursor-pointer" viewBox="0 0 512 512" onclick="sortTable('<?= $col['key'] ?>')">
                                    <path d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64z" data-original="#000000" />
                                </svg>
                            <?php endif; ?>
                        </div>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody class="whitespace-nowrap divide-y divide-gray-200">
            <?php if (empty($data)): ?>
                <tr>
                    <td colspan="<?= count($columns) + ($bulkActions ? 1 : 0) ?>" class="px-4 py-12 text-center">
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
                        <?= $onRowClick ? 'onclick="' . str_replace('{id}', $row['id'] ?? $index, $onRowClick) . '"' : '' ?>>
                        <?php if ($bulkActions): ?>
                            <td class="pl-4 w-8">
                                <input id="checkbox<?= $index ?>" type="checkbox" class="hidden peer row-checkbox" data-id="<?= $row['id'] ?? $index ?>" />
                                <label for="checkbox<?= $index ?>"
                                    class="relative flex items-center justify-center p-0.5 peer-checked:before:hidden before:block before:absolute before:w-full before:h-full before:bg-white w-5 h-5 cursor-pointer bg-primary border border-gray-400 rounded overflow-hidden">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-full fill-white" viewBox="0 0 520 520">
                                        <path
                                            d="M79.423 240.755a47.529 47.529 0 0 0-36.737 77.522l120.73 147.894a43.136 43.136 0 0 0 36.066 16.009c14.654-.787 27.884-8.626 36.319-21.515L486.588 56.773a6.13 6.13 0 0 1 .128-.2c2.353-3.613 1.59-10.773-3.267-15.271a13.321 13.321 0 0 0-19.362 1.343q-.135.166-.278.327L210.887 328.736a10.961 10.961 0 0 1-15.585.843l-83.94-76.386a47.319 47.319 0 0 0-31.939-12.438z"
                                            data-name="7-Check" data-original="#000000" />
                                    </svg>
                                </label>
                            </td>
                        <?php endif; ?>
                        
                        <?php foreach ($columns as $col): ?>
                            <td class="px-4 py-3 text-sm text-slate-900 font-medium">
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
                                        <span class="inline-flex items-center border border-gray-200 gap-2 px-2 py-1 rounded-lg">
                                            <span class="w-2 h-2 <?= strpos($badgeClass, 'green') !== false ? 'bg-green-600' : (strpos($badgeClass, 'red') !== false ? 'bg-red-600' : (strpos($badgeClass, 'yellow') !== false ? 'bg-yellow-600' : 'bg-gray-600')) ?> rounded-full"></span>
                                            <?= htmlspecialchars(ucfirst($value)) ?>
                                        </span>
                                        <?php
                                        break;
                                    
                                    case 'actions':
                                        $actions = $col['actions'] ?? [];
                                        $actionBaseUrl = $col['baseUrl'] ?? $baseUrl;
                                        ?>
                                        <div class="flex gap-3">
                                            <?php foreach ($actions as $action): ?>
                                                <?php
                                                $actionConfig = is_array($action) ? $action : ['type' => $action];
                                                $actionType = $actionConfig['type'] ?? $action;
                                                
                                                // Replace {id} placeholder in URL
                                                $actionUrl = $actionConfig['url'] ?? ($actionBaseUrl . '/' . $actionType . '/' . ($row['id'] ?? ''));
                                                $actionUrl = str_replace('{id}', $row['id'] ?? '', $actionUrl);
                                                
                                                $actionClass = $actionConfig['class'] ?? '';
                                                
                                                $actionClasses = [
                                                    'edit' => 'text-blue-600 bg-blue-50',
                                                    'delete' => 'text-red-600 bg-red-50',
                                                    'view' => 'text-green-600 bg-green-50',
                                                ];
                                                $defaultClass = $actionClasses[$actionType] ?? 'text-gray-600 bg-gray-50';
                                                ?>
                                                <?php if ($actionType === 'delete'): ?>
                                                    <button onclick="handleDeleteAction('<?= htmlspecialchars($actionUrl) ?>', <?= $row['id'] ?? $index ?>, this)" 
                                                            class="flex items-center gap-2 rounded-lg <?= $defaultClass ?> <?= $actionClass ?> border border-gray-200 px-3 py-1 cursor-pointer">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current inline" viewBox="0 0 512 512">
                                                            <path d="M424 64h-88V48c0-26.467-21.533-48-48-48h-64c-26.467 0-48 21.533-48 48v16H88c-22.056 0-40 17.944-40 40v56c0 8.836 7.164 16 16 16h8.744l13.823 290.283C87.788 491.919 108.848 512 134.512 512h242.976c25.665 0 46.725-20.081 47.945-45.717L439.256 176H448c8.836 0 16-7.164 16-16v-56c0-22.056-17.944-40-40-40zM208 48c0-8.822 7.178-16 16-16h64c8.822 0 16 7.178 16 16v16h-96zM80 104c0-4.411 3.589-8 8-8h336c4.411 0 8 3.589 8 8v40H80zm313.469 360.761A15.98 15.98 0 0 1 377.488 480H134.512a15.98 15.98 0 0 1-15.981-15.239L104.78 176h302.44z" data-original="#000000" />
                                                            <path d="M256 448c8.836 0 16-7.164 16-16V224c0-8.836-7.164-16-16-16s-16 7.164-16 16v208c0 8.836 7.163 16 16 16zm80 0c8.836 0 16-7.164 16-16V224c0-8.836-7.164-16-16-16s-16 7.164-16 16v208c0 8.836 7.163 16 16 16zm-160 0c8.836 0 16-7.164 16-16V224c0-8.836-7.164-16-16-16s-16 7.164-16 16v208c0 8.836 7.163 16 16 16z" data-original="#000000" />
                                                        </svg>
                                                        Delete
                                                    </button>
                                                <?php else: ?>
                                                    <a href="<?= htmlspecialchars($actionUrl) ?>" 
                                                       class="flex items-center gap-2 rounded-lg <?= $defaultClass ?> <?= $actionClass ?> border border-gray-200 px-3 py-1 cursor-pointer">
                                                        <?php if ($actionType === 'edit'): ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current inline" viewBox="0 0 64 64">
                                                                <path d="M11.105 43.597a2 2 0 0 1-1.414-3.414L40.945 8.929a2 2 0 1 1 2.828 2.828L12.519 43.011c-.39.39-.902.586-1.414.586z" data-original="#000000" />
                                                                <path d="M8.017 58a2 2 0 0 1-1.957-2.42l3.09-14.403a2 2 0 1 1 3.911.839l-3.09 14.403A2 2 0 0 1 8.017 58zm14.401-3.09a2 2 0 0 1-1.414-3.414l31.254-31.253a2 2 0 1 1 2.828 2.828L23.833 54.324a1.994 1.994 0 0 1-1.415.586z" data-original="#000000" />
                                                                <path d="M8.013 58a2.001 2.001 0 0 1-.418-3.956l14.403-3.09a2 2 0 0 1 .839 3.911l-14.403 3.09a1.958 1.958 0 0 1-.421.045zm40.002-28.687a1.99 1.99 0 0 1-1.414-.586L35.288 17.414a2 2 0 1 1 2.828-2.828l11.313 11.313a2 2 0 0 1-1.414 3.414zm5.657-5.656a2 2 0 0 1-1.415-3.415c1.113-1.113 1.726-2.62 1.726-4.242s-.613-3.129-1.726-4.242c-1.114-1.114-2.621-1.727-4.243-1.727s-3.129.613-4.242 1.727a2 2 0 1 1-2.829-2.829c1.868-1.869 4.379-2.898 7.071-2.898 2.691 0 5.203 1.029 7.071 2.898 1.869 1.868 2.898 4.379 2.898 7.071s-1.029 5.203-2.898 7.071a1.99 1.99 0 0 1-1.413.586z" data-original="#000000" />
                                                            </svg>
                                                            Edit
                                                        <?php elseif ($actionType === 'view'): ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-current inline" viewBox="0 0 24 24">
                                                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" data-original="#000000" />
                                                            </svg>
                                                            View
                                                        <?php else: ?>
                                                            <?= htmlspecialchars(ucfirst($actionType)) ?>
                                                        <?php endif; ?>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php
                                        break;
                                    
                                    case 'image':
                                        ?>
                                        <div class="flex items-center cursor-pointer w-max">
                                            <img src="<?= htmlspecialchars($value) ?>" 
                                                 alt="<?= htmlspecialchars($row['name'] ?? '') ?>" 
                                                 class="w-9 h-9 rounded-full shrink-0"
                                                 onerror="this.src='<?= \App\Core\View::asset('images/default-avatar.png') ?>'">
                                        </div>
                                        <?php
                                        break;
                                    
                                    case 'date':
                                        $dateValue = !empty($value) ? date('d M Y, h:i a', strtotime($value)) : '-';
                                        echo htmlspecialchars($dateValue);
                                        break;
                                    
                                    case 'currency':
                                        echo 'Rs. ' . number_format((float)$value, 2);
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
                                        echo htmlspecialchars($value);
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

    <?php if ($pagination): ?>
        <div class="md:flex m-4">
            <p class="text-sm text-slate-600 flex-1">
                Showing <?= (($pagination['currentPage'] - 1) * ($pagination['perPage'] ?? 10) + 1) ?> to 
                <?= min($pagination['currentPage'] * ($pagination['perPage'] ?? 10), $pagination['total']) ?> of 
                <?= $pagination['total'] ?> entries
            </p>

            <?php if ($pagination['totalPages'] > 1): ?>
            <div class="flex items-center max-md:mt-4">
                <p class="text-sm text-slate-600">Display</p>
                <select class="text-sm text-slate-900 border border-gray-300 rounded-md h-9 mx-4 px-1 outline-none" onchange="changePerPage(this.value)">
                    <option value="10" <?= ($pagination['perPage'] ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= ($pagination['perPage'] ?? 20) == 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= ($pagination['perPage'] ?? 50) == 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= ($pagination['perPage'] ?? 100) == 100 ? 'selected' : '' ?>>100</option>
                </select>

                <ul class="flex space-x-3 justify-center">
                    <?php if ($pagination['currentPage'] > 1): ?>
                        <li class="flex items-center justify-center shrink-0 bg-gray-100 w-9 h-9 rounded-md cursor-pointer hover:bg-gray-200" onclick="goToPage(<?= $pagination['currentPage'] - 1 ?>)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 fill-gray-400" viewBox="0 0 55.753 55.753">
                                <path d="M12.745 23.915c.283-.282.59-.52.913-.727L35.266 1.581a5.4 5.4 0 0 1 7.637 7.638L24.294 27.828l18.705 18.706a5.4 5.4 0 0 1-7.636 7.637L13.658 32.464a5.367 5.367 0 0 1-.913-.727 5.367 5.367 0 0 1-1.572-3.911 5.369 5.369 0 0 1 1.572-3.911z" data-original="#000000" />
                            </svg>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $pagination['currentPage'] - 2);
                    $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="flex items-center justify-center shrink-0 <?= $i === $pagination['currentPage'] ? 'bg-primary border border-primary text-white' : 'border border-gray-300 hover:border-primary' ?> cursor-pointer text-sm font-medium text-slate-900 px-[13px] h-9 rounded-md" 
                            onclick="goToPage(<?= $i ?>)">
                            <?= $i ?>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['currentPage'] < $pagination['totalPages']): ?>
                        <li class="flex items-center justify-center shrink-0 border border-gray-300 hover:border-primary cursor-pointer w-9 h-9 rounded-md" onclick="goToPage(<?= $pagination['currentPage'] + 1 ?>)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 fill-gray-400 rotate-180" viewBox="0 0 55.753 55.753">
                                <path d="M12.745 23.915c.283-.282.59-.52.913-.727L35.266 1.581a5.4 5.4 0 0 1 7.637 7.638L24.294 27.828l18.705 18.706a5.4 5.4 0 0 1-7.636 7.637L13.658 32.464a5.367 5.367 0 0 1-.913-.727 5.367 5.367 0 0 1-1.572-3.911 5.369 5.369 0 0 1 1.572-3.911z" data-original="#000000" />
                            </svg>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function goToPage(page) {
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    window.location.href = url.toString();
}

function changePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('page', 1);
    window.location.href = url.toString();
}

function sortTable(column) {
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

function handleDeleteAction(url, id, button) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    
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
            // Remove the row with animation
            const row = button.closest('tr');
            if (row) {
                row.style.transition = 'opacity 0.3s, transform 0.3s';
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                setTimeout(() => {
                    row.remove();
                    // Check if table is empty
                    const tbody = document.querySelector('#<?= $tableId ?> tbody');
                    if (tbody && tbody.children.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="<?= count($columns) + ($bulkActions ? 1 : 0) ?>" class="px-4 py-12 text-center"><div class="flex flex-col items-center"><i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i><h3 class="text-lg font-medium text-gray-900 mb-2">No data found</h3><p class="text-gray-500">No records available.</p></div></td></tr>';
                    }
                }, 300);
            }
            
            // Show success message
            if (typeof showNotification === 'function') {
                showNotification(data.message || 'Item deleted successfully', 'success');
            } else {
                alert(data.message || 'Item deleted successfully');
            }
        } else {
            button.disabled = false;
            button.innerHTML = originalText;
            alert(data.message || 'Failed to delete item');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        button.disabled = false;
        button.innerHTML = originalText;
        alert('An error occurred while deleting the item');
    });
}

<?php if ($bulkActions): ?>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }
    
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(rowCheckboxes).every(c => c.checked);
            if (selectAll) selectAll.checked = allChecked;
        });
    });
});
<?php endif; ?>
</script>
