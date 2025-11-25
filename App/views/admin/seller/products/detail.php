<?php ob_start(); ?>
<?php $page = 'seller-products'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Review Product: <?= htmlspecialchars($product['product_name']) ?></h1>
        <a href="<?= \App\Core\View::url('admin/seller/products') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <!-- Product Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Product Information</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Product Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($product['product_name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Description</label>
                        <p class="text-gray-900 whitespace-pre-wrap"><?= htmlspecialchars($product['description'] ?? 'N/A') ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Price</label>
                            <p class="text-gray-900">रु <?= number_format($product['price'], 2) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Sale Price</label>
                            <p class="text-gray-900"><?= $product['sale_price'] ? 'रु ' . number_format($product['sale_price'], 2) : 'N/A' ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Stock</label>
                            <p class="text-gray-900"><?= $product['stock_quantity'] ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Category</label>
                            <p class="text-gray-900"><?= htmlspecialchars($product['category'] ?? 'N/A') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <?php if (!empty($images)): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Product Images</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($images as $image): ?>
                            <div>
                                <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                     alt="Product Image"
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200">
                                <?php if ($image['is_primary']): ?>
                                    <p class="text-xs text-center text-primary mt-1">Primary</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <!-- Seller Info -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Seller Information</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-gray-900"><?= htmlspecialchars($product['seller_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900"><?= htmlspecialchars($product['seller_email'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Phone</label>
                        <p class="text-gray-900"><?= htmlspecialchars($product['seller_phone'] ?? 'N/A') ?></p>
                    </div>
                </div>
            </div>

            <!-- Approval Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Review & Approval</h2>
                
                <?php if ($product['approval_status'] === 'pending' || empty($product['approval_status'])): ?>
                    <form action="<?= \App\Core\View::url('admin/seller/products/detail/' . $product['id']) ?>" method="POST">
                        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                        
                        <div class="mb-4">
                            <label for="approval_notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Approval Notes (Optional)
                            </label>
                            <textarea id="approval_notes" 
                                      name="approval_notes" 
                                      rows="3"
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-sm"
                                      placeholder="Add notes for the seller (e.g., reason for rejection or approval)"><?= htmlspecialchars($product['approval_notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex gap-3">
                            <button type="submit" 
                                    name="action" 
                                    value="approve"
                                    class="flex-1 bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 font-medium">
                                <i class="fas fa-check mr-2"></i>Approve
                            </button>
                            <button type="submit" 
                                    name="action" 
                                    value="reject"
                                    class="flex-1 bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 font-medium">
                                <i class="fas fa-times mr-2"></i>Reject
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Status</label>
                            <p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $product['approval_status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ucfirst($product['approval_status']) ?>
                                </span>
                            </p>
                        </div>
                        <?php if (!empty($product['approval_notes'])): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Notes</label>
                                <p class="text-gray-900"><?= htmlspecialchars($product['approval_notes']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['approved_at'])): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Approved At</label>
                                <p class="text-gray-900"><?= date('M j, Y g:i A', strtotime($product['approved_at'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(dirname(__FILE__))) . '/layouts/admin.php'; ?>

