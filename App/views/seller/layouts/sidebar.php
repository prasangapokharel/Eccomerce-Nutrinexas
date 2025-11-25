<?php
$currentPage = $page ?? 'dashboard';
?>
<aside id="sidebar" class="sidebar bg-gradient-to-b from-primary to-primary-dark text-white shadow-2xl custom-scroll">
    <div class="p-6 flex flex-col h-full">
        <!-- Logo Section -->
        <div class="flex items-center justify-between mb-8 pb-6 border-b border-white/20">
            <a href="<?= \App\Core\View::url('seller/dashboard') ?>" class="flex items-center space-x-3 min-w-0">
                <div class="sidebar-text min-w-0">
                    <h1 class="text-xl font-bold truncate">Nutri Nexus</h1>
                    <p class="text-xs text-white/70">Seller Panel</p>
                </div>
            </a>
            <button id="toggleSidebar" class="text-white/80 hover:text-white lg:block hidden p-2 hover:bg-white/10 flex-shrink-0">
                <i class="fas fa-bars text-sm"></i>
            </button>
        </div>
        
        <!-- Navigation Menu -->
        <nav class="flex-1 space-y-2">
            <!-- Dashboard -->
            <a href="<?= \App\Core\View::url('seller/dashboard') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                <i class="fas fa-home w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Dashboard</span>
            </a>
            
            <!-- Products -->
            <a href="<?= \App\Core\View::url('seller/products') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'products' ? 'active' : '' ?>" title="Products">
                <i class="fas fa-box w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Products</span>
            </a>
            
            <!-- Orders -->
            <a href="<?= \App\Core\View::url('seller/orders') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'orders' ? 'active' : '' ?>" title="Orders">
                <i class="fas fa-shopping-cart w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Orders</span>
            </a>
            
            <!-- Inventory -->
            <a href="<?= \App\Core\View::url('seller/inventory') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'inventory' ? 'active' : '' ?>" title="Inventory">
                <i class="fas fa-warehouse w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Inventory</span>
            </a>
            
            <!-- Stock Movement -->
            <a href="<?= \App\Core\View::url('seller/stock-movement') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'stock-movement' ? 'active' : '' ?>" title="Stock Movement">
                <i class="fas fa-exchange-alt w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Stock Movement</span>
            </a>
            
            <!-- Customers -->
            <a href="<?= \App\Core\View::url('seller/customers') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'customers' ? 'active' : '' ?>" title="Customers">
                <i class="fas fa-users w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Customers</span>
            </a>
            
            <!-- Analytics -->
            <a href="<?= \App\Core\View::url('seller/analytics') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'analytics' ? 'active' : '' ?>" title="Analytics">
                <i class="fas fa-chart-line w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Analytics</span>
            </a>
            
            <!-- Reports -->
            <a href="<?= \App\Core\View::url('seller/reports') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'reports' ? 'active' : '' ?>" title="Reports">
                <i class="fas fa-file-alt w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Reports</span>
            </a>
            
            <!-- Marketing -->
            <a href="<?= \App\Core\View::url('seller/marketing') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'marketing' ? 'active' : '' ?>" title="Marketing">
                <i class="fas fa-bullhorn w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Marketing</span>
            </a>
            
            <!-- Ads -->
            <a href="<?= \App\Core\View::url('seller/ads') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'ads' ? 'active' : '' ?>" title="Ads">
                <i class="fas fa-ad w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Ads</span>
            </a>
            
            <!-- Wallet -->
            <a href="<?= \App\Core\View::url('seller/wallet') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'wallet' ? 'active' : '' ?>" title="Wallet">
                <i class="fas fa-wallet w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Wallet</span>
            </a>
            
            <!-- Reviews -->
            <a href="<?= \App\Core\View::url('seller/reviews') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'reviews' ? 'active' : '' ?>" title="Reviews">
                <i class="fas fa-star w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Reviews</span>
            </a>
            
            <!-- Support -->
            <a href="<?= \App\Core\View::url('seller/support') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'support' ? 'active' : '' ?>" title="Support">
                <i class="fas fa-headset w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Support</span>
            </a>
            
            <!-- Cancellations -->
            <a href="<?= \App\Core\View::url('seller/cancellations') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'cancellations' ? 'active' : '' ?>" title="Cancellations">
                <i class="fas fa-times-circle w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Cancellations</span>
            </a>
        </nav>
        
        <!-- Bottom Section -->
        <div class="mt-auto pt-6 border-t border-white/20 space-y-2">
            <a href="<?= \App\Core\View::url('seller/notifications') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'notifications' ? 'active' : '' ?>" title="Notifications">
                <i class="fas fa-bell w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Notifications</span>
                <?php
                $unreadCount = 0;
                try {
                    $db = \App\Core\Database::getInstance();
                    $sellerId = \App\Core\Session::get('seller_id');
                    if ($sellerId) {
                        $result = $db->query("SELECT COUNT(*) as count FROM seller_notifications WHERE seller_id = ? AND is_read = 0", [$sellerId])->single();
                        $unreadCount = $result['count'] ?? 0;
                    }
                } catch (Exception $e) {
                    // Ignore errors
                }
                if ($unreadCount > 0):
                ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= \App\Core\View::url('seller/settings') ?>" class="nav-item flex items-center p-3 <?= $currentPage === 'settings' ? 'active' : '' ?>" title="Settings">
                <i class="fas fa-cog w-5 text-accent flex-shrink-0"></i>
                <span class="ml-3 sidebar-text font-medium">Settings</span>
            </a>
            
            <!-- Company Logo & Name -->
            <?php
            $sellerModel = new \App\Models\Seller();
            $sellerId = \App\Core\Session::get('seller_id');
            $seller = $sellerModel->find($sellerId);
            $companyName = $seller['company_name'] ?? $seller['name'] ?? 'My Company';
            $companyLogo = $seller['logo_url'] ?? null;
            ?>
            <div class="pt-4 mt-4 border-t border-white/20">
                <div class="flex items-center space-x-3 p-3 rounded-lg bg-white/5 hover:bg-white/10 transition-colors">
                    <?php if ($companyLogo): ?>
                        <img src="<?= htmlspecialchars($companyLogo) ?>" 
                             alt="<?= htmlspecialchars($companyName) ?>"
                             class="w-10 h-10 rounded-lg object-cover border border-white/20 flex-shrink-0"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php endif; ?>
                    <div class="w-10 h-10 rounded-lg bg-primary/80 text-white flex items-center justify-center text-sm font-semibold flex-shrink-0 <?= $companyLogo ? 'hidden' : '' ?>">
                        <?= strtoupper(substr($companyName, 0, 2)) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate sidebar-text"><?= htmlspecialchars($companyName) ?></p>
                        <p class="text-xs text-white/70 truncate sidebar-text">Seller Account</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>

