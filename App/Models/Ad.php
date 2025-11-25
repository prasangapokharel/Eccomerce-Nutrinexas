<?php

namespace App\Models;

use App\Core\Model;

class Ad extends Model
{
    protected $table = 'ads';

    /**
     * Get all ads with related data
     */
    /**
     * Create new ad
     */
    public function create($data)
    {
        // Use parent Model's create method which handles all fields automatically
        return parent::create($data);
    }

    /**
     * Find ad by ID
     */
    public function find($id)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads WHERE id = ?",
            [$id]
        )->single();
    }

    /**
     * Delete ad
     */
    public function delete($id)
    {
        return $this->getDb()->query(
            "DELETE FROM ads WHERE id = ?",
            [$id]
        )->execute();
    }

    public function getAllWithDetails()
    {
        return $this->getDb()->query(
            "SELECT a.*, 
                    at.name as ad_type_name,
                    at.description as ad_type_description,
                    ac.duration_days,
                    ac.cost_amount,
                    s.name as seller_name,
                    s.company_name,
                    p.product_name,
                    p.slug as product_slug
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
             LEFT JOIN sellers s ON a.seller_id = s.id
             LEFT JOIN products p ON a.product_id = p.id
             ORDER BY a.created_at DESC"
        )->all();
    }

    /**
     * Get ads by seller ID
     */
    public function getBySellerId($sellerId)
    {
        return $this->getDb()->query(
            "SELECT a.*, 
                    at.name as ad_type_name,
                    ac.duration_days,
                    ac.cost_amount,
                    p.product_name,
                    p.slug as product_slug
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
             LEFT JOIN products p ON a.product_id = p.id
             WHERE a.seller_id = ?
             ORDER BY a.created_at DESC",
            [$sellerId]
        )->all();
    }

    /**
     * Get ad by ID with details
     */
    public function findWithDetails($id)
    {
        $ad = $this->getDb()->query(
            "SELECT a.*, 
                    at.name as ad_type_name,
                    at.description as ad_type_description,
                    s.name as seller_name,
                    s.company_name,
                    p.product_name,
                    p.slug as product_slug
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN sellers s ON a.seller_id = s.id
             LEFT JOIN products p ON a.product_id = p.id
             WHERE a.id = ?",
            [$id]
        )->single();
        
        // Calculate total cost for clicks-based system
        if ($ad) {
            $ad['total_cost'] = 0;
            if (isset($ad['per_click_rate']) && isset($ad['total_clicks'])) {
                $ad['total_cost'] = (float)$ad['per_click_rate'] * (int)$ad['total_clicks'];
            }
        }
        
        return $ad;
    }

    /**
     * Get active ads (only with paid payment, not suspended)
     */
    public function getActiveAds()
    {
        return $this->getDb()->query(
            "SELECT a.*, 
                    at.name as ad_type_name,
                    p.product_name,
                    p.slug as product_slug
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN products p ON a.product_id = p.id
             INNER JOIN ads_payments ap ON a.id = ap.ads_id
             WHERE a.status = 'active' 
             AND a.status != 'suspended'
             AND DATE(CURDATE()) >= DATE(a.start_date) 
             AND DATE(CURDATE()) <= DATE(a.end_date)
             AND ap.payment_status = 'paid'
             ORDER BY a.created_at DESC"
        )->all();
    }

    /**
     * Get active banner ads with bid-based display time
     * Higher bid = longer display time (bid amount determines display duration)
     * 
     * @param int $limit Maximum number of ads to return
     * @return array Active banner ads sorted by bid amount (highest first)
     */
    public function getActiveBannerAds($limit = 10)
    {
        $ads = $this->getDb()->query(
            "SELECT a.*, at.name as ad_type_name, ac.cost_amount as bid_amount
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             LEFT JOIN ads_costs ac ON a.ads_cost_id = ac.id
             LEFT JOIN ads_payments ap ON a.id = ap.ads_id
             WHERE a.status = 'active' 
             AND (a.auto_paused = 0 OR a.auto_paused IS NULL)
             AND a.status != 'suspended'
             AND at.name = 'banner_external'
             AND DATE(CURDATE()) >= DATE(a.start_date) 
             AND DATE(CURDATE()) <= DATE(a.end_date)
             AND (ap.payment_status = 'paid' OR ap.id IS NULL)
             AND a.banner_image IS NOT NULL
             AND a.banner_image != ''
             AND a.banner_link IS NOT NULL
             AND a.banner_link != ''
             ORDER BY ac.cost_amount DESC, a.created_at DESC
             LIMIT ?",
            [$limit]
        )->all();

        // Calculate display time based on bid amount
        // Formula: display_time_seconds = (bid_amount / 100) * 60
        // Example: रु 2000 = 20 minutes, रु 1000 = 10 minutes
        foreach ($ads as &$ad) {
            $bidAmount = (float)($ad['bid_amount'] ?? 0);
            // Display time in seconds: (bid / 100) * 60 = minutes * 60
            // Minimum 30 seconds, maximum 5 minutes per ad
            $displayTimeSeconds = max(30, min(300, ($bidAmount / 100) * 60));
            $ad['display_time_seconds'] = (int)$displayTimeSeconds;
            $ad['display_time_minutes'] = round($displayTimeSeconds / 60, 1);
        }
        unset($ad);

        return $ads;
    }

    /**
     * Get active product ads by product IDs (only with paid payment)
     */
    public function getActiveProductAdsByProductIds($productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        return $this->getDb()->query(
            "SELECT a.*, at.name as ad_type_name, a.product_id
             FROM ads a
             LEFT JOIN ads_types at ON a.ads_type_id = at.id
             INNER JOIN ads_payments ap ON a.id = ap.ads_id
             WHERE a.status = 'active' 
             AND at.name = 'product_internal'
             AND (a.approval_status = 'approved' OR a.approval_status IS NULL)
             AND a.product_id IN ($placeholders)
             AND DATE(CURDATE()) >= DATE(a.start_date) 
             AND DATE(CURDATE()) <= DATE(a.end_date)
             AND ap.payment_status = 'paid'",
            $productIds
        )->all();
    }

    /**
     * Update ad status
     */
    public function updateStatus($id, $status, $startDate = null, $endDate = null, $rejectionReason = null)
    {
        $query = "UPDATE ads SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($startDate !== null && $endDate !== null) {
            $query .= ", start_date = ?, end_date = ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        if ($rejectionReason !== null) {
            $query .= ", notes = CONCAT(COALESCE(notes, ''), '\n[REJECTED: ', NOW(), '] Reason: ', ?)";
            $params[] = $rejectionReason;
        }
        
        $query .= " WHERE id = ?";
        $params[] = $id;
        
        return $this->getDb()->query($query, $params)->execute();
    }

    /**
     * Check if ad can be activated based on dates
     * Returns true if start_date <= today AND end_date >= today
     */
    public function canBeActivated($id)
    {
        $ad = $this->find($id);
        if (!$ad) {
            return false;
        }
        
        $today = date('Y-m-d');
        $startDate = strtotime($ad['start_date']);
        $endDate = strtotime($ad['end_date']);
        $todayTimestamp = strtotime($today);
        
        return ($startDate <= $todayTimestamp && $endDate >= $todayTimestamp);
    }

    /**
     * Get ads by status
     */
    public function getByStatus($status)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads WHERE status = ? ORDER BY created_at DESC",
            [$status]
        )->all();
    }

    /**
     * Log ad click with fraud detection
     */
    public function logClick($adsId, $ipAddress)
    {
        $ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';
        $fraudService = null;

        if ($ipLimitEnabled) {
            // Check for fraud before logging
            $fraudService = new \App\Services\AdFraudDetectionService();
            $fraudCheck = $fraudService->checkRapidClickFraud($adsId, $ipAddress);
            
            // If duplicate click or fraud detected, don't log or charge
            if ($fraudCheck['is_duplicate'] || ($fraudCheck['is_fraud'] && $fraudCheck['fraud_score'] >= 50)) {
                // Log fraud attempt
                $securityLog = new \App\Services\SecurityLogService();
                $securityLog->logAdFraudAttempt($adsId, $ipAddress, $fraudCheck);
                
                error_log("Ad click blocked (fraud/duplicate): Ad #{$adsId} | IP: {$ipAddress} | Score: {$fraudCheck['fraud_score']}");
                return $fraudCheck;
            }
        } else {
            // When IP limit checks are disabled, return a safe default structure
            $fraudCheck = [
                'is_fraud' => false,
                'fraud_score' => 0,
                'indicators' => [],
                'click_count' => 0,
                'session_clicks' => 0,
                'total_clicks' => 0,
                'should_suspend' => false,
                'is_duplicate' => false
            ];
        }
        
        // Log the click (only if not duplicate/fraud)
        $this->getDb()->query(
            "INSERT INTO ads_click_logs (ads_id, ip_address, clicked_at) VALUES (?, ?, NOW())",
            [$adsId, $ipAddress]
        )->execute();

        // Update click count
        $this->getDb()->query(
            "UPDATE ads SET click = click + 1 WHERE id = ?",
            [$adsId]
        )->execute();
        
        // Auto-suspend if fraud threshold exceeded
        if ($fraudCheck['should_suspend'] && $fraudService) {
            $fraudService->autoSuspendAd($adsId, implode('; ', $fraudCheck['indicators']));
            error_log("Ad #{$adsId} auto-suspended due to rapid click fraud");
        } elseif ($fraudCheck['is_fraud']) {
            // Log fraud but allow click (lower severity)
            $securityLog = new \App\Services\SecurityLogService();
            $securityLog->logAdFraudAttempt($adsId, $ipAddress, $fraudCheck);
            error_log("Fraud detected on Ad #{$adsId}: " . implode('; ', $fraudCheck['indicators']));
        }
        
        return $fraudCheck;
    }

    /**
     * Log ad reach/view
     */
    public function logReach($adsId, $ipAddress)
    {
        // Check if already viewed today
        $existing = $this->getDb()->query(
            "SELECT id FROM ads_reach_logs 
             WHERE ads_id = ? AND ip_address = ? AND DATE(viewed_at) = CURDATE()
             LIMIT 1",
            [$adsId, $ipAddress]
        )->single();

        if (!$existing) {
            // Insert reach log
            $this->getDb()->query(
                "INSERT INTO ads_reach_logs (ads_id, ip_address) VALUES (?, ?)",
                [$adsId, $ipAddress]
            )->execute();

            // Update reach count
            $this->getDb()->query(
                "UPDATE ads SET reach = reach + 1 WHERE id = ?",
                [$adsId]
            )->execute();
        }
    }

    /**
     * Get ad statistics
     */
    public function getStatistics($adsId)
    {
        $stats = $this->getDb()->query(
            "SELECT 
                COUNT(DISTINCT arl.id) as total_reach,
                COUNT(DISTINCT acl.id) as total_clicks,
                COUNT(DISTINCT CASE WHEN DATE(arl.viewed_at) = CURDATE() THEN arl.id END) as today_reach,
                COUNT(DISTINCT CASE WHEN DATE(acl.clicked_at) = CURDATE() THEN acl.id END) as today_clicks
             FROM ads a
             LEFT JOIN ads_reach_logs arl ON a.id = arl.ads_id
             LEFT JOIN ads_click_logs acl ON a.id = acl.ads_id
             WHERE a.id = ?",
            [$adsId]
        )->single();

        $ad = $this->find($adsId);
        if ($ad) {
            $stats['reach'] = $ad['reach'];
            $stats['click'] = $ad['click'];
        }

        return $stats;
    }
}

