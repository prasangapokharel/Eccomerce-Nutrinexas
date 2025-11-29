<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Standard Action Row: Title Left, Search/Filter/Add Button Right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Products</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your product inventory and details</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 sm:flex-initial sm:w-64">
                <input type="text" 
                       id="searchInput" 
                       placeholder="Search products..." 
                       class="input native-input pr-10">
                <button id="searchButton" 
                        class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-times text-sm hidden" id="clearSearch"></i>
                </button>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 text-sm"></i>
                </div>
            </div>
            
            <!-- Category Filter -->
            <select id="categoryFilter" 
                    class="input native-input sm:w-48">
                <option value="">All Categories</option>
                <?php 
                $categories = array_unique(array_column($products, 'category'));
                foreach ($categories as $category): 
                    if (!empty($category)):
                ?>
                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                <?php 
                    endif;
                endforeach; 
                ?>
            </select>

            <!-- Status Filter -->
            <select id="statusFilter"
                    class="input native-input sm:w-40">
                <?php $currentStatus = $_GET['status'] ?? ''; ?>
                <option value="" <?= $currentStatus === '' ? 'selected' : '' ?>>All Statuses</option>
                <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
            
            <!-- Add Button -->
            <a href="<?= \App\Core\View::url('admin/addProduct') ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Add Product
            </a>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div id="products-bulk-container" class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6 hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600" id="selected-count">0 products selected</span>
                <div class="flex gap-2">
                    <button onclick="bulkUpdateStatus('active')" class="btn btn-sm btn-primary">
                        <i class="fas fa-check mr-1"></i>Activate
                    </button>
                    <button onclick="bulkUpdateStatus('inactive')" class="btn btn-sm btn-outline">
                        <i class="fas fa-pause mr-1"></i>Deactivate
                    </button>
                    <button onclick="bulkDelete()" class="btn btn-sm btn-delete">
                        <i class="fas fa-trash mr-1"></i>Delete
                    </button>
                </div>
            </div>
            <button onclick="clearSelection()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-box text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Products</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $totalCount ?? count($products) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">In Stock</p>
                    <h3 class="text-xl font-bold text-gray-900">
                        <?= count(array_filter($products, function($p) { return $p['stock_quantity'] > 0; })) ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-red-50 text-red-600">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Low Stock</p>
                    <h3 class="text-xl font-bold text-gray-900">
                        <?= count(array_filter($products, function($p) { return $p['stock_quantity'] <= 5 && $p['stock_quantity'] > 0; })) ?>
                    </h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-star text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Featured</p>
                    <h3 class="text-xl font-bold text-gray-900">
                        <?= count(array_filter($products, function($p) { return isset($p['is_featured']) && $p['is_featured']; })) ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Table Header -->
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Product List</h2>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="form-checkbox">
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
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100" id="productsTableBody">
                    <?php if (empty($products)): ?>
                        <tr id="noProductsRow">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-box-open text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                                    <p class="text-gray-500 mb-4">Get started by adding your first product.</p>
                                    <a href="<?= \App\Core\View::url('admin/addProduct') ?>" 
                                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-primary hover:bg-primary-dark">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Product
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="hover:bg-gray-50 transition-colors product-row" data-product-id="<?= $product['id'] ?>" 
                                data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>"
                                data-category="<?= strtolower(htmlspecialchars($product['category'] ?? '')) ?>">
                                <td class="px-4 py-4">
                                    <input type="checkbox" class="product-checkbox" value="<?= $product['id'] ?>">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <?php 
                                            $primaryImage = $product['primary_image'] ?? null;
                                            $defaultImage = \App\Core\View::asset('images/product_default.jpg');
                                            
                                            if ($primaryImage && !empty($primaryImage['image_url'])) {
                                                if (filter_var($primaryImage['image_url'], FILTER_VALIDATE_URL)) {
                                                    $imageUrl = $primaryImage['image_url'];
                                                } else {
                                                    $imageUrl = \App\Core\View::asset('uploads/images/' . $primaryImage['image_url']);
                                                }
                                            } else {
                                                $imageUrl = $defaultImage;
                                            }
                                            ?>
                                            <img class="h-12 w-12 rounded-lg object-cover border border-gray-200" 
                                                 src="<?= htmlspecialchars($imageUrl) ?>" 
                                                 alt="<?= htmlspecialchars($product['product_name']) ?>"
                                                 onerror="this.src='<?= htmlspecialchars($defaultImage) ?>'">
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
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="status-toggle sr-only peer" 
                                               data-product-id="<?= $product['id'] ?>" 
                                               <?= (isset($product['status']) && $product['status'] === 'active') ? 'checked' : '' ?>
                                               onchange="toggleProductStatus(<?= $product['id'] ?>, this.checked)">
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <button onclick="openStockModal(<?= $product['id'] ?>, '<?= htmlspecialchars($product['product_name'], ENT_QUOTES) ?>', <?= $product['stock_quantity'] ?>)" 
                                                class="text-blue-600 hover:text-blue-800 transition-colors"
                                                title="Quick Stock Update">
                                            <i class="fas fa-boxes"></i>
                                        </button>
                                        <a href="<?= \App\Core\View::url('admin/editProduct/' . $product['id']) ?>" 
                                           class="text-primary hover:text-primary-dark transition-colors"
                                           title="Edit Product">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?= $product['id'] ?>)" 
                                                class="text-red-600 hover:text-red-800 transition-colors"
                                                title="Delete Product">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <a href="<?= \App\Core\View::url('products/view/' . ($product['slug'] ?? $product['id'])) ?>" 
                                           target="_blank"
                                           class="text-gray-600 hover:text-gray-800 transition-colors"
                                           title="View Product">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <label class="relative inline-flex items-center cursor-pointer" title="Toggle Featured">
                                            <input type="checkbox" 
                                                   class="featured-toggle sr-only peer" 
                                                   data-product-id="<?= $product['id'] ?>" 
                                                   <?= (!empty($product['is_featured'])) ? 'checked' : '' ?>
                                                   onchange="toggleProductFeatured(<?= $product['id'] ?>, this.checked)">
                                            <div class="relative w-10 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-yellow-400/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:left-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-yellow-400"></div>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer with Pagination -->
        <?php if (!empty($products) || isset($totalCount)): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-600">
                <div class="flex items-center gap-4">
                    <span class="font-medium">
                        Showing <?= isset($currentPage) && isset($perPage) ? (($currentPage - 1) * $perPage + 1) : 1 ?> to 
                        <?= isset($currentPage) && isset($perPage) ? min($currentPage * $perPage, $totalCount ?? count($products)) : count($products) ?> of 
                        <?= $totalCount ?? count($products) ?> products
                    </span>
                </div>
                
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="flex items-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?= $currentPage - 1 ?><?= !empty($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : '' ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                            <i class="fas fa-chevron-left mr-1"></i>Previous
                        </span>
                    <?php endif; ?>
                    
                    <div class="flex items-center gap-1">
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?page=1<?= !empty($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : '' ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <span class="px-3 py-2 border border-primary bg-primary text-white rounded-lg font-medium"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= !empty($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : '' ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="px-2 text-gray-500">...</span>
                            <?php endif; ?>
                            <a href="?page=<?= $totalPages ?><?= !empty($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : '' ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors"><?= $totalPages ?></a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?= $currentPage + 1 ?><?= !empty($_GET['status']) ? '&status=' . htmlspecialchars($_GET['status']) : '' ?>" 
                           class="px-3 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-2 border border-gray-300 rounded-lg text-gray-400 bg-gray-100 cursor-not-allowed">
                            Next<i class="fas fa-chevron-right ml-1"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay hidden">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="modal-title">Delete Product</h3>
            <button id="cancelDeleteBtn" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mx-auto mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <p class="text-sm text-gray-500 text-center">
                Are you sure you want to delete this product? This action cannot be undone and will remove all associated images and data.
            </p>
        </div>
        <div class="modal-footer">
            <button id="cancelDeleteBtn2" class="btn btn-outline">Cancel</button>
            <button id="confirmDeleteBtn" class="btn btn-delete">Delete</button>
        </div>
    </div>
</div>

<script>
let productToDelete = null;

function confirmDelete(productId) {
    productToDelete = productId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const statusFilter = document.getElementById('statusFilter');
    const clearSearch = document.getElementById('clearSearch');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const productsTableBody = document.getElementById('productsTableBody');
    
    // Search functionality
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedCategory = categoryFilter.value.toLowerCase();
        const rows = document.querySelectorAll('.product-row');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const productName = row.dataset.name;
            const productCategory = row.dataset.category;
            
            const matchesSearch = !searchTerm || productName.includes(searchTerm);
            const matchesCategory = !selectedCategory || productCategory === selectedCategory;
            
            if (matchesSearch && matchesCategory) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide clear button
        if (searchTerm) {
            clearSearch.classList.remove('hidden');
        } else {
            clearSearch.classList.add('hidden');
        }
        
        // Show no results message
        const noProductsRow = document.getElementById('noProductsRow');
        if (visibleCount === 0 && rows.length > 0) {
            if (!document.getElementById('noResultsRow')) {
                const noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                            <p class="text-gray-500">Try adjusting your search or filter criteria.</p>
                        </div>
                    </td>
                `;
                productsTableBody.appendChild(noResultsRow);
            }
        } else {
            const noResultsRow = document.getElementById('noResultsRow');
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }
    
    // Event listeners
    searchInput.addEventListener('input', filterProducts);
    categoryFilter.addEventListener('change', filterProducts);
    statusFilter.addEventListener('change', function() {
        const baseUrl = '<?= \App\Core\View::url('admin/products') ?>';
        const status = statusFilter.value;
        const url = status ? `${baseUrl}?status=${encodeURIComponent(status)}` : baseUrl;
        window.location.href = url;
    });
    
    clearSearch.addEventListener('click', function() {
        searchInput.value = '';
        filterProducts();
        searchInput.focus();
    });
    
    // Delete modal handlers
    confirmDeleteBtn.addEventListener('click', function() {
        if (productToDelete) {
            fetch('<?= \App\Core\View::url('admin/deleteProduct/') ?>' + productToDelete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the row from table
                    const row = document.querySelector(`tr.product-row[data-product-id="${productToDelete}"]`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            // Check if table is empty
                            const remainingRows = document.querySelectorAll('.product-row');
                            if (remainingRows.length === 0) {
                                location.reload();
                            }
                        }, 300);
                    } else {
                        // Fallback: reload page if row not found
                        location.reload();
                    }
                    deleteModal.classList.add('hidden');
                    productToDelete = null;
                    
                    // Show success message
                    showNotification('Product deleted successfully', 'success');
                } else {
                    alert(data.message || 'Failed to delete product');
                    deleteModal.classList.add('hidden');
                    productToDelete = null;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the product');
                deleteModal.classList.add('hidden');
                productToDelete = null;
            });
        }
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
        productToDelete = null;
    });
    
    const cancelDeleteBtn2 = document.getElementById('cancelDeleteBtn2');
    if (cancelDeleteBtn2) {
        cancelDeleteBtn2.addEventListener('click', function() {
            deleteModal.classList.add('hidden');
            productToDelete = null;
        });
    }
    
    // Close modal on outside click
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
            productToDelete = null;
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            deleteModal.classList.add('hidden');
            productToDelete = null;
        }
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
    });
});

// Toggle Product Status
function toggleProductStatus(productId, isActive) {
    const status = isActive ? 'active' : 'inactive';
    fetch('<?= \App\Core\View::url('admin/updateProductStatus') ?>/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Product status updated', 'success');
        } else {
            showNotification(data.message || 'Failed to update status', 'error');
            // Revert toggle
            const toggle = document.querySelector(`.status-toggle[data-product-id="${productId}"]`);
            if (toggle) toggle.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        // Revert toggle
        const toggle = document.querySelector(`.status-toggle[data-product-id="${productId}"]`);
        if (toggle) toggle.checked = !isActive;
    });
}

// Toggle Product Featured
function toggleProductFeatured(productId, isFeatured) {
    fetch('<?= \App\Core\View::url('admin/toggleProductFeatured') ?>/' + productId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ is_featured: isFeatured ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Featured status updated', 'success');
        } else {
            showNotification(data.message || 'Failed to update featured status', 'error');
            // Revert toggle
            const toggle = document.querySelector(`.featured-toggle[data-product-id="${productId}"]`);
            if (toggle) toggle.checked = !isFeatured;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        // Revert toggle
        const toggle = document.querySelector(`.featured-toggle[data-product-id="${productId}"]`);
        if (toggle) toggle.checked = !isFeatured;
    });
}

function showNotification(message, type) {
    // Simple notification - you can enhance this
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<style>
/* iOS Safari specific fixes */
input[type="text"], 
input[type="search"], 
select, 
textarea {
    -webkit-appearance: none;
    appearance: none;
    -webkit-border-radius: 0;
    border-radius: 0.5rem;
}

/* Custom select arrow for better cross-browser compatibility */
select {
    background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%23666' d='M2 0L0 2h4zm0 5L0 3h4z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 0.7rem center;
    background-size: 0.65rem auto;
    padding-right: 2.5rem;
}

/* Smooth transitions */
.product-row {
    transition: background-color 0.15s ease-in-out;
}

/* Mobile responsive table */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}
</style>

<script>
// Bulk Actions for Products
function updateBulkActionState() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkContainer = document.getElementById('products-bulk-container');
    const selectedCount = document.getElementById('selected-count');
    
    if (checkedBoxes.length > 0) {
        bulkContainer.classList.remove('hidden');
        selectedCount.textContent = `${checkedBoxes.length} product${checkedBoxes.length > 1 ? 's' : ''} selected`;
    } else {
        bulkContainer.classList.add('hidden');
    }
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActionState();
}

function bulkUpdateStatus(status) {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showNotification('Please select at least one product', 'error');
        return;
    }
    const action = status === 'active' ? 'activate' : 'deactivate';
    
    if (!confirm(`Are you sure you want to ${action} ${checkedBoxes.length} product${checkedBoxes.length > 1 ? 's' : ''}?`)) {
        return;
    }

    const productIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    fetch('<?= \App\Core\View::url('admin/products/bulkUpdate') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_ids: productIds,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Reload to show updated status
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Failed to update products', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating products', 'error');
    });
}

function bulkDelete() {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkedBoxes.length === 0) {
        showNotification('Please select at least one product', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} product${checkedBoxes.length > 1 ? 's' : ''}? This action cannot be undone.`)) {
        return;
    }

    const productIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
    
    fetch('<?= \App\Core\View::url('admin/products/bulkUpdate') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            product_ids: productIds,
            action: 'delete'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            // Remove deleted rows from table
            productIds.forEach(id => {
                const row = document.querySelector(`tr.product-row[data-product-id="${id}"]`);
                if (row) {
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.remove();
                        // Check if table is empty
                        const remainingRows = document.querySelectorAll('.product-row');
                        if (remainingRows.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            });
            // Clear selection
            clearSelection();
        } else {
            showNotification(data.message || 'Failed to delete products', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while deleting products', 'error');
    });
}

// Stock Update Modal
function openStockModal(productId, productName, currentStock) {
    document.getElementById('stock-modal-product-id').value = productId;
    document.getElementById('stock-modal-product-name').textContent = productName;
    document.getElementById('stock-modal-current').textContent = currentStock;
    document.getElementById('stock-modal-quantity').value = currentStock;
    document.getElementById('stock-modal').classList.remove('hidden');
}

function closeStockModal() {
    document.getElementById('stock-modal').classList.add('hidden');
}

function updateStock() {
    const productId = document.getElementById('stock-modal-product-id').value;
    const newQuantity = parseInt(document.getElementById('stock-modal-quantity').value);
    
    if (isNaN(newQuantity) || newQuantity < 0) {
        alert('Please enter a valid quantity');
        return;
    }
    
    const form = new FormData();
    form.append('product_id', productId);
    form.append('stock_quantity', newQuantity);
    form.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('admin/products/updateStock') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: form
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeStockModal();
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update stock'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating stock');
    });
}

// Initialize bulk actions
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionState);
    });

    // Select all handling
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const rows = document.querySelectorAll('.product-checkbox');
            rows.forEach(cb => { cb.checked = selectAll.checked; });
            updateBulkActionState();
        });
    }

    // Status toggle handling
    document.querySelectorAll('.status-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.dataset.productId;
            const action = this.checked ? 'activate' : 'deactivate';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/products/bulkUpdate') ?>';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'product_ids[]';
            idInput.value = id;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        });
    });

    // Featured toggle handling
    document.querySelectorAll('.featured-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const id = this.dataset.productId;
            const action = this.checked ? 'feature_on' : 'feature_off';
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= \App\Core\View::url('admin/products/bulkUpdate') ?>';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'product_ids[]';
            idInput.value = id;
            form.appendChild(idInput);

            document.body.appendChild(form);
            form.submit();
        });
    });
});
</script>

<!-- Stock Update Modal -->
<div id="stock-modal" class="modal-overlay hidden">
    <div class="modal-panel">
        <div class="modal-header">
            <h3 class="modal-title">Update Stock</h3>
            <button onclick="closeStockModal()" class="modal-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">Product: <span id="stock-modal-product-name" class="font-medium text-gray-900"></span></p>
                <p class="text-sm text-gray-600">Current Stock: <span id="stock-modal-current" class="font-medium text-gray-900"></span></p>
            </div>
            
            <div>
                <label for="stock-modal-quantity" class="block text-sm font-medium text-gray-700 mb-2">
                    New Stock Quantity
                </label>
                <input type="number" id="stock-modal-quantity" min="0" step="1"
                       class="input native-input"
                       placeholder="Enter new quantity">
                <input type="hidden" id="stock-modal-product-id">
            </div>
        </div>
        
        <div class="modal-footer">
            <button onclick="closeStockModal()" class="btn btn-outline">Cancel</button>
            <button onclick="updateStock()" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>
                Update Stock
            </button>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>