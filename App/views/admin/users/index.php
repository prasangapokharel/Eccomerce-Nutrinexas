<?php ob_start(); ?>

<div class="space-y-6">
    <!-- Standard Action Row: Title Left, Search/Filter/Add Button Right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Users</h1>
            <p class="mt-1 text-sm text-gray-500">View and manage all registered users</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Export Button -->
            <button onclick="exportUsers()" 
                    class="btn btn-outline">
                <i class="fas fa-download mr-2"></i>
                Export Users
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php
        $stats = [
            'total' => $total ?? count($users),
            'active' => count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'active')),
            'inactive' => count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'inactive')),
            'suspended' => count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'suspended')),
        ];
        ?>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-users text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-green-50 text-green-600">
                    <i class="fas fa-check-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Active Users</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['active'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-red-50 text-red-600">
                    <i class="fas fa-times-circle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Inactive Users</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['inactive'] ?></h3>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="p-3 rounded-xl bg-yellow-50 text-yellow-600">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Suspended Users</p>
                    <h3 class="text-xl font-bold text-gray-900"><?= $stats['suspended'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table using Table Component -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php
        // Prepare table configuration
        $tableConfig = [
            'id' => 'usersTable',
            'columns' => [
                [
                    'key' => 'user',
                    'label' => 'User',
                    'type' => 'custom',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 512 512"><path d="M337.711 241.3a16 16 0 0 0-11.461 3.988c-18.739 16.561-43.688 25.682-70.25 25.682s-51.511-9.121-70.25-25.683a16.007 16.007 0 0 0-11.461-3.988c-78.926 4.274-140.752 63.672-140.752 135.224v107.152C33.537 499.293 46.9 512 63.332 512h385.336c16.429 0 29.8-12.707 29.8-28.325V376.523c-.005-71.552-61.831-130.95-140.757-135.223zM446.463 480H65.537V376.523c0-52.739 45.359-96.888 104.351-102.8C193.75 292.63 224.055 302.97 256 302.97s62.25-10.34 86.112-29.245c58.992 5.91 104.351 50.059 104.351 102.8zM256 234.375a117.188 117.188 0 1 0-117.188-117.187A117.32 117.32 0 0 0 256 234.375zM256 32a85.188 85.188 0 1 1-85.188 85.188A85.284 85.284 0 0 1 256 32z" data-original="#000000"></path></svg>',
                    'render' => function($row) {
                        $user = $row['user'];
                        $imageUrl = $user['image'] ?? null;
                        $initials = strtoupper(substr($user['name'], 0, 1) . (strlen($user['name']) > 1 ? substr($user['name'], strpos($user['name'], ' ') + 1, 1) : ''));
                        return '<div class="flex items-center cursor-pointer w-max">
                            <div class="flex-shrink-0 relative">
                                <div class="h-12 w-12 ' . (($user['sponsor_status'] ?? 'inactive') === 'active' ? 'ring-2 ring-accent' : '') . ' rounded-full overflow-hidden">
                                    ' . ($imageUrl ? '<img src="' . htmlspecialchars($imageUrl) . '" alt="' . htmlspecialchars($user['name']) . '" class="w-full h-full object-cover" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' : '') . '
                                    <div class="w-full h-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-bold text-sm" ' . ($imageUrl ? 'style="display: none;"' : '') . '>
                                        ' . $initials . '
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4 min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate">' . htmlspecialchars($user['name']) . '</div>
                                <div class="text-xs text-gray-500">ID: ' . $user['id'] . ($user['username'] ? ' • @' . htmlspecialchars($user['username']) : '') . '</div>
                            </div>
                        </div>';
                    }
                ],
                [
                    'key' => 'contact',
                    'label' => 'Contact',
                    'type' => 'custom',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 512 512"><path d="M467 76H45C20.238 76 0 96.149 0 121v270c0 24.86 20.251 45 45 45h422c24.762 0 45-20.149 45-45V121c0-24.857-20.248-45-45-45zm-6.91 30L267.624 299.094c-5.864 5.882-17.381 5.886-23.248 0L51.91 106h408.18zM30 385.485v-258.97L159.065 256 30 385.485zM51.91 406l128.334-128.752 42.885 43.025c17.574 17.631 48.175 17.624 65.743 0l42.885-43.024L460.09 406H51.91zM482 385.485 352.935 256 482 126.515v258.97z" data-original="#000000" /></svg>',
                    'render' => function($row) {
                        $contact = $row['contact'];
                        return '<div class="text-sm text-gray-900">' . htmlspecialchars($contact['email']) . '</div>' .
                               ($contact['phone'] ? '<div class="text-xs text-gray-500">' . htmlspecialchars($contact['phone']) . '</div>' : '');
                    }
                ],
                [
                    'key' => 'role',
                    'label' => 'Role',
                    'type' => 'badge',
                    'badgeConfig' => [
                        'admin' => 'info',
                        'customer' => 'primary'
                    ],
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 24 24"><g transform="matrix(1.08 0 0 1.08 -.96 -.96)"><path d="M11.5 20.263H2.95a.2.2 0 0 1-.2-.2v-1.45c0-.831.593-1.563 1.507-2.185 1.632-1.114 4.273-1.816 7.243-1.816a.75.75 0 0 0 0-1.5c-3.322 0-6.263.831-8.089 2.076-1.393.95-2.161 2.157-2.161 3.424v1.451a1.7 1.7 0 0 0 1.7 1.7h8.55a.75.75 0 1 0 0-1.5zm0-19.013C8.464 1.25 6 3.714 6 6.75s2.464 5.5 5.5 5.5S17 9.786 17 6.75s-2.464-5.5-5.5-5.5zm0 1.5c2.208 0 4 1.792 4 4s-1.792 4-4 4-4-1.792-4-4 1.792-4 4-4zm5.25 14.75V20a.75.75 0 0 0 1.5 0v-2.5a.75.75 0 0 0-1.5 0z" data-original="#000000" /><circle cx="17.5" cy="15.25" r="1" data-original="#000000" /><path d="M17.5 12.25c-2.898 0-5.25 2.352-5.25 5.25s2.352 5.25 5.25 5.25 5.25-2.352 5.25-5.25-2.352-5.25-5.25-5.25zm0 1.5c2.07 0 3.75 1.68 3.75 3.75s-1.68 3.75-3.75 3.75-3.75-1.68-3.75-3.75 1.68-3.75 3.75-3.75z" data-original="#000000" /></g></svg>'
                ],
                [
                    'key' => 'status',
                    'label' => 'Status',
                    'type' => 'badge',
                    'badgeConfig' => [
                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning'
                    ],
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 24 24"><path d="M15.24 23.61H8.76c-5.864 0-8.37-2.506-8.37-8.37V8.76C.39 2.896 2.896.39 8.76.39h6.48c5.864 0 8.37 2.506 8.37 8.37v6.48c0 5.864-2.506 8.37-8.37 8.37zM8.76 2.01c-4.979 0-6.75 1.771-6.75 6.75v6.48c0 4.979 1.771 6.75 6.75 6.75h6.48c4.979 0 6.75-1.771 6.75-6.75V8.76c0-4.979-1.771-6.75-6.75-6.75z" data-original="#000000" /><path d="M6.956 15.5a.795.795 0 0 1-.496-.174.809.809 0 0 1-.152-1.134l2.57-3.337c.314-.4.757-.659 1.264-.723a1.886 1.886 0 0 1 1.404.388l1.977 1.556a.23.23 0 0 0 .205.054c.043 0 .119-.022.184-.108l2.494-3.219a.8.8 0 0 1 1.134-.14c.357.27.422.777.14 1.134l-2.494 3.218c-.313.4-.756.659-1.264.713a1.873 1.873 0 0 1-1.404-.389l-1.976-1.555a.238.238 0 0 0-.205-.054c-.043 0-.119.022-.184.108l-2.57 3.337a.732.732 0 0 1-.627.324z" data-original="#000000" /></svg>'
                ],
                [
                    'key' => 'referrals',
                    'label' => 'Referrals',
                    'type' => 'custom',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 24 24"><g transform="matrix(1.05 0 0 1.05 -.6 -.6)"><path d="M19 22.75H5c-2.07 0-3.75-1.68-3.75-3.75V7c0-2.07 1.68-3.75 3.75-3.75h14c2.07 0 3.75 1.68 3.75 3.75v12c0 2.07-1.68 3.75-3.75 3.75zm-14-18C3.76 4.75 2.75 5.76 2.75 7v12c0 1.24 1.01 2.25 2.25 2.25h14c1.24 0 2.25-1.01 2.25-2.25V7c0-1.24-1.01-2.25-2.25-2.25z" data-original="#000000" /><path d="M22 9.75H2c-.41 0-.75-.34-.75-.75s.34-.75.75-.75h20c.41 0 .75.34.75.75s-.34.75-.75.75zm-5-5c-.41 0-.75-.34-.75-.75V2c0-.41.34-.75.75-.75s.75.34.75.75v2c0 .41-.34.75-.75.75zm-10 0c-.41 0-.75-.34-.75-.75V2c0-.41.34-.75.75-.75s.75.34.75.75v2c0 .41-.34.75-.75.75z" data-original="#000000" /><circle cx="7" cy="13" r="1" data-original="#000000" /><circle cx="12" cy="13" r="1" data-original="#000000" /><circle cx="17" cy="13" r="1" data-original="#000000" /><circle cx="7" cy="18" r="1" data-original="#000000" /><circle cx="12" cy="18" r="1" data-original="#000000" /><circle cx="17" cy="18" r="1" data-original="#000000" /></g></svg>',
                    'render' => function($row) {
                        return '<div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-users mr-1"></i>
                                ' . $row['referrals'] . '
                            </span>
                            ' . ($row['referrals'] > 0 ? '<div class="ml-2 text-xs text-gray-500">Rs' . number_format($row['earnings'], 0) . '</div>' : '') . '
                        </div>';
                    }
                ],
                [
                    'key' => 'joined',
                    'label' => 'Joined date',
                    'type' => 'date',
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 24 24"><path d="M12 23.5C5.675 23.5.5 18.325.5 12S5.675.5 12 .5c.69 0 1.15.46 1.15 1.15S12.69 2.8 12 2.8c-5.06 0-9.2 4.14-9.2 9.2s4.14 9.2 9.2 9.2 9.2-4.14 9.2-9.2c0-.69.46-1.15 1.15-1.15s1.15.46 1.15 1.15c0 6.325-5.175 11.5-11.5 11.5z" data-original="#000000" /><path d="M12 18.325c-3.45 0-6.325-2.875-6.325-6.325S8.55 5.675 12 5.675c.69 0 1.15.46 1.15 1.15s-.46 1.15-1.15 1.15c-2.185 0-4.025 1.84-4.025 4.025s1.84 4.025 4.025 4.025 4.025-1.84 4.025-4.025c0-.69.46-1.15 1.15-1.15s1.15.46 1.15 1.15c0 3.45-2.875 6.325-6.325 6.325z" data-original="#000000" /><path d="M12 13.15c-.345 0-.575-.115-.805-.345-.46-.46-.46-1.15 0-1.61l3.68-3.68c.46-.46 1.15-.46 1.61 0s.46 1.15 0 1.61l-3.565 3.68c-.345.23-.575.345-.92.345z" data-original="#000000" /><path d="M19.245 9.585h-3.68c-.69 0-1.15-.46-1.15-1.15v-3.68c0-.345.115-.575.345-.805L17.865.845c.345-.345.805-.46 1.265-.23s.69.575.69 1.035v2.415h2.53c.46 0 .92.23 1.035.69.23.46.115.92-.23 1.265L20.05 9.24c-.23.115-.46.345-.805.345zm-2.53-2.3h1.955l.805-.805h-.805c-.69 0-1.15-.46-1.15-1.15v-.92l-.805.805z" data-original="#000000" /></svg>'
                ],
                [
                    'key' => 'actions',
                    'label' => 'Action',
                    'type' => 'actions',
                    'baseUrl' => \App\Core\View::url('admin'),
                    'actions' => [
                        ['type' => 'view', 'url' => \App\Core\View::url('admin/viewUser/{id}')],
                        ['type' => 'edit', 'url' => \App\Core\View::url('admin/editUser/{id}')],
                        ['type' => 'delete', 'url' => \App\Core\View::url('admin/deleteUser/{id}')]
                    ],
                    'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 fill-gray-500 inline mr-2" viewBox="0 0 24 24"><path d="M12 23.5C5.675 23.5.5 18.325.5 12S5.675.5 12 .5c.69 0 1.15.46 1.15 1.15S12.69 2.8 12 2.8c-5.06 0-9.2 4.14-9.2 9.2s4.14 9.2 9.2 9.2 9.2-4.14 9.2-9.2c0-.69.46-1.15 1.15-1.15s1.15.46 1.15 1.15c0 6.325-5.175 11.5-11.5 11.5z" data-original="#000000" /><path d="M12 18.325c-3.45 0-6.325-2.875-6.325-6.325S8.55 5.675 12 5.675c.69 0 1.15.46 1.15 1.15s-.46 1.15-1.15 1.15c-2.185 0-4.025 1.84-4.025 4.025s1.84 4.025 4.025 4.025 4.025-1.84 4.025-4.025c0-.69.46-1.15 1.15-1.15s1.15.46 1.15 1.15c0 3.45-2.875 6.325-6.325 6.325z" data-original="#000000" /><path d="M12 13.15c-.345 0-.575-.115-.805-.345-.46-.46-.46-1.15 0-1.61l3.68-3.68c.46-.46 1.15-.46 1.61 0s.46 1.15 0 1.61l-3.565 3.68c-.345.23-.575.345-.92.345z" data-original="#000000" /><path d="M19.245 9.585h-3.68c-.69 0-1.15-.46-1.15-1.15v-3.68c0-.345.115-.575.345-.805L17.865.845c.345-.345.805-.46 1.265-.23s.69.575.69 1.035v2.415h2.53c.46 0 .92.23 1.035.69.23.46.115.92-.23 1.265L20.05 9.24c-.23.115-.46.345-.805.345zm-2.53-2.3h1.955l.805-.805h-.805c-.69 0-1.15-.46-1.15-1.15v-.92l-.805.805z" data-original="#000000" /></svg>'
                ]
            ],
            'data' => $tableData ?? [],
            'pagination' => [
                'currentPage' => $currentPage ?? 1,
                'totalPages' => $totalPages ?? 1,
                'total' => $total ?? 0,
                'perPage' => $perPage ?? 20
            ],
            'bulkActions' => true,
            'baseUrl' => \App\Core\View::url('admin/users')
        ];
        
        include __DIR__ . '/../../components/Table.php';
        ?>
    </div>
</div>

<script>
function exportUsers() {
    alert('Export functionality will be implemented soon!');
}
</script>

<?php $content = ob_get_clean(); ?>
<?php include dirname(dirname(__FILE__)) . '/layouts/admin.php'; ?>
