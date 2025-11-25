<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">API Key Management</h1>
            <button onclick="generateNewKey()" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Generate New Key
            </button>
        </div>

        <?php if (empty($apiKeys)): ?>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">No API Keys Found</h3>
                <p class="text-gray-600 mb-4">You haven't generated any API keys yet. Generate one to start using our API.</p>
                <button onclick="generateNewKey()" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Generate Your First API Key
                </button>
            </div>
        <?php else: ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($apiKeys as $key): ?>
                        <li class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($key['name']) ?></h3>
                                        <?php if ($key['is_active']): ?>
                                            <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
                                        <?php else: ?>
                                            <span class="ml-2 px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Inactive</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-600">
                                            <strong>Permissions:</strong> 
                                            <?php 
                                            $permissions = json_decode($key['permissions'], true);
                                            echo implode(', ', $permissions);
                                            ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <strong>Created:</strong> <?= date('F j, Y \a\t g:i A', strtotime($key['created_at'])) ?>
                                        </p>
                                        <?php if ($key['last_used']): ?>
                                            <p class="text-sm text-gray-600">
                                                <strong>Last Used:</strong> <?= date('F j, Y \a\t g:i A', strtotime($key['last_used'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <?php if ($key['is_active']): ?>
                                        <button onclick="deactivateKey(<?= $key['id'] ?>)" 
                                                class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                                            Deactivate
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">API Documentation</h3>
            <p class="text-blue-800 mb-4">Learn how to use our API with comprehensive documentation and examples.</p>
            <a href="<?= \App\Core\View::url('api/docs') ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                View API Documentation
            </a>
        </div>
    </div>
</div>

<!-- Generate New Key Modal -->
<div id="generateKeyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Generate New API Key</h3>
            
            <form id="generateKeyForm">
                <div class="mb-4">
                    <label for="keyName" class="block text-sm font-medium text-gray-700 mb-2">Key Name</label>
                    <input type="text" 
                           id="keyName" 
                           name="name" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="e.g., Mobile App Key"
                           required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="read" checked class="mr-2">
                            <span class="text-sm text-gray-700">Read (View products, orders, etc.)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="permissions[]" value="write" checked class="mr-2">
                            <span class="text-sm text-gray-700">Write (Add to cart, place orders, etc.)</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" 
                            onclick="closeGenerateModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function generateNewKey() {
    document.getElementById('generateKeyModal').classList.remove('hidden');
}

function closeGenerateModal() {
    document.getElementById('generateKeyModal').classList.add('hidden');
}

function deactivateKey(keyId) {
    if (confirm('Are you sure you want to deactivate this API key? This will immediately revoke access for any applications using this key.')) {
        fetch('<?= ASSETS_URL ?>/api/deactivate/' + keyId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to deactivate API key: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deactivating the API key');
        });
    }
}

document.getElementById('generateKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const permissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked')).map(cb => cb.value);
    
    const data = {
        name: formData.get('name'),
        permissions: permissions
    };
    
    fetch('<?= ASSETS_URL ?>/api/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to generated key page
            window.location.href = '<?= \App\Core\View::url('api/generated') ?>?key=' + encodeURIComponent(data.data.key);
        } else {
            alert('Failed to generate API key: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating the API key');
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__FILE__) . '/../layouts/main.php'; ?>















