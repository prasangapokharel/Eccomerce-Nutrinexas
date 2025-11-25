<?php ob_start(); ?>
<?php $page = 'support'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Ticket #<?= htmlspecialchars($ticket['ticket_number']) ?></h1>
        <a href="<?= \App\Core\View::url('seller/support') ?>" class="link-gray">
            <i class="fas fa-arrow-left icon-spacing"></i> Back
        </a>
    </div>

    <!-- Ticket Info -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($ticket['subject']) ?></h2>
                <div class="flex gap-3 mt-2">
                    <?php
                    $statusColors = [
                        'open' => 'bg-green-100 text-green-800',
                        'in_progress' => 'bg-blue-100 text-blue-800',
                        'waiting_reply' => 'bg-yellow-100 text-yellow-800',
                        'closed' => 'bg-gray-100 text-gray-800'
                    ];
                    $statusColor = $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?= ucfirst($ticket['category']) ?>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <?= ucfirst($ticket['priority']) ?> Priority
                    </span>
                </div>
            </div>
            <div class="text-sm text-gray-500">
                Created: <?= date('M j, Y g:i A', strtotime($ticket['created_at'])) ?>
            </div>
        </div>
    </div>

    <!-- Replies -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Conversation</h3>
        
        <div class="space-y-4">
            <?php foreach ($replies as $reply): ?>
                <div class="border border-gray-200 rounded-lg p-4 <?= $reply['user_type'] === 'admin' ? 'bg-blue-50' : '' ?>">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                <?= strtoupper(substr($reply['user_type'], 0, 1)) ?>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">
                                    <?= $reply['user_type'] === 'admin' ? 'Admin' : 'You' ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= date('M j, Y g:i A', strtotime($reply['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-700 whitespace-pre-wrap">
                        <?= htmlspecialchars($reply['message']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ticket['status'] !== 'closed'): ?>
            <div class="mt-6 border-t border-gray-200 pt-6">
                <form action="<?= \App\Core\View::url('seller/support/detail/' . $ticket['id']) ?>" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                    <div>
                        <label for="reply" class="block text-sm font-medium text-gray-700 mb-2">Add Reply</label>
                        <textarea id="reply" 
                                  name="reply" 
                                  rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm"
                                  placeholder="Type your reply here"
                                  required></textarea>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            Send Reply
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

