<?php ob_start(); ?>
<?php $page = 'notifications'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Notifications</h1>
        <?php if ($unreadCount > 0): ?>
            <form action="<?= \App\Core\View::url('seller/notifications/mark-all-read') ?>" method="POST" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-check-double icon-spacing"></i> Mark All Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Filter -->
    <div class="bg-white p-4 rounded-lg shadow flex gap-4">
        <a href="<?= \App\Core\View::url('seller/notifications') ?>" 
           class="px-4 py-2 rounded-lg <?= !$unreadOnly ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' ?>">
            All (<?= count($notifications) ?>)
        </a>
        <a href="<?= \App\Core\View::url('seller/notifications?unread=1') ?>" 
           class="px-4 py-2 rounded-lg <?= $unreadOnly ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' ?>">
            Unread (<?= $unreadCount ?>)
        </a>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fas fa-bell empty-state-icon"></i>
            <h3 class="empty-state-title">No notifications</h3>
            <p class="empty-state-text">You're all caught up!</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $notification): ?>
                <div class="bg-white rounded-lg shadow p-6 <?= !$notification['is_read'] ? 'border-l-4 border-primary' : '' ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start flex-1">
                            <?php if (!empty($notification['icon'])): ?>
                                <div class="p-2 bg-primary/10 rounded-lg mr-4">
                                    <i class="fas <?= htmlspecialchars($notification['icon']) ?> text-primary"></i>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900 <?= !$notification['is_read'] ? '' : 'font-normal' ?>">
                                        <?= htmlspecialchars($notification['title']) ?>
                                    </h3>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="w-2 h-2 bg-primary rounded-full"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-2">
                                    <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2 ml-4">
                            <?php if (!$notification['is_read']): ?>
                                <form action="<?= \App\Core\View::url('seller/notifications/mark-read/' . $notification['id']) ?>" method="POST" class="inline">
                                    <input type="hidden" name="_csrf_token" value="<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>">
                                    <button type="submit" class="text-primary hover:text-primary-dark" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (!empty($notification['link'])): ?>
                                <a href="<?= htmlspecialchars($notification['link']) ?>" class="text-primary hover:text-primary-dark">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

