<?php 
ob_start(); 
use App\Helpers\CategoryHelper;
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Edit Product</h1>
            <p class="mt-1 text-sm text-gray-500">Update product information and specifications</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/products') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Products
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form action="<?= \App\Core\View::url('admin/updateProduct/' . ($product['id'] ?? '')) ?>" method="POST" enctype="multipart/form-data" id="productForm">
            <!-- Form Header -->
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">Product Information</h2>
                <p class="text-sm text-gray-600 mt-1">Update the details below to modify your product</p>
            </div>

            <div class="p-6 space-y-8">
                <!-- Basic Information -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Basic Information</h3>
                    
                    <!-- Product Name -->
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="product_name" 
                               name="product_name" 
                               value="<?= htmlspecialchars($product['product_name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                               placeholder="Enter product name"
                               required>
                        <?php if (isset($errors['product_name'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['product_name']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Price and Sale Price -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                       value="<?= htmlspecialchars($product['price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
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
                                       value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Stock and Category -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                                Stock Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="stock_quantity" 
                                   name="stock_quantity" 
                                   min="0"
                                   value="<?= htmlspecialchars($product['stock_quantity'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="0"
                                   required>
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                                Main Category <span class="text-red-500">*</span>
                            </label>
                            <select id="category" 
                                    name="category" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                    required>
                                <option value="">Select Main Category</option>
                                <?php foreach (CategoryHelper::getMainCategories() as $key => $value): ?>
                                    <option value="<?= htmlspecialchars($key) ?>" <?= (isset($product['category']) && $product['category'] === $key) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($value) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                    </div>

                    <!-- Subcategory -->
                    <div>
                        <label for="subcategory" class="block text-sm font-medium text-gray-700 mb-2">
                            Subcategory <span class="text-gray-400 text-xs">(Optional)</span>
                        </label>
                        <input type="text" 
                               id="subcategory" 
                               name="subcategory" 
                               value="<?= htmlspecialchars($product['subtype'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                               placeholder="Enter subcategory (e.g., Protein, Creatine, Pre-workout)">
                        <p class="text-xs text-gray-500 mt-1">Enter a flexible subcategory for better product organization</p>
                        <?php if (isset($errors['subcategory'])): ?>
                            <p class="text-red-500 text-sm mt-2 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?= htmlspecialchars($errors['subcategory']) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Product Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="product_type_main" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Type <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <select id="product_type_main" 
                                    name="product_type_main" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                                <option value="">Select Product Type</option>
                                <option value="Supplement" <?= (isset($product['product_type_main']) && $product['product_type_main'] === 'Supplement') ? 'selected' : '' ?>>Supplement</option>
                                <option value="Vitamins" <?= (isset($product['product_type_main']) && $product['product_type_main'] === 'Vitamins') ? 'selected' : '' ?>>Vitamins</option>
                                <option value="Accessories" <?= (isset($product['product_type_main']) && $product['product_type_main'] === 'Accessories') ? 'selected' : '' ?>>Accessories</option>
                                <option value="Digital" <?= (isset($product['product_type_main']) && $product['product_type_main'] === 'Digital') ? 'selected' : '' ?>>Digital</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Select the main product type</p>
                        </div>

                        <div>
                            <label for="product_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Product Sub-Type <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="text" 
                                   id="product_type" 
                                   name="product_type" 
                                   value="<?= htmlspecialchars($product['product_type'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., Protein, Clothing, Equipment">
                            <p class="text-xs text-gray-500 mt-1">More specific product type</p>
                        </div>
                    </div>

                    <!-- Digital Product & Colors -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                                <input type="checkbox" 
                                       id="is_digital" 
                                       name="is_digital" 
                                       value="1"
                                       <?= (isset($product['is_digital']) && $product['is_digital']) ? 'checked' : '' ?>
                                       class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                <label for="is_digital" class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">Digital Product</div>
                                    <div class="text-xs text-gray-500">No shipping required (e.g., e-books, courses, software)</div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="colors" class="block text-sm font-medium text-gray-700 mb-2">
                                Available Colors <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <?php
                            $colorsValue = '';
                            if (!empty($product['colors'])) {
                                $colorsArray = is_string($product['colors']) ? json_decode($product['colors'], true) : $product['colors'];
                                if (is_array($colorsArray)) {
                                    $colorsValue = implode(',', $colorsArray);
                                }
                            }
                            ?>
                            <input type="text" 
                                   id="colors" 
                                   name="colors" 
                                   value="<?= htmlspecialchars($colorsValue) ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., Red,Blue,Green or #FF0000,#0000FF,#00FF00">
                            <p class="text-xs text-gray-500 mt-1">Enter colors separated by commas (names or hex codes)</p>
                            <p class="text-xs text-gray-400 mt-1">Note: You can add detailed color variants after creating the product</p>
                        </div>
                    </div>
                </div>

                <!-- Featured Product -->
                <div class="space-y-6">
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <input type="checkbox" 
                               id="is_featured" 
                               name="is_featured" 
                               value="1"
                               <?= (isset($product['is_featured']) && $product['is_featured']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_featured" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Featured Product</div>
                            <div class="text-xs text-gray-500">Display prominently on homepage</div>
                        </label>
                    </div>
                </div>

                <!-- Product Specifications -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Specifications</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">
                                Weight/Size
                            </label>
                            <input type="text" 
                                   id="weight" 
                                   name="weight" 
                                   value="<?= htmlspecialchars($product['weight'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., 500g, L, XL, 10kg">
                            <p class="text-xs text-gray-500 mt-1">Product weight or size</p>
                        </div>

                        <div>
                            <label for="serving" class="block text-sm font-medium text-gray-700 mb-2">
                                Serving/Quantity
                            </label>
                            <input type="text" 
                                   id="serving" 
                                   name="serving" 
                                   value="<?= htmlspecialchars($product['serving'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., 30 servings, 1 piece">
                            <p class="text-xs text-gray-500 mt-1">Number of servings or quantity included</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="flavor" class="block text-sm font-medium text-gray-700 mb-2">
                                Flavor/Color
                            </label>
                            <input type="text" 
                                   id="flavor" 
                                   name="flavor" 
                                   value="<?= htmlspecialchars($product['flavor'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., Chocolate, Vanilla, Black, Red">
                            <p class="text-xs text-gray-500 mt-1">Flavor for supplements, color for clothing</p>
                        </div>

                        <div>
                            <label for="material" class="block text-sm font-medium text-gray-700 mb-2">
                                Material
                            </label>
                            <input type="text" 
                                   id="material" 
                                   name="material" 
                                   value="<?= htmlspecialchars($product['material'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., 100% Cotton, Polyester Blend">
                            <p class="text-xs text-gray-500 mt-1">Material composition (for clothing, accessories)</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="optimal_weight" class="block text-sm font-medium text-gray-700 mb-2">
                                Optimal Weight
                            </label>
                            <input type="text" 
                                   id="optimal_weight" 
                                   name="optimal_weight" 
                                   value="<?= htmlspecialchars($product['optimal_weight'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., 2.5 kg">
                        </div>

                        <div>
                            <label for="serving_size" class="block text-sm font-medium text-gray-700 mb-2">
                                Serving Size
                            </label>
                            <input type="text" 
                                   id="serving_size" 
                                   name="serving_size" 
                                   value="<?= htmlspecialchars($product['serving_size'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g., 69 Servings">
                        </div>
                    </div>
                    
                    <div>
                        <label for="ingredients" class="block text-sm font-medium text-gray-700 mb-2">
                            Ingredients
                        </label>
                        <textarea id="ingredients" 
                                  name="ingredients" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
                                  placeholder="Key ingredients (for supplements)"><?= htmlspecialchars($product['ingredients'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Key ingredients (for supplements)</p>
                    </div>
                    
                    <div>
                        <label for="size_available" class="block text-sm font-medium text-gray-700 mb-2">
                            Available Sizes
                        </label>
                        <?php
                        $sizesValue = '';
                        if (!empty($product['size_available'])) {
                            $sizesArray = is_string($product['size_available']) ? json_decode($product['size_available'], true) : $product['size_available'];
                            if (is_array($sizesArray)) {
                                $sizesValue = implode(',', $sizesArray);
                            }
                        }
                        ?>
                        <input type="text" 
                               id="size_available" 
                               name="size_available" 
                               value="<?= htmlspecialchars($sizesValue) ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                               placeholder="e.g., S, M, L, XL, XXL or Small, Medium, Large">
                        <p class="text-xs text-gray-500 mt-1">Enter sizes separated by commas (for clothing/accessories)</p>
                    </div>
                    
                    <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                        <input type="checkbox" 
                               id="capsule" 
                               name="capsule" 
                               value="1"
                               <?= (isset($product['capsule']) && $product['capsule']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="capsule" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Capsule Format</div>
                            <div class="text-xs text-gray-500">Product is in capsule/tablet format</div>
                        </label>
                    </div>
                </div>
                
                <!-- Pricing & Commission -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Pricing & Commission</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Cost Price (Rs)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">Rs</span>
                                </div>
                                <input type="number" 
                                       id="cost_price" 
                                       name="cost_price" 
                                       step="0.01" 
                                       min="0"
                                       value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label for="compare_price" class="block text-sm font-medium text-gray-700 mb-2">
                                Compare At Price (Rs)
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 text-sm">Rs</span>
                                </div>
                                <input type="number" 
                                       id="compare_price" 
                                       name="compare_price" 
                                       step="0.01" 
                                       min="0"
                                       value="<?= htmlspecialchars($product['compare_price'] ?? '') ?>"
                                       class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="0.00">
                            </div>
                        </div>

                        <div>
                            <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                Commission Rate (%)
                            </label>
                            <input type="number" 
                                   id="commission_rate" 
                                   name="commission_rate" 
                                   step="0.01" 
                                   min="0"
                                   max="100"
                                   value="<?= htmlspecialchars($product['commission_rate'] ?? '10.00') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="10.00">
                        </div>
                    </div>
                </div>
                
                <!-- SEO & Metadata -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">SEO & Metadata</h3>
                    
                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Meta Title
                        </label>
                        <input type="text" 
                               id="meta_title" 
                               name="meta_title" 
                               value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                               placeholder="SEO title for search engines">
                        <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                    </div>
                    
                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Meta Description
                        </label>
                        <textarea id="meta_description" 
                                  name="meta_description" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
                                  placeholder="SEO description for search engines"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
                    </div>
                    
                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">
                            Tags
                        </label>
                        <input type="text" 
                               id="tags" 
                               name="tags" 
                               value="<?= htmlspecialchars($product['tags'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                               placeholder="Enter tags separated by commas">
                        <p class="text-xs text-gray-500 mt-1">Tags for search and filtering (comma-separated)</p>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Status</h3>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            Status
                        </label>
                        <select id="status" 
                                name="status" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                            <option value="active" <?= (isset($product['status']) && $product['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (isset($product['status']) && $product['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Active products are visible to customers</p>
                    </div>
                </div>

                <!-- Descriptions -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Descriptions</h3>
                    
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                        <textarea id="short_description" 
                                  name="short_description" 
                                  rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
                                  placeholder="Brief product description for listings (recommended: 100-150 characters)"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-xs text-gray-500">This will appear in product listings and search results</p>
                            <span id="shortDescCount" class="text-xs text-gray-400">0/150</span>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Full Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="6"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
                                  placeholder="Detailed product description including benefits, ingredients, usage instructions, etc."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Detailed information about the product, benefits, and usage</p>
                    </div>
                </div>

                <!-- Product Images -->
                <div class="space-y-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">
                        Product Images
                    </h3>
                    
                    <!-- Current Images -->
                    <?php if (!empty($product['images'])): ?>
                    <div class="space-y-4">
                        <h4 class="text-sm font-medium text-gray-700">Current Images</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach ($product['images'] as $index => $image): ?>
                            <div class="relative border border-gray-200 rounded-lg overflow-hidden">
                                <div class="aspect-square bg-gray-100">
                                    <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                         alt="Product Image <?= $index + 1 ?>" 
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="p-2">
                                    <p class="text-xs text-gray-600 truncate">
                                        <?= $image['is_primary'] ? 'Primary Image' : 'Image ' . ($index + 1) ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?= $image['sort_order'] + 1 ?> of <?= count($product['images']) ?>
                                    </p>
                                </div>
                                <?php if ($image['is_primary']): ?>
                                <div class="absolute top-2 right-2 bg-green-500 text-white rounded px-2 py-1 text-xs font-medium">
                                    Primary
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-500">Images are managed separately. Use the image management tools to add/remove images.</p>
                    </div>
                    <?php endif; ?>
                    
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
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
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
                               <?= (isset($product['is_scheduled']) && $product['is_scheduled']) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="is_scheduled" class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Schedule Product Release</div>
                            <div class="text-xs text-gray-500">Set a future date when this product becomes available for purchase</div>
                        </label>
                    </div>

                    <div id="schedulingOptions" class="space-y-4 <?= (isset($product['is_scheduled']) && $product['is_scheduled']) ? '' : 'hidden' ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" 
                                       id="scheduled_date" 
                                       name="scheduled_date" 
                                       value="<?= !empty($product['scheduled_date']) ? date('Y-m-d\TH:i', strtotime($product['scheduled_date'])) : '' ?>"
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
                                       value="<?= !empty($product['scheduled_end_date']) ? date('Y-m-d\TH:i', strtotime($product['scheduled_end_date'])) : '' ?>"
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
                                       value="<?= htmlspecialchars($product['scheduled_duration'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                       placeholder="Leave empty if using dates">
                                <p class="text-xs text-gray-500 mt-1">Alternative: Product available for X days from start date</p>
                            </div>
                            
                            <div class="flex items-end">
                                <div class="w-full">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Schedule Status
                                    </label>
                                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <?php
                                        $now = new DateTime();
                                        $startDate = !empty($product['scheduled_date']) ? new DateTime($product['scheduled_date']) : null;
                                        $endDate = !empty($product['scheduled_end_date']) ? new DateTime($product['scheduled_end_date']) : null;
                                        
                                        if ($startDate) {
                                            if ($now < $startDate) {
                                                $status = 'Scheduled';
                                                $statusColor = 'text-blue-600 bg-blue-50';
                                                $statusText = 'Starts: ' . $startDate->format('M d, Y H:i');
                                            } elseif ($endDate && $now > $endDate) {
                                                $status = 'Ended';
                                                $statusColor = 'text-gray-600 bg-gray-50';
                                                $statusText = 'Ended: ' . $endDate->format('M d, Y H:i');
                                            } else {
                                                $status = 'Active';
                                                $statusColor = 'text-green-600 bg-green-50';
                                                $statusText = 'Currently Available';
                                            }
                                        } else {
                                            $status = 'Not Scheduled';
                                            $statusColor = 'text-gray-600 bg-gray-50';
                                            $statusText = 'No schedule set';
                                        }
                                        ?>
                                        <span class="text-xs font-semibold <?= $statusColor ?> px-2 py-1 rounded"><?= $status ?></span>
                                        <p class="text-xs text-gray-600 mt-1"><?= $statusText ?></p>
                                        <?php if ($endDate): ?>
                                            <p class="text-xs text-gray-500 mt-1">Ends: <?= $endDate->format('M d, Y H:i') ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="scheduled_message" class="block text-sm font-medium text-gray-700 mb-2">
                                Countdown Message
                            </label>
                            <input type="text" 
                                   id="scheduled_message" 
                                   name="scheduled_message" 
                                   value="<?= htmlspecialchars($product['scheduled_message'] ?? 'Coming Soon! Get ready for this amazing product.') ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="Coming Soon! Get ready for this amazing product.">
                            <p class="text-xs text-gray-500 mt-1">Message shown during the countdown period</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex flex-col sm:flex-row sm:justify-end gap-3">
                <a href="<?= \App\Core\View::url('admin/products') ?>" 
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        id="submitBtn"
                        class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    <span id="submitText">Update Product</span>
                    <i class="fas fa-spinner fa-spin ml-2 hidden" id="submitSpinner"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation function
    window.validateForm = function() {
        const productName = document.getElementById('product_name').value.trim();
        const price = parseFloat(document.getElementById('price').value);
        const stockQuantity = parseInt(document.getElementById('stock_quantity').value);
        const category = document.getElementById('category').value;
        
        let isValid = true;
        let errorMessage = '';
        
        if (!productName) {
            errorMessage = 'Product name is required';
            isValid = false;
        } else if (price <= 0) {
            errorMessage = 'Price must be greater than zero';
            isValid = false;
        } else if (stockQuantity < 0) {
            errorMessage = 'Stock quantity cannot be negative';
            isValid = false;
        } else if (!category) {
            errorMessage = 'Category is required';
            isValid = false;
        }
        
        if (!isValid) {
            alert('Please fix the following error:\n' + errorMessage);
            return false;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');
        
        if (submitBtn && submitText && submitSpinner) {
            submitBtn.disabled = true;
            submitText.textContent = 'Updating Product...';
            submitSpinner.classList.remove('hidden');
        }
        
        return true;
    };

    // Elements
    const categorySelect = document.getElementById('category');
    const productForm = document.getElementById('productForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitSpinner = document.getElementById('submitSpinner');

    // Form submission
    if (productForm) {
        productForm.addEventListener('submit', function() {
            if (submitBtn && submitText && submitSpinner) {
                submitBtn.disabled = true;
                submitText.textContent = 'Updating Product...';
                submitSpinner.classList.remove('hidden');
            }
        });
    }

    // Image handling functionality
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
    const shortDescTextarea = document.getElementById('short_description');
    const shortDescCount = document.getElementById('shortDescCount');

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
            imagesInput.files = files;
            handleFileSelection(files);
        }
    });

    // File input change
    imagesInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelection(this.files);
        }
    });

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

    // Character counter for short description
    shortDescTextarea.addEventListener('input', function() {
        const count = this.value.length;
        shortDescCount.textContent = `${count}/150`;
        
        if (count > 150) {
            shortDescCount.classList.add('text-red-500');
            shortDescCount.classList.remove('text-gray-400');
        } else {
            shortDescCount.classList.remove('text-red-500');
            shortDescCount.classList.add('text-gray-400');
        }
    });

    // Initialize character counter
    if (shortDescTextarea) {
        shortDescTextarea.dispatchEvent(new Event('input'));
    }

    // Price validation
    const priceInput = document.getElementById('price');
    const salePriceInput = document.getElementById('sale_price');

    if (salePriceInput) {
        salePriceInput.addEventListener('input', function() {
            const price = parseFloat(priceInput.value) || 0;
            const salePrice = parseFloat(this.value) || 0;
            
            if (salePrice > 0 && salePrice >= price) {
                this.setCustomValidity('Sale price must be less than regular price');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Toggle scheduling options visibility
    const isScheduledCheckbox = document.getElementById('is_scheduled');
    const schedulingOptions = document.getElementById('schedulingOptions');
    
    if (isScheduledCheckbox && schedulingOptions) {
        // Initial state
        schedulingOptions.style.display = isScheduledCheckbox.checked ? 'block' : 'none';
        
        // Toggle on change
        isScheduledCheckbox.addEventListener('change', function() {
            if (this.checked) {
                schedulingOptions.classList.remove('hidden');
                schedulingOptions.style.display = 'block';
                // Ensure date inputs are visible and functional
                const dateInputs = schedulingOptions.querySelectorAll('input[type="datetime-local"]');
                dateInputs.forEach(input => {
                    input.style.display = 'block';
                    input.style.visibility = 'visible';
                    input.removeAttribute('disabled');
                    input.style.opacity = '1';
                });
            } else {
                schedulingOptions.classList.add('hidden');
                schedulingOptions.style.display = 'none';
            }
        });
    }
    
    // Ensure datetime-local inputs are properly displayed
    const dateInputs = document.querySelectorAll('input[type="datetime-local"]');
    dateInputs.forEach(input => {
        // Force display and visibility
        input.style.display = 'block';
        input.style.visibility = 'visible';
        input.style.opacity = '1';
        input.removeAttribute('disabled');
        input.style.width = '100%';
        input.style.padding = '0.75rem 1rem';
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
    -webkit-border-radius: 0.5rem;
    border-radius: 0.5rem;
    font-size: 16px; /* Prevents zoom on iOS */
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

/* Remove checkmark from toggle switches - only show switch */
input[type="checkbox"].sr-only:checked::before,
input[type="checkbox"].toggle-status:checked::before,
input[type="checkbox"].peer:checked::before {
    content: none;
}

/* Only show checkmark for regular checkboxes, not toggle switches */
input[type="checkbox"]:not(.sr-only):not(.toggle-status):not(.peer):checked::before {
    content: "✓";
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
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

/* Aspect ratio utility */
.aspect-square {
    aspect-ratio: 1 / 1;
}

/* Mobile responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    
    .md\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
