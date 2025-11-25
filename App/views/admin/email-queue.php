<?php ob_start(); ?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Email Queue Management</h1>
            <p class="mt-2 text-gray-600">Monitor and manage background email processing</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['pending'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Processing</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['processing'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Sent</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['sent'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Failed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?= $stats['failed'] ?? 0 ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Queue Actions</h3>
                <div class="flex flex-wrap gap-4">
<button onclick="processQueue()" class="btn">
                        Process Queue Now
                    </button>
<button onclick="refreshStats()" class="btn">
                        Refresh Stats
                    </button>
<button onclick="cleanOldEmails()" class="btn">
                        Clean Old Emails
                    </button>
                </div>
            </div>
        </div>

        <!-- Email Queue Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Emails</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Latest emails in the queue</p>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php if (!empty($emails)): ?>
                    <?php foreach ($emails as $email): ?>
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($email['to_email']) ?></p>
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?= $email['status'] === 'sent' ? 'bg-green-100 text-green-800' : 
                                                    ($email['status'] === 'failed' ? 'bg-red-100 text-red-800' : 
                                                    ($email['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800')) ?>">
                                                <?= ucfirst($email['status']) ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($email['subject']) ?></p>
                                        <p class="text-xs text-gray-400">Template: <?= htmlspecialchars($email['template']) ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <div class="text-right">
                                        <p><?= date('M j, Y', strtotime($email['created_at'])) ?></p>
                                        <p><?= date('g:i A', strtotime($email['created_at'])) ?></p>
                                        <?php if ($email['attempts'] > 0): ?>
                                            <p class="text-xs">Attempts: <?= $email['attempts'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="px-4 py-8 sm:px-6 text-center text-gray-500">
                        No emails in queue
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
function processQueue() {
    fetch('/admin/email-queue/process', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
            alert(`Processed: ${data.processed}, Failed: ${data.failed}`);
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing queue');
        });
}

function refreshStats() {
    location.reload();
}

function cleanOldEmails() {
    if (confirm('Are you sure you want to clean old emails? This will delete emails older than 30 days.')) {
        fetch('/admin/email-queue/clean', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert('Old emails cleaned successfully');
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error cleaning old emails');
            });
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/main.php'; ?>
















