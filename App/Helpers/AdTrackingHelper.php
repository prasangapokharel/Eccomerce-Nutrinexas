<?php

namespace App\Helpers;

/**
 * Ad Tracking Helper
 * 
 * Clean, reusable helper for tracking ad reach and clicks
 * Use this helper wherever ads are displayed
 */
class AdTrackingHelper
{
    /**
     * Track ad reach/view (impression)
     * Call this when an ad is displayed/visible
     * This will also charge for the impression if using real-time billing
     * 
     * @param int $adId Ad ID
     * @param string|null $ipAddress IP address (auto-detected if null)
     * @return bool Success status
     */
    public static function trackReach($adId, $ipAddress = null)
    {
        if (!$adId) {
            return false;
        }

        try {
            $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            // Use SponsoredAdsService which handles billing
            $sponsoredService = new \App\Services\SponsoredAdsService();
            $sponsoredService->logAdView($adId, $ipAddress);
            return true;
        } catch (\Exception $e) {
            error_log('Ad reach tracking error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Track ad click
     * Call this when user clicks on an ad
     * This will also charge for the click if using per-click billing
     * 
     * Respects ADS_IP_LIMIT config:
     * - 'disable': No IP limit checking (development mode - all clicks allowed)
     * - 'enable': IP limit checking enabled (production mode - blocks duplicates/excessive clicks)
     * 
     * @param int $adId Ad ID
     * @param string|null $ipAddress IP address (auto-detected if null)
     * @return bool Success status
     */
    public static function trackClick($adId, $ipAddress = null)
    {
        if (!$adId) {
            return false;
        }

        try {
            $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
            
            // Check IP limit setting
            $ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';
            
            if (!$ipLimitEnabled) {
                // Development mode: No IP limit checking - allow all clicks
                error_log("AdTrackingHelper: IP limit disabled - allowing click for ad #{$adId} from IP: {$ipAddress}");
            }
            
            // Use SponsoredAdsService which handles billing and fraud detection
            $sponsoredService = new \App\Services\SponsoredAdsService();
            $sponsoredService->logAdClick($adId, $ipAddress);
            return true;
        } catch (\Exception $e) {
            error_log('Ad click tracking error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get JavaScript tracking code for reach
     * Returns JS function call string
     * 
     * @param int $adId Ad ID
     * @return string JavaScript code
     */
    public static function getReachTrackingJS($adId)
    {
        if (!$adId) {
            return '';
        }
        return "if(typeof trackAdReach === 'function') trackAdReach({$adId});";
    }

    /**
     * Get JavaScript tracking code for click
     * Returns JS function call string
     * 
     * @param int $adId Ad ID
     * @return string JavaScript code
     */
    public static function getClickTrackingJS($adId)
    {
        if (!$adId) {
            return '';
        }
        return "if(typeof trackAdClick === 'function') trackAdClick({$adId});";
    }
}

