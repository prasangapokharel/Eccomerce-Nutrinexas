<?php
$page = 'support';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Help & Support</h1>
    <p class="text-gray-600 mt-2">Get help with your deliveries and technical issues</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Contact Admin -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            <i class="fas fa-user-shield text-primary mr-2"></i>Contact Admin
        </h2>
        <p class="text-gray-600 mb-4">Need to contact the administrator? Use the form below.</p>
        <form id="contactForm">
            <input type="hidden" name="type" value="admin_contact">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <input type="text" name="subject" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea name="message" rows="4" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
            </div>
            
            <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <i class="fas fa-paper-plane mr-2"></i>Send Message
            </button>
        </form>
    </div>
    
    <!-- Raise Issue -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            <i class="fas fa-exclamation-circle text-warning mr-2"></i>Raise Issue
        </h2>
        <p class="text-gray-600 mb-4">Report any issues you're experiencing with the system.</p>
        <form id="issueForm">
            <input type="hidden" name="type" value="issue">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Issue Type</label>
                <select name="subject" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select Issue Type</option>
                    <option value="Order Issue">Order Issue</option>
                    <option value="Payment Issue">Payment Issue</option>
                    <option value="Technical Issue">Technical Issue</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="message" rows="4" required
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
            </div>
            
            <button type="submit" class="w-full px-4 py-2 bg-warning text-white rounded-lg hover:bg-warning-dark transition-colors">
                <i class="fas fa-flag mr-2"></i>Submit Issue
            </button>
        </form>
    </div>
</div>

<!-- Delivery Dispute Form -->
<div class="bg-white rounded-lg shadow p-6 mb-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">
        <i class="fas fa-gavel text-error mr-2"></i>Delivery Dispute Form
    </h2>
    <p class="text-gray-600 mb-4">Report disputes related to deliveries, payments, or customer issues.</p>
    <form id="disputeForm">
        <input type="hidden" name="type" value="dispute">
        <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Order ID</label>
                <input type="number" name="order_id" placeholder="Enter order ID"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Dispute Type</label>
                <select name="subject" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Select Dispute Type</option>
                    <option value="Payment Dispute">Payment Dispute</option>
                    <option value="Delivery Dispute">Delivery Dispute</option>
                    <option value="Customer Issue">Customer Issue</option>
                    <option value="Other">Other</option>
                </select>
            </div>
        </div>
        
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Dispute Details</label>
            <textarea name="message" rows="4" required
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"></textarea>
        </div>
        
        <button type="submit" class="px-6 py-2 bg-error text-white rounded-lg hover:bg-error-dark transition-colors">
            <i class="fas fa-paper-plane mr-2"></i>Submit Dispute
        </button>
    </form>
</div>

<!-- Technical Support -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">
        <i class="fas fa-headset text-primary mr-2"></i>Technical Support
    </h2>
    <div class="space-y-4">
        <div>
            <h3 class="font-medium text-gray-900 mb-2">Common Issues</h3>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>Unable to update order status</li>
                <li>App not loading properly</li>
                <li>Location tracking issues</li>
                <li>Payment collection problems</li>
            </ul>
        </div>
        
        <div>
            <h3 class="font-medium text-gray-900 mb-2">Contact Information</h3>
            <p class="text-gray-600">Email: support@nutrinexus.com</p>
            <p class="text-gray-600">Phone: +977-XXXXXXXXX</p>
        </div>
    </div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitSupportRequest(this);
});

document.getElementById('issueForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitSupportRequest(this);
});

document.getElementById('disputeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitSupportRequest(this);
});

function submitSupportRequest(form) {
    const formData = new FormData(form);
    
    fetch('<?= \App\Core\View::url('curior/support/submit') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Request submitted successfully!');
            form.reset();
        } else {
            alert('Error: ' + (data.message || 'Failed to submit request'));
        }
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

