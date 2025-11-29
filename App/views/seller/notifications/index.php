<?php ob_start(); ?>
<?php $page = 'notifications'; ?>

<div class="space-y-6">
    <div class="page-header">
        <h1 class="page-title">Notifications</h1>
    </div>

    <!-- Filter -->
    <div class="bg-white p-4 rounded-lg shadow flex gap-4">
        <button type="button" onclick="window.location.href='<?= \App\Core\View::url('seller/notifications') ?>'" 
           class="px-4 py-2 rounded-lg border <?= !$unreadOnly ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300' ?>">
            All (<?= count($notifications) ?>)
        </button>
        <button type="button" onclick="window.location.href='<?= \App\Core\View::url('seller/notifications?unread=1') ?>'" 
           class="px-4 py-2 rounded-lg border <?= $unreadOnly ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300' ?>">
            Unread (<?= $unreadCount ?>)
        </button>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="fas fa-bell empty-state-icon"></i>
            <h3 class="empty-state-title">No notifications</h3>
            <p class="empty-state-text">You're all caught up!</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" id="selectAllNotifications" class="w-4 h-4 text-primary border-gray-300 rounded">
                <span class="text-sm font-medium text-gray-700">Select All</span>
            </label>
        </div>
        <div class="space-y-3">
            <?php foreach ($notifications as $notification): ?>
                <div class="bg-white rounded-lg shadow p-6 <?= !$notification['is_read'] ? 'border-l-4 border-primary' : '' ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start flex-1">
                            <input type="checkbox" class="notification-checkbox w-4 h-4 text-primary border-gray-300 rounded mr-3 mt-1" value="<?= $notification['id'] ?>" data-notification-id="<?= $notification['id'] ?>">
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
                                <button type="button" onclick="markAsRead(<?= $notification['id'] ?>)" class="text-primary" title="Mark as read">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllNotifications');
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (!this.checked) {
                if (selectAll) selectAll.checked = false;
            } else {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                if (selectAll) selectAll.checked = allChecked;
            }
        });
    });
});

function markAsRead(notificationId) {
    fetch('<?= \App\Core\View::url("seller/notifications/mark-read") ?>/' + notificationId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            _csrf_token: '<?= \App\Helpers\SecurityHelper::generateCSRFToken() ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(__DIR__) . '/layouts/main.php'; ?>

