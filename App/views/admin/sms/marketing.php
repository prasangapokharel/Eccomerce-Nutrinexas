<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">SMS Marketing Campaigns</h1>
            <p class="mt-1 text-sm text-gray-500">Send bulk SMS to customers, users, and order contacts</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="<?= \App\Core\View::url('admin/sms') ?>" 
               class="btn">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to SMS Templates
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= count($users) ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-address-book text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Customers</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $customersCount ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                    <i class="fas fa-envelope text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Sent</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['total_sent'] ?? 0 ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-percentage text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Success Rate</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['delivery_rate'] ?? 0 ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- SMS Campaign Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Create SMS Campaign</h2>
        </div>
        
        <form method="POST" action="<?= \App\Core\View::url('admin/sms/sendAll') ?>" class="p-6 space-y-6" id="campaignForm">
            <input type="hidden" name="_csrf" value="<?= $_SESSION['_csrf'] ?>">
            
            <!-- Target Audience Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Target Audience</label>
                <div class="space-y-4">
                    <div>
                        <label class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" name="target_audience" value="customers" checked 
                                        id="target_audience_customers"
                                        class="focus:ring-primary h-4 w-4 text-primary border-gray-300">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="target_audience_customers" class="font-medium text-gray-700">Customers</label>
                                <p class="text-gray-500">Send to all customers in your database</p>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" name="target_audience" value="users" 
                                        id="target_audience_users"
                                        class="focus:ring-primary h-4 w-4 text-primary border-gray-300">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="target_audience_users" class="font-medium text-gray-700">Registered Users</label>
                                <p class="text-gray-500">Send to all registered users</p>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="relative flex items-start">
                            <div class="flex items-center h-5">
                                <input type="radio" name="target_audience" value="orders" 
                                        id="target_audience_orders"
                                        class="focus:ring-primary h-4 w-4 text-primary border-gray-300">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="target_audience_orders" class="font-medium text-gray-700">Recent Orders</label>
                                <p class="text-gray-500">Send to customers with recent orders</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Template Selection -->
            <div>
                <label for="template_id" class="block text-sm font-medium text-gray-700 mb-2">
                    SMS Template (Optional)
                </label>
                <select id="template_id" name="template_id" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select a template or write custom message</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['name']) ?> (<?= $template['category'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Custom Message -->
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                    Message Content <span class="text-red-500">*</span>
                </label>
                <textarea id="message" name="message" rows="4" required
                          placeholder="Enter your SMS message here. Use {{first_name}} for personalization."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
                <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                    <span>Characters: <span id="charCount">0</span>/160</span>
                    <span>Estimated cost: ₹<span id="estimatedCost">0.00</span></span>
                </div>
            </div>
            
            <!-- Template Variables -->
            <div id="templateVariables" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Template Variables</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="variablesContainer">
                    <!-- Variables will be populated here -->
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-100">
                <button type="button" onclick="previewCampaign()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-eye mr-2"></i>Preview
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Send Campaign
                </button>
            </div>
        </form>
    </div>

    <!-- Customer List Preview -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Customer List</h2>
            <p class="text-sm text-gray-500">All customers with valid phone numbers for SMS campaigns</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Customer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact Number
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Address
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                                    <p class="text-gray-500">Add customers first to send SMS campaigns</p>
                                    <a href="<?= \App\Core\View::url('admin/customers/create') ?>" 
                                       class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary-dark">
                                        <i class="fas fa-plus mr-2"></i>Add Customer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($customers as $customer): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-primary-50 flex items-center justify-center">
                                                <i class="fas fa-user text-primary text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($customer['customer_name']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($customer['contact_no']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= !empty($customer['email']) ? htmlspecialchars($customer['email']) : '<span class="text-gray-400">No email</span>' ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= !empty($customer['address']) ? htmlspecialchars($customer['address']) : 'No address' ?>">
                                        <?= !empty($customer['address']) ? htmlspecialchars($customer['address']) : '<span class="text-gray-400">No address</span>' ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (!empty($customers)): ?>
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            <div class="text-sm text-gray-700">
                Total: <?= count($customers) ?> customers with valid phone numbers
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent SMS Logs -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900">Recent SMS Activity</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Recipient
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Message
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Sent
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No SMS logs found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($log['phone_number']) ?>
                                    </div>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($log['user_name']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="<?= htmlspecialchars($log['message']) ?>">
                                        <?= htmlspecialchars($log['message']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusConfig = [
                                        'sent' => ['bg-green-100 text-green-800', 'fas fa-check-circle'],
                                        'failed' => ['bg-red-100 text-red-800', 'fas fa-times-circle'],
                                        'pending' => ['bg-yellow-100 text-yellow-800', 'fas fa-clock']
                                    ];
                                    $config = $statusConfig[$log['status']] ?? ['bg-gray-100 text-gray-800', 'fas fa-question'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $config[0] ?>">
                                        <i class="<?= $config[1] ?> mr-1"></i>
                                        <?= ucfirst($log['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        <?= date('M j, Y', strtotime($log['created_at'])) ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?= date('g:i A', strtotime($log['created_at'])) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                <i class="fas fa-eye text-blue-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Campaign Preview</h3>
            <div class="mt-2 px-7 py-3">
                <div class="text-left text-sm text-gray-500">
                    <p><strong>Target:</strong> <span id="previewTarget">Customers</span></p>
                    <p><strong>Recipients:</strong> <span id="previewRecipients">0</span></p>
                    <p><strong>Message:</strong></p>
                    <div class="mt-2 p-3 bg-gray-100 rounded text-left">
                        <span id="previewMessage">Your message will appear here</span>
                    </div>
                </div>
            </div>
            <div class="items-center px-4 py-3">
                <button id="closePreviewBtn" 
                        class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-lg w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

                <!-- Campaign Preview Modal -->
                <div id="campaignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Campaign Preview</h3>
                                <button onclick="closeCampaignModal()" class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            
                            <div id="campaignPreviewContent">
                                <!-- Preview content will be loaded here -->
                            </div>
                            
                            <div class="flex justify-end space-x-3 mt-6">
                                <button onclick="closeCampaignModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                                    Cancel
                                </button>
                                <button id="sendCampaignBtn" onclick="sendCampaign()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Campaign
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Modal -->
                <div id="progressModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div class="mt-3">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Sending Campaign...</h3>
                                <div class="text-sm text-gray-500" id="progressStatus">Initializing...</div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                                <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            
                            <!-- Progress Details -->
                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span>Progress:</span>
                                    <span id="progressText">0 / 0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Success:</span>
                                    <span id="successCount" class="text-green-600">0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Failed:</span>
                                    <span id="failedCount" class="text-red-600">0</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span>Total Cost:</span>
                                    <span id="totalCost" class="text-blue-600">$0.00</span>
                                </div>
                            </div>
                            
                            <!-- Live Log -->
                            <div class="bg-gray-50 p-3 rounded-md max-h-40 overflow-y-auto">
                                <div id="progressLog" class="text-sm text-gray-700 space-y-1">
                                    <!-- Progress logs will appear here -->
                                </div>
                            </div>
                            
                            <div class="flex justify-end mt-6">
                                <button id="closeProgressBtn" onclick="closeProgressModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors hidden">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

<script>
    // Global variables for progress tracking
    let campaignData = {};
    let totalRecipients = 0;
    let currentProgress = 0;
    let successCount = 0;
    let failedCount = 0;
    let totalCost = 0;

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Set default target audience to customers
        document.getElementById('target_audience_customers').checked = true;
        
        // Update recipient count
        updateRecipientCount();
        
        // Load template variables when template changes
        document.getElementById('template_id').addEventListener('change', loadTemplateVariables);
        
        // Update character count and cost when message changes
        document.getElementById('message').addEventListener('input', updateMessageStats);
        
        // Update recipient count when target audience changes
        document.querySelectorAll('input[name="target_audience"]').forEach(radio => {
            radio.addEventListener('change', updateRecipientCount);
        });
    });

    function updateRecipientCount() {
        const targetAudience = document.querySelector('input[name="target_audience"]:checked').value;
        let count = 0;
        
        if (targetAudience === 'customers') {
            count = <?= $customersCount ?? 0 ?>;
        } else if (targetAudience === 'users') {
            count = <?= count($users) ?>;
        } else if (targetAudience === 'orders') {
            count = <?= count($recentOrders) ?>;
        }
        
        document.getElementById('recipientCount').textContent = count.toLocaleString();
        updateMessageStats();
    }

    function loadTemplateVariables() {
        const templateId = document.getElementById('template_id').value;
        if (!templateId) {
            document.getElementById('templateVariables').innerHTML = '';
            return;
        }

        fetch(`<?= ASSETS_URL ?>/admin/sms/variables/${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.variables.length > 0) {
                    let html = '<div class="space-y-3">';
                    data.variables.forEach(variable => {
                        html += `
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    ${variable.name} ${variable.required ? '<span class="text-red-500">*</span>' : ''}
                                </label>
                                <input type="text" 
                                       name="variables[${variable.name}]" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="${variable.description || variable.name}"
                                       ${variable.required ? 'required' : ''}>
                            </div>
                        `;
                    });
                    html += '</div>';
                    document.getElementById('templateVariables').innerHTML = html;
                } else {
                    document.getElementById('templateVariables').innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Error loading template variables:', error);
                document.getElementById('templateVariables').innerHTML = '';
            });
    }

    function updateMessageStats() {
        const message = document.getElementById('message').value;
        const charCount = message.length;
        const smsCount = Math.ceil(charCount / 160);
        const costPerSMS = 0.02; // $0.02 per SMS
        const totalCost = (smsCount * costPerSMS).toFixed(2);
        
        const targetAudience = document.querySelector('input[name="target_audience"]:checked').value;
        let recipientCount = 0;
        
        if (targetAudience === 'customers') {
            recipientCount = <?= $customersCount ?? 0 ?>;
        } else if (targetAudience === 'users') {
            recipientCount = <?= count($users) ?>;
        } else if (targetAudience === 'orders') {
            recipientCount = <?= count($recentOrders) ?>;
        }
        
        const estimatedCost = (smsCount * costPerSMS * recipientCount).toFixed(2);
        
        document.getElementById('charCount').textContent = charCount;
        document.getElementById('smsCount').textContent = smsCount;
        document.getElementById('costPerSMS').textContent = `$${costPerSMS}`;
        document.getElementById('estimatedCost').textContent = `$${estimatedCost}`;
    }

    function previewCampaign() {
        const form = document.getElementById('campaignForm');
        const formData = new FormData(form);
        
        // Validate form
        if (!formData.get('target_audience')) {
            alert('Please select a target audience');
            return;
        }
        
        if (!formData.get('message').trim()) {
            alert('Please enter a message');
            return;
        }
        
        // Prepare campaign data
        campaignData = {
            target_audience: formData.get('target_audience'),
            template_id: formData.get('template_id'),
            message: formData.get('message'),
            variables: {}
        };
        
        // Get template variables
        const variableInputs = document.querySelectorAll('#templateVariables input');
        variableInputs.forEach(input => {
            const name = input.name.match(/\[([^\]]+)\]/)[1];
            campaignData.variables[name] = input.value;
        });
        
        // Show preview
        showCampaignPreview();
    }

    function showCampaignPreview() {
        const targetAudience = campaignData.target_audience;
        let recipientList = '';
        let recipientCount = 0;
        
        if (targetAudience === 'customers') {
            const customers = <?= json_encode($customers ?? []) ?>;
            recipientCount = customers.length;
            recipientList = customers.slice(0, 5).map(c => 
                `<div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="font-medium">${c.customer_name}</span>
                    <span class="text-gray-600">${c.contact_no}</span>
                </div>`
            ).join('');
            if (customers.length > 5) {
                recipientList += `<div class="text-center py-2 text-gray-500">... and ${customers.length - 5} more customers</div>`;
            }
        } else if (targetAudience === 'users') {
            const users = <?= json_encode($users ?? []) ?>;
            recipientCount = users.length;
            recipientList = users.slice(0, 5).map(u => 
                `<div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="font-medium">${u.first_name} ${u.last_name}</span>
                    <span class="text-gray-600">${u.phone}</span>
                </div>`
            ).join('');
            if (users.length > 5) {
                recipientList += `<div class="text-center py-2 text-gray-500">... and ${users.length - 5} more users</div>`;
            }
        }
        
        const message = campaignData.message;
        const charCount = message.length;
        const smsCount = Math.ceil(charCount / 160);
        const costPerSMS = 0.02;
        const estimatedCost = (smsCount * costPerSMS * recipientCount).toFixed(2);
        
        document.getElementById('campaignPreviewContent').innerHTML = `
            <div class="space-y-4">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Campaign Details</h4>
                    <div class="bg-gray-50 p-3 rounded-md space-y-2 text-sm">
                        <div><strong>Target Audience:</strong> ${targetAudience.charAt(0).toUpperCase() + targetAudience.slice(1)}</div>
                        <div><strong>Recipients:</strong> ${recipientCount.toLocaleString()}</div>
                        <div><strong>Message Length:</strong> ${charCount} characters (${smsCount} SMS)</div>
                        <div><strong>Estimated Cost:</strong> $${estimatedCost}</div>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Message Preview</h4>
                    <div class="bg-gray-50 p-3 rounded-md">
                        <p class="text-gray-700">${message}</p>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Sample Recipients</h4>
                    <div class="bg-gray-50 p-3 rounded-md">
                        ${recipientList}
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('campaignModal').classList.remove('hidden');
    }

    function closeCampaignModal() {
        document.getElementById('campaignModal').classList.add('hidden');
    }

    function sendCampaign() {
        // Close preview modal
        closeCampaignModal();
        
        // Show progress modal
        document.getElementById('progressModal').classList.remove('hidden');
        
        // Initialize progress
        totalRecipients = 0;
        currentProgress = 0;
        successCount = 0;
        failedCount = 0;
        totalCost = 0;
        
        // Get recipient count
        if (campaignData.target_audience === 'customers') {
            totalRecipients = <?= $customersCount ?? 0 ?>;
        } else if (campaignData.target_audience === 'users') {
            totalRecipients = <?= count($users) ?>;
        } else if (campaignData.target_audience === 'orders') {
            totalRecipients = <?= count($recentOrders) ?>;
        }
        
        // Update progress display
        updateProgressDisplay();
        addProgressLog('Starting campaign...', 'info');
        
        // Send campaign
        sendCampaignToServer();
    }

    function sendCampaignToServer() {
        const formData = new FormData();
        formData.append('target_audience', campaignData.target_audience);
        formData.append('template_id', campaignData.template_id || '');
        formData.append('message', campaignData.message);
        formData.append('variables', JSON.stringify(campaignData.variables));
        
        fetch('<?= ASSETS_URL ?>/admin/sms/sendAll', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addProgressLog('Campaign completed successfully!', 'success');
                updateProgressStatus('Campaign completed!');
                showCloseButton();
                
                // Show success message
                setTimeout(() => {
                    alert(`Campaign sent successfully!\n\nSuccess: ${successCount}\nFailed: ${failedCount}\nTotal Cost: $${totalCost.toFixed(2)}`);
                }, 1000);
            } else {
                addProgressLog('Campaign failed: ' + (data.message || 'Unknown error'), 'error');
                updateProgressStatus('Campaign failed');
                showCloseButton();
            }
        })
        .catch(error => {
            console.error('Error sending campaign:', error);
            addProgressLog('Network error: ' + error.message, 'error');
            updateProgressStatus('Network error');
            showCloseButton();
        });
    }

    function updateProgressDisplay() {
        const percentage = totalRecipients > 0 ? (currentProgress / totalRecipients) * 100 : 0;
        document.getElementById('progressBar').style.width = percentage + '%';
        document.getElementById('progressText').textContent = `${currentProgress} / ${totalRecipients}`;
        document.getElementById('successCount').textContent = successCount;
        document.getElementById('failedCount').textContent = failedCount;
        document.getElementById('totalCost').textContent = `$${totalCost.toFixed(2)}`;
    }

    function addProgressLog(message, type = 'info') {
        const log = document.getElementById('progressLog');
        const timestamp = new Date().toLocaleTimeString();
        const colorClass = type === 'success' ? 'text-green-600' : 
                          type === 'error' ? 'text-red-600' : 
                          type === 'warning' ? 'text-yellow-600' : 'text-gray-700';
        
        const logEntry = document.createElement('div');
        logEntry.className = `flex justify-between ${colorClass}`;
        logEntry.innerHTML = `
            <span>${message}</span>
            <span class="text-xs text-gray-500">${timestamp}</span>
        `;
        
        log.appendChild(logEntry);
        log.scrollTop = log.scrollHeight;
    }

    function updateProgressStatus(status) {
        document.getElementById('progressStatus').textContent = status;
    }

    function showCloseButton() {
        document.getElementById('closeProgressBtn').classList.remove('hidden');
    }

    function closeProgressModal() {
        document.getElementById('progressModal').classList.add('hidden');
        // Reset progress
        currentProgress = 0;
        successCount = 0;
        failedCount = 0;
        totalCost = 0;
        updateProgressDisplay();
        document.getElementById('progressLog').innerHTML = '';
        document.getElementById('closeProgressBtn').classList.add('hidden');
        updateProgressStatus('Initializing...');
    }

    // Function to handle individual SMS progress updates (called by server)
    function updateSMSProgress(success, cost, phoneNumber, message) {
        currentProgress++;
        if (success) {
            successCount++;
            totalCost += cost;
            addProgressLog(`✅ SMS sent to ${phoneNumber}`, 'success');
        } else {
            failedCount++;
            addProgressLog(`❌ SMS failed to ${phoneNumber}: ${message}`, 'error');
        }
        updateProgressDisplay();
    }
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
