<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin Dashboard - Nutri Nexus' ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/admin.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/sidebar.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/modal.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Removed TinyMCE (Tiny Cloud) -->
    <?php if (isset($extraStyles)): ?>
        <?= $extraStyles ?>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar text-white shadow-2xl custom-scroll">
            <div class="p-6 flex flex-col h-full">
                <!-- Logo Section -->
                <div class="flex items-center justify-between mb-8 pb-6 border-b border-white/20">
                    <a href="<?= \App\Core\View::url('admin') ?>" class="flex items-center space-x-3 min-w-0">
                        <!-- <div class="w-12 h-12 bg-golden rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                            <i class="fas fa-leaf text-primary text-xl"></i>
                        </div> -->
                        <div class="sidebar-text min-w-0">
                            <h1 class="text-xl font-bold truncate">Nutri Nexus</h1>
                            <p class="text-xs text-white/70">Admin Panel</p>
                        </div>
                    </a>
                    <button id="toggleSidebar" class="text-white/80 lg:block hidden p-2 rounded-lg flex-shrink-0">
                        <i class="fas fa-bars text-sm"></i>
                    </button>
                </div>
                
                <!-- Navigation Menu -->
                <nav class="flex-1 space-y-2">
                    <!-- Dashboard -->
                    <a href="<?= \App\Core\View::url('admin') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Dashboard">
                        <i class="fas fa-tachometer-alt w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Dashboard</span>
                    </a>
                    
                    <!-- Products -->
                    <a href="<?= \App\Core\View::url('admin/products') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Products">
                        <i class="fas fa-box w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Products</span>
                    </a>
                    
                    <!-- Orders Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('ordersDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Orders">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-shopping-cart w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Orders</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs flex-shrink-0" id="ordersChevron"></i>
                        </button>
                        <div id="ordersDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/orders') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Orders</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/cancels') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-times-circle w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Cancellations</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Users -->
                    <a href="<?= \App\Core\View::url('admin/users') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Users">
                        <i class="fas fa-users w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Users</span>
                    </a>
                    
                    <!-- Customers -->
                    <a href="<?= \App\Core\View::url('admin/customers') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Customers">
                        <i class="fas fa-user-friends w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Customers</span>
                    </a>
                    
                    <!-- Staff -->
                    <a href="<?= \App\Core\View::url('admin/staff') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Staff">
                        <i class="fas fa-user-tie w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Staff</span>
                    </a>
                    
                    <!-- Curiors -->
                    <a href="<?= \App\Core\View::url('admin/curior') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Curiors">
                        <i class="fas fa-truck w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Curiors</span>
                    </a>
                    
                    <!-- Sellers Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('sellersDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Sellers">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-store w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Sellers</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs flex-shrink-0" id="sellersChevron"></i>
                        </button>
                        <div id="sellersDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/seller') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-users w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Manage Sellers</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/seller/products') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-box w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Product Approval</span>
                                <?php
                                try {
                                    $db = \App\Core\Database::getInstance();
                                    $pendingCount = $db->query("SELECT COUNT(*) as count FROM products WHERE (approval_status = 'pending' OR approval_status IS NULL) AND seller_id IS NOT NULL AND seller_id > 0")->single()['count'] ?? 0;
                                    if ($pendingCount > 0):
                                ?>
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?= $pendingCount ?></span>
                                <?php endif; } catch (Exception $e) {} ?>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/seller/withdraws') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-money-bill-wave w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Seller Withdrawals</span>
                                <?php
                                try {
                                    $db = \App\Core\Database::getInstance();
                                    $pendingWithdrawCount = $db->query("SELECT COUNT(*) as count FROM seller_withdraw_requests WHERE status = 'pending'")->single()['count'] ?? 0;
                                    if ($pendingWithdrawCount > 0):
                                ?>
                                    <span class="ml-auto bg-yellow-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?= $pendingWithdrawCount ?></span>
                                <?php endif; } catch (Exception $e) {} ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Payment Gateways -->
                    <a href="<?= \App\Core\View::url('admin/payment') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Payment Gateways">
                        <i class="fas fa-credit-card w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Payment Gateways</span>
                    </a>
                    
                    <!-- Delivery -->
                    <a href="<?= \App\Core\View::url('admin/delivery') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Delivery Charges">
                        <i class="fas fa-truck w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Delivery</span>
                    </a>
                    
                    <!-- Inventory Management Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('inventoryDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Inventory Management">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-warehouse w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Inventory</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs flex-shrink-0" id="inventoryChevron"></i>
                        </button>
                        <div id="inventoryDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/inventory') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-tachometer-alt w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Dashboard</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/inventory/supplier') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-truck w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Suppliers</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/inventory/products') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-box w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Products</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/inventory/purchases') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-shopping-cart w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Purchases</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/inventory/payments') ?>" class="flex items-center p-2 rounded-md">
                                <i class="fas fa-credit-card w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Payments</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Coupons Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('couponsDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Coupons">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-tags w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Coupons</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="couponsChevron"></i>
                        </button>
                        <div id="couponsDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/coupons') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Coupons</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/coupons/create') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-plus w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Create Coupon</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Sales -->
                    <a href="<?= \App\Core\View::url('admin/sales') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Site-Wide Sales">
                        <i class="fas fa-fire w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Sales</span>
                    </a>

                    <!-- Blog Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('blogDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Blog">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-newspaper w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Blog</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="blogChevron"></i>
                        </button>
                        <div id="blogDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/blog') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Posts</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/blog/create') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-plus w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Create Post</span>
                            </a>
                        </div>
                    </div>

                    <!-- Slider Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('sliderDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Sliders">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-images w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Sliders</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="sliderChevron"></i>
                        </button>
                        <div id="sliderDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/slider') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Sliders</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/slider/create') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-plus w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Create Slider</span>
                            </a>
                        </div>
                    </div>

                    <!-- Banners Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('bannersDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Banners">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-image w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Banners</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="bannersChevron"></i>
                        </button>
                        <div id="bannersDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/banners') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Banners</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/banners/create') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-plus w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Create Banner</span>
                            </a>
                        </div>
                    </div>

                    <!-- Ads Management -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('adsDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Ads">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-ad w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Ads</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="adsChevron"></i>
                        </button>
                        <div id="adsDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/ads') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Ads</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/ads/costs') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-dollar-sign w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Ad Costs</span>
                            </a>
                        </div>
                    </div>

                    <!-- SMS Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('smsDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="SMS">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-sms w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">SMS</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="smsChevron"></i>
                        </button>
                        <div id="smsDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/sms') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-list w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">All Templates</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/sms/marketing') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-user w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Marketing</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Withdrawals (General) -->
                    <a href="<?= \App\Core\View::url('admin/withdrawals') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Withdrawals">
                        <i class="fas fa-money-bill-wave w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Withdrawals</span>
                    </a>
                    
                    <!-- Reviews -->
                    <a href="<?= \App\Core\View::url('admin/reviews') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Reviews">
                        <i class="fas fa-star w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Reviews</span>
                    </a>
                    
                    <!-- Referrals -->
                    <a href="<?= \App\Core\View::url('admin/referrals') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Referrals">
                        <i class="fas fa-user-plus w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Referrals</span>
                    </a>
                    
                    <!-- Analytics -->
                    <a href="<?= \App\Core\View::url('admin/analytics') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Analytics">
                        <i class="fas fa-chart-line w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Analytics</span>
                    </a>
                    
                    <!-- Reports Dropdown -->
                    <div class="nav-item">
                        <button onclick="toggleDropdown('reportsDropdown')" class="w-full flex items-center justify-between p-3 rounded-lg" title="Reports">
                            <div class="flex items-center min-w-0">
                                <i class="fas fa-chart-bar w-5 text-golden flex-shrink-0"></i>
                                <span class="ml-3 sidebar-text font-medium">Reports</span>
                            </div>
                            <i class="fas fa-chevron-down sidebar-text text-xs  flex-shrink-0" id="reportsChevron"></i>
                        </button>
                        <div id="reportsDropdown" class="dropdown-menu ml-8 mt-2 space-y-1 sidebar-text">
                            <a href="<?= \App\Core\View::url('admin/reports/best-selling') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-star w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Best Selling</span>
                            </a>
                            <a href="<?= \App\Core\View::url('admin/reports/low-stock') ?>" class="flex items-center p-2 rounded-md ">
                                <i class="fas fa-exclamation-triangle w-4 text-golden/80 flex-shrink-0"></i>
                                <span class="ml-2 text-sm">Low Stock Alerts</span>
                            </a>
                        </div>
                    </div>
                </nav>
                
                <!-- Bottom Section -->
                <div class="mt-auto pt-6 border-t border-white/20 space-y-2">
                    <a href="<?= \App\Core\View::url() ?>" class="nav-item flex items-center p-3 rounded-lg" title="View Store">
                        <i class="fas fa-store w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">View Store</span>
                    </a>
                    <a href="<?= \App\Core\View::url('admin/settings') ?>" class="nav-item flex items-center p-3 rounded-lg" title="Settings">
                        <i class="fas fa-cog w-5 text-golden flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Settings</span>
                    </a>
                    <a href="<?= \App\Core\View::url('auth/logout') ?>" class="nav-item flex items-center p-3 rounded-lg text-red-300 hover:text-red-200 hover:bg-red-500/20" title="Logout">
                        <i class="fas fa-sign-out-alt w-5 flex-shrink-0"></i>
                        <span class="ml-3 sidebar-text font-medium">Logout</span>
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div id="mainContent" class="main-content flex flex-col overflow-x-hidden">
            <!-- Top Navbar -->
            <header class="bg-white shadow-sm border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center justify-between p-4 lg:p-6">
                    <div class="flex items-center space-x-4">
                        <button id="mobileMenuButton" class="lg:hidden text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Breadcrumb -->
                        <nav class="hidden md:flex" aria-label="Breadcrumb">
                            <ol class="flex items-center space-x-2 text-sm text-gray-500">
                                <li><a href="<?= \App\Core\View::url('admin') ?>" class="">Admin</a></li>
                                <li><i class="fas fa-chevron-right text-xs"></i></li>
                                <li class="text-gray-900 font-medium"><?= $title ?? 'Dashboard' ?></li>
                            </ol>
                        </nav>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <button class="relative p-2 text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100">
                            <i class="fas fa-bell text-lg"></i>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
                        </button>
                        
                        <!-- User Dropdown -->
                        <div class="relative">
                            <button onclick="toggleDropdown('userDropdown')" class="flex items-center space-x-2 text-gray-700 hover:text-primary p-2 rounded-lg hover:bg-gray-100">
                                <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                                </div>
                                <span class="hidden md:block font-medium"><?= $_SESSION['user_name'] ?? 'Admin' ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div id="userDropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-20 border border-gray-200">
                                <a href="<?= \App\Core\View::url('admin/profile') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-user w-4 mr-2"></i>Profile
                                </a>
                                <a href="<?= \App\Core\View::url('admin/settings') ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-cog w-4 mr-2"></i>Settings
                                </a>
                                <hr class="my-1">
                                <a href="<?= \App\Core\View::url('auth/logout') ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt w-4 mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Global Notification Component -->
            <?php include ROOT_DIR . '/App/views/components/alert.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <?= $content ?>
            </main>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>
    
    <script>
        // Cookie utility functions
        function setCookie(name, value, days = 30) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
        }
        
        function getCookie(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            for(let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const mobileOverlay = document.getElementById('mobileOverlay');
            const toast = document.getElementById('toast');
            
            // Load sidebar state from cookie
            const sidebarCollapsed = getCookie('sidebar_collapsed') === 'true';
            
            // Apply saved state on page load
            if (sidebarCollapsed && window.innerWidth >= 1024) {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('collapsed');
            }
            
            // Toast notification
            if (toast) {
                setTimeout(() => {
                    toast.style.transform = 'translateX(0)';
                }, 100);
                
                setTimeout(() => {
                    closeToast();
                }, 5000);
            }
            
            // Toggle sidebar on desktop
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    
                    if (isCollapsed) {
                        // Expand sidebar
                        sidebar.classList.remove('sidebar-collapsed');
                        mainContent.classList.remove('collapsed');
                        setCookie('sidebar_collapsed', 'false');
                    } else {
                        // Collapse sidebar
                        sidebar.classList.add('sidebar-collapsed');
                        mainContent.classList.add('collapsed');
                        setCookie('sidebar_collapsed', 'true');
                    }
                });
            }
            
            // Toggle sidebar on mobile
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                    mobileOverlay.classList.toggle('hidden');
                });
            }
            
            // Close sidebar when clicking overlay
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.add('hidden');
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth < 1024) {
                    // Mobile view - reset classes
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('collapsed');
                } else {
                    // Desktop view - apply saved state
                    const sidebarCollapsed = getCookie('sidebar_collapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('sidebar-collapsed');
                        mainContent.classList.add('collapsed');
                    }
                    // Close mobile menu if open
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.add('hidden');
                }
            });
            
            // Set active nav item based on current URL
            setActiveNavItem();
        });
        
        // Toggle dropdown menus
        function toggleDropdown(dropdownId) {
            const dropdown = document.getElementById(dropdownId);
            const chevron = document.getElementById(dropdownId.replace('Dropdown', 'Chevron'));
            
            if (!dropdown) return;
            
            dropdown.classList.toggle('open');
            if (chevron) {
                chevron.style.transform = dropdown.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
            }
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu.id !== dropdownId) {
                    menu.classList.remove('open');
                    const otherChevron = document.getElementById(menu.id.replace('Dropdown', 'Chevron'));
                    if (otherChevron) {
                        otherChevron.style.transform = 'rotate(0deg)';
                    }
                }
            });
        }
        
        // Close toast notification
        function closeToast() {
            const toast = document.getElementById('toast');
            if (toast) {
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        // Set active navigation item
        function setActiveNavItem() {
            const currentPath = window.location.pathname;
            const navItems = document.querySelectorAll('.nav-item');
            
            navItems.forEach(item => {
                const mainLink = item.querySelector('a');
                const dropdownButton = item.querySelector('button[onclick*="toggleDropdown"]');
                let isActive = false;

                // Check if the main link matches the current path
                if (mainLink && mainLink.getAttribute('href')) {
                    const linkPath = new URL(mainLink.getAttribute('href'), window.location.origin).pathname;
                    if (currentPath === linkPath || (linkPath === '/admin' && (currentPath === '/admin/' || currentPath === '/admin'))) {
                        isActive = true;
                    }
                }

                // Check if any dropdown sub-item matches the current path
                const dropdownLinks = item.querySelectorAll('.dropdown-menu a');
                dropdownLinks.forEach(subLink => {
                    if (subLink.getAttribute('href')) {
                        const subLinkPath = new URL(subLink.getAttribute('href'), window.location.origin).pathname;
                        if (currentPath === subLinkPath) {
                            isActive = true;
                            // If a sub-item is active, ensure its parent dropdown is open
                            const dropdown = subLink.closest('.dropdown-menu');
                            if (dropdown && !dropdown.classList.contains('open')) {
                                toggleDropdown(dropdown.id);
                            }
                        }
                    }
                });

                if (isActive) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative') && !event.target.closest('[onclick*="toggleDropdown"]')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('open');
                    const chevron = document.getElementById(menu.id.replace('Dropdown', 'Chevron'));
                    if (chevron) {
                        chevron.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });
    </script>
    <?php if (isset($extraScripts)): ?>
        <?= $extraScripts ?>
    <?php endif; ?>
    
    <!-- Admin Enhancements -->
    <?php include __DIR__ . '/../../includes/admin-enhancements.php'; ?>
</body>
</html>
