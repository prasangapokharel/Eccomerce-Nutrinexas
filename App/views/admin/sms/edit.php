<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Back to SMS Dashboard -->
    <div class="mb-6">
        <a href="<?= \App\Core\View::url('admin/sms') ?>" class="text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to SMS Dashboard
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_message']) ?></span>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_error']) ?></span>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Edit Template Form -->
    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-semibold mb-6">Edit SMS Template</h2>
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="<?= \App\Core\View::url('admin/sms/update/' . $template['id']) ?>" method="POST" id="editTemplateForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($data['_csrf'] ?? $_SESSION['_csrf']) ?>">
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                <input type="text" name="name" id="name" value="<?= htmlspecialchars($template['name'] ?? '') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                <select name="category" id="category" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($categories as $key => $value): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $template['category'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="content" class="block text-sm font-medium text-gray-700">Message Content</label>
                <textarea name="content" id="content" rows="5" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($template['content'] ?? '') ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Character count: <span id="charCount">0</span>/160</p>
            </div>
            <div class="mb-4">
                <label for="variables" class="block text-sm font-medium text-gray-700">Variables (comma-separated)</label>
                <input type="text" name="variables[]" id="variables" value="<?= htmlspecialchars(implode(',', $template['variables'] ?? [])) ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                <input type="number" name="priority" id="priority" value="<?= htmlspecialchars($template['priority'] ?? 1) ?>" min="1" max="10" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="mb-4">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="is_active" value="1" <?= $template['is_active'] ? 'checked' : '' ?> class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Update Template</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const contentTextarea = document.getElementById('content');
        const charCountSpan = document.getElementById('charCount');

        function updateCharCount() {
            const content = contentTextarea.value;
            charCountSpan.textContent = content.length;
            if (content.length > 160) {
                charCountSpan.classList.add('text-red-500');
            } else {
                charCountSpan.classList.remove('text-red-500');
            }
        }

        contentTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
    });
</script>

<?php
$content = ob_get_clean();
require_once dirname(__DIR__, 2) . '/layouts/admin.php';
?>