<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Courier Dashboard' ?> - NutriNexus</title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/tailwind.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div id="mainContent" class="main-content flex flex-col overflow-x-hidden">
            <?php include __DIR__ . '/header.php'; ?>
            
            <!-- Global Notification Component -->
            <?php include ROOT_DIR . '/App/views/components/alert.php'; ?>
            
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <?php if (isset($content)): ?>
                    <?= $content ?>
                <?php endif; ?>
            </main>
            
            <?php include __DIR__ . '/footer.php'; ?>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <script>
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
            
            const sidebarCollapsed = getCookie('sidebar_collapsed') === 'true';
            
            if (sidebarCollapsed && window.innerWidth >= 1024) {
                sidebar.classList.add('sidebar-collapsed');
                mainContent.classList.add('collapsed');
            }
            
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
                    
                    if (isCollapsed) {
                        sidebar.classList.remove('sidebar-collapsed');
                        mainContent.classList.remove('collapsed');
                        setCookie('sidebar_collapsed', 'false');
                    } else {
                        sidebar.classList.add('sidebar-collapsed');
                        mainContent.classList.add('collapsed');
                        setCookie('sidebar_collapsed', 'true');
                    }
                });
            }
            
            if (mobileMenuButton) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('mobile-open');
                    mobileOverlay.classList.toggle('hidden');
                });
            }
            
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.add('hidden');
                });
            }
            
            window.addEventListener('resize', function() {
                if (window.innerWidth < 1024) {
                    sidebar.classList.remove('sidebar-collapsed');
                    mainContent.classList.remove('collapsed');
                } else {
                    const sidebarCollapsed = getCookie('sidebar_collapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('sidebar-collapsed');
                        mainContent.classList.add('collapsed');
                    }
                    sidebar.classList.remove('mobile-open');
                    mobileOverlay.classList.add('hidden');
                }
            });
        });


        $(document).ready(function() {
            if ($('.data-table').length) {
                $('.data-table').DataTable({
                    pageLength: 25,
                    order: [[0, 'desc']],
                    scrollX: true,
                    autoWidth: false
                });
            }
        });
    </script>
</body>
</html>
