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
            
            <!-- Flash Messages -->
            <?php 
            $flash = \App\Core\Session::getFlash();
            if ($flash && is_array($flash) && isset($flash['type']) && isset($flash['message'])): 
                $type = $flash['type'];
                $message = $flash['message'];
                $colors = [
                    'success' => ['bg' => 'bg-success-50', 'border' => 'border-success/30', 'text' => 'text-success-dark', 'icon' => 'text-success', 'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z'],
                    'error' => ['bg' => 'bg-error-50', 'border' => 'border-error/30', 'text' => 'text-error-dark', 'icon' => 'text-error', 'iconPath' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z'],
                    'warning' => ['bg' => 'bg-warning-50', 'border' => 'border-warning/30', 'text' => 'text-warning-dark', 'icon' => 'text-warning', 'iconPath' => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z'],
                    'info' => ['bg' => 'bg-primary-50', 'border' => 'border-primary/30', 'text' => 'text-primary-700', 'icon' => 'text-primary', 'iconPath' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z']
                ];
                $color = $colors[$type] ?? $colors['info'];
            ?>
                <div id="flash-message" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full mx-4 flash-message-container">
                    <div class="<?= $color['bg'] ?> border-2 <?= $color['border'] ?> rounded-lg p-4 shadow-xl flex items-start animate-slide-down">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 <?= $color['icon'] ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="<?= $color['iconPath'] ?>" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-semibold <?= $color['text'] ?>"><?= htmlspecialchars($message) ?></p>
                        </div>
                        <button onclick="closeFlashMessage()" class="ml-4 <?= $color['icon'] ?> hover:opacity-75 transition-opacity" aria-label="Close">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
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

        function closeFlashMessage() {
            const flashMsg = document.getElementById('flash-message');
            if (flashMsg) {
                flashMsg.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';
                flashMsg.style.opacity = '0';
                flashMsg.style.transform = 'translateX(-50%) translateY(-20px)';
                setTimeout(() => flashMsg.remove(), 300);
            }
        }

        setTimeout(function() {
            const flashMsg = document.getElementById('flash-message');
            if (flashMsg) {
                closeFlashMessage();
            }
        }, 5000);

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
