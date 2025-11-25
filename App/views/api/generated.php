<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <svg class="w-8 h-8 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h1 class="text-2xl font-bold text-green-800">API Key Generated Successfully!</h1>
            </div>
            <p class="text-green-700 mb-4">Your API key has been generated and is ready to use. Please save it securely as it won't be shown again.</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">API Key Details</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" 
                               value="<?= htmlspecialchars($apiKey['key']) ?>" 
                               readonly 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm"
                               id="apiKeyInput">
                        <button onclick="copyApiKey()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Copy
                        </button>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <p class="text-gray-900"><?= htmlspecialchars($apiKey['name']) ?></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($apiKey['permissions'] as $permission): ?>
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                <?= htmlspecialchars($permission) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Created At</label>
                    <p class="text-gray-900"><?= date('F j, Y \a\t g:i A', strtotime($apiKey['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-3">How to Use Your API Key</h3>
            <div class="space-y-3 text-sm text-blue-800">
                <p><strong>1. Include in Headers:</strong></p>
                <code class="block bg-blue-100 p-2 rounded text-xs">X-API-Key: <?= htmlspecialchars($apiKey['key']) ?></code>
                
                <p><strong>2. Or as Bearer Token:</strong></p>
                <code class="block bg-blue-100 p-2 rounded text-xs">Authorization: Bearer <?= htmlspecialchars($apiKey['key']) ?></code>
                
                <p><strong>3. Or as Query Parameter:</strong></p>
                <code class="block bg-blue-100 p-2 rounded text-xs">?api_key=<?= htmlspecialchars($apiKey['key']) ?></code>
            </div>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-yellow-900 mb-3">⚠️ Important Security Notes</h3>
            <ul class="space-y-2 text-sm text-yellow-800">
                <li>• Keep your API key secure and never share it publicly</li>
                <li>• This key has full access to your account</li>
                <li>• You can deactivate it anytime from your account settings</li>
                <li>• If compromised, deactivate it immediately and generate a new one</li>
            </ul>
        </div>

        <div class="flex space-x-4">
            <a href="<?= \App\Core\View::url('api/manage') ?>" 
               class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                Manage API Keys
            </a>
            <a href="<?= \App\Core\View::url('api/docs') ?>" 
               class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                View API Documentation
            </a>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const apiKeyInput = document.getElementById('apiKeyInput');
    apiKeyInput.select();
    apiKeyInput.setSelectionRange(0, 99999); // For mobile devices
    
    try {
        document.execCommand('copy');
        
        // Show success message
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600', 'hover:bg-green-700');
        button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600', 'hover:bg-green-700');
            button.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }, 2000);
    } catch (err) {
        alert('Failed to copy API key. Please copy it manually.');
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__FILE__) . '/../layouts/main.php'; ?>























