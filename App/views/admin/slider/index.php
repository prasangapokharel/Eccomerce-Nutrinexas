<?php ob_start(); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Manage Sliders</h1>
        <a href="<?= \App\Core\View::url('admin/slider/create') ?>" class="btn-normal">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add New Slider
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <?= $_SESSION['flash_message'] ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
            </svg>
            <?= $_SESSION['flash_error'] ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <?php if (empty($sliders)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No sliders</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new slider.</p>
                <div class="mt-6">
                    <a href="<?= \App\Core\View::url('admin/slider/create') ?>" class="btn-normal">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        New Slider
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sliders as $slider): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex-shrink-0 h-16 w-24">
                                        <?php 
                                            $mediaUrl = $slider['image_url'] ?? '';
                                            $path = parse_url($mediaUrl, PHP_URL_PATH);
                                            $ext = strtolower(pathinfo($path ?? '', PATHINFO_EXTENSION));
                                            $isVideo = in_array($ext, ['mp4','webm','ogg']);
                                            $fallbackThumb = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTXJF0rB9E_lgUHI0BHJF6m_khbFtbmhi6XBg&s';
                                        ?>
                                        <?php if ($isVideo): ?>
                                            <video class="h-16 w-24 object-cover rounded-lg border" 
                                                   preload="metadata" playsinline muted loop 
                                                   poster="<?= $fallbackThumb ?>">
                                                <source src="<?= htmlspecialchars($mediaUrl) ?>" type="video/<?= $ext ?: 'mp4' ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php else: ?>
                                            <img class="h-16 w-24 object-cover rounded-lg border" 
                                                 src="<?= htmlspecialchars($mediaUrl) ?>" 
                                                 alt="<?= htmlspecialchars($slider['title']) ?>"
                                                 onerror="this.src='<?= $fallbackThumb ?>'">
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($slider['title']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?php if (!empty($slider['link_url'])): ?>
                                            <a href="<?= htmlspecialchars($slider['link_url']) ?>" 
                                               target="_blank" 
                                               class="text-blue-600 hover:text-blue-800 truncate max-w-xs block">
                                                <?= htmlspecialchars($slider['link_url']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">No link</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?= $slider['sort_order'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" 
                                               class="slider-status-toggle sr-only peer" 
                                               data-slider-id="<?= $slider['id'] ?>" 
                                               <?= $slider['is_active'] ? 'checked' : '' ?>
                                               onchange="toggleSliderStatus(<?= $slider['id'] ?>, this.checked)">
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                    </label>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="<?= \App\Core\View::url('admin/slider/edit/' . $slider['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Edit
                                        </a>
                                        <form action="<?= \App\Core\View::url('admin/slider/delete/' . $slider['id']) ?>" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this slider?')">
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-800 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle Slider Status
function toggleSliderStatus(sliderId, isActive) {
    fetch('<?= \App\Core\View::url('admin/slider/toggleStatus') ?>/' + sliderId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ is_active: isActive ? 1 : 0 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message || 'Slider status updated', 'success');
        } else {
            showNotification(data.message || 'Failed to update status', 'error');
            // Revert toggle
            const toggle = document.querySelector(`.slider-status-toggle[data-slider-id="${sliderId}"]`);
            if (toggle) toggle.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        // Revert toggle
        const toggle = document.querySelector(`.slider-status-toggle[data-slider-id="${sliderId}"]`);
        if (toggle) toggle.checked = !isActive;
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>