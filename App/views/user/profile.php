<?php ob_start(); ?>
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-6 max-w-4xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">My Profile</h1>
        
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-error/10 border border-error text-error px-4 py-3 rounded relative mb-6" role="alert">
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
            </div>
            
            <form action="<?= \App\Core\View::url('user/updateProfile') ?>" method="post" class="p-6" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <!-- Profile Image Section -->
                <div class="mb-8 border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Image</h3>
                    <div class="flex items-center space-x-6">
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <div class="<?= isset($user['sponsor_status']) && $user['sponsor_status'] === 'active' ? 'border-4 border-accent' : 'border-4 border-gray-200' ?> rounded-full overflow-hidden">
                                    <img id="profileImagePreview" 
                                         src="<?= $user['profile_image'] ? ASSETS_URL . '/profileimage/' . htmlspecialchars($user['profile_image']) : ASSETS_URL . '/images/default-avatar.png' ?>" 
                                         alt="Profile Image" 
                                         class="w-24 h-24 object-cover"
                                         onerror="this.src='<?= ASSETS_URL ?>/images/default-avatar.png'">
                                </div>
                                <?php if (isset($user['sponsor_status']) && $user['sponsor_status'] === 'active'): ?>
                                    <div class="absolute -bottom-1 -right-1 w-8 h-8 bg-white rounded-full p-1 shadow-lg">
                                        <img src="<?= ASSETS_URL ?>/images/icons/vip.png" 
                                             alt="VIP" 
                                             class="w-full h-full object-contain"
                                             onerror="this.style.display='none';">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex-1">
                            <label for="profile_image" class="block text-sm font-medium text-gray-700 mb-2">Upload New Image</label>
                            <input type="file" 
                                   name="profile_image" 
                                   id="profile_image" 
                                   accept="image/*" 
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-info/10 file:text-info hover:file:bg-info/20">
                            <p class="mt-1 text-xs text-gray-500">JPG, PNG or WebP. Max 5MB. Will be automatically resized to 300x300.</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" id="first_name" value="<?= $user['first_name'] ?? '' ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" id="last_name" value="<?= $user['last_name'] ?? '' ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" id="email" value="<?= $user['email'] ?? '' ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" name="phone" id="phone" value="<?= $user['phone'] ?? '' ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                </div>
                
                <div class="mt-8 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                    <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                            <input type="password" name="current_password" id="current_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div></div>
                        
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <input type="password" name="new_password" id="new_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        
      
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileImageInput = document.getElementById('profile_image');
    const profileImagePreview = document.getElementById('profileImagePreview');
    
    if (profileImageInput && profileImagePreview) {
        profileImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file.');
                    this.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB.');
                    this.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // API Key Management
    const generateApiKeyForm = document.getElementById('generateApiKeyForm');
    if (generateApiKeyForm) {
        generateApiKeyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            generateApiKey();
        });
    }
    
    // Load API keys and stats on page load
    loadApiKeys();
    loadApiStats();
});

// Generate new API key
async function generateApiKey() {
    const form = document.getElementById('generateApiKeyForm');
    const formData = new FormData(form);
    
    const permissions = [];
    formData.getAll('permissions[]').forEach(perm => permissions.push(perm));
    
    const keyData = {
        name: formData.get('keyName'),
        abilities: permissions,
        expires_at: formData.get('keyExpiry') || null
    };
    
    try {
        const response = await fetch('/api/keys/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(keyData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show the generated API key
            showApiKeyModal(result.data);
            
            // Reload the API keys list
            loadApiKeys();
            loadApiStats();
            
            // Reset the form
            form.reset();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        console.error('Error generating API key:', error);
        alert('Error generating API key. Please try again.');
    }
}

// Show API key modal
function showApiKeyModal(keyData) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">🔑 API Key Generated Successfully!</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key:</label>
                <div class="flex">
                    <input type="text" value="${keyData.api_key}" readonly 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-l-none bg-gray-50 text-sm font-mono">
                    <button onclick="copyToClipboard('${keyData.api_key}')" 
                            class="px-3 py-2 bg-info text-white rounded-r-none hover:bg-info-dark">
                        Copy
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">⚠️ Copy this key now! It won't be shown again.</p>
            </div>
            <div class="mb-4">
                <p><strong>Name:</strong> ${keyData.name}</p>
                <p><strong>Permissions:</strong> ${keyData.abilities.join(', ')}</p>
                ${keyData.expires_at ? `<p><strong>Expires:</strong> ${new Date(keyData.expires_at).toLocaleDateString()}</p>` : ''}
            </div>
            <button onclick="this.closest('.fixed').remove()" 
                    class="w-full px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">
                Close
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show temporary success message
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-success');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-success');
        }, 2000);
    });
}

// Load API keys
async function loadApiKeys() {
    try {
        const response = await fetch('/api/keys/list', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayApiKeys(result.data.api_keys);
        } else {
            console.error('Error loading API keys:', result.error);
        }
    } catch (error) {
        console.error('Error loading API keys:', error);
    }
}

// Display API keys
function displayApiKeys(keys) {
    const container = document.getElementById('apiKeysList');
    
    if (keys.length === 0) {
        container.innerHTML = `
            <div class="text-center text-gray-500 py-8">
                <div class="text-4xl mb-2">🔑</div>
                <p>No API keys found</p>
                <p class="text-sm">Generate your first API key above</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = keys.map(key => `
        <div class="border border-gray-200 rounded-lg p-4">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h4 class="font-medium text-gray-900">${key.name}</h4>
                    <p class="text-sm text-gray-600">Created: ${new Date(key.created_at).toLocaleDateString()}</p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="editApiKey(${key.id})" 
                            class="px-3 py-1 text-sm bg-info text-white rounded hover:bg-info-dark">
                        Edit
                    </button>
                    <button onclick="revokeApiKey(${key.id})" 
                            class="px-3 py-1 text-sm bg-error text-white rounded hover:bg-error-dark">
                        Revoke
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium">Token:</span>
                    <span class="font-mono text-gray-600">${key.token_preview}</span>
                </div>
                <div>
                    <span class="font-medium">Permissions:</span>
                    <span class="text-gray-600">${key.abilities.join(', ')}</span>
                </div>
                <div>
                    <span class="font-medium">Last Used:</span>
                    <span class="text-gray-600">${key.last_used_at ? new Date(key.last_used_at).toLocaleDateString() : 'Never'}</span>
                </div>
                <div>
                    <span class="font-medium">Expires:</span>
                    <span class="text-gray-600">${key.expires_at ? new Date(key.expires_at).toLocaleDateString() : 'Never'}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Load API stats
async function loadApiStats() {
    try {
        const response = await fetch('/api/keys/stats', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('totalCalls').textContent = result.data.total_api_calls || 0;
            document.getElementById('activeKeys').textContent = result.data.api_keys?.length || 0;
            
            // Calculate this month's calls
            const thisMonth = result.data.recent_activity?.filter(activity => {
                const activityDate = new Date(activity.created_at);
                const now = new Date();
                return activityDate.getMonth() === now.getMonth() && 
                       activityDate.getFullYear() === now.getFullYear();
            }).length || 0;
            
            document.getElementById('thisMonth').textContent = thisMonth;
        }
    } catch (error) {
        console.error('Error loading API stats:', error);
    }
}

// Edit API key
async function editApiKey(keyId) {
    // This would open an edit modal
    // For now, just show a simple prompt
    const newName = prompt('Enter new name for the API key:');
    if (newName) {
        try {
            const formData = new FormData();
            formData.append('_method', 'PUT');
            formData.append('key_id', keyId);
            formData.append('name', newName);
            
            const response = await fetch('/api/keys/update', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadApiKeys();
                alert('API key updated successfully!');
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error updating API key:', error);
            alert('Error updating API key. Please try again.');
        }
    }
}

// Revoke API key
async function revokeApiKey(keyId) {
    if (confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
        try {
            const formData = new FormData();
            formData.append('_method', 'DELETE');
            formData.append('key_id', keyId);
            
            const response = await fetch('/api/keys/revoke', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                loadApiKeys();
                loadApiStats();
                alert('API key revoked successfully!');
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error revoking API key:', error);
            alert('Error revoking API key. Please try again.');
        }
    }
}
</script>

<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>











