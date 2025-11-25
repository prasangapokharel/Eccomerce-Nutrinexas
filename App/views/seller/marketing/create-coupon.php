<?php ob_start(); ?>
<?php $page = 'marketing'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Create Coupon</h1>
        <a href="<?= \App\Core\View::url('seller/marketing') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back to Marketing
        </a>
    </div>

    <div class="card">
        <form action="<?= \App\Core\View::url('seller/marketing/create-coupon') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="grid grid-cols-1 grid-cols-2">
                <div>
                    <label for="code">Coupon Code *</label>
                    <input type="text" id="code" name="code" required placeholder="SAVE10" oninput="this.value = this.value.toUpperCase()">
                </div>

                <div>
                    <label for="discount_type">Discount Type *</label>
                    <select id="discount_type" name="discount_type" required>
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed Amount</option>
                    </select>
                </div>

                <div>
                    <label for="discount_value">Discount Value *</label>
                    <input type="number" id="discount_value" name="discount_value" step="0.01" required>
                </div>

                <div>
                    <label for="min_purchase">Minimum Purchase (रु)</label>
                    <input type="number" id="min_purchase" name="min_purchase" step="0.01">
                </div>

                <div>
                    <label for="max_discount">Max Discount (रु)</label>
                    <input type="number" id="max_discount" name="max_discount" step="0.01">
                </div>

                <div>
                    <label for="usage_limit">Usage Limit</label>
                    <input type="number" id="usage_limit" name="usage_limit" min="1" placeholder="Leave empty for unlimited">
                </div>

                <div>
                    <label for="valid_from">Valid From</label>
                    <input type="date" id="valid_from" name="valid_from" value="<?= date('Y-m-d') ?>">
                </div>

                <div>
                    <label for="valid_until">Valid Until</label>
                    <input type="date" id="valid_until" name="valid_until">
                </div>

                <div>
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 mt-6">
                <a href="<?= \App\Core\View::url('seller/marketing') ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Create Coupon
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>
