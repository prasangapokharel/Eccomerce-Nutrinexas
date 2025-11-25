<?php ob_start(); ?>
<?php $page = 'support'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Support Tickets</h1>
        <a href="<?= \App\Core\View::url('seller/support/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus icon-spacing"></i> New Ticket
        </a>
    </div>

    <!-- Filter -->
    <div class="bg-white p-4 rounded-lg shadow flex gap-4">
        <select onchange="window.location.href='?status=' + this.value" class="px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
            <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="waiting_reply" <?= $statusFilter === 'waiting_reply' ? 'selected' : '' ?>>Waiting Reply</option>
            <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
        </select>
    </div>

    <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <i class="fas fa-headset empty-state-icon"></i>
            <h3 class="empty-state-title">No support tickets</h3>
            <p class="empty-state-text">Create a ticket to get help</p>
            <a href="<?= \App\Core\View::url('seller/support/create') ?>" class="btn btn-primary">
                Create Ticket
            </a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">All Tickets</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Replies</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($ticket['ticket_number']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <?= htmlspecialchars($ticket['subject']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= ucfirst($ticket['category']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $priorityColors = [
                                        'low' => 'bg-gray-100 text-gray-800',
                                        'medium' => 'bg-blue-100 text-blue-800',
                                        'high' => 'bg-orange-100 text-orange-800',
                                        'urgent' => 'bg-red-100 text-red-800'
                                    ];
                                    $priorityColor = $priorityColors[$ticket['priority']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $priorityColor ?>">
                                        <?= ucfirst($ticket['priority']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
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
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $ticket['reply_count'] ?? 0 ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $ticket['last_reply_at'] ? date('M j, Y', strtotime($ticket['last_reply_at'])) : date('M j, Y', strtotime($ticket['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="<?= \App\Core\View::url('seller/support/detail/' . $ticket['id']) ?>" 
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

