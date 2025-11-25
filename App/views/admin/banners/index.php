<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Manage Banners</h1>
            <p class="text-gray-600 mt-1">Create and manage promotional banners</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/banners/create') ?>" class="btn-normal">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Banner
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <?= $_SESSION['flash_error'] ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($banners)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No banners</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new banner.</p>
                <div class="mt-6">
                    <a href="<?= \App\Core\View::url('admin/banners/create') ?>" class="btn-normal">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        New Banner
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-gray-50">
                <div class="flex items-center gap-4">
                    <button id="selectAllBtn" onclick="toggleSelectAll()" class="text-sm text-gray-700 hover:text-gray-900">
                        Select All
                    </button>
                    <button id="bulkDeleteBtn" onclick="bulkDelete()" disabled class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Delete Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placement</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reach</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CTR</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($banners as $banner): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="banner_ids[]" value="<?= $banner['id'] ?>" class="banner-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" onchange="updateBulkDeleteButton()">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-20 w-32">
                                        <img src="<?= htmlspecialchars($banner['banner_image'] ?? '') ?>" 
                                             alt="Banner" 
                                             class="h-20 w-32 object-cover rounded-lg border"
                                             onerror="this.src='<?= \App\Core\View::asset('images/products/default.jpg') ?>'">
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate">
                                        <?= !empty($banner['banner_link']) ? htmlspecialchars($banner['banner_link']) : '<span class="text-gray-400">No link</span>' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                        $slot = $slotMeta[$banner['slot_key']] ?? null;
                                        $tierMeta = $slot ? \App\Config\BannerSlotConfig::TIERS[$slot['tier']] : null;
                                    ?>
                                    <div class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($slot['label'] ?? 'Custom Slot') ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?= htmlspecialchars($tierMeta['label'] ?? strtoupper($banner['tier'] ?? 'tier3')) ?>
                                        · Rs <?= number_format($tierMeta['price'] ?? $banner['slot_price'] ?? 0, 2) ?>/week
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= date('M d', strtotime($banner['start_date'])) ?></div>
                                    <div class="text-xs text-gray-500">to <?= date('M d', strtotime($banner['end_date'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium"><?= number_format($banner['reach'] ?? 0) ?></div>
                                    <div class="text-xs text-gray-500">Reach</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 font-medium"><?= number_format($banner['click'] ?? 0) ?></div>
                                    <div class="text-xs text-gray-500">Clicks</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $reach = (int)($banner['reach'] ?? 0);
                                    $clicks = (int)($banner['click'] ?? 0);
                                    $ctr = $reach > 0 ? ($clicks / $reach) * 100 : 0;
                                    $ctrColor = $ctr >= 2 ? 'text-green-600' : ($ctr >= 1 ? 'text-yellow-600' : 'text-gray-600');
                                    ?>
                                    <div class="text-sm <?= $ctrColor ?> font-semibold"><?= number_format($ctr, 2) ?>%</div>
                                    <div class="text-xs text-gray-500">CTR</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php
                                        echo match($banner['status']) {
                                            'active' => 'bg-green-100 text-green-800',
                                            'inactive' => 'bg-gray-100 text-gray-800',
                                            'suspended' => 'bg-red-100 text-red-800',
                                            'expired' => 'bg-yellow-100 text-yellow-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        };
                                    ?>">
                                        <?= ucfirst($banner['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-3">
                                        <!-- Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer" 
                                                   <?= ($banner['status'] === 'active') ? 'checked' : '' ?>
                                                   onchange="toggleBannerStatus(<?= $banner['id'] ?>, this)">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                        <a href="<?= \App\Core\View::url('admin/banners/edit/' . $banner['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <button onclick="deleteBanner(<?= $banner['id'] ?>)" 
                                                class="text-red-600 hover:text-red-900" title="Delete">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?= $currentPage - 1 ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?= $currentPage + 1 ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleBannerStatus(id, checkbox) {
    const wasChecked = checkbox.checked;
    
    fetch('<?= \App\Core\View::url('admin/banners/toggle/') ?>' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status badge without reload
            const row = checkbox.closest('tr');
            const statusCell = row.querySelector('td:nth-child(9)');
            const statusBadge = statusCell.querySelector('span');
            statusBadge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
            statusBadge.className = 'px-2 py-1 text-xs font-medium rounded-full ' + 
                (data.new_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
        } else {
            // Revert toggle on error
            checkbox.checked = !wasChecked;
            alert('Failed to update banner status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = !wasChecked;
        alert('An error occurred');
    });
}

function deleteBanner(id) {
    if (!confirm('Are you sure you want to delete this banner?')) {
        return;
    }
    
    fetch('<?= \App\Core\View::url('admin/banners/delete/') ?>' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to delete banner');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.banner-checkbox');
    const isChecked = selectAllCheckbox.checked;
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = isChecked;
    });
    
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.banner-checkbox:checked');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedCount = document.getElementById('selectedCount');
    const count = checkboxes.length;
    
    selectedCount.textContent = count;
    bulkDeleteBtn.disabled = count === 0;
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.banner-checkbox');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    selectAllCheckbox.checked = allCheckboxes.length > 0 && checkboxes.length === allCheckboxes.length;
}

function bulkDelete() {
    const checkboxes = document.querySelectorAll('.banner-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    if (ids.length === 0) {
        alert('Please select at least one banner to delete');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${ids.length} banner(s)?`)) {
        return;
    }
    
    fetch('<?= \App\Core\View::url('admin/banners/bulk-delete') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to delete banners');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateBulkDeleteButton();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>

