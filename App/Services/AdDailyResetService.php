<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\SellerWallet;

class AdDailyResetService
{
    private $db;
    private $adModel;
    private $walletModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
        $this->walletModel = new SellerWallet();
    }

    /**
     * Reset daily spend at midnight (for daily_budget billing type)
     * No balance locking - charges happen on each click/impression
     * 
     * @return array ['success' => bool, 'processed' => int, 'errors' => int]
     */
    public function resetDailySpendAndLockBudget()
    {
        $today = date('Y-m-d');
        $processed = 0;
        $errors = 0;

        // Get all active ads that need reset
        $ads = $this->db->query(
            "SELECT * FROM ads 
             WHERE status = 'active' 
             AND (last_spend_reset_date IS NULL OR last_spend_reset_date < ?)
             AND CURDATE() BETWEEN start_date AND end_date",
            [$today]
        )->all();

        foreach ($ads as $ad) {
            try {
                $this->db->beginTransaction();

                // Reset current_day_spent = 0
                $this->db->query(
                    "UPDATE ads SET 
                        current_day_spent = 0,
                        current_daily_spend = 0,
                        last_spend_reset_date = ?,
                        auto_paused = 0,
                        status = 'active',
                        updated_at = NOW()
                     WHERE id = ?",
                    [$today, $ad['id']]
                )->execute();

                // For clicks-based system, no balance locking needed
                // Balance is checked and charged on each click

                $this->db->commit();
                $processed++;

            } catch (\Exception $e) {
                $this->db->rollBack();
                error_log("AdDailyResetService: Error processing ad #{$ad['id']}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($ads)
        ];
    }

    /**
     * Process expired ads - mark as expired
     * No balance to release (no locking system)
     * 
     * @return array ['success' => bool, 'processed' => int, 'errors' => int]
     */
    public function processExpiredAds()
    {
        $today = date('Y-m-d');
        $processed = 0;
        $errors = 0;

        // Get ads that expired today
        $ads = $this->db->query(
            "SELECT * FROM ads 
             WHERE status != 'expired' 
             AND end_date < ?",
            [$today]
        )->all();

        foreach ($ads as $ad) {
            try {
                // Mark status as expired
                $this->db->query(
                    "UPDATE ads SET 
                        status = 'expired',
                        updated_at = NOW()
                     WHERE id = ?",
                    [$ad['id']]
                )->execute();

                $processed++;

            } catch (\Exception $e) {
                error_log("AdDailyResetService: Error processing expired ad #{$ad['id']}: " . $e->getMessage());
                $errors++;
            }
        }

        return [
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($ads)
        ];
    }
}
