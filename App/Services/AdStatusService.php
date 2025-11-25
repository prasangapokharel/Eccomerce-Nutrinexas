<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;

/**
 * Service to update ad statuses automatically based on dates
 * Should be called via cron job or scheduled task
 */
class AdStatusService
{
    private $db;
    private $adModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
    }

    /**
     * Update all ad statuses based on dates
     * Call this method periodically (e.g., via cron job every hour)
     */
    public function updateAllAdStatuses()
    {
        try {
            $today = date('Y-m-d');
            
            // Update ads that should be scheduled (start_date > today)
            $this->db->query(
                "UPDATE ads 
                 SET status = 'inactive', updated_at = NOW()
                 WHERE status = 'active' 
                 AND start_date > ?",
                [$today]
            )->execute();
            
            // Update ads that should be expired (end_date < today)
            $this->db->query(
                "UPDATE ads 
                 SET status = 'expired', updated_at = NOW()
                 WHERE status IN ('active', 'inactive') 
                 AND end_date < ?",
                [$today]
            )->execute();
            
            // Activate ads that are scheduled and should now be active (start_date <= today AND end_date >= today)
            // Only if payment is paid
            $this->db->query(
                "UPDATE ads a
                 INNER JOIN ads_payments ap ON a.id = ap.ads_id
                 SET a.status = 'active', a.updated_at = NOW()
                 WHERE a.status = 'inactive'
                 AND a.start_date <= ?
                 AND a.end_date >= ?
                 AND ap.payment_status = 'paid'",
                [$today, $today]
            )->execute();
            
            return true;
        } catch (\Exception $e) {
            error_log('AdStatusService Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get scheduled ads (start_date > today)
     */
    public function getScheduledAds()
    {
        $today = date('Y-m-d');
        return $this->db->query(
            "SELECT * FROM ads 
             WHERE start_date > ? 
             AND status != 'expired'
             ORDER BY start_date ASC",
            [$today]
        )->all();
    }

    /**
     * Get expired ads (end_date < today)
     */
    public function getExpiredAds()
    {
        $today = date('Y-m-d');
        return $this->db->query(
            "SELECT * FROM ads 
             WHERE end_date < ? 
             AND status != 'expired'
             ORDER BY end_date DESC",
            [$today]
        )->all();
    }
}




