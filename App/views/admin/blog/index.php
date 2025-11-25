<?php
$title = 'Manage Blog Posts';
$posts = $data['posts'];
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Manage Blog Posts</h1>
                        <p class="text-gray-600 mt-1">Create, edit, and manage your blog content</p>
                    </div>
                    <a href="<?= \App\Core\View::url('admin/blog/create') ?>" 
                       class="btn-normal">
                        <i class="fas fa-plus mr-2"></i>New Post
                    </a>
                </div>
            </div>

            <div class="p-6">
                <?php if (empty($posts)): ?>
                <div class="text-center py-12">
                    <div class="text-gray-500 mb-4">
                        <i class="fas fa-newspaper text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 mb-2">No blog posts yet</h3>
                    <p class="text-gray-600 mb-4">Get started by creating your first blog post.</p>
                    <a href="<?= \App\Core\View::url('admin/blog/create') ?>" 
                       class="btn-normal">
                        Create First Post
                    </a>
                </div>
                <?php else: ?>
                <form method="POST" action="<?= \App\Core\View::url('admin/blog/bulkDelete') ?>" id="bulkDeleteForm">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-gray-600">
                            <span id="selectedCount">0</span> selected
                        </div>
                        <button type="submit" id="bulkDeleteBtn"
                                class="btn-delete"
                                disabled
                                onclick="return confirm('Delete selected posts? This action cannot be undone.')">
                            <i class="fas fa-trash mr-2"></i>Delete Selected
                        </button>
                    </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">
                                    <input type="checkbox" id="selectAll" class="form-checkbox h-4 w-4 text-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Post
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Author
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Views
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($posts as $post): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="ids[]" value="<?= (int)$post['id'] ?>" class="rowCheckbox form-checkbox h-4 w-4 text-red-600">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-start space-x-3">
                                        <?php if ($post['featured_image']): ?>
                                        <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                             alt="<?= htmlspecialchars($post['title']) ?>"
                                             class="w-12 h-12 object-cover rounded-lg">
                                        <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-500 truncate">
                                                <?= htmlspecialchars($post['excerpt'] ?? '') ?>
                                            </p>
                                            <?php if (isset($post['is_featured']) && $post['is_featured']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 mt-1">
                                                <i class="fas fa-star mr-1"></i>Featured
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if (!empty($post['category_name'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-sm">No category</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($post['full_author_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'published' => 'bg-green-100 text-green-800',
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'scheduled' => 'bg-blue-100 text-blue-800'
                                    ];
                                    $statusColor = $statusColors[$post['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-eye text-gray-400 mr-1"></i>
                                        <?= number_format($post['views_count'] ?? 0) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($post['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <?php if (isset($post['status']) && $post['status'] === 'published'): ?>
                                        <a href="<?= \App\Core\View::url('blog/view/' . $post['slug']) ?>" 
                                           target="_blank"
                                           class="text-blue-600 hover:text-blue-900" title="View Post">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?= \App\Core\View::url('admin/blog/edit/' . $post['id']) ?>" 
                                           class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="<?= \App\Core\View::url('admin/blog/delete/' . $post['id']) ?>" 
                                              class="inline" onsubmit="return confirm('Are you sure you want to delete this post?')">
                                            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Bulk selection handling
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.rowCheckbox');
const bulkBtn = document.getElementById('bulkDeleteBtn');
const countEl = document.getElementById('selectedCount');

function updateSelectionState() {
    const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
    countEl.textContent = selected;
    bulkBtn.disabled = selected === 0;
}

if (selectAll) {
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
        updateSelectionState();
    });
}

checkboxes.forEach(cb => cb.addEventListener('change', updateSelectionState));

// Prevent accidental submission without selection
document.getElementById('bulkDeleteForm')?.addEventListener('submit', function(e) {
    const anySelected = Array.from(checkboxes).some(cb => cb.checked);
    if (!anySelected) {
        e.preventDefault();
    }
});
</script>

<?php
$content = ob_get_clean();
include dirname(dirname(__FILE__)) . '/layouts/admin.php';
?>
