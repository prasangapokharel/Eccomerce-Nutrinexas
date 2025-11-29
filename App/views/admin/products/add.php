<?php 
ob_start(); 
use App\Helpers\CategoryHelper;
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Add New Product</h1>
            <p class="mt-1 text-sm text-gray-500">Quick and easy product creation</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/products') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Products
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="<?= \App\Core\View::url('admin/addProduct') ?>" method="POST" enctype="multipart/form-data" id="productForm">
            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Product Information</h2>
                <p class="text-sm text-gray-600 mt-1">Fill in the essential details to create your product</p>
            </div>

            <div class="p-6 space-y-6">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                    
                    <!-- Product Name -->
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="product_name" 
                               name="product_name" 
                               value="<?= htmlspecialchars($data['product_name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter product name"
                               required>
                        <?php if (isset($errors['product_name'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['product_name']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Main Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                required>
                            <option value="">Select Main Category</option>
                            <?php foreach (CategoryHelper::getMainCategories() as $key => $value): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= (isset($data['category']) && $data['category'] === $key) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($value) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Select the main product category</p>
                    </div>

                    <!-- Subcategory -->
                    <div>
                        <label for="subcategory" class="block text-sm font-medium text-gray-700 mb-2">
                            Subcategory <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" 
                               id="subcategory" 
                               name="subcategory" 
                               value="<?= htmlspecialchars($data['subcategory'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Enter subcategory (e.g., Protein, Creatine, Pre-workout)">
                        <p class="text-xs text-gray-500 mt-1">Enter a flexible subcategory for better product organization</p>
                        <?php if (isset($errors['subcategory'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['subcategory']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Product Type Section -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>Product Type Information
                        </h4>
                        <p class="text-xs text-blue-700 mb-3">Help customers find products easily by selecting the appropriate type.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="product_type_main" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Type <span class="text-gray-400 text-xs">(Recommended)</span>
                            </label>
                            <select id="product_type_main" 
                                    name="product_type_main" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    onchange="handleProductTypeChange()">
                                <option value="">-- Select Product Type --</option>
                                <option value="Supplement" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Supplement') ? 'selected' : '' ?>>Supplement (Protein, Creatine, etc.)</option>
                                <option value="Vitamins" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Vitamins') ? 'selected' : '' ?>>Vitamins (Multivitamins, etc.)</option>
                                <option value="Accessories" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Accessories') ? 'selected' : '' ?>>Accessories (Clothing, Equipment, etc.)</option>
                                <option value="Digital" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Digital') ? 'selected' : '' ?>>Digital (E-books, Courses, Software)</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-lightbulb text-yellow-500"></i> 
                                Select the main category for this product
                            </p>
                        </div>

                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Sub-Type <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="product_type" 
                                   name="product_type" 
                                   value="<?= htmlspecialchars($data['product_type'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., Protein, Clothing, Equipment">
                            <p class="text-xs text-gray-500 mt-1">More specific type (e.g., "Whey Protein", "T-Shirt", "Dumbbells")</p>
                        </div>
                    </div>

                    <!-- Digital Product & Colors -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <div class="flex items-start p-4 border-2 border-gray-200 rounded-lg hover:border-primary transition-colors">
                                <input type="checkbox" 
                                       id="is_digital" 
                                       name="is_digital" 
                                       value="1"
                                       <?= isset($data['is_digital']) && $data['is_digital'] ? 'checked' : '' ?>
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
                                    <div class="text-xs text-purple-600 mt-1 font-medium">
                                        <i class="fas fa-info-circle"></i> Tip: Digital products don't need stock quantity
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
                                   value="<?= htmlspecialchars($data['colors'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="Red, Blue, Green or #FF0000, #0000FF, #00FF00">
                            <div class="mt-2 space-y-1">
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-check-circle text-green-500"></i> 
                                    Enter colors separated by commas
                                </p>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-palette text-blue-500"></i> 
                                    Use color names (Red, Blue) or hex codes (#FF0000)
                                </p>
                                <p class="text-xs text-blue-600 font-medium">
                                    <i class="fas fa-lightbulb"></i> 
                                    You can add detailed color variants with stock after creating the product
                                </p>
                            </div>
                        </div>
                    </div>

                    <script>
                    function handleProductTypeChange() {
                        const productType = document.getElementById('product_type_main').value;
                        const isDigitalCheckbox = document.getElementById('is_digital');
                        const colorsInput = document.getElementById('colors');
                        const stockInput = document.getElementById('stock_quantity');
                        const digitalStockNote = document.getElementById('digital-stock-note');
                        
                        // Auto-check digital if Digital type selected
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
                    
                    // Also handle when digital checkbox is toggled
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
                        
                        // Auto-fill default images for Accessories
                        const productTypeMain = document.getElementById('product_type_main');
                        const imageUrlsTextarea = document.getElementById('image_urls');
                        if (productTypeMain && imageUrlsTextarea) {
                            productTypeMain.addEventListener('change', function() {
                                if (this.value === 'Accessories' && !imageUrlsTextarea.value.trim()) {
                                    const defaultImages = [
                                        'https://apparel.goldsgym.com/media/image/38/4b/0b/Vorschauq2YVQ1o02K49b_1142x1142@2x.jpg',
                                        'https://apparel.goldsgym.com/media/image/a0/c2/02/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1446_1142x1142@2x.jpg',
                                        'https://apparel.goldsgym.com/media/image/68/b3/fd/221205_GG_Classic_MEN_Heavy_Weight_Classic_Joe_Grau-1441_1142x1142@2x.jpg'
                                    ];
                                    imageUrlsTextarea.value = defaultImages.join('\n');
                                    // Trigger preview generation
                                    if (imageUrlsTextarea.dispatchEvent) {
                                        imageUrlsTextarea.dispatchEvent(new Event('input'));
                                    }
                                }
                            });
                        }
                    });
                    </script>

                    <!-- Price and Sale Price -->
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
                                       value="<?= htmlspecialchars($data['price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                       placeholder="0.00"
                                       required>
                            </div>
                            <?php if (isset($errors['price'])): ?>
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <?= htmlspecialchars($errors['price']) ?>
                                </p>
                            <?php endif; ?>
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
                                       value="<?= htmlspecialchars($data['sale_price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                       placeholder="0.00">
                            </div>
                            <?php if (isset($errors['sale_price'])): ?>
                                <p class="text-red-500 text-sm mt-2 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <?= htmlspecialchars($errors['sale_price']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Stock Quantity -->
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
                               value="<?= htmlspecialchars($data['stock_quantity'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="0"
                               required>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-box text-gray-400"></i> 
                            Enter the number of items available in stock
                        </p>
                        <?php if (isset($errors['stock_quantity'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['stock_quantity']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Weight/Size -->
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                                Weight/Size <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="weight" 
                                   name="weight"
                                   value="<?= htmlspecialchars($data['weight'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., 1kg, 500g, L, XL">
                            <p class="text-xs text-gray-500 mt-1">Product weight or size</p>
                        </div>

                        <!-- Serving -->
                        <div>
                            <label for="serving" class="block text-sm font-medium text-gray-700 mb-2">
                                Serving/Quantity <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="serving" 
                                   name="serving"
                                   value="<?= htmlspecialchars($data['serving'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., 29, 30 servings, 1 piece">
                            <p class="text-xs text-gray-500 mt-1">Number of servings or quantity</p>
                        </div>

                        <!-- Flavor -->
                        <div>
                            <label for="flavor" class="block text-sm font-medium text-gray-700 mb-2">
                                Flavor/Color <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="flavor" 
                                   name="flavor"
                                   value="<?= htmlspecialchars($data['flavor'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., Chocolate, Vanilla, Black, Red">
                            <p class="text-xs text-gray-500 mt-1">Flavor for supplements, color for clothing</p>
                        </div>
                    </div>
                </div>

                <!-- Featured Product -->
                <div class="space-y-4">
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <input type="checkbox" 
                               id="is_featured" 
                               name="is_featured" 
                               value="1"
                               <?= isset($data['is_featured']) && $data['is_featured'] ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_featured" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Featured Product</div>
                            <div class="text-xs text-gray-500">Display prominently on homepage</div>
                        </label>
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
                                  placeholder="Brief product description for listings"><?= htmlspecialchars($data['short_description'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">This will appear in product listings and search results</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Description <span class="text-gray-400 text-xs">(Markdown supported)</span>
                        </label>

                        <!-- Minimal Markdown textarea -->
                        <textarea id="description"
                                  name="description"
                                  rows="8"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-y"
                                  placeholder="Detailed information about the product, benefits, and usage. Markdown syntax is supported (e.g., # Headers, * Lists, **Bold**).">
                            <?= htmlspecialchars($data['description'] ?? '') ?>
                        </textarea>
                        <p class="text-xs text-gray-500 mt-2">Supports Markdown formatting. Use CDN image URLs for images.</p>
                    </div>
                </div>

                <!-- Product Images -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                        Product Images <span class="text-red-500">*</span>
                    </h3>
                    
                    <!-- Image Upload Tabs -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <div class="flex border-b border-gray-200 bg-gray-50">
                            <button type="button" 
                                    id="uploadTab" 
                                    class="flex-1 px-4 py-3 text-sm font-medium text-center border-r border-gray-200 bg-white text-primary border-b-2 border-primary">
                                <i class="fas fa-upload mr-2"></i>Upload Files
                            </button>
                            <button type="button" 
                                    id="urlTab" 
                                    class="flex-1 px-4 py-3 text-sm font-medium text-center text-gray-500 hover:text-gray-700">
                                <i class="fas fa-link mr-2"></i>Image URLs
                            </button>
                        </div>

                        <!-- Upload Tab Content -->
                        <div id="uploadTabContent" class="p-6">
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors" id="dropZone">
                                <div class="space-y-4">
                                    <div class="mx-auto h-12 w-12 text-gray-400">
                                        <i class="fas fa-cloud-upload-alt text-4xl"></i>
                                    </div>
                                    <div>
                                        <label for="images" class="cursor-pointer">
                                            <span class="text-primary font-medium hover:text-primary-dark">Click to upload</span>
                                            <span class="text-gray-500"> or drag and drop</span>
                                        </label>
                                        <input type="file" 
                                               id="images" 
                                               name="images[]" 
                                               multiple 
                                               accept="image/*"
                                               class="hidden">
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF, WebP up to 5MB each</p>
                                </div>
                            </div>
                            
                            <!-- File Preview -->
                            <div id="filePreview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 hidden"></div>
                        </div>

                        <!-- URL Tab Content -->
                        <div id="urlTabContent" class="p-6 hidden">
                            <div class="space-y-4">
                                <div>
                                    <label for="image_urls" class="block text-sm font-medium text-gray-700 mb-2">Media URLs (Images/Videos)</label>
                                    <textarea id="image_urls" 
                                              name="image_urls" 
                                              rows="6"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                              placeholder="Enter image/video URLs, one per line:&#10;https://example.com/image1.jpg&#10;https://example.com/video.mp4&#10;https://example.com/image2.jpg"><?= htmlspecialchars($data['image_urls'] ?? '') ?></textarea>
                                    <p class="text-sm text-gray-500 mt-2">Enter one URL per line. Supports images (.jpg, .png, .webp) and videos (.mp4, .webm, .ogg). Make sure URLs are publicly accessible.</p>
                                    <p class="text-xs text-blue-600 mt-1">
                                        <i class="fas fa-info-circle"></i> 
                                        For Accessories, default images will be used if none provided.
                                    </p>
                                </div>
                                
                                <!-- URL Preview -->
                                <div id="urlPreview" class="hidden">
                                    <h4 class="text-sm font-medium text-gray-700 mb-3">Preview & Select Primary Image</h4>
                                    <div id="urlPreviewContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                                    <input type="hidden" id="primary_image_url" name="primary_image_url" value="">
                                    <p class="text-xs text-gray-500 mt-2">Click on an image to set it as the primary image.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($errors['images'])): ?>
                        <p class="text-red-500 text-sm flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <?= htmlspecialchars($errors['images']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Product Scheduling -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Scheduling</h3>
                    
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <input type="checkbox" 
                               id="is_scheduled" 
                               name="is_scheduled" 
                               value="1"
                               <?= (isset($data['is_scheduled']) && $data['is_scheduled']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_scheduled" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Schedule Product Release</div>
                            <div class="text-xs text-gray-500">Set a future date when this product becomes available for purchase</div>
                        </label>
                    </div>

                    <div id="schedulingOptions" class="space-y-4 <?= (isset($data['is_scheduled']) && $data['is_scheduled']) ? '' : 'hidden' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       id="scheduled_date" 
                                       name="scheduled_date" 
                                       value="<?= !empty($data['scheduled_date']) ? date('Y-m-d\TH:i', strtotime($data['scheduled_date'])) : '' ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       required>
                                <p class="text-xs text-gray-500 mt-1">When the product becomes available</p>
                            </div>

                            <div>
                                <label for="scheduled_end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    End Date <span class="text-gray-400 text-xs">(Optional)</span>
                                </label>
                                <input type="datetime-local" 
                                       id="scheduled_end_date" 
                                       name="scheduled_end_date" 
                                       value="<?= !empty($data['scheduled_end_date']) ? date('Y-m-d\TH:i', strtotime($data['scheduled_end_date'])) : '' ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                                <p class="text-xs text-gray-500 mt-1">When the scheduled period ends (leave empty for no end date)</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="scheduled_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                    Duration (Days) <span class="text-gray-400 text-xs">(Alternative)</span>
                                </label>
                                <input type="number" 
                                       id="scheduled_duration" 
                                       name="scheduled_duration" 
                                       min="1"
                                       max="365"
                                       value="<?= htmlspecialchars($data['scheduled_duration'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="Leave empty if using dates">
                                <p class="text-xs text-gray-500 mt-1">Alternative: Product available for X days from start date</p>
                            </div>
                            
                            <div>
                                <label for="scheduled_message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Countdown Message
                                </label>
                                <input type="text" 
                                       id="scheduled_message" 
                                       name="scheduled_message" 
                                       value="<?= htmlspecialchars($data['scheduled_message'] ?? 'Coming Soon! Get ready for this amazing product.') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="Coming Soon! Get ready for this amazing product.">
                                <p class="text-xs text-gray-500 mt-1">Message shown during the countdown period</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:justify-end gap-3">
                <a href="<?= \App\Core\View::url('admin/products') ?>" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    Cancel
                </a>
                <button type="submit" 
                        id="submitBtn"
                        class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                    <i class="fas fa-plus mr-2"></i>
                    <span id="submitText">Add Product</span>
                    <i class="fas fa-spinner fa-spin ml-2 hidden" id="submitSpinner"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const uploadTab = document.getElementById('uploadTab');
    const urlTab = document.getElementById('urlTab');
    const uploadTabContent = document.getElementById('uploadTabContent');
    const urlTabContent = document.getElementById('urlTabContent');
    const imagesInput = document.getElementById('images');
    const imageUrlsTextarea = document.getElementById('image_urls');
    const dropZone = document.getElementById('dropZone');
    const filePreview = document.getElementById('filePreview');
    const urlPreview = document.getElementById('urlPreview');
    const urlPreviewContainer = document.getElementById('urlPreviewContainer');
    const primaryImageUrlInput = document.getElementById('primary_image_url');
    const productForm = document.getElementById('productForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');

    // Tab switching
    uploadTab.addEventListener('click', function() {
        switchTab('upload');
    });

    urlTab.addEventListener('click', function() {
        switchTab('url');
    });

    function switchTab(tab) {
        if (tab === 'upload') {
            uploadTab.classList.add('bg-white', 'text-primary', 'border-b-2', 'border-primary');
            uploadTab.classList.remove('text-gray-500');
            urlTab.classList.remove('bg-white', 'text-primary', 'border-b-2', 'border-primary');
            urlTab.classList.add('text-gray-500');
            uploadTabContent.classList.remove('hidden');
            urlTabContent.classList.add('hidden');
            // Clear URL inputs
            imageUrlsTextarea.value = '';
            urlPreview.classList.add('hidden');
        } else {
            urlTab.classList.add('bg-white', 'text-primary', 'border-b-2', 'border-primary');
            urlTab.classList.remove('text-gray-500');
            uploadTab.classList.remove('bg-white', 'text-primary', 'border-b-2', 'border-primary');
            uploadTab.classList.add('text-gray-500');
            urlTabContent.classList.remove('hidden');
            uploadTabContent.classList.add('hidden');
            // Clear file inputs
            imagesInput.value = '';
            filePreview.classList.add('hidden');
            filePreview.innerHTML = '';
        }
    }

    // Drag and drop functionality
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-primary', 'bg-primary-50');
    });

    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-primary', 'bg-primary-50');
    });

    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-primary', 'bg-primary-50');

        const droppedFiles = e.dataTransfer.files;
        if (droppedFiles.length > 0) {
            const dataTransfer = new DataTransfer();
            Array.from(droppedFiles).forEach(file => dataTransfer.items.add(file));
            imagesInput.files = dataTransfer.files;
            handleFileSelection(dataTransfer.files);
        }
    });

    // File input change
    imagesInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files);
        }
    });

    // Schedule toggle functionality
    const scheduleCheckbox = document.getElementById('is_scheduled');
    const schedulingOptions = document.getElementById('schedulingOptions');
    
    if (scheduleCheckbox && schedulingOptions) {
        scheduleCheckbox.addEventListener('change', function() {
            if (this.checked) {
                schedulingOptions.classList.remove('hidden');
            } else {
                schedulingOptions.classList.add('hidden');
            }
        });
    }

    function handleFileSelection(files) {
        filePreview.innerHTML = '';
        filePreview.classList.remove('hidden');

        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'relative border border-gray-200 rounded-lg overflow-hidden';
                    previewDiv.innerHTML = `
                        <div class="aspect-square bg-gray-100">
                            <img src="${e.target.result}" alt="Preview ${index + 1}" 
                                 class="w-full h-full object-cover">
                        </div>
                        <div class="absolute top-2 left-2 bg-white rounded px-2 py-1 text-xs font-medium shadow">
                            ${index + 1}
                        </div>
                        ${index === 0 ? '<div class="absolute top-2 right-2 bg-green-500 text-white rounded px-2 py-1 text-xs font-medium">Primary</div>' : ''}
                        <div class="p-2">
                            <p class="text-xs text-gray-600 truncate">${file.name}</p>
                            <p class="text-xs text-gray-400">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                        </div>
                    `;
                    filePreview.appendChild(previewDiv);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // URL textarea handling
    imageUrlsTextarea.addEventListener('input', function() {
        const urls = this.value.split('\n').filter(url => url.trim());
        
        if (urls.length > 0) {
            generateUrlPreviews(urls);
        } else {
            urlPreview.classList.add('hidden');
        }
    });

    function generateUrlPreviews(urls) {
        urlPreviewContainer.innerHTML = '';
        
        if (urls.length === 0) {
            urlPreview.classList.add('hidden');
            return;
        }
        
        urlPreview.classList.remove('hidden');
        
        urls.forEach((url, index) => {
            url = url.trim();
            if (!url) return;
            
            const previewDiv = document.createElement('div');
            previewDiv.className = 'relative cursor-pointer border-2 border-gray-200 rounded-lg overflow-hidden hover:border-primary transition-colors';
            previewDiv.dataset.url = url;
            previewDiv.dataset.index = index;
            
            // Set first URL as primary by default
            if (index === 0) {
                previewDiv.classList.add('border-primary', 'bg-primary-50');
                primaryImageUrlInput.value = url;
            }
            
            previewDiv.innerHTML = `
                <div class="aspect-square bg-gray-100 flex items-center justify-center">
                    <img src="${url}" alt="Preview ${index + 1}" 
                         class="w-full h-full object-cover" 
                         onerror="this.parentElement.innerHTML='<div class=\\'text-gray-400 text-xs p-2 text-center\\'>Invalid Image URL</div>'">
                </div>
                <div class="absolute top-2 left-2 bg-white rounded px-2 py-1 text-xs font-medium shadow">
                    ${index + 1}
                </div>
                <div class="absolute top-2 right-2 primary-badge ${index === 0 ? '' : 'hidden'} bg-green-500 text-white rounded px-2 py-1 text-xs font-medium">
                    Primary
                </div>
            `;
            
            previewDiv.addEventListener('click', function() {
                // Remove primary styling from all previews
                document.querySelectorAll('#urlPreviewContainer > div').forEach(div => {
                    div.classList.remove('border-primary', 'bg-primary-50');
                    div.querySelector('.primary-badge').classList.add('hidden');
                });
                
                // Add primary styling to clicked preview
                this.classList.add('border-primary', 'bg-primary-50');
                this.querySelector('.primary-badge').classList.remove('hidden');
                
                // Set primary image URL
                primaryImageUrlInput.value = this.dataset.url;
            });
            
            urlPreviewContainer.appendChild(previewDiv);
        });
    }

    // Form submission
    productForm.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitText.textContent = 'Adding Product...';
        submitSpinner.classList.remove('hidden');
    });

    // Price validation
    const priceInput = document.getElementById('price');
    const salePriceInput = document.getElementById('sale_price');

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

<style>
/* iOS Safari specific fixes */
input[type="text"], 
input[type="number"], 
input[type="email"], 
input[type="password"], 
input[type="search"], 
select, 
textarea {
    -webkit-appearance: none;
    appearance: none;
    -webkit-border-radius: 0.5rem;
    border-radius: 0.5rem;
    font-size: 16px; /* Prevents zoom on iOS */
}

</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
