<?php 
ob_start(); 
$title = $title ?? 'Edit Delivery Charge';
?>

<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-4">
                        <li>
                            <div>
                                <a href="<?= \App\Core\View::url('admin/delivery') ?>" class="text-gray-400 hover:text-gray-500">
                                    <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                                    </svg>
                                    <span class="sr-only">Home</span>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <a href="<?= \App\Core\View::url('admin/delivery') ?>" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Delivery Charges
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="ml-4 text-sm font-medium text-gray-500">Edit Location</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h2 class="mt-2 text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Edit Delivery Charge
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Update the delivery fee for <?= htmlspecialchars($charge['location_name']) ?>
                </p>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (\App\Core\Session::hasFlash('error')): ?>
            <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
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
                                   value="<?= htmlspecialchars($charge['location_name']) ?>"
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
                                   value="<?= htmlspecialchars($charge['charge']) ?>"
                                   class="focus:ring-primary focus:border-primary block w-full pl-8 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00"
                                   required>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            Enter 0 for free delivery
                        </p>
                    </div>
                </div>

                <!-- Current Status -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Current Status</h3>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($charge['location_name']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Current fee: रु<?= number_format($charge['charge'], 2) ?>
                                </div>
                            </div>
                        </div>
                        <div class="ml-auto">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
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
                        Update Delivery Charge
                    </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
