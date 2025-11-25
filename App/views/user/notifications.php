<?php ob_start(); ?>
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-primary mb-2">My Notifications</h1>
                <p class="text-gray-600">Stay updated with your orders, wishlist, and account activities</p>
            </div>

            <!-- Notification Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= count($notifications ?? []) ?></p>
                            <p class="text-sm text-gray-600">Total Notifications</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">
                                <?= count(array_filter($notifications ?? [], function($n) { return isset($n['is_read']) && $n['is_read']; })) ?>
                            </p>
                            <p class="text-sm text-gray-600">Read</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">
                                <?= count(array_filter($notifications ?? [], function($n) { return !isset($n['is_read']) || !$n['is_read']; })) ?>
                            </p>
                            <p class="text-sm text-gray-600">Unread</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <?php if (empty($notifications)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">No notifications yet</h2>
                    <p class="text-gray-600 mb-8 max-w-md mx-auto">You don't have any notifications at the moment. When you receive notifications about orders, promotions, or account updates, they'll appear here.</p>
                    <a href="<?= \App\Core\View::url('') ?>" class="inline-flex items-center px-6 py-3 bg-primary text-white rounded-xl hover:bg-primary-dark transition-all duration-300 transform hover:scale-105">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900">Recent Notifications</h2>
                            <button onclick="markAllAsRead()" class="text-sm text-primary hover:text-primary-dark font-medium transition-colors">
                                Mark all as read
                            </button>
                        </div>
                    </div>
                    
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($notifications as $notification): ?>
                            <?php
                            $date = new \DateTime($notification['created_at']);
                            $formattedDate = $date->format('M j, Y');
                            $formattedTime = $date->format('g:i A');
                            $isUnread = !isset($notification['is_read']) || !$notification['is_read'];
                            
                            $iconConfig = [
                                'order_status' => ['icon' => 'shopping-bag', 'color' => 'blue'],
                                'withdrawal_request' => ['icon' => 'credit-card', 'color' => 'green'],
                                'referral_earning' => ['icon' => 'users', 'color' => 'purple'],
                                'system' => ['icon' => 'info-circle', 'color' => 'orange'],
                                'default' => ['icon' => 'bell', 'color' => 'gray']
                            ];
                            
                            $config = $iconConfig[$notification['type']] ?? $iconConfig['default'];
                            $iconClass = $config['icon'];
                            $colorClass = $config['color'];
                            ?>
                            <div class="p-6 hover:bg-gray-50 transition-colors cursor-pointer notification-item <?= $isUnread ? 'bg-blue-50/30 border-l-4 border-l-primary' : '' ?>" 
                                 data-notification-id="<?= $notification['id'] ?? '' ?>"
                                 onclick="markAsRead(<?= $notification['id'] ?? 0 ?>)">
                                <div class="flex">
                                    <div class="flex-shrink-0 mr-4">
                                        <div class="w-12 h-12 rounded-full bg-<?= $colorClass ?>-100 flex items-center justify-center text-<?= $colorClass ?>-600">
                                            <?php if ($iconClass === 'shopping-bag'): ?>
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                                </svg>
                                            <?php elseif ($iconClass === 'credit-card'): ?>
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                                </svg>
                                            <?php elseif ($iconClass === 'users'): ?>
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                            <?php elseif ($iconClass === 'info-circle'): ?>
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold text-gray-900 <?= $isUnread ? 'font-bold' : '' ?>">
                                                    <?= htmlspecialchars($notification['title']) ?>
                                                </h3>
                                                <p class="text-gray-600 mt-1 leading-relaxed">
                                                    <?= htmlspecialchars($notification['message']) ?>
                                                </p>
                                                <div class="mt-3 flex items-center text-sm text-gray-500">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span><?= $formattedDate ?> at <?= $formattedTime ?></span>
                                                </div>
                                            </div>
                                            <?php if ($isUnread): ?>
                                                <div class="flex-shrink-0 ml-4">
                                                    <div class="w-3 h-3 bg-primary rounded-full"></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (isset($notification['reference_id']) && $notification['reference_id']): ?>
                                            <?php 
                                            $referenceUrl = '';
                                            $linkText = 'View Details';
                                            switch ($notification['type']) {
                                                case 'order_status':
                                                    $referenceUrl = \App\Core\View::url('orders/view/' . $notification['reference_id']);
                                                    $linkText = 'View Order';
                                                    break;
                                                case 'withdrawal_request':
                                                    $referenceUrl = \App\Core\View::url('user/balance');
                                                    $linkText = 'View Balance';
                                                    break;
                                                case 'referral_earning':
                                                    $referenceUrl = \App\Core\View::url('user/invite');
                                                    $linkText = 'View Referrals';
                                                    break;
                                            }
                                            
                                            if ($referenceUrl):
                                            ?>
                                                <div class="mt-4">
                                                    <a href="<?= $referenceUrl ?>" 
                                                       class="inline-flex items-center text-primary hover:text-primary-dark font-medium transition-colors"
                                                       onclick="event.stopPropagation();">
                                                        <span><?= $linkText ?></span>
                                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                        </svg>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Mark individual notification as read
function markAsRead(notificationId) {
    if (!notificationId) return;
    
    fetch('<?= ASSETS_URL ?>/api/notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.classList.remove('bg-blue-50/30', 'border-l-4', 'border-l-primary');
                const unreadDot = notificationElement.querySelector('.w-3.h-3.bg-primary');
                if (unreadDot) {
                    unreadDot.remove();
                }
                const title = notificationElement.querySelector('h3');
                if (title) {
                    title.classList.remove('font-bold');
                }
            }
            
            // Update navbar notification count
            if (window.NavbarCartManager) {
                window.NavbarCartManager.updateNotificationCount(data.unread_count || 0);
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

// Mark all notifications as read
function markAllAsRead() {
    fetch('<?= ASSETS_URL ?>/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread styling from all notifications
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('bg-blue-50/30', 'border-l-4', 'border-l-primary');
                const unreadDot = item.querySelector('.w-3.h-3.bg-primary');
                if (unreadDot) {
                    unreadDot.remove();
                }
                const title = item.querySelector('h3');
                if (title) {
                    title.classList.remove('font-bold');
                }
            });
            
            // Update navbar notification count
            if (window.NavbarCartManager) {
                window.NavbarCartManager.updateNotificationCount(0);
            }
            
            // Show success message
            if (window.showToast) {
                window.showToast('All notifications marked as read', 'success');
            }
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        if (window.showToast) {
            window.showToast('Failed to mark notifications as read', 'error');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh notifications every 30 seconds
    setInterval(() => {
        // Check for new notifications
        fetch('<?= ASSETS_URL ?>/api/notifications/count')
            .then(response => response.json())
            .then(data => {
                if (data.success && window.NavbarCartManager) {
                    window.NavbarCartManager.updateNotificationCount(data.count || 0);
                }
            })
            .catch(error => console.error('Error checking notifications:', error));
    }, 30000);
});
</script>

<?php $content = ob_get_clean(); ?>

<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
