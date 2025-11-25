<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <!-- Back to Admin Dashboard -->
    <div class="mb-6">
        <a href="<?= \App\Core\View::url('admin/dashboard') ?>" class="btn">
            <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
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

    <!-- SMS Management Dashboard -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manage SMS Templates</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Total Templates: <?= htmlspecialchars($totalTemplates ?? 0) ?>
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                    <a href="<?= \App\Core\View::url('admin/sms/logs') ?>" class="btn">
                        <i class="fas fa-history mr-2"></i> View SMS Logs
                    </a>
                    <button onclick="openCreateModal()" class="btn">
                        <i class="fas fa-plus mr-2"></i> Create Template
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <form method="GET" action="<?= \App\Core\View::url('admin/sms') ?>" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $selectedCategory === $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1">
                    <label for="is_active" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="is_active" id="is_active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $isActive === true ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $isActive === false ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Templates Table -->
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($templates)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No templates found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($templates as $template): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?= htmlspecialchars($template['name'] ?? 'N/A') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= htmlspecialchars($categories[$template['category']] ?? $template['category']) ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?= htmlspecialchars(substr($template['content'] ?? '', 0, 50)) . (strlen($template['content'] ?? '') > 50 ? '...' : '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium <?= $template['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $template['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?= htmlspecialchars($template['priority'] ?? 1) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openSendModal(<?= $template['id'] ?>, '<?= htmlspecialchars($template['name'], ENT_QUOTES) ?>')" class="text-blue-600 hover:text-blue-800 mr-2">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                        <a href="<?= \App\Core\View::url('admin/sms/update/') . $template['id'] ?>" class="text-indigo-600 hover:text-indigo-800 mr-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="duplicateTemplate(<?= $template['id'] ?>)" class="text-green-600 hover:text-green-800 mr-2">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button onclick="toggleTemplate(<?= $template['id'] ?>)" class="text-yellow-600 hover:text-yellow-800 mr-2">
                                            <i class="fas fa-toggle-<?= $template['is_active'] ? 'on' : 'off' ?>"></i>
                                        </button>
                                        <button onclick="deleteTemplate(<?= $template['id'] ?>)" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-700">
                        Showing <?= ($offset ?? 0) + 1 ?> to <?= min(($offset ?? 0) + count($templates), $totalTemplates) ?> of <?= $totalTemplates ?> templates
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= \App\Core\View::url('admin/sms?page=' . ($currentPage - 1) . ($selectedCategory ? '&category=' . urlencode($selectedCategory) : '') . ($isActive !== null ? '&is_active=' . ($isActive ? '1' : '0') : '')) ?>" 
                               class="btn">
                                Previous
                            </a>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= \App\Core\View::url('admin/sms?page=' . ($currentPage + 1) . ($selectedCategory ? '&category=' . urlencode($selectedCategory) : '') . ($isActive !== null ? '&is_active=' . ($isActive ? '1' : '0') : '')) ?>" 
                               class="btn">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Template Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
            <h2 class="text-xl font-bold mb-4">Create SMS Template</h2>
            <form id="createForm" method="POST" action="<?= \App\Core\View::url('admin/sms/create') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700">Template Name</label>
                    <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                    <?php if (isset($errors['name'])): ?>
                        <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['name']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category'])): ?>
                        <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['category']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label for="content" class="block text-sm font-medium text-gray-700">Content (max 160 characters)</label>
                    <textarea name="content" id="content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required></textarea>
                    <p class="text-sm text-gray-500 mt-1">Character count: <span id="charCount">0</span>/160</p>
                    <?php if (isset($errors['content'])): ?>
                        <p class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['content']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
                    <input type="number" name="priority" id="priority" value="1" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCreateModal()" class="btn">Cancel</button>
                    <button type="submit" class="btn">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Send SMS Modal -->
    <div id="sendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
            <h2 class="text-xl font-bold mb-4">Send SMS</h2>
            <form id="sendForm" method="POST" action="<?= \App\Core\View::url('admin/sms/send') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                <input type="hidden" name="template_id" id="sendTemplateId">
                <div class="mb-4">
                    <label for="user_selection" class="block text-sm font-medium text-gray-700">Send To</label>
                    <select name="user_selection" id="user_selection" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" onchange="toggleUserInput()">
                        <option value="all">All Users</option>
                        <option value="single">Single User</option>
                    </select>
                </div>
                <div class="mb-4" id="user_select_container" style="display: none;">
                    <label for="user_select" class="block text-sm font-medium text-gray-700">Select User</label>
                    <select name="user_id" id="user_select" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>">
                                <?= htmlspecialchars($user['name'] ?? $user['email'] ?? $user['id']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                <div class="mb-4">
                    <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="message" id="message" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"></textarea>
                    <p class="text-sm text-gray-500 mt-1">Character count: <span id="sendCharCount">0</span>/160</p>
                </div>
                <div id="variablesContainer" class="mb-4 hidden">
                    <label class="block text-sm font-medium text-gray-700">Template Variables</label>
                    <div id="variablesFields"></div>
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="auto_send" id="auto_send" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" onchange="toggleAutoSend()">
                        <span class="ml-2 text-sm text-gray-700">Enable Auto Send (Every 28 Days)</span>
                    </label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSendModal()" class="btn">Cancel</button>
                    <button type="submit" class="btn">Send</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Duplicate Template Modal -->
    <div id="duplicateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
            <h2 class="text-xl font-bold mb-4">Duplicate Template</h2>
            <form id="duplicateForm" method="POST" action="">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                <input type="hidden" name="id" id="duplicateTemplateId">
                <div class="mb-4">
                    <label for="new_name" class="block text-sm font-medium text-gray-700">New Template Name</label>
                    <input type="text" name="new_name" id="new_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDuplicateModal()" class="btn">Cancel</button>
                    <button type="submit" class="btn">Duplicate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character count for create template
    const contentTextarea = document.getElementById('content');
    const charCountSpan = document.getElementById('charCount');
    if (contentTextarea && charCountSpan) {
        contentTextarea.addEventListener('input', function() {
            charCountSpan.textContent = contentTextarea.value.length;
            if (contentTextarea.value.length > 160) {
                charCountSpan.classList.add('text-red-600');
            } else {
                charCountSpan.classList.remove('text-red-600');
            }
        });
    }

    // Character count for send SMS
    const sendMessageTextarea = document.getElementById('message');
    const sendCharCountSpan = document.getElementById('sendCharCount');
    if (sendMessageTextarea && sendCharCountSpan) {
        sendMessageTextarea.addEventListener('input', function() {
            sendCharCountSpan.textContent = sendMessageTextarea.value.length;
            if (sendMessageTextarea.value.length > 160) {
                sendCharCountSpan.classList.add('text-red-600');
            } else {
                sendCharCountSpan.classList.remove('text-red-600');
            }
        });
    }
});

function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createForm').reset();
    document.getElementById('charCount').textContent = '0';
}

function openSendModal(templateId, templateName) {
    const modal = document.getElementById('sendModal');
    const form = document.getElementById('sendForm');
    const templateIdInput = document.getElementById('sendTemplateId');
    const messageTextarea = document.getElementById('message');
    const variablesContainer = document.getElementById('variablesContainer');
    const variablesFields = document.getElementById('variablesFields');

    templateIdInput.value = templateId;

    // Fetch template variables via AJAX
    fetch('<?= ASSETS_URL ?>/admin/sms/variables/' + templateId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        variablesFields.innerHTML = '';
        if (data.variables && data.variables.length > 0) {
            variablesContainer.classList.remove('hidden');
            data.variables.forEach(variable => {
                const div = document.createElement('div');
                div.className = 'mb-2';
                div.innerHTML = `
                    <label for="variable_${variable}" class="block text-sm font-medium text-gray-700">${variable}</label>
                    <input type="text" name="variables[${variable}]" id="variable_${variable}" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50" required>
                `;
                variablesFields.appendChild(div);
            });
        } else {
            variablesContainer.classList.add('hidden');
        }
    })
    .catch(error => {
        console.error('Error fetching variables:', error);
        variablesContainer.classList.add('hidden');
    });

    modal.classList.remove('hidden');
}

function closeSendModal() {
    document.getElementById('sendModal').classList.add('hidden');
    document.getElementById('sendForm').reset();
    document.getElementById('sendCharCount').textContent = '0';
    document.getElementById('variablesContainer').classList.add('hidden');
    document.getElementById('user_select_container').style.display = 'none';
    document.getElementById('auto_send').checked = false;
}

function deleteTemplate(id) {
    if (confirm('Are you sure you want to delete this template?')) {
        const button = event.target.closest('button');
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('<?= ASSETS_URL ?>/admin/sms/delete/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '_csrf=<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-trash"></i>';
            }
        })
        .catch(error => {
            alert('Error: Failed to delete template');
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-trash"></i>';
        });
    }
}

function toggleTemplate(id) {
    const button = event.target.closest('button');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('<?= ASSETS_URL ?>/admin/sms/toggle/' + id, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: '_csrf=<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-toggle-' + (button.dataset.active === '1' ? 'on' : 'off') + '"></i>';
        }
    })
    .catch(error => {
        alert('Error: Failed to toggle template status');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-toggle-' + (button.dataset.active === '1' ? 'on' : 'off') + '"></i>';
    });
}

function duplicateTemplate(id) {
    const modal = document.getElementById('duplicateModal');
    const form = document.getElementById('duplicateForm');
    const templateIdInput = document.getElementById('duplicateTemplateId');
    templateIdInput.value = id;
    form.action = '<?= \App\Core\View::url('admin/sms/duplicate/') ?>' + id;
    modal.classList.remove('hidden');
}

function closeDuplicateModal() {
    document.getElementById('duplicateModal').classList.add('hidden');
    document.getElementById('duplicateForm').reset();
}

function toggleUserInput() {
    const select = document.getElementById('user_selection');
    const userSelectContainer = document.getElementById('user_select_container');
    if (select.value === 'single') {
        userSelectContainer.style.display = 'block';
    } else {
        userSelectContainer.style.display = 'none';
    }
}

function toggleAutoSend() {
    const autoSendCheckbox = document.getElementById('auto_send');
    if (autoSendCheckbox.checked) {
        if (confirm('Enabling auto-send will trigger SMS every 28 days for orders. Proceed?')) {
            console.log('Auto-send enabled for template with ID: ' + document.getElementById('sendTemplateId').value);
        } else {
            autoSendCheckbox.checked = false;
        }
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>