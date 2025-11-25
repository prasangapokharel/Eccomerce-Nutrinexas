<?php

namespace App\Helpers;

/**
 * Flash message helper (Legacy)
 * @deprecated Use NotificationHelper instead
 * This class is kept for backward compatibility
 * All methods delegate to NotificationHelper
 */
class FlashHelper
{
    /**
     * Get and display flash message safely
     * @deprecated Use NotificationHelper::getFlashMessage() instead
     */
    public static function getFlashMessage($type)
    {
        return NotificationHelper::getFlashMessage($type);
    }
    
    /**
     * Check if flash message exists
     * @deprecated Use NotificationHelper::hasFlash() instead
     */
    public static function hasFlash($type)
    {
        return NotificationHelper::hasFlash($type);
    }
    
    /**
     * Set flash message
     * @deprecated Use NotificationHelper::success()/error()/warning()/info() instead
     */
    public static function setFlash($type, $message)
    {
        switch ($type) {
            case 'success':
                NotificationHelper::success($message);
                break;
            case 'error':
                NotificationHelper::error($message);
                break;
            case 'warning':
                NotificationHelper::warning($message);
                break;
            case 'info':
                NotificationHelper::info($message);
                break;
            default:
                NotificationHelper::success($message);
        }
    }
}