<?php
$title = 'Edit Blog Post';
$post = $data['post'];
$categories = $data['categories'];
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit Blog Post</h1>
                        <p class="text-gray-600 mt-1">Update your blog post content and settings</p>
                    </div>
                    <a href="<?= \App\Core\View::url('admin/blog') ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Back to Posts
                    </a>
                </div>
            </div>

            <form action="<?= \App\Core\View::url('admin/blog/edit/' . $post['id']) ?>" method="POST" class="p-6">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Post Content</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                                    <input type="text" id="title" name="title" 
                                           value="<?= htmlspecialchars($post['title']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                                    <input type="text" id="slug" name="slug" 
                                           value="<?= htmlspecialchars($post['slug']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-gray-500 text-sm mt-1">Leave empty to auto-generate from title</p>
                                </div>

                                <div>
                                    <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">Excerpt</label>
                                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="500"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($post['excerpt']) ?></textarea>
                                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                                        <span>Brief description of the article</span>
                                        <span id="excerpt-counter">0/500</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                                    <textarea id="content" name="content" rows="15"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                              required><?= htmlspecialchars($post['content']) ?></textarea>
                                </div>

                                <div>
                                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Featured Image URL</label>
                                    <input type="url" id="featured_image" name="featured_image" 
                                           value="<?= htmlspecialchars($post['featured_image']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- SEO Settings -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Settings</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Title</label>
                                    <input type="text" id="meta_title" name="meta_title" maxlength="60"
                                           value="<?= htmlspecialchars($post['meta_title']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                                        <span>SEO optimized title</span>
                                        <span id="meta-title-counter">0/60</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
                                    <textarea id="meta_description" name="meta_description" rows="3" maxlength="160"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($post['meta_description']) ?></textarea>
                                    <div class="flex justify-between text-sm text-gray-500 mt-1">
                                        <span>Description for search results</span>
                                        <span id="meta-desc-counter">0/160</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">Meta Keywords</label>
                                        <input type="text" id="meta_keywords" name="meta_keywords" 
                                               value="<?= htmlspecialchars($post['meta_keywords']) ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <p class="text-gray-500 text-sm mt-1">Separate with commas</p>
                                    </div>

                                    <div>
                                        <label for="focus_keyword" class="block text-sm font-medium text-gray-700 mb-2">Focus Keyword</label>
                                        <input type="text" id="focus_keyword" name="focus_keyword" 
                                               value="<?= htmlspecialchars($post['focus_keyword']) ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="og_title" class="block text-sm font-medium text-gray-700 mb-2">Open Graph Title</label>
                                        <input type="text" id="og_title" name="og_title" 
                                               value="<?= htmlspecialchars($post['og_title']) ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>

                                    <div>
                                        <label for="og_image" class="block text-sm font-medium text-gray-700 mb-2">Open Graph Image URL</label>
                                        <input type="url" id="og_image" name="og_image" 
                                               value="<?= htmlspecialchars($post['og_image']) ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label for="og_description" class="block text-sm font-medium text-gray-700 mb-2">Open Graph Description</label>
                                    <textarea id="og_description" name="og_description" rows="2"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($post['og_description']) ?></textarea>
                                </div>

                                <div>
                                    <label for="canonical_url" class="block text-sm font-medium text-gray-700 mb-2">Canonical URL</label>
                                    <input type="url" id="canonical_url" name="canonical_url" 
                                           value="<?= htmlspecialchars($post['canonical_url']) ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Publish Settings -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Publish Settings</h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select id="status" name="status" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                        <option value="scheduled" <?= $post['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                    <select id="category_id" name="category_id" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" <?= $post['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                           <?= $post['is_featured'] ? 'checked' : '' ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Featured Post</label>
                                </div>
                            </div>
                        </div>

                        <!-- Post Statistics -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Post Statistics</h3>
                            
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div class="bg-white p-3 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600"><?= number_format($post['views_count']) ?></div>
                                    <div class="text-sm text-gray-600">Views</div>
                                </div>
                                <div class="bg-white p-3 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600"><?= $post['reading_time'] ?></div>
                                    <div class="text-sm text-gray-600">Min Read</div>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-sm text-gray-600">
                                <div>Created: <?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></div>
                                <div>Updated: <?= date('M j, Y g:i A', strtotime($post['updated_at'])) ?></div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="space-y-3">
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Update Post
                            </button>
                            <a href="<?= \App\Core\View::url('admin/blog') ?>" 
                               class="block w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-center">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counters
    function updateCharCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        
        function updateCount() {
            const length = input.value.length;
            counter.textContent = `${length}/${maxLength}`;
            
            if (length > maxLength * 0.8) {
                counter.classList.add('text-yellow-600');
                counter.classList.remove('text-gray-500', 'text-red-600');
            } else if (length > maxLength) {
                counter.classList.add('text-red-600');
                counter.classList.remove('text-gray-500', 'text-yellow-600');
            } else {
                counter.classList.add('text-gray-500');
                counter.classList.remove('text-yellow-600', 'text-red-600');
            }
        }
        
        input.addEventListener('input', updateCount);
        updateCount(); // Initial count
    }

    updateCharCounter('excerpt', 'excerpt-counter', 500);
    updateCharCounter('meta_title', 'meta-title-counter', 60);
    updateCharCounter('meta_description', 'meta-desc-counter', 160);

    // Auto-generate slug from title
    document.getElementById('title').addEventListener('input', function() {
        const slugField = document.getElementById('slug');
        if (slugField.value === '') {
            const slug = this.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugField.value = slug;
        }
    });
});
</script>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/admin.php';
?>
