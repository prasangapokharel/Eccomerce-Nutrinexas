<?php ob_start(); ?>

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
                            Category <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="category" 
                               name="category" 
                               value="<?= htmlspecialchars($data['category'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="e.g., Protein, Vitamins, Supplements, Creatine"
                               required>
                        <p class="text-xs text-gray-500 mt-1">Main product category</p>
                    </div>

                    <!-- Price and Sale Price -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">$</span>
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
                                Sale Price <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">$</span>
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
                        </label>
                        <input type="number" 
                               id="stock_quantity" 
                               name="stock_quantity" 
                               min="0"
                               value="<?= htmlspecialchars($data['stock_quantity'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="0"
                               required>
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
                    
                    <!-- Product Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="product_type_main" class="block text-sm font-medium text-gray-700 mb-2">
                                Main Type <span class="text-red-500">*</span>
                            </label>
                            <select id="product_type_main" 
                                    name="product_type_main" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                    required>
                                <option value="">Select Type</option>
                                <option value="Supplement" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Supplement') ? 'selected' : '' ?>>Supplement</option>
                                <option value="Vitamins" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Vitamins') ? 'selected' : '' ?>>Vitamins</option>
                                <option value="Protein" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Protein') ? 'selected' : '' ?>>Protein</option>
                                <option value="Creatine" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Creatine') ? 'selected' : '' ?>>Creatine</option>
                                <option value="Pre-Workout" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Pre-Workout') ? 'selected' : '' ?>>Pre-Workout</option>
                                <option value="Post-Workout" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Post-Workout') ? 'selected' : '' ?>>Post-Workout</option>
                                <option value="Clothing" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Clothing') ? 'selected' : '' ?>>Clothing</option>
                                <option value="Accessories" <?= (isset($data['product_type_main']) && $data['product_type_main'] === 'Accessories') ? 'selected' : '' ?>>Accessories</option>
                            </select>
                        </div>

                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Sub Type <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="product_type" 
                                   name="product_type" 
                                   value="<?= htmlspecialchars($data['product_type'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="e.g., Whey Protein, Multivitamin, T-Shirt"
                                   required>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Short Description <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <textarea id="short_description" 
                                  name="short_description" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                  placeholder="Brief description that will appear in product listings"><?= htmlspecialchars($data['short_description'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">This will appear in product listings and search results</p>
                    </div>

                    <!-- Full Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Description <span class="text-gray-400 text-xs">(Markdown supported)</span>
                        </label>
                        
                        <!-- Markdown Editor Container -->
                        <div class="border border-gray-300 rounded-lg overflow-hidden">
                            <!-- Toolbar -->
                            <div class="bg-gray-50 border-b border-gray-200 px-3 py-2 flex flex-wrap gap-2">
                                <button type="button" onclick="insertMarkdown('**', '**')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Bold">
                                    <strong>B</strong>
                                </button>
                                <button type="button" onclick="insertMarkdown('*', '*')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Italic">
                                    <em>I</em>
                                </button>
                                <button type="button" onclick="insertMarkdown('`', '`')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Code">
                                    <code>&lt;/&gt;</code>
                                </button>
                                <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                <button type="button" onclick="insertMarkdown('# ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Heading 1">
                                    H1
                                </button>
                                <button type="button" onclick="insertMarkdown('## ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Heading 2">
                                    H2
                                </button>
                                <button type="button" onclick="insertMarkdown('### ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Heading 3">
                                    H3
                                </button>
                                <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                <button type="button" onclick="insertMarkdown('- ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Bullet List">
                                    •
                                </button>
                                <button type="button" onclick="insertMarkdown('1. ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Numbered List">
                                    1.
                                </button>
                                <button type="button" onclick="insertMarkdown('> ', '')" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Quote">
                                    "
                                </button>
                                <div class="w-px h-6 bg-gray-300 mx-1"></div>
                                <button type="button" onclick="insertImageUrl()" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Insert Image URL">
                                    🖼️
                                </button>
                                <button type="button" onclick="insertLink()" class="px-2 py-1 text-xs bg-white border border-gray-300 rounded hover:bg-gray-50" title="Insert Link">
                                    🔗
                                </button>
                            </div>
                            
                            <!-- Editor -->
                            <textarea id="description" 
                                      name="description" 
                                      rows="8"
                                      class="w-full px-4 py-3 border-0 focus:outline-none focus:ring-0 text-sm resize-none"
                                      placeholder="Detailed information about the product, benefits, and usage. You can use Markdown (# Headers, * Lists, **Bold**, etc.) or insert image URLs from CDN."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mt-2 flex items-start space-x-4">
                            <p class="text-xs text-gray-500">Supports Markdown formatting. Use CDN image URLs (e.g., https://cdn.example.com/image.jpg) for images.</p>
                            <button type="button" onclick="previewMarkdown()" class="text-xs text-blue-600 hover:text-blue-800">Preview</button>
                        </div>
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
                                    <label for="image_urls" class="block text-sm font-medium text-gray-700 mb-2">Image URLs</label>
                                    <textarea id="image_urls" 
                                              name="image_urls" 
                                              rows="6"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm resize-none"
                                              placeholder="Enter image URLs, one per line:&#10;https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"></textarea>
                                    <p class="text-sm text-gray-500 mt-2">Enter one URL per line. Make sure URLs are publicly accessible.</p>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">Launch Date</label>
                                <input type="date" 
                                       id="scheduled_date" 
                                       name="scheduled_date" 
                                       value="<?= htmlspecialchars($data['scheduled_date'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            </div>
                            <div>
                                <label for="scheduled_duration" class="block text-sm font-medium text-gray-700 mb-2">Countdown Duration (days)</label>
                                <input type="number" 
                                       id="scheduled_duration" 
                                       name="scheduled_duration" 
                                       min="1"
                                       max="365"
                                       value="<?= htmlspecialchars($data['scheduled_duration'] ?? '7') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                       placeholder="7">
                            </div>
                        </div>
                        <div>
                            <label for="scheduled_message" class="block text-sm font-medium text-gray-700 mb-2">Custom Message</label>
                            <input type="text" 
                                   id="scheduled_message" 
                                   name="scheduled_message" 
                                   value="<?= htmlspecialchars($data['scheduled_message'] ?? 'Coming Soon! Get ready for this amazing product.') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                   placeholder="Coming Soon! Get ready for this amazing product.">
                        </div>
                    </div>
                </div>

                <!-- Additional Options -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Additional Options</h3>
                    
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <input type="checkbox" 
                               id="is_featured" 
                               name="is_featured" 
                               value="1"
                               <?= (isset($data['is_featured']) && $data['is_featured']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_featured" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Featured Product</div>
                            <div class="text-xs text-gray-500">Show this product prominently on the homepage</div>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="<?= \App\Core\View::url('admin/products') ?>" 
                       class="px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Cancel
                    </a>
                    <button type="submit" 
                            id="submitBtn"
                            class="px-6 py-3 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submitText">Add Product</span>
                        <span id="submitSpinner" class="hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
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
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelection(files);
        }
    });

    // File input change
    imagesInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files);
        }
    });

    // Scheduling checkbox
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
                    previewDiv.className = 'relative border-2 border-gray-200 rounded-lg overflow-hidden';
                    previewDiv.innerHTML = `
                        <div class="aspect-square bg-gray-100">
                            <img src="${e.target.result}" alt="Preview ${index + 1}" class="w-full h-full object-cover">
                        </div>
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

// Markdown Editor Functions
function insertMarkdown(before, after) {
    const textarea = document.getElementById('description');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const newText = before + selectedText + after;
    
    textarea.value = textarea.value.substring(0, start) + newText + textarea.value.substring(end);
    
    // Set cursor position
    const newCursorPos = start + before.length + selectedText.length;
    textarea.setSelectionRange(newCursorPos, newCursorPos);
    textarea.focus();
}

function insertImageUrl() {
    const url = prompt('Enter image URL (CDN recommended):', 'https://cdn.example.com/image.jpg');
    if (url && url.trim()) {
        const alt = prompt('Enter alt text for the image:', 'Product image');
        const markdown = `![${alt || 'Image'}](${url})`;
        insertMarkdown(markdown, '');
    }
}

function insertLink() {
    const url = prompt('Enter URL:', 'https://example.com');
    if (url && url.trim()) {
        const text = prompt('Enter link text:', 'Link');
        const markdown = `[${text || 'Link'}](${url})`;
        insertMarkdown(markdown, '');
    }
}

function previewMarkdown() {
    const content = document.getElementById('description').value;
    if (!content.trim()) {
        alert('No content to preview');
        return;
    }
    
    // Create preview window
    const previewWindow = window.open('', '_blank', 'width=800,height=600,scrollbars=yes');
    previewWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Markdown Preview</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; line-height: 1.6; }
                h1, h2, h3, h4, h5, h6 { margin-top: 24px; margin-bottom: 16px; font-weight: 600; }
                h1 { font-size: 2em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
                h2 { font-size: 1.5em; border-bottom: 1px solid #eaecef; padding-bottom: 0.3em; }
                h3 { font-size: 1.25em; }
                p { margin-bottom: 16px; }
                ul, ol { margin-bottom: 16px; padding-left: 30px; }
                li { margin-bottom: 4px; }
                blockquote { margin: 0 0 16px; padding: 0 1em; color: #6a737d; border-left: 0.25em solid #dfe2e5; }
                code { padding: 0.2em 0.4em; margin: 0; font-size: 85%; background-color: rgba(27,31,35,0.05); border-radius: 3px; }
                pre { padding: 16px; overflow: auto; font-size: 85%; line-height: 1.45; background-color: #f6f8fa; border-radius: 3px; }
                img { max-width: 100%; height: auto; }
                a { color: #0366d6; text-decoration: none; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div id="preview-content"></div>
            <script src="https://cdn.jsdelivr.net/npm/marked@4.3.0/marked.min.js"></script>
            <script>
                const content = \`${content.replace(/`/g, '\\`')}\`;
                document.getElementById('preview-content').innerHTML = marked.parse(content);
            </script>
        </body>
        </html>
    `);
}

// Auto-resize textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
</script>

<style>
/* Aspect ratio utility */
.aspect-square {
    aspect-ratio: 1 / 1;
}

/* Smooth transitions */
.transition-colors {
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

/* Focus states for better accessibility */
input:focus,
textarea:focus,
select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Custom checkbox styling */
input[type="checkbox"] {
    -webkit-appearance: none;
    appearance: none;
    background-color: #fff;
    margin: 0;
    font: inherit;
    color: currentColor;
    width: 1rem;
    height: 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    transform: translateY(-0.075em);
    display: grid;
    place-content: center;
}

input[type="checkbox"]:checked {
    background-color: #0A3167;
    border-color: #0A3167;
}

input[type="checkbox"]:checked::before {
    content: "✓";
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
    
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
