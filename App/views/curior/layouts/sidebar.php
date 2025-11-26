<?php
$currentPage = $page ?? 'dashboard';
?>
<aside id="sidebar" class="sidebar text-white shadow-2xl custom-scroll">
    <div class="p-6 flex flex-col h-full">
        <!-- Logo Section -->
        <div class="flex items-center justify-between mb-8 pb-6 border-b border-white/20">
            <a href="<?= \App\Core\View::url('curior/dashboard') ?>" class="flex items-center space-x-3 min-w-0">
                <div class="sidebar-text min-w-0">
                    <h1 class="text-xl font-bold truncate">Nutri Nexus</h1>
                    <p class="text-xs text-white/70">Courier Panel</p>
                </div>
            </a>
            <button id="toggleSidebar" class="text-white/80 hover:text-white lg:block hidden p-2 hover:bg-white/10 flex-shrink-0">
                <i class="fas fa-bars text-sm"></i>
            </button>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 space-y-2">
            <!-- Dashboard -->
            <a href="<?= \App\Core\View::url('curior/dashboard') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                <i class="fas fa-home w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Dashboard</span>
            </a>
            
            <!-- Orders -->
            <a href="<?= \App\Core\View::url('curior/orders') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'orders' ? 'active' : '' ?>" title="All Orders">
                <i class="fas fa-shopping-cart w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">All Orders</span>
            </a>
            
            <!-- Pickup Management -->
            <a href="<?= \App\Core\View::url('curior/pickup') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'pickup' ? 'active' : '' ?>" title="Pickup Management">
                <i class="fas fa-box-open w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Pickup</span>
            </a>
            
            <!-- Delivery Management -->
            <a href="<?= \App\Core\View::url('curior/delivery') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'delivery' ? 'active' : '' ?>" title="Delivery Management">
                <i class="fas fa-truck w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Delivery</span>
            </a>
            
            <!-- Returns -->
            <a href="<?= \App\Core\View::url('curior/returns') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'returns' ? 'active' : '' ?>" title="Returns & RTO">
                <i class="fas fa-undo w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Returns</span>
            </a>
            
            <!-- Settlement -->
            <a href="<?= \App\Core\View::url('curior/settlements') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'settlement' ? 'active' : '' ?>" title="COD Settlement">
                <i class="fas fa-money-bill-wave w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Settlements</span>
            </a>
            
            <!-- Performance -->
            <a href="<?= \App\Core\View::url('curior/performance') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'performance' ? 'active' : '' ?>" title="Performance">
                <i class="fas fa-chart-line w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Performance</span>
            </a>
            
            <!-- History -->
            <a href="<?= \App\Core\View::url('curior/history') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'history' ? 'active' : '' ?>" title="History & Logs">
                <i class="fas fa-history w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">History</span>
            </a>
            
            <!-- Notifications -->
            <a href="<?= \App\Core\View::url('curior/notifications') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'notifications' ? 'active' : '' ?>" title="Notifications">
                <i class="fas fa-bell w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Notifications</span>
            </a>
            
            <!-- Profile -->
            <a href="<?= \App\Core\View::url('curior/profile') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'profile' ? 'active' : '' ?>" title="Profile">
                <i class="fas fa-user w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Profile</span>
            </a>
            
            <!-- Support -->
            <a href="<?= \App\Core\View::url('curior/support') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'support' ? 'active' : '' ?>" title="Help & Support">
                <i class="fas fa-headset w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Support</span>
            </a>
        </nav>
        
        <!-- Bottom Section -->
        <div class="mt-auto pt-6 border-t border-white/20 space-y-2">
            <a href="<?= \App\Core\View::url('curior/logout') ?>" class="nav-item flex items-center p-3" title="Logout">
                <i class="fas fa-sign-out-alt w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Logout</span>
            </a>
            
            <!-- Courier Info -->
            <?php
            $curiorName = \App\Core\Session::get('curior_name') ?? 'Courier';
            ?>
            <div class="pt-4 mt-4 border-t border-white/20">
                <div class="flex items-center space-x-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-primary/80 text-white flex items-center justify-center text-sm font-semibold flex-shrink-0">
                        <?= strtoupper(substr($curiorName, 0, 2)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate sidebar-text"><?= htmlspecialchars($curiorName) ?></p>
                        <p class="text-xs text-white/70 truncate sidebar-text">Courier Account</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
