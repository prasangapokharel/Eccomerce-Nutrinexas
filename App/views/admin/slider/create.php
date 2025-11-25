<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Create Slider</h1>
        <a href="<?= \App\Core\View::url('admin/slider') ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Back to Sliders
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title (Optional)</label>
                <input type="text" name="title" id="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Limited time offer">
                <p class="text-xs text-gray-500 mt-1">Optional - Small text above subtitle</p>
            </div>

            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">Subtitle (Optional)</label>
                <input type="text" name="subtitle" id="subtitle" value="<?= htmlspecialchars($data['subtitle'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="20% Off All Proteins">
                <p class="text-xs text-gray-500 mt-1">Main headline text</p>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Clean nutrition, same day dispatch."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Supporting text below subtitle</p>
            </div>

            <div>
                <label for="button_text" class="block text-sm font-medium text-gray-700 mb-2">Button Text (Optional)</label>
                <input type="text" name="button_text" id="button_text" value="<?= htmlspecialchars($data['button_text'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="Shop now">
                <p class="text-xs text-gray-500 mt-1">Button will only show if both button text and link URL are provided</p>
            </div>

            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700 mb-2">Link URL (Optional)</label>
                <input type="url" name="link_url" id="link_url" value="<?= htmlspecialchars($data['link_url'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="https://example.com/products">
                <p class="text-xs text-gray-500 mt-1">Button will only show if both button text and link URL are provided</p>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" name="sort_order" id="sort_order" value="<?= htmlspecialchars($data['sort_order'] ?? 0) ?>" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= isset($data['is_active']) && $data['is_active'] ? 'checked' : '' ?>
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
            </div>

            <div class="md:col-span-2">
                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">Image URL *</label>
                <input type="url" name="image_url" id="image_url" value="<?= htmlspecialchars($data['image_url'] ?? '') ?>" required
                       placeholder="https://example.com/image.jpg"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <p class="text-xs text-gray-500 mt-1">Enter the full URL of the slider image (recommended size: 1920x600px)</p>
                
                <!-- Image Preview -->
                <div id="image-preview" class="mt-4 hidden">
                    <img id="preview-img" src="<?= ASSETS_URL ?>/placeholder.svg" alt="Image Preview" class="max-w-xs h-32 object-cover border rounded">
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-between">
            <a href="<?= \App\Core\View::url('admin/slider') ?>" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Create Slider
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('image_url').addEventListener('input', function() {
    const url = this.value;
    const preview = document.getElementById('image-preview');
    const img = document.getElementById('preview-img');
    
    if (url && url.match(/\.(jpeg|jpg|gif|png|webp)$/i)) {
        img.src = url;
        preview.classList.remove('hidden');
        
        img.onerror = function() {
            preview.classList.add('hidden');
        };
    } else {
        preview.classList.add('hidden');
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>