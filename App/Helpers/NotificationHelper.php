<?php

namespace App\Helpers;

use App\Core\Session;

/**
 * Unified Notification Helper for Admin Pages
 * Provides consistent alert/notification system across all admin CRUD operations
 */
class NotificationHelper
{
    /**
     * Show success notification
     */
    public static function success(string $message): void
    {
        Session::setFlash('success', $message);
    }

    /**
     * Show error notification
     */
    public static function error(string $message): void
    {
        Session::setFlash('error', $message);
    }

    /**
     * Show warning notification
     */
    public static function warning(string $message): void
    {
        Session::setFlash('warning', $message);
    }

    /**
     * Show info notification
     */
    public static function info(string $message): void
    {
        Session::setFlash('info', $message);
    }

    /**
     * Get flash message and clear it
     * Note: This will clear the flash, so only call once per request
     */
    public static function getFlash(string $type): ?string
    {
        // Check if flash exists without clearing it first
        if (!Session::hasFlash()) {
            return null;
        }
        
        // Get the flash (this clears it)
        $flash = Session::getFlash();
        
        if ($flash && is_array($flash) && isset($flash['type']) && $flash['type'] === $type) {
            return $flash['message'] ?? null;
        }
        
        // If type doesn't match, restore it for other checks
        if ($flash && is_array($flash)) {
            $_SESSION['flash'] = $flash;
        }
        
        return null;
    }

    /**
     * Check if flash message exists (without clearing)
     */
    public static function hasFlash(string $type): bool
    {
        if (!Session::hasFlash()) {
            return false;
        }
        
        $flash = $_SESSION['flash'] ?? null;
        if ($flash && is_array($flash) && isset($flash['type']) && $flash['type'] === $type) {
            return true;
        }
        
        return false;
    }

    /**
     * Render notification HTML
     * Also supports legacy FlashHelper compatibility
     */
    public static function render(): string
    {
        $html = '';
        
        // Get flash once (it clears after getting)
        $flash = Session::getFlash();
        
        if ($flash && is_array($flash) && isset($flash['type']) && isset($flash['message'])) {
            $type = $flash['type'];
            $message = $flash['message'];
            
            // Only render if it's a valid notification type
            if (in_array($type, ['success', 'error', 'warning', 'info'])) {
                $html .= self::renderAlert($type, $message);
            }
        }
        
        return $html;
    }

    /**
     * Legacy FlashHelper compatibility methods
     * @deprecated Use NotificationHelper methods instead
     */
    public static function getFlashMessage(string $type): string
    {
        $message = self::getFlash($type);
        return $message ? htmlspecialchars($message) : '';
    }

    public static function hasFlashMessage(string $type): bool
    {
        return self::hasFlash($type);
    }

    /**
     * Render single alert
     */
    private static function renderAlert(string $type, string $message): string
    {
        $colors = [
            'success' => 'bg-green-50 border-green-200 text-green-800',
            'error' => 'bg-red-50 border-red-200 text-red-800',
            'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
            'info' => 'bg-blue-50 border-blue-200 text-blue-800'
        ];
        
        $icons = [
            'success' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
            'error' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>',
            'warning' => '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
            'info' => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>'
        ];
        
        $color = $colors[$type] ?? $colors['info'];
        $icon = $icons[$type] ?? $icons['info'];
        
        return '
        <div class="fixed top-4 right-4 z-50 max-w-sm w-full" id="notification-' . $type . '">
            <div class="' . $color . ' border rounded-lg p-4 shadow-lg flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        ' . $icon . '
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">' . htmlspecialchars($message) . '</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <script>
            setTimeout(function() {
                const el = document.getElementById("notification-' . $type . '");
                if (el) {
                    el.style.transition = "opacity 0.5s";
                    el.style.opacity = "0";
                    setTimeout(function() { el.remove(); }, 500);
                }
            }, 5000);
        </script>';
    }
}

