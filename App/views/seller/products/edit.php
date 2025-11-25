<?php ob_start(); ?>
<?php $page = 'products'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Edit Product</h1>
        <a href="<?= \App\Core\View::url('seller/products') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Products
        </a>
    </div>

    <div class="card">
        <form action="<?= \App\Core\View::url('seller/products/edit/' . $product['id']) ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 grid-cols-2">
                <div style="grid-column: span 2;">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product['product_name'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="price">Price (रु) *</label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($product['price'] ?? 0) ?>" step="0.01" required>
                </div>

                <div>
                    <label for="sale_price">Sale Price (रु)</label>
                    <input type="number" id="sale_price" name="sale_price" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>" step="0.01">
                </div>

                <div>
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($product['stock_quantity'] ?? 0) ?>" required min="0">
                </div>

                <div>
                    <label for="category">Category *</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>" required>
                </div>

                <div>
                    <label for="affiliate_commission">Affiliate Commission (%) <span class="text-gray-400 text-xs">(Optional)</span></label>
                    <div class="relative">
                        <input type="number" 
                               id="affiliate_commission" 
                               name="affiliate_commission" 
                               value="<?= htmlspecialchars($product['affiliate_commission'] ?? '') ?>" 
                               step="0.1"
                               min="0"
                               max="50"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                               placeholder="Leave empty to use default">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <span class="text-gray-500 text-sm">%</span>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Custom affiliate commission % for this product (0-50%). If set to 0, no referral commission will be given. If empty, system default will be used.
                    </p>
                </div>

                <div>
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="grid-col-span-2">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                </div>

                <div class="grid-col-span-2">
                    <label for="description">Full Description</label>
                    <textarea id="description" name="description" rows="5"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <?php if (!empty($images)): ?>
                    <div class="grid-col-span-2">
                        <label>Current Images</label>
                        <div class="grid grid-cols-4 gap-4">
                            <?php foreach ($images as $image): ?>
                                <div style="position: relative;">
                                    <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                         alt="Product Image"
                                         style="width: 100%; height: 6rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                                    <?php if ($image['is_primary']): ?>
                                        <span class="badge badge-info" style="position: absolute; top: 0.25rem; right: 0.25rem;">Primary</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid-col-span-2">
                    <label for="image_url">Update Primary Image URL (CDN)</label>
                    <input type="url" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                    <p class="text-xs text-gray-600 mt-1">Enter new CDN URL to replace primary image</p>
                </div>

                <div class="grid-col-span-2">
                    <label for="additional_images">Add Additional Image URLs</label>
                    <textarea id="additional_images" name="additional_images" rows="3" placeholder="https://example.com/image1.jpg, https://example.com/image2.jpg"></textarea>
                    <p class="text-xs text-gray-600 mt-1">Enter multiple image URLs separated by commas</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4" style="margin-top: 1.5rem;">
                <a href="<?= \App\Core\View::url('seller/products') ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update Product
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
