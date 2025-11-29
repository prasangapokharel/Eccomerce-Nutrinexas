<?php ob_start(); ?>
<?php $page = 'products'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Edit Product</h1>
        <a href="<?= \App\Core\View::url('seller/products') ?>" class="btn btn-outline">
            <i class="fas fa-arrow-left mr-2"></i>Back to Products
        </a>
    </div>

    <div class="card">
        <form action="<?= \App\Core\View::url('seller/products/edit/' . $product['id']) ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 grid-cols-2">
                <div style="grid-column: span 2;">
                    <label for="product_name">Product Name *</label>
                    <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product['product_name'] ?? '') ?>" class="input native-input" required>
                </div>

                <div>
                    <label for="price">Price (रु) *</label>
                    <input type="number" id="price" name="price" value="<?= htmlspecialchars($product['price'] ?? 0) ?>" step="0.01" class="input native-input" required>
                </div>

                <div>
                    <label for="sale_price">Sale Price (रु)</label>
                    <input type="number" id="sale_price" name="sale_price" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>" step="0.01" class="input native-input">
                </div>

                <div>
                    <label for="stock_quantity">Stock Quantity *</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($product['stock_quantity'] ?? 0) ?>" class="input native-input" required min="0">
                </div>

                <div>
                    <label for="category">Category *</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>" class="input native-input" required>
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
                               class="input native-input"
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
                    <select id="status" name="status" class="input native-input" required>
                        <option value="active" <?= ($product['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="grid-col-span-2">
                    <label for="short_description">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2" class="input native-input"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                </div>

                <div class="grid-col-span-2">
                    <label for="description">Full Description</label>
                    <textarea id="description" name="description" rows="5" class="input native-input"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                </div>

                <?php if (!empty($images)): ?>
                    <div class="grid-col-span-2">
                        <label>Current Images</label>
                        <div class="grid grid-cols-4 gap-4">
                            <?php foreach ($images as $image): 
                                $isVideo = \App\Helpers\MediaHelper::isVideo($image['image_url']);
                            ?>
                                <div class="relative group" data-image-id="<?= (int)($image['id'] ?? 0) ?>">
                                    <?php if ($isVideo): ?>
                                        <video src="<?= htmlspecialchars($image['image_url']) ?>" 
                                               style="width: 100%; height: 6rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--gray-200);"
                                               muted
                                               preload="none">
                                        </video>
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <svg class="w-4 h-4 text-white drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                             alt="Product Media"
                                             style="width: 100%; height: 6rem; object-fit: cover; border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                                    <?php endif; ?>
                                    <?php if (!empty($image['is_primary'])): ?>
                                        <span class="badge badge-info absolute top-1 right-1">Primary</span>
                                    <?php endif; ?>
                                    <button type="button"
                                            class="absolute top-1 left-1 bg-red-600 text-white text-[11px] px-2 py-0.5 rounded opacity-90 hover:opacity-100 seller-delete-image-btn"
                                            data-image-id="<?= (int)($image['id'] ?? 0) ?>">
                                        Delete
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">You can delete any image (including the primary). Product must keep at least one image.</p>
                    </div>
                <?php endif; ?>

                <div class="grid-col-span-2">
                    <label for="image_url">Update Primary Media URL (CDN)</label>
                    <input type="url" id="image_url" name="image_url" class="input native-input" placeholder="https://example.com/image.jpg or https://example.com/video.mp4">
                    <p class="text-xs text-gray-600 mt-1">Enter CDN URL for image (.jpg, .png, .webp) or video (.mp4, .webm, .ogg)</p>
                </div>

                <div class="grid-col-span-2">
                    <label for="additional_images">Add Additional Media URLs</label>
                    <textarea id="additional_images" name="additional_images" rows="3" class="input native-input" placeholder="https://example.com/image1.jpg, https://example.com/video.mp4"></textarea>
                    <p class="text-xs text-gray-600 mt-1">Enter multiple image/video URLs separated by commas</p>
                </div>
            </div>

            <!-- Product Scheduling -->
            <div class="space-y-4" style="margin-top: 2rem;">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">Product Scheduling</h3>

                <div class="flex items-center p-4 border border-gray-200 rounded-lg bg-gray-50">
                    <input type="checkbox" 
                           id="is_scheduled" 
                           name="is_scheduled" 
                           value="1"
                           <?= (isset($product['is_scheduled']) && $product['is_scheduled']) ? 'checked' : '' ?>
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_scheduled" class="ml-3 cursor-pointer">
                        <div class="text-sm font-medium text-gray-900">Schedule Product Launch</div>
                        <div class="text-xs text-gray-500">Set a future date when this product becomes available for customers.</div>
                    </label>
                </div>

                <div id="sellerSchedulingOptions" class="space-y-4 <?= (isset($product['is_scheduled']) && $product['is_scheduled']) ? '' : 'hidden' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Launch Date <span class="text-red-500">*</span>
                            </label>
                            <input type="datetime-local" 
                                   id="scheduled_date" 
                                   name="scheduled_date" 
                                   value="<?= !empty($product['scheduled_date']) ? date('Y-m-d\TH:i', strtotime($product['scheduled_date'])) : '' ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm">
                            <p class="text-xs text-gray-500 mt-1">After this date/time, the product becomes orderable.</p>
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
                            <p class="text-xs text-gray-500 mt-1">Optional: when the scheduled launch period ends.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="scheduled_duration" class="block text-sm font-medium text-gray-700 mb-2">
                                Launch Highlight Duration (days) <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <input type="number" 
                                   id="scheduled_duration" 
                                   name="scheduled_duration" 
                                   value="<?= htmlspecialchars($product['scheduled_duration'] ?? '') ?>"
                                   min="1"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm"
                                   placeholder="e.g. 7">
                            <p class="text-xs text-gray-500 mt-1">Controls how long the product is treated as a launch highlight.</p>
                        </div>

                        <div>
                            <label for="scheduled_message" class="block text-sm font-medium text-gray-700 mb-2">
                                Launch Message <span class="text-gray-400 text-xs">(Optional)</span>
                            </label>
                            <textarea id="scheduled_message" 
                                      name="scheduled_message" 
                                      rows="2"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-colors text-sm resize-none"
                                      placeholder="Short note to show on product page before launch"><?= htmlspecialchars($product['scheduled_message'] ?? '') ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Example: “Limited launch – special pricing for early buyers.”</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEO & Metadata -->
            <div class="space-y-4" style="margin-top: 2rem;">
                <h3 class="text-lg font-medium text-gray-900 border-b border-gray-200 pb-2">SEO & Metadata</h3>
                
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Title
                    </label>
                    <input type="text" 
                           id="meta_title" 
                           name="meta_title" 
                           value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>"
                           class="input native-input"
                           placeholder="SEO title for search engines (50-60 characters recommended)"
                           maxlength="60">
                    <p class="text-xs text-gray-500 mt-1">Recommended: 50-60 characters</p>
                </div>
                
                <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                        Meta Description
                    </label>
                    <textarea id="meta_description" 
                              name="meta_description" 
                              rows="3"
                              class="input native-input resize-none"
                              placeholder="SEO description for search engines (150-160 characters recommended)"
                              maxlength="160"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                    <p class="text-xs text-gray-500 mt-1">Recommended: 150-160 characters</p>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scheduleCheckbox = document.getElementById('is_scheduled');
    const scheduleOptions = document.getElementById('sellerSchedulingOptions');

    if (scheduleCheckbox && scheduleOptions) {
        scheduleCheckbox.addEventListener('change', function () {
            if (this.checked) {
                scheduleOptions.classList.remove('hidden');
            } else {
                scheduleOptions.classList.add('hidden');
            }
        });
    }

    // AJAX delete for product images
    document.querySelectorAll('.seller-delete-image-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const imageId = this.dataset.imageId;
            if (!imageId) return;
            if (!confirm('Are you sure you want to delete this image?')) return;

            fetch('<?= \App\Core\View::url('seller/products/delete-image/') ?>' + imageId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(resp => resp.json())
              .then(data => {
                  if (data.success) {
                      const wrapper = this.closest('[data-image-id]');
                      if (wrapper) wrapper.remove();
                  } else {
                      alert(data.message || 'Failed to delete image');
                  }
              }).catch(() => {
                  alert('Failed to delete image');
              });
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
