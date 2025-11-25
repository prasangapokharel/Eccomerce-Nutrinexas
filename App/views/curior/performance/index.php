<?php
$page = 'performance';
ob_start();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Performance Report</h1>
        <p class="text-gray-600 mt-2">Track your delivery performance and statistics</p>
    </div>
    <form method="GET" class="flex items-center space-x-4">
        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" 
               class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
        <span class="text-gray-500">to</span>
        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" 
               class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
        <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <i class="fas fa-filter mr-2"></i>Filter
        </button>
    </form>
</div>

<!-- Performance Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Total Orders</p>
                <p class="text-2xl font-bold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></p>
            </div>
            <div class="bg-primary-50 rounded-full p-3">
                <i class="fas fa-shopping-cart text-primary text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Delivered</p>
                <p class="text-2xl font-bold text-accent"><?= $stats['delivered_orders'] ?? 0 ?></p>
            </div>
            <div class="bg-accent/20 rounded-full p-3">
                <i class="fas fa-check-circle text-accent text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Failed Attempts</p>
                <p class="text-2xl font-bold text-error"><?= $stats['failed_attempts'] ?? 0 ?></p>
            </div>
            <div class="bg-error-50 rounded-full p-3">
                <i class="fas fa-exclamation-triangle text-error text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600 mb-1">Delivery Accuracy</p>
                <p class="text-2xl font-bold text-primary"><?= $stats['delivery_accuracy'] ?? 0 ?>%</p>
            </div>
            <div class="bg-primary-50 rounded-full p-3">
                <i class="fas fa-chart-line text-primary text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Stats -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Statistics</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Total Orders</span>
                <span class="font-semibold text-gray-900"><?= $stats['total_orders'] ?? 0 ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Delivered</span>
                <span class="font-semibold text-accent"><?= $stats['delivered_orders'] ?? 0 ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Cancelled</span>
                <span class="font-semibold text-error"><?= $stats['cancelled_orders'] ?? 0 ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Returned</span>
                <span class="font-semibold text-warning"><?= $stats['returned_orders'] ?? 0 ?></span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Statistics</h3>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">COD Collected</span>
                <span class="font-semibold text-accent">Rs <?= number_format($stats['cod_collected'] ?? 0, 2) ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Return Pickups</span>
                <span class="font-semibold text-gray-900"><?= $stats['return_pickups'] ?? 0 ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Failed Attempts</span>
                <span class="font-semibold text-error"><?= $stats['failed_attempts'] ?? 0 ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">Delivery Accuracy</span>
                <span class="font-semibold text-primary"><?= $stats['delivery_accuracy'] ?? 0 ?>%</span>
            </div>
        </div>
    </div>
</div>

<!-- Performance Chart Placeholder -->
<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Overview</h3>
    <div class="text-center py-12 text-gray-500">
        <i class="fas fa-chart-bar text-4xl mb-4 text-gray-300"></i>
        <p>Performance charts will be displayed here</p>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/main.php'; ?>

