<?php ob_start(); ?>
<?php $page = 'support'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Create Support Ticket</h1>
        <a href="<?= \App\Core\View::url('seller/support') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="<?= \App\Core\View::url('seller/support/create') ?>" method="POST">
            <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                           placeholder="Brief description of your issue"
                           required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                required>
                            <option value="general">General</option>
                            <option value="technical">Technical</option>
                            <option value="billing">Billing</option>
                            <option value="orders">Orders</option>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" 
                                name="priority" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea id="message" 
                              name="message" 
                              rows="6"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                              placeholder="Describe your issue in detail"
                              required></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="<?= \App\Core\View::url('seller/support') ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Create Ticket
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

