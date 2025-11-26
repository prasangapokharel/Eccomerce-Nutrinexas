<?php ob_start(); ?>
<?php $page = 'products'; ?>
<?php use App\Helpers\CategoryHelper; ?>

<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Add New Product</h1>
            <p class="mt-1 text-sm text-gray-500">Create your product listing</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('seller/products') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Products
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="<?= \App\Core\View::url('seller/products/create') ?>" method="POST" id="productForm">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Product Information</h2>
                <p class="text-sm text-gray-600 mt-1">Fill in the essential details to create your product</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                    
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="product_name" 
                               name="product_name" 
                               class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter product name"
                               required>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Main Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category" 
                                class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                required>
                            <option value="">Select Main Category</option>
                            <?php foreach (CategoryHelper::getMainCategories() as $key => $value): ?>
                                <option value="<?= htmlspecialchars($key) ?>">
                                    <?= htmlspecialchars($value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Allowed categories: Supplements, Accessories, Protein, Clean Protein, Cycle, Equipments</p>
                    </div>

                    <div>
                        <label for="subcategory" class="block text-sm font-medium text-gray-700 mb-2">
                            Subcategory <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" 
                               id="subcategory" 
                               name="subcategory" 
                               class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter custom subcategory (e.g., Whey Protein, Resistance Bands)">
                        <p class="text-xs text-gray-500 mt-1">Keep this field unique—enter your own descriptive subcategory.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="product_type_main" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Type <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <select id="product_type_main" 
                                    name="product_type_main" 
                                    class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    onchange="handleProductTypeChange()">
                                <option value="">-- Select Product Type --</option>
                                <option value="Supplements">Supplements</option>
                                <option value="Protein">Protein</option>
                                <option value="Accessories">Accessories</option>
                                <option value="Cycle">Cycle</option>
                                <option value="Digital">Digital</option>
                            </select>
                        </div>

                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Sub-Type <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="product_type" 
                                   name="product_type" 
                                   class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., Whey, Bulking Cycle, Resistance Bands">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <div class="flex items-start p-4 border-2 border-gray-200 rounded-lg hover:border-primary transition-colors">
                                <input type="checkbox" 
                                       id="is_digital" 
                                       name="is_digital" 
                                       value="1"
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded mt-1">
                                <label for="is_digital" class="ml-3 flex-1">
                                    <div class="text-sm font-medium text-gray-900 flex items-center">
                                        <i class="fas fa-download text-purple-600 mr-2"></i>
                                        Digital Product
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Check this if the product is digital (e-books, online courses, software, etc.). 
                                        <strong>No shipping required</strong> - customer receives instant access.
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="colors" class="block text-sm font-medium text-gray-700 mb-2">
                                Available Colors <span class="text-gray-400 text-xs">(For Accessories/Clothing)</span>
                            </label>
                            <input type="text" 
                                   id="colors" 
                                   name="colors" 
                                   class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="Red, Blue, Green or #FF0000, #0000FF, #00FF00">
                            <p class="text-xs text-gray-500 mt-1">Enter colors separated by commas</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Regular Price (Rs) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">Rs</span>
                                </div>
                                <input type="number" 
                                       id="price" 
                                       name="price" 
                                       step="0.01" 
                                       min="0"
                                       class="input native-input w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                       placeholder="0.00"
                                       required>
                            </div>
                        </div>

                        <div>
                            <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Sale Price (Rs) <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">Rs</span>
                                </div>
                                <input type="number" 
                                       id="sale_price" 
                                       name="sale_price" 
                                       step="0.01" 
                                       min="0"
                                       class="input native-input w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                            Stock Quantity <span class="text-red-500">*</span>
                            <span id="digital-stock-note" class="text-xs text-purple-600 font-medium hidden ml-2">
                                <i class="fas fa-info-circle"></i> Digital products: Use 999 or higher
                            </span>
                        </label>
                        <input type="number" 
                               id="stock_quantity" 
                               name="stock_quantity" 
                               min="0"
                               class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="0"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-box text-gray-400"></i> 
                            Enter the number of items available in stock
                        </p>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                                Weight/Size <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="weight" 
                                   name="weight"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., 1kg, 500g, L, XL">
                        </div>

                        <div>
                            <label for="serving" class="block text-sm font-medium text-gray-700 mb-2">
                                Serving/Quantity <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="serving" 
                                   name="serving"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., 29, 30 servings, 1 piece">
                        </div>

                        <div>
                            <label for="flavor" class="block text-sm font-medium text-gray-700 mb-2">
                                Flavor/Color <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="flavor" 
                                   name="flavor"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., Chocolate, Vanilla, Black, Red">
                        </div>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Descriptions</h3>
                    
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                        <textarea id="short_description" 
                                  name="short_description" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                  placeholder="Brief product description for listings"></textarea>
                        <p class="text-xs text-gray-500 mt-1">This will appear in product listings and search results</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Description <span class="text-gray-400 text-xs">(Markdown supported)</span>
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="8"
                                  class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-y"
                                  placeholder="Detailed information about the product, benefits, and usage. Markdown syntax is supported."></textarea>
                        <p class="text-xs text-gray-500 mt-2">Supports Markdown formatting. Use CDN image URLs for images.</p>
                    </div>
                </div>

                <!-- Product Images -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                        Product Images <span class="text-red-500">*</span>
                    </h3>
                    
                    <div>
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">Primary Image URL (CDN) *</label>
                        <input type="url" 
                               id="image_url" 
                               name="image_url" 
                               class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="https://example.com/image.jpg"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Enter the full CDN URL for the product image</p>
                    </div>

                    <div>
                        <label for="additional_images" class="block text-sm font-medium text-gray-700 mb-2">Additional Image URLs (Optional)</label>
                        <textarea id="additional_images" 
                                  name="additional_images" 
                                  rows="4"
                                  class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                  placeholder="Enter image URLs, one per line (press Enter for each new link)"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Enter one URL per line. We also accept comma-separated values.</p>
                    </div>
                </div>

                <!-- Affiliate Commission -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Affiliate Commission</h3>
                    <div>
                        <label for="affiliate_commission" class="block text-sm font-medium text-gray-700 mb-2">
                            Affiliate Commission (%) <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="affiliate_commission" 
                                   name="affiliate_commission" 
                                   step="0.1"
                                   min="0"
                                   max="100"
                                   class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="Leave empty to use default">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">%</span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle text-blue-500"></i> 
                            Set custom affiliate commission % for this product (0-50%). If set to 0, no referral commission will be given. If left empty, system default (<?= number_format($defaultCommissionRate ?? 10, 1) ?>%) will be used.
                        </p>
                    </div>
                </div>

                <!-- Status -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Status</h3>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select id="status" 
                                name="status" 
                                class="input native-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:justify-end gap-3">
                <a href="<?= \App\Core\View::url('seller/products') ?>" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" 
                        class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark btn btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Create Product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function handleProductTypeChange() {
    const productType = document.getElementById('product_type_main').value;
    const isDigitalCheckbox = document.getElementById('is_digital');
    const colorsInput = document.getElementById('colors');
    const stockInput = document.getElementById('stock_quantity');
    const digitalStockNote = document.getElementById('digital-stock-note');
    
    if (productType === 'Digital') {
        isDigitalCheckbox.checked = true;
        colorsInput.disabled = true;
        colorsInput.placeholder = 'Not applicable for digital products';
        digitalStockNote.classList.remove('hidden');
        if (!stockInput.value || stockInput.value == '0') {
            stockInput.value = '999';
        }
    } else {
        colorsInput.disabled = false;
        colorsInput.placeholder = 'Red, Blue, Green or #FF0000, #0000FF, #00FF00';
        digitalStockNote.classList.add('hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const isDigitalCheckbox = document.getElementById('is_digital');
    if (isDigitalCheckbox) {
        isDigitalCheckbox.addEventListener('change', function() {
            const stockInput = document.getElementById('stock_quantity');
            const digitalStockNote = document.getElementById('digital-stock-note');
            if (this.checked) {
                digitalStockNote.classList.remove('hidden');
                if (!stockInput.value || stockInput.value == '0') {
                    stockInput.value = '999';
                }
            } else {
                digitalStockNote.classList.add('hidden');
            }
        });
    }

    const salePriceInput = document.getElementById('sale_price');
    const priceInput = document.getElementById('price');
    
    salePriceInput.addEventListener('input', function() {
        const price = parseFloat(priceInput.value) || 0;
        const salePrice = parseFloat(this.value) || 0;
        
        if (salePrice > 0 && salePrice >= price) {
            this.setCustomValidity('Sale price must be less than regular price');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
