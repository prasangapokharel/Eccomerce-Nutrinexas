<?php ob_start(); ?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manual Payment Methods</h1>
        <a href="<?= \App\Core\View::url('admin/payment/create') ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Add New Method
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?= $_SESSION['flash_error'] ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Mode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($gateways as $gateway): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($gateway['name']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($gateway['description']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $gateway['type'] === 'manual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                <?= ucfirst($gateway['type']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="toggleStatus(<?= $gateway['id'] ?>)" 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $gateway['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $gateway['is_active'] ? 'Active' : 'Inactive' ?>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="toggleTestMode(<?= $gateway['id'] ?>)" 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?= $gateway['is_test_mode'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                <?= $gateway['is_test_mode'] ? 'Test' : 'Live' ?>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="<?= \App\Core\View::url('admin/payment/edit/' . $gateway['id']) ?>" 
                               class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <?php if (!in_array($gateway['id'], [1, 2, 3, 4])): ?>
                                <a href="<?= \App\Core\View::url('admin/payment/delete/' . $gateway['id']) ?>" 
                                   class="text-red-600 hover:text-red-900"
                                   onclick="return confirm('Are you sure you want to delete this gateway?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleStatus(id) {
    fetch(`<?= ASSETS_URL ?>/admin/payment/toggleStatus/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function toggleTestMode(id) {
    fetch(`<?= ASSETS_URL ?>/admin/payment/toggleTestMode/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>