<?php ob_start(); ?>
<?php $page = 'products'; ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">My Products</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your product inventory and details</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= \App\Core\View::url('seller/products/bulk-upload') ?>" 
               class="btn btn-outline">
                <i class="fas fa-upload mr-2"></i>
                Bulk Upload
            </a>
            <a href="<?= \App\Core\View::url('seller/products/create') ?>" 
               class="btn">
                <i class="fas fa-plus mr-2"></i>
                Add Product
            </a>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="hidden bg-primary/10 border-b border-gray-200 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span id="selectedCount" class="text-sm font-medium text-gray-700">0 selected</span>
                    <button id="bulkDeleteBtn" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete Selected
                    </button>
                    <button id="clearSelectionBtn" 
                            class="btn btn-outline">
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" 
                               id="selectAll" 
                               class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Select All</span>
                    </label>
                    <h2 class="text-lg font-semibold text-gray-900">Product List</h2>
                </div>
                
                <!-- Search and Filters -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search products..." 
                               class="input native-input"
                               style="padding-left: 2.5rem;">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <button id="searchButton" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary transition-colors">
                            <i class="fas fa-times text-sm hidden" id="clearSearch"></i>
                        </button>
                    </div>
                    
                    <select id="statusFilter"
                            class="input native-input">
                        <?php $currentStatus = $_GET['status'] ?? ''; ?>
                        <option value="" <?= $currentStatus === '' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>

                    <select id="approvalFilter"
                            class="input native-input">
                        <option value="">All Approvals</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                            <input type="checkbox" 
                                   id="selectAllHeader" 
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Product
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Price
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stock
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Approval
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="productsTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="noProductsRow">
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                                    <p class="text-gray-500 mb-4">Get started by adding your first product.</p>
                                    <a href="<?= \App\Core\View::url('seller/products/create') ?>" 
                                       class="btn">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Product
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 transition-colors product-row" 
                                data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>"
                                data-status="<?= strtolower(htmlspecialchars($product['status'] ?? '')) ?>"
                                data-approval="<?= strtolower(htmlspecialchars($product['approval_status'] ?? 'pending')) ?>"
                                data-product-id="<?= $product['id'] ?>">
                                <td class="px-6 py-4">
                                    <input type="checkbox" 
                                           class="product-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" 
                                           value="<?= $product['id'] ?>">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <?php 
                                            $imageUrl = !empty($product['image_url']) 
                                                ? $product['image_url'] 
                                                : \App\Core\View::asset('images/product_default.jpg');
                                            ?>
                                            <img class="h-12 w-12 rounded-lg object-cover border border-gray-200" 
                                                 src="<?= htmlspecialchars($imageUrl) ?>" 
                                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                 onerror="this.src='<?= \App\Core\View::asset('images/product_default.jpg') ?>'">
                                        </div>
                                        <div class="ml-4 min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900 truncate" style="max-width: 220px;">
                                                <?= htmlspecialchars($product['product_name']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?= $product['id'] ?>
                                                <?php if (isset($product['is_featured']) && $product['is_featured']): ?>
                                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-star mr-1"></i>Featured
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                            <div class="flex items-center space-x-2">
                                                <span class="font-medium text-green-600">Rs<?= number_format($product['sale_price'], 2) ?></span>
                                                <span class="text-xs text-gray-500 line-through">Rs<?= number_format($product['price'], 2) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="font-medium">Rs<?= number_format($product['price'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $stockClass = 'bg-gray-100 text-gray-800';
                                    $stockIcon = 'fas fa-minus';
                                    if ($product['stock_quantity'] > 10) {
                                        $stockClass = 'bg-green-100 text-green-800';
                                        $stockIcon = 'fas fa-check';
                                    } elseif ($product['stock_quantity'] > 0) {
                                        $stockClass = 'bg-yellow-100 text-yellow-800';
                                        $stockIcon = 'fas fa-exclamation';
                                    } else {
                                        $stockClass = 'bg-red-100 text-red-800';
                                        $stockIcon = 'fas fa-times';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $stockClass ?>">
                                        <i class="<?= $stockIcon ?> mr-1"></i>
                                        <?= $product['stock_quantity'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $product['status'] === 'active' ? 'bg-green-100 text-green-800' : ($product['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $approvalStatus = $product['approval_status'] ?? 'pending';
                                    $approvalClass = 'bg-yellow-100 text-yellow-800';
                                    if ($approvalStatus === 'approved') {
                                        $approvalClass = 'bg-green-100 text-green-800';
                                    } elseif ($approvalStatus === 'rejected') {
                                        $approvalClass = 'bg-red-100 text-red-800';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $approvalClass ?>" 
                                          title="<?= !empty($product['approval_notes']) ? htmlspecialchars($product['approval_notes']) : '' ?>">
                                        <?= ucfirst($approvalStatus) ?>
                                    </span>
                                    <?php if ($approvalStatus === 'pending'): ?>
                                        <div class="text-xs text-gray-500 mt-1">Awaiting review</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <!-- Status Toggle Switch -->
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer" 
                                                   <?= ($product['status'] ?? 'inactive') === 'active' ? 'checked' : '' ?>
                                                   onchange="toggleProductStatus(<?= $product['id'] ?>, this.checked)"
                                                   title="<?= ($product['status'] ?? 'inactive') === 'active' ? 'Active' : 'Inactive' ?>">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                        
                                        <a href="<?= \App\Core\View::url('seller/products/edit/' . $product['id']) ?>" 
                                           class="text-primary hover:text-primary-dark transition-colors"
                                           title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?= \App\Core\View::url('seller/products/delete/' . $product['id']) ?>" 
                                              method="POST" 
                                              onsubmit="return confirm('Are you sure you want to delete this product?')"
                                              class="inline-block">
                                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-800 transition-colors"
                                                    title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Showing <?= ($currentPage - 1) * 20 + 1 ?> to <?= min($currentPage * 20, $total) ?> of <?= $total ?> products
                </div>
                <div class="flex gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-outline">Previous</a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-outline">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const approvalFilter = document.getElementById('approvalFilter');
    const productRows = document.querySelectorAll('.product-row');
    const noProductsRow = document.getElementById('noProductsRow');
    const clearSearch = document.getElementById('clearSearch');

    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const approvalValue = approvalFilter.value.toLowerCase();
        let visibleCount = 0;

        productRows.forEach(row => {
            const name = row.dataset.name || '';
            const status = row.dataset.status || '';
            const approval = row.dataset.approval || '';
            
            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesApproval = !approvalValue || approval === approvalValue;
            
            if (matchesSearch && matchesStatus && matchesApproval) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCount === 0 && productRows.length > 0) {
            noProductsRow.style.display = '';
        } else {
            noProductsRow.style.display = 'none';
        }
    }

    searchInput.addEventListener('input', filterProducts);
    statusFilter.addEventListener('change', filterProducts);
    approvalFilter.addEventListener('change', filterProducts);

    if (searchInput.value) {
        clearSearch.classList.remove('hidden');
    }

    searchInput.addEventListener('input', function() {
        if (this.value) {
            clearSearch.classList.remove('hidden');
        } else {
            clearSearch.classList.add('hidden');
        }
    });

    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        clearSearch.classList.add('hidden');
        filterProducts();
    });
});

// Toggle Product Status
function toggleProductStatus(productId, isActive) {
    const status = isActive ? 'active' : 'inactive';
    const formData = new FormData();
    formData.append('status', status);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('seller/products/toggle-status') ?>/' + productId, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const flashDiv = document.createElement('div');
            flashDiv.className = 'mb-4 bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg flex items-start';
            flashDiv.innerHTML = `
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-green-800">${data.message || 'Product status updated successfully'}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-400 hover:opacity-75">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            document.querySelector('.space-y-6').insertBefore(flashDiv, document.querySelector('.space-y-6').firstChild);
            setTimeout(() => flashDiv.remove(), 5000);
            
            // Update status badge
            const row = document.querySelector(`tr[data-product-id="${productId}"]`) || event.target.closest('tr');
            if (row) {
                const statusCell = row.querySelector('td:nth-child(5)');
                if (statusCell) {
                    const badge = statusCell.querySelector('span');
                    if (badge) {
                        badge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`;
                        badge.textContent = isActive ? 'Active' : 'Inactive';
                    }
                }
            }
        } else {
            // Revert toggle
            event.target.checked = !isActive;
            alert(data.message || 'Failed to update product status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        event.target.checked = !isActive;
        alert('An error occurred while updating product status');
    });
}

// Bulk Delete Functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const selectAllHeaderCheckbox = document.getElementById('selectAllHeader');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');

    function updateBulkActionsBar() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            bulkActionsBar.classList.remove('hidden');
            selectedCount.textContent = count + ' selected';
        } else {
            bulkActionsBar.classList.add('hidden');
        }
        
        // Update select all checkboxes
        const allChecked = checkedBoxes.length === productCheckboxes.length && productCheckboxes.length > 0;
        if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
        if (selectAllHeaderCheckbox) selectAllHeaderCheckbox.checked = allChecked;
    }

    function selectAll(checked) {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        updateBulkActionsBar();
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            selectAll(this.checked);
        });
    }

    if (selectAllHeaderCheckbox) {
        selectAllHeaderCheckbox.addEventListener('change', function() {
            selectAll(this.checked);
        });
    }

    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsBar);
    });

    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            if (selectAllHeaderCheckbox) selectAllHeaderCheckbox.checked = false;
            updateBulkActionsBar();
        });
    }

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const productIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (productIds.length === 0) {
                alert('Please select at least one product to delete');
                return;
            }

            if (!confirm(`Are you sure you want to delete ${productIds.length} product(s)? This action cannot be undone.`)) {
                return;
            }

            const formData = new FormData();
            productIds.forEach(id => {
                formData.append('product_ids[]', id);
            });
            formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');

            bulkDeleteBtn.disabled = true;
            bulkDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deleting...';

            fetch('<?= \App\Core\View::url('seller/products/bulk-delete') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const flashDiv = document.createElement('div');
                    flashDiv.className = 'mb-4 bg-green-50 border border-green-200 rounded-lg p-4 shadow-lg flex items-start';
                    flashDiv.innerHTML = `
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-green-800">${data.message || 'Products deleted successfully'}</p>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-green-400 hover:opacity-75">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    `;
                    document.querySelector('.space-y-6').insertBefore(flashDiv, document.querySelector('.space-y-6').firstChild);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert(data.message || 'Failed to delete products');
                    bulkDeleteBtn.disabled = false;
                    bulkDeleteBtn.innerHTML = '<i class="fas fa-trash mr-2"></i>Delete Selected';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting products');
                bulkDeleteBtn.disabled = false;
                bulkDeleteBtn.innerHTML = '<i class="fas fa-trash mr-2"></i>Delete Selected';
            });
        });
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
