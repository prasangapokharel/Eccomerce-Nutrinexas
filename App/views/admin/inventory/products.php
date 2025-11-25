<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Products Management</h1>
            <p class="text-gray-600 mt-2">Manage your wholesale products and inventory</p>
        </div>
        <a href="<?= \App\Core\View::url('admin/inventory/add-product') ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Product
        </a>
    </div>

    <!-- Flash Messages -->
    <?php 
    $flashMessage = \App\Helpers\FlashHelper::getFlashMessage('success');
    if ($flashMessage): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $flashMessage ?>
        </div>
    <?php endif; ?>

    <?php 
    $flashError = \App\Helpers\FlashHelper::getFlashMessage('error');
    if ($flashError): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $flashError ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Products</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total_products'] ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-box text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Products</p>
                    <p class="text-2xl font-bold text-green-600"><?= $stats['active_products'] ?></p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Quantity</p>
                    <p class="text-2xl font-bold text-blue-600"><?= number_format($stats['total_quantity'] ?? 0) ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-cubes text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Inventory Value</p>
                    <p class="text-2xl font-bold text-purple-600">Rs <?= number_format($stats['total_inventory_value'] ?? 0, 2) ?></p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-rupee-sign text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">All Products</h2>
                <div class="flex items-center space-x-4">
                    <!-- Barcode/SKU Scanner -->
                    <div class="relative">
                        <input type="text" id="barcodeScanner" placeholder="Scan barcode/SKU..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               autocomplete="off">
                        <i class="fas fa-barcode absolute left-3 top-3 text-gray-400"></i>
                        <button onclick="startBarcodeScan()" class="absolute right-2 top-2 text-blue-600 hover:text-blue-800" title="Start Camera Scanner">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    <!-- Search -->
                    <div class="relative">
                        <input type="text" id="searchProducts" placeholder="Search products..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost/Selling</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="product-row hover:bg-gray-50" data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($product['product_name']) ?></div>
                                        <?php if (!empty($product['sku'])): ?>
                                            <div class="text-sm text-gray-500">SKU: <?= htmlspecialchars($product['sku']) ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($product['type'])): ?>
                                            <div class="text-sm text-gray-500">Type: <?= htmlspecialchars($product['type']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($product['supplier_name']) ?></div>
                                    <?php if (!empty($product['supplier_phone'])): ?>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($product['supplier_phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div>Cost: Rs <?= number_format($product['cost_amount'], 2) ?></div>
                                        <?php if (!empty($product['selling_price'])): ?>
                                            <div>Selling: Rs <?= number_format($product['selling_price'], 2) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium"><?= number_format($product['quantity']) ?> units</div>
                                        <div class="text-xs text-gray-500">Min: <?= $product['min_stock_level'] ?></div>
                                        <?php if ($product['quantity'] <= $product['min_stock_level']): ?>
                                            <div class="text-xs text-red-600 font-medium">Low Stock!</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status = $product['status'];
                                    $statusConfig = [
                                        'active' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                        'inactive' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                                        'discontinued' => ['bg-gray-100 text-gray-800', 'fas fa-ban'],
                                    ];
                                    $statusStyle = $statusConfig[$status] ?? $statusConfig['active'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusStyle[0] ?>">
                                        <i class="<?= $statusStyle[1] ?> mr-1"></i>
                                        <?= ucfirst($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/inventory/edit-product/' . $product['product_id']) ?>" 
                                           class="text-blue-600 hover:text-blue-900 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteProduct(<?= $product['product_id'] ?>)" 
                                                class="text-red-600 hover:text-red-900 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-box text-4xl text-gray-300 mb-4"></i>
                                <p class="text-lg font-medium">No products found</p>
                                <p class="text-sm">Get started by adding your first product</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Delete Product</h3>
                        <p class="text-sm text-gray-600">This action cannot be undone</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-6">Are you sure you want to delete this product? All associated purchases will also be deleted.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST" class="inline">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Barcode/SKU Scanner functionality
let barcodeScanTimeout;
const barcodeInput = document.getElementById('barcodeScanner');

barcodeInput.addEventListener('input', function() {
    const barcode = this.value.trim();
    
    // Clear previous timeout
    clearTimeout(barcodeScanTimeout);
    
    // Wait for user to finish typing (500ms delay)
    if (barcode.length >= 3) {
        barcodeScanTimeout = setTimeout(() => {
            scanBarcode(barcode);
        }, 500);
    }
});

barcodeInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = this.value.trim();
        if (barcode) {
            scanBarcode(barcode);
        }
    }
});

function scanBarcode(barcode) {
    const formData = new FormData();
    formData.append('barcode', barcode);
    formData.append('_csrf_token', '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>');
    
    fetch('<?= \App\Core\View::url('admin/inventory/scanBarcode') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Highlight the found product
            highlightProduct(data.product.product_id);
            // Show success message
            showNotification('Product found: ' + data.product.product_name, 'success');
            // Clear input
            barcodeInput.value = '';
        } else {
            showNotification(data.message || 'Product not found', 'error');
        }
    })
    .catch(error => {
        console.error('Barcode scan error:', error);
        showNotification('Error scanning barcode', 'error');
    });
}

function highlightProduct(productId) {
    // Remove previous highlights
    document.querySelectorAll('.product-row').forEach(row => {
        row.classList.remove('bg-yellow-100', 'border-l-4', 'border-yellow-500');
    });
    
    // Find and highlight the product row
    const rows = document.querySelectorAll('.product-row');
    rows.forEach(row => {
        const editLink = row.querySelector('a[href*="edit-product"]');
        if (editLink && editLink.href.includes(productId)) {
            row.classList.add('bg-yellow-100', 'border-l-4', 'border-yellow-500');
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

function startBarcodeScan() {
    // Request camera access for barcode scanning
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(stream => {
                // Camera access granted - you can integrate a barcode scanner library here
                // For now, focus on the input field
                barcodeInput.focus();
                showNotification('Camera access granted. You can now scan barcodes.', 'info');
            })
            .catch(err => {
                console.error('Camera access error:', err);
                barcodeInput.focus();
                showNotification('Camera access denied. Please type the barcode manually.', 'warning');
            });
    } else {
        barcodeInput.focus();
        showNotification('Camera not available. Please type the barcode manually.', 'info');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        type === 'warning' ? 'bg-yellow-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Search functionality
document.getElementById('searchProducts').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        if (name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete product
function deleteProduct(productId) {
    document.getElementById('deleteForm').action = `<?= \App\Core\View::url('admin/inventory/delete-product/') ?>${productId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>