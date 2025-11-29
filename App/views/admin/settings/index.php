<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Admin Settings</h1>
            <p class="mt-1 text-sm text-gray-500">Configure system-wide settings and preferences</p>
        </div>
    </div>

    <!-- Settings Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Website Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Website Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure global website preferences</p>
            </div>
            <div class="p-6">
                <form id="websiteSettingsForm" class="space-y-6">
                    <!-- Website URL -->
                    <div>
                        <label for="website_url" class="block text-sm font-medium text-gray-700 mb-2">
                            Website URL
                        </label>
                        <input type="url"
                               id="website_url"
                               name="website_url"
                               value="<?= htmlspecialchars($settings['website_url'] ?? (defined('BASE_URL') ? BASE_URL : '')) ?>"
                               placeholder="http://example.com"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">Base URL used across the site and admin</p>
                    </div>
                </form>
            </div>
        </div>
        <!-- Withdrawal Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Withdrawal Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure withdrawal limits and processing options</p>
            </div>
            <div class="p-6">
                <form id="withdrawalSettingsForm" class="space-y-6">
                    <!-- Minimum Withdrawal Amount -->
                    <div>
                        <label for="min_withdrawal" class="block text-sm font-medium text-gray-700 mb-2">
                            Minimum Withdrawal Amount (रु)
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="min_withdrawal" 
                                   name="min_withdrawal" 
                                   value="<?= $settings['min_withdrawal'] ?? 100 ?>" 
                                   min="0" 
                                   step="1"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">रु</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Minimum amount required for withdrawal requests</p>
                    </div>

                    <!-- Auto Approve Withdrawals -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Auto Approve Withdrawals</h3>
                            <p class="text-sm text-gray-500">Automatically approve withdrawal requests</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   id="auto_approve" 
                                   name="auto_approve" 
                                   <?= ($settings['auto_approve'] ?? 'true') === 'true' ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </form>
            </div>
        </div>

        <!-- Commission Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Commission Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure referral commission rates</p>
            </div>
            <div class="p-6">
                <form id="commissionSettingsForm" class="space-y-6">
                    <!-- Commission Rate -->
                    <div>
                        <label for="commission_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Commission Rate (%)
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="commission_rate" 
                                   name="commission_rate" 
                                   value="<?= $settings['commission_rate'] ?? 5 ?>" 
                                   min="0" 
                                   max="100" 
                                   step="0.1"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">%</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Percentage of order total that referrers earn</p>
                    </div>
                    <!-- Tax Rate -->
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Tax Rate (%)
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   id="tax_rate" 
                                   name="tax_rate" 
                                   value="<?= $settings['tax_rate'] ?? 0 ?>" 
                                   min="0" 
                                   max="100" 
                                   step="0.01"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 text-sm">%</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">Global tax percentage applied at checkout</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Maintenance Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Maintenance Mode</h2>
                <p class="text-sm text-gray-500 mt-1">Enable maintenance mode to block site access</p>
            </div>
            <div class="p-6">
                <form id="maintenanceSettingsForm" class="space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Enable Maintenance Mode</h3>
                            <p class="text-sm text-gray-500">Block entire site except admin routes</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   id="maintenance_mode" 
                                   name="maintenance_mode" 
                                   <?= ($settings['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                        <script>
                        document.getElementById('maintenance_mode').addEventListener('change', function() {
                            const isEnabled = this.checked;
                            fetch('<?= BASE_URL ?>/admin/settings/update', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'ngrok-skip-browser-warning': 'true',
                                },
                                body: JSON.stringify({
                                    maintenance_mode: isEnabled
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Maintenance mode ' + (isEnabled ? 'enabled' : 'disabled') + ' successfully!');
                                } else {
                                    showNotification('Error updating maintenance mode', 'error');
                                    this.checked = !isEnabled;
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showNotification('Error updating maintenance mode', 'error');
                                this.checked = !isEnabled;
                            });
                        });
                        </script>
                    </div>
                </form>
            </div>
        </div>

        <!-- Login Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Login Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Configure user session and login preferences</p>
            </div>
            <div class="p-6">
                <form id="loginSettingsForm" class="space-y-6">
                    <!-- Remember Me Duration -->
                    <div>
                        <label for="remember_token_days" class="block text-sm font-medium text-gray-700 mb-2">
                            Remember Me Duration (Days)
                        </label>
                        <input type="number" 
                               id="remember_token_days" 
                               name="remember_token_days" 
                               value="<?= $settings['remember_token_days'] ?? 30 ?>" 
                               min="1" 
                               max="365" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <p class="text-sm text-gray-500 mt-1">Number of days to keep users logged in when "Remember Me" is checked</p>
                    </div>

                    <!-- Enable Remember Me -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h3 class="text-sm font-medium text-gray-900">Enable Remember Me</h3>
                            <p class="text-sm text-gray-500">Allow users to stay logged in between sessions</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   id="enable_remember_me" 
                                   name="enable_remember_me" 
                                   <?= ($settings['enable_remember_me'] ?? 'true') === 'true' ? 'checked' : '' ?>
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </form>
            </div>
        </div>

        <!-- Database Settings -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Database Settings</h2>
                <p class="text-sm text-gray-500 mt-1">Database optimization and maintenance options</p>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <!-- Database Status -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-900">Database Status</h3>
                        <div class="mt-2 flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <p class="text-sm text-gray-700">Connected and operational</p>
                        </div>
                    </div>

                    <!-- Database Actions -->
                    <div class="space-y-3">
                        <button type="button" id="optimizeDbBtn" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            <i class="fas fa-database mr-2"></i>
                            Optimize Database Tables
                        </button>
                        <button type="button" id="backupDbBtn" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            <i class="fas fa-download mr-2"></i>
                            Backup Database
                        </button>
                        <button type="button" id="exportDbXlsBtn" class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>
                            Export Database (XLS)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Management -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Cache Management</h2>
                <p class="text-sm text-gray-500 mt-1">Clear application cache to refresh stored data</p>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <!-- Cache Status -->
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-900">Cache Status</h3>
                        <div class="mt-2 flex items-center">
                            <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            <p class="text-sm text-gray-700">Cache system active</p>
                        </div>
                    </div>

                    <!-- Cache Actions -->
                    <div class="space-y-3">
                        <button type="button" id="clearCacheBtn" class="w-full flex items-center justify-center px-4 py-2 border border-red-300 rounded-lg text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Clear All Cache
                        </button>
                        <p class="text-xs text-gray-500 text-center">This will delete all files in App/storage/cache/ folder</p>
                    </div>
                </div>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="button" 
                id="saveAllSettings"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
            <i class="fas fa-save mr-2"></i>
            Save All Settings
        </button>
    </div>
</div>

<script>
// Function to show notification
function showNotification(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-6 py-3 rounded-lg shadow-lg z-50`;
    alert.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'}" />
            </svg>
            ${message}
        </div>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Save all settings
document.getElementById('saveAllSettings').addEventListener('click', function() {
    // Collect data from all forms
    const withdrawalSettings = new FormData(document.getElementById('withdrawalSettingsForm'));
    const commissionSettings = new FormData(document.getElementById('commissionSettingsForm'));
    const loginSettings = new FormData(document.getElementById('loginSettingsForm'));
    const websiteSettings = new FormData(document.getElementById('websiteSettingsForm'));
    const maintenanceSettings = new FormData(document.getElementById('maintenanceSettingsForm'));
    
    // Combine all settings
    const allSettings = {
        website_url: websiteSettings.get('website_url'),
        min_withdrawal: withdrawalSettings.get('min_withdrawal'),
        auto_approve: document.getElementById('auto_approve').checked,
        commission_rate: commissionSettings.get('commission_rate'),
        tax_rate: commissionSettings.get('tax_rate'),
        remember_token_days: loginSettings.get('remember_token_days'),
        enable_remember_me: document.getElementById('enable_remember_me').checked,
        maintenance_mode: document.getElementById('maintenance_mode').checked
    };
    
    // Send data to server
    fetch('<?= BASE_URL ?>/admin/settings/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            // Bypass ngrok browser warning page in dev
            'ngrok-skip-browser-warning': 'true',
        },
        body: JSON.stringify(allSettings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Settings saved successfully!');
        } else {
            showNotification('Error saving settings: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving settings', 'error');
    });
});

// Database optimization button
document.getElementById('optimizeDbBtn').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Optimizing...';
    
    fetch('<?= BASE_URL ?>/admin/settings/optimize-db', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-database mr-2"></i> Optimize Database Tables';
        
        if (data.success) {
            showNotification('Database optimized successfully!');
        } else {
            showNotification('Error optimizing database: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-database mr-2"></i> Optimize Database Tables';
        showNotification('Error optimizing database', 'error');
    });
});

// Database backup button
document.getElementById('backupDbBtn').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Backing up...';
    
    fetch('<?= BASE_URL ?>/admin/settings/backup-db', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-download mr-2"></i> Backup Database';
        
        if (data.success) {
            showNotification('Database backup created successfully!');
            // If there's a download URL, trigger download
            if (data.download_url) {
                window.location.href = data.download_url;
            }
        } else {
            showNotification('Error backing up database: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-download mr-2"></i> Backup Database';
        showNotification('Error backing up database', 'error');
    });
});

// Database export to XLS button
document.getElementById('exportDbXlsBtn').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Exporting...';

    // Trigger file download
    window.location.href = '<?= BASE_URL ?>/admin/settings/export-db-xls';

    setTimeout(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-file-excel mr-2"></i> Export Database (XLS)';
    }, 1500);
});

// Clear cache button
document.getElementById('clearCacheBtn').addEventListener('click', function() {
    if (!confirm('Are you sure you want to clear all cache? This will delete all cached files in App/storage/cache/ folder.')) {
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Clearing...';
    
    fetch('<?= BASE_URL ?>/admin/settings/clear-cache', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'ngrok-skip-browser-warning': 'true',
        }
    })
    .then(response => response.json())
    .then(data => {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Clear All Cache';
        
        if (data.success) {
            showNotification('Cache cleared successfully!');
        } else {
            showNotification('Error clearing cache: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Clear All Cache';
        showNotification('Error clearing cache', 'error');
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
