<?php 
ob_start(); 
$title = $title ?? 'Add Delivery Charge';
$prefilledLocation = $_GET['location'] ?? '';
?>

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Add Delivery Charge</h1>
            <p class="mt-1 text-sm text-gray-500">Add a new location and set its delivery fee</p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="<?= \App\Core\View::url('admin/delivery') ?>" 
               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Delivery Charges
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (\App\Core\Session::hasFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">
                            <?= \App\Core\Session::getFlash('error') ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Location Name -->
                    <div class="sm:col-span-2">
                        <label for="location_name" class="block text-sm font-medium text-gray-700">
                            Location Name *
                        </label>
                        <div class="mt-1">
                            <input type="text" 
                                   name="location_name" 
                                   id="location_name" 
                                   value="<?= htmlspecialchars($prefilledLocation) ?>"
                                   class="shadow-sm focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="e.g., Kathmandu, Pokhara, Butwal"
                                   required>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Enter the city or location name where delivery is available
                        </p>
                    </div>

                    <!-- Delivery Charge -->
                    <div class="sm:col-span-2">
                        <label for="charge" class="block text-sm font-medium text-gray-700">
                            Delivery Fee (रु) *
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">रु</span>
                            </div>
                            <input type="number" 
                                   name="charge" 
                                   id="charge" 
                                   step="0.01" 
                                   min="0"
                                   class="focus:ring-primary focus:border-primary block w-full pl-8 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00"
                                   required>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Enter 0 for free delivery
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="<?= \App\Core\View::url('admin/delivery') ?>" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        Add Delivery Charge
                    </button>
            </div>
        </form>
    </div>

    <!-- Quick Add Suggestions -->
    <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Add Popular Locations</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php
                $quickLocations = [
                    ['name' => 'Pokhara', 'charge' => 200],
                    ['name' => 'Lalitpur', 'charge' => 150],
                    ['name' => 'Bharatpur', 'charge' => 250],
                    ['name' => 'Biratnagar', 'charge' => 300],
                    ['name' => 'Birgunj', 'charge' => 350],
                    ['name' => 'Dharan', 'charge' => 280]
                ];
                ?>
                
                <?php foreach ($quickLocations as $location): ?>
                    <button type="button" 
                            onclick="fillQuickLocation('<?= htmlspecialchars($location['name']) ?>', <?= $location['charge'] ?>)"
                            class="text-left p-3 border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-primary transition-colors">
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($location['name']) ?></div>
                        <div class="text-sm text-gray-500">रु<?= number_format($location['charge'], 2) ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function fillQuickLocation(location, charge) {
    document.getElementById('location_name').value = location;
    document.getElementById('charge').value = charge;
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
