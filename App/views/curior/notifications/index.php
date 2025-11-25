<?php
$page = 'notifications';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
    <p class="text-gray-600 mt-2">Stay updated with order assignments and delivery updates</p>
</div>

<?php if (empty($notifications)): ?>
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-bell-slash text-gray-400 text-5xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
        <p class="text-gray-500">You don't have any notifications at the moment</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="divide-y divide-gray-200">
            <?php foreach ($notifications as $notification): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <?php
                                    $icon = 'fa-info-circle';
                                    $color = 'text-primary';
                                    if (strpos($notification['action'], 'assigned') !== false) {
                                        $icon = 'fa-bell';
                                        $color = 'text-accent';
                                    } elseif (strpos($notification['action'], 'urgent') !== false) {
                                        $icon = 'fa-exclamation-triangle';
                                        $color = 'text-error';
                                    } elseif (strpos($notification['action'], 'cod') !== false) {
                                        $icon = 'fa-money-bill-wave';
                                        $color = 'text-warning';
                                    }
                                    ?>
                                    <i class="fas <?= $icon ?> <?= $color ?> text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">
                                        <?= ucfirst(str_replace('_', ' ', $notification['action'])) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Order #<?= $notification['order_id'] ?? 'N/A' ?>
                                    </p>
                                    <?php if (!empty($notification['data'])): ?>
                                        <?php
                                        $data = json_decode($notification['data'], true);
                                        if (is_array($data) && !empty($data['notes'])): ?>
                                            <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($data['notes']) ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="ml-4 text-sm text-gray-500">
                            <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

