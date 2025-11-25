<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Edit Slider</h1>
        <a href="<?= \App\Core\View::url('admin/slider') ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Sliders
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <div class="flex items-center mb-2">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <strong>Please fix the following errors:</strong>
            </div>
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Title (Optional)
                </label>
                <input type="text" 
                       name="title" 
                       id="title" 
                       value="<?= htmlspecialchars($data['title'] ?? $slider['title'] ?? '') ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Limited time offer">
                <p class="text-xs text-gray-500 mt-1">Optional - Small text above subtitle</p>
            </div>

            <div>
                <label for="subtitle" class="block text-sm font-medium text-gray-700 mb-2">Subtitle (Optional)</label>
                <input type="text" 
                       name="subtitle" 
                       id="subtitle" 
                       value="<?= htmlspecialchars($data['subtitle'] ?? $slider['subtitle'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="20% Off All Proteins">
                <p class="text-xs text-gray-500 mt-1">Main headline text</p>
            </div>

            <div class="md:col-span-2">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                <textarea name="description" 
                          id="description" 
                          rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Clean nutrition, same day dispatch."><?= htmlspecialchars($data['description'] ?? $slider['description'] ?? '') ?></textarea>
                <p class="text-xs text-gray-500 mt-1">Supporting text below subtitle</p>
            </div>

            <div>
                <label for="button_text" class="block text-sm font-medium text-gray-700 mb-2">Button Text (Optional)</label>
                <input type="text" 
                       name="button_text" 
                       id="button_text" 
                       value="<?= htmlspecialchars($data['button_text'] ?? $slider['button_text'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Shop now">
                <p class="text-xs text-gray-500 mt-1">Button will only show if both button text and link URL are provided</p>
            </div>

            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700 mb-2">Link URL (Optional)</label>
                <input type="url" 
                       name="link_url" 
                       id="link_url" 
                       value="<?= htmlspecialchars($data['link_url'] ?? $slider['link_url'] ?? '') ?>"
                       placeholder="https://example.com"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Button will only show if both button text and link URL are provided</p>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" 
                       name="sort_order" 
                       id="sort_order" 
                       value="<?= htmlspecialchars($data['sort_order'] ?? $slider['sort_order']) ?>" 
                       min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
            </div>

            <div class="flex items-center">
                <input type="checkbox" 
                       name="is_active" 
                       id="is_active" 
                       value="1" 
                       <?= (isset($data['is_active']) ? $data['is_active'] : $slider['is_active']) ? 'checked' : '' ?>
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label for="is_active" class="ml-2 block text-sm text-gray-700">Active</label>
            </div>

            <div class="md:col-span-2">
                <label for="image_url" class="block text-sm font-medium text-gray-700 mb-2">
                    Image URL <span class="text-red-500">*</span>
                </label>
                <input type="url" 
                       name="image_url" 
                       id="image_url" 
                       value="<?= htmlspecialchars($data['image_url'] ?? $slider['image_url']) ?>" 
                       required
                       placeholder="https://example.com/image.jpg"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Enter the full URL of the slider image (recommended size: 1920x600px)</p>
                
                <!-- Current Image Preview -->
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Current Image:</p>
                    <img id="current-img" 
                         src="<?= htmlspecialchars($slider['image_url']) ?>" 
                         alt="Current slider image" 
                         class="max-w-xs h-32 object-cover border rounded"
                         onerror="this.src='/placeholder.svg?height=128&width=256'">
                </div>
                
                <!-- New Image Preview -->
                <div id="image-preview" class="mt-4 hidden">
                    <p class="text-sm font-medium text-gray-700 mb-2">New Image Preview:</p>
                    <img id="preview-img" 
                         src="<?= ASSETS_URL ?>/placeholder.svg" 
                         alt="Image Preview" 
                         class="max-w-xs h-32 object-cover border rounded">
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-between">
            <a href="<?= \App\Core\View::url('admin/slider') ?>" 
               class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Cancel
            </a>
            <button type="submit" 
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Update Slider
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