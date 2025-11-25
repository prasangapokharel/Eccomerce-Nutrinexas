<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;

/**
 * Service to detect and handle ad fraud (rapid clicks, suspicious activity)
 */
class AdFraudDetectionService
{
    private $db;
    private $adModel;
    
    // Fraud detection thresholds
    private $rapidClickThreshold = 10; // clicks per minute from same IP (rapid-fire = fraud)
    private $rapidClickWindow = 60; // seconds
    private $fraudSuspensionThreshold = 50; // rapid clicks before auto-suspension
    private $sessionClickLimit = 5; // max clicks per session per ad
    private $uniqueIpCheck = true; // track unique IPs only
    private $maxClicksPerHourPerIp = 3; // Maximum valid clicks per hour from same IP (like Google/Facebook)
    private $duplicateClickWindow = 60; // minutes - check for duplicates within this window
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
    }
    
    /**
     * Check for rapid click fraud on an ad
     * Prevents duplicate clicks from same IP/session
     * 
     * @param int $adId Ad ID
     * @param string $ipAddress IP address of the click
     * @return array Fraud detection result
     */
    public function checkRapidClickFraud($adId, $ipAddress = null)
    {
        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $sessionId = session_id() ?? '';
        
        // Check if IP limit checking is disabled (for development)
        $ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';
        
        // If IP limit is disabled, allow all clicks (development mode)
        if (!$ipLimitEnabled) {
            return [
                'is_fraud' => false,
                'fraud_score' => 0,
                'indicators' => [],
                'click_count' => 0,
                'total_clicks' => 0,
                'should_suspend' => false,
                'is_duplicate' => false
            ];
        }
        
        // IP limit is enabled - check for fraud/duplicates
        // Check clicks from same IP in last hour
        $clicksLastHour = $this->db->query(
            "SELECT COUNT(*) as click_count
             FROM ads_click_logs
             WHERE ads_id = ?
             AND ip_address = ?
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$adId, $ipAddress]
        )->single();
        
        $clicksInLastHour = (int)($clicksLastHour['click_count'] ?? 0);
        
        // Allow up to maxClicksPerHourPerIp (e.g., 3) clicks per hour from same IP (valid clicks)
        // Block if exceeds limit (likely fraud/bot)
        if ($clicksInLastHour >= $this->maxClicksPerHourPerIp) {
            return [
                'is_fraud' => true,
                'fraud_score' => 80,
                'indicators' => ["Exceeded click limit: {$clicksInLastHour} clicks from same IP in last hour (limit: {$this->maxClicksPerHourPerIp})"],
                'click_count' => $clicksInLastHour,
                'total_clicks' => 0,
                'should_suspend' => false,
                'is_duplicate' => false // Not duplicate, but exceeds limit
            ];
        }
        
        // Check for rapid-fire clicks (same IP clicking very quickly = bot/fraud)
        $recentRapidClick = $this->db->query(
            "SELECT id, clicked_at
             FROM ads_click_logs
             WHERE ads_id = ?
             AND ip_address = ?
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
             ORDER BY clicked_at DESC
             LIMIT 1",
            [$adId, $ipAddress]
        )->single();
        
        // If same IP clicked within 30 seconds, it's rapid-fire (likely bot/fraud)
        if ($recentRapidClick) {
            return [
                'is_fraud' => true,
                'fraud_score' => 100,
                'indicators' => ['Rapid-fire click from same IP within 30 seconds - likely bot/fraud'],
                'click_count' => $clicksInLastHour + 1,
                'total_clicks' => 0,
                'should_suspend' => false,
                'is_duplicate' => true
            ];
        }
        
        // Count clicks in the last minute from same IP
        $recentClicks = $this->db->query(
            "SELECT COUNT(*) as click_count
             FROM ads_click_logs
             WHERE ads_id = ?
             AND ip_address = ?
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$adId, $ipAddress, $this->rapidClickWindow]
        )->single();
        
        $clickCount = (int)($recentClicks['click_count'] ?? 0);
        
        // Check session-based clicks (if session tracking available)
        $sessionClicks = 0;
        if ($sessionId) {
            $sessionClicksResult = $this->db->query(
                "SELECT COUNT(*) as count
                 FROM ads_click_logs
                 WHERE ads_id = ?
                 AND ip_address = ?
                 AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
                [$adId, $ipAddress]
            )->single();
            $sessionClicks = (int)($sessionClicksResult['count'] ?? 0);
        }
        
        // Check total rapid clicks in last hour
        $totalRapidClicks = $this->db->query(
            "SELECT ip_address, COUNT(*) as total_count
             FROM ads_click_logs
             WHERE ads_id = ?
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY ip_address
             HAVING COUNT(*) >= ?",
            [$adId, $this->rapidClickThreshold]
        )->all();
        
        $totalRapidCount = count($totalRapidClicks);
        
        $isFraud = false;
        $fraudScore = 0;
        $indicators = [];
        
        // Check rapid clicks from same IP
        if ($clickCount >= $this->rapidClickThreshold) {
            $isFraud = true;
            $fraudScore += 30;
            $indicators[] = "Rapid clicks from same IP: {$clickCount} clicks in {$this->rapidClickWindow} seconds";
        }
        
        // Check session-based limit
        if ($sessionClicks >= $this->sessionClickLimit) {
            $isFraud = true;
            $fraudScore += 40;
            $indicators[] = "Session click limit exceeded: {$sessionClicks} clicks from same session (limit: {$this->sessionClickLimit})";
        }
        
        // Check multiple IPs showing rapid patterns
        if ($totalRapidCount > 0) {
            $fraudScore += 20;
            $indicators[] = "Multiple IPs showing rapid click patterns: {$totalRapidCount} IPs";
        }
        
        // Check if ad should be auto-suspended
        $totalSuspiciousClicks = $this->db->query(
            "SELECT COUNT(*) as total
             FROM ads_click_logs
             WHERE ads_id = ?
             AND clicked_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$adId]
        )->single();
        
        $totalClicks = (int)($totalSuspiciousClicks['total'] ?? 0);
        
        if ($totalClicks >= $this->fraudSuspensionThreshold) {
            $isFraud = true;
            $fraudScore += 50;
            $indicators[] = "Excessive clicks detected: {$totalClicks} clicks in last hour (threshold: {$this->fraudSuspensionThreshold})";
        }
        
        return [
            'is_fraud' => $isFraud,
            'fraud_score' => $fraudScore,
            'indicators' => $indicators,
            'click_count' => $clickCount,
            'session_clicks' => $sessionClicks,
            'total_clicks' => $totalClicks,
            'should_suspend' => $totalClicks >= $this->fraudSuspensionThreshold,
            'is_duplicate' => false
        ];
    }
    
    /**
     * Auto-suspend ad due to fraud detection
     * 
     * @param int $adId Ad ID
     * @param string $reason Suspension reason
     * @return bool Success
     */
    public function autoSuspendAd($adId, $reason = 'Fraudulent activity detected')
    {
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            return false;
        }
        
        // Update ad status to suspended
        $this->adModel->updateStatus($adId, 'suspended');
        
        // Add notes about suspension
        $notes = ($ad['notes'] ?? '') . "\n[SUSPENDED: " . date('Y-m-d H:i:s') . "] " . $reason;
        $this->db->query(
            "UPDATE ads SET notes = ? WHERE id = ?",
            [$notes, $adId]
        )->execute();
        
        error_log("Ad #{$adId} auto-suspended due to fraud: {$reason}");
        
        return true;
    }
}

