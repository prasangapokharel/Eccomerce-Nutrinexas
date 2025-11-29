<?php
/**
 * Global Notification Component
 * Shows success/error notifications at bottom-right corner
 * Auto-hides after 3 seconds with smooth animations
 */
$flash = \App\Core\Session::getFlash();
?>

<?php if ($flash && is_array($flash) && isset($flash['type']) && isset($flash['message'])): ?>
    <?php
    $type = $flash['type'];
    $message = htmlspecialchars($flash['message']);
    
    // Only show success and error notifications
    if (!in_array($type, ['success', 'error'])) {
        return;
    }
    
    // Set styles based on type
    $bgColor = $type === 'success' ? 'bg-primary' : 'bg-red-600';
    $textColor = 'text-white'; // Always white for visibility
    $icon = $type === 'success' 
        ? '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>';
    ?>
    
    <div id="globalNotification" 
         class="fixed bottom-4 right-4 z-[9999] <?= $bgColor ?> <?= $textColor ?> px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[280px] max-w-[90vw] sm:max-w-md animate-fade-in">
        <div class="flex-shrink-0">
            <?= $icon ?>
        </div>
        <p class="flex-1 text-sm font-medium"><?= $message ?></p>
        <button onclick="closeNotification()" 
                class="flex-shrink-0 ml-2 hover:opacity-70 transition-opacity"
                aria-label="Close notification">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(20px);
            }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
        
        .animate-fade-out {
            animation: fadeOut 0.3s ease-in;
        }
    </style>

    <script>
        (function() {
            'use strict';
            
            const notification = document.getElementById('globalNotification');
            if (!notification) return;
            
            let autoHideTimeout;
            let isClosing = false;
            
            function closeNotification() {
                if (isClosing) return;
                isClosing = true;
                
                clearTimeout(autoHideTimeout);
                notification.classList.remove('animate-fade-in');
                notification.classList.add('animate-fade-out');
                
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }
            
            // Auto-hide after 3 seconds
            autoHideTimeout = setTimeout(closeNotification, 3000);
            
            // Manual close button
            const closeBtn = notification.querySelector('button[onclick="closeNotification()"]');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeNotification);
            }
            
            // Make closeNotification available globally
            window.closeNotification = closeNotification;
        })();
    </script>
<?php endif; ?>

