<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\SellerWallet;

class AdActivationService
{
    private $db;
    private $adModel;
    private $walletModel;
    private $validationService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
        $this->walletModel = new SellerWallet();
        $this->validationService = new AdValidationService();
    }

    /**
     * Activate ad - no balance locking, just activate
     * Balance will be charged on each click
     * 
     * @param int $adId
     * @return array ['success' => bool, 'message' => string]
     */
    public function activateAd($adId)
    {
        $ad = $this->adModel->find($adId);
        
        if (!$ad) {
            return ['success' => false, 'message' => 'Ad not found'];
        }

        // Validate before activation
        $validation = $this->validationService->validateBeforeActivation($adId);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(', ', $validation['errors'])];
        }

        try {
            $this->db->beginTransaction();

            // Simply activate the ad - no balance locking
            // Balance will be checked and charged on each click
            $this->db->query(
                "UPDATE ads SET 
                    status = 'active',
                    remaining_clicks = total_clicks,
                    current_day_spent = 0,
                    current_daily_spend = 0,
                    last_spend_reset_date = CURDATE(),
                    auto_paused = 0,
                    updated_at = NOW()
                 WHERE id = ?",
                [$adId]
            )->execute();

            $this->db->commit();

            return ['success' => true, 'message' => 'Ad activated successfully. You will be charged on each click.'];

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("AdActivationService: Error activating ad #{$adId}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error activating ad: ' . $e->getMessage()];
        }
    }
}
