<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\SellerWallet;

class RealTimeAdBillingService
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
     * Check if ad can be shown (has enough wallet balance)
     * 
     * @param int $adId
     * @return array ['can_show' => bool, 'reason' => string, 'balance' => float]
     */
    public function canShowAd($adId)
    {
        $ad = $this->adModel->find($adId);
        
        if (!$ad) {
            return ['can_show' => false, 'reason' => 'Ad not found', 'balance' => 0];
        }

        // Check if ad is active and not auto-paused
        if ($ad['status'] !== 'active' || $ad['auto_paused'] == 1) {
            return ['can_show' => false, 'reason' => 'Ad is not active', 'balance' => 0];
        }

        // Get seller wallet
        $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
        $balance = (float)($wallet['balance'] ?? 0);

        // Get billing type first
        $billingType = $ad['billing_type'] ?? 'daily_budget';
        
        // Reset daily spend if it's a new day (only for daily_budget)
        if ($billingType === 'daily_budget') {
            $this->resetDailySpendIfNeeded($ad);
            // Reload ad to get updated current_daily_spend after reset
            $ad = $this->adModel->find($adId);
        }

        // Check based on billing type
        if ($billingType === 'daily_budget') {
            $dailyBudget = (float)($ad['daily_budget'] ?? 0);
            
            if ($dailyBudget <= 0) {
                return ['can_show' => false, 'reason' => 'Daily budget not set', 'balance' => $balance];
            }
            
            $currentSpend = (float)($ad['current_daily_spend'] ?? 0);
            
            // Check if daily budget is exhausted
            if ($currentSpend >= $dailyBudget) {
                $this->autoPauseAd($adId, 'Daily budget exhausted');
                return ['can_show' => false, 'reason' => 'Daily budget exhausted', 'balance' => $balance];
            }
            
            // Check if wallet has enough balance for at least one impression
            // Estimate cost per impression (daily_budget / expected impressions per day)
            // For safety, check if balance >= 1% of daily budget
            $minBalance = max(0.01, $dailyBudget * 0.01);
            
            if ($balance < $minBalance) {
                $this->autoPauseAd($adId, 'Insufficient wallet balance');
                return ['can_show' => false, 'reason' => 'Insufficient wallet balance', 'balance' => $balance];
            }
            
        } elseif ($billingType === 'per_click') {
            $perClickRate = (float)($ad['per_click_rate'] ?? 0);
            
            if ($perClickRate <= 0) {
                return ['can_show' => false, 'reason' => 'Per-click rate not set', 'balance' => $balance];
            }
            
            if ($balance < $perClickRate) {
                $this->autoPauseAd($adId, 'Insufficient wallet balance for click');
                return ['can_show' => false, 'reason' => 'Insufficient wallet balance', 'balance' => $balance];
            }
            
        } elseif ($billingType === 'per_impression') {
            $perImpressionRate = (float)($ad['per_impression_rate'] ?? 0);
            
            if ($perImpressionRate <= 0) {
                return ['can_show' => false, 'reason' => 'Per-impression rate not set', 'balance' => $balance];
            }
            
            if ($balance < $perImpressionRate) {
                $this->autoPauseAd($adId, 'Insufficient wallet balance for impression');
                return ['can_show' => false, 'reason' => 'Insufficient wallet balance', 'balance' => $balance];
            }
        }

        return ['can_show' => true, 'reason' => '', 'balance' => $balance];
    }

    /**
     * Charge for ad impression
     * 
     * @param int $adId
     * @return array ['success' => bool, 'charged' => float, 'message' => string]
     */
    public function chargeImpression($adId)
    {
        $ad = $this->adModel->find($adId);
        
        // Check if ad exists and is active
        if (!$ad || $ad['status'] !== 'active' || $ad['auto_paused'] == 1) {
            return ['success' => false, 'charged' => 0, 'message' => 'Ad is stopped or inactive - no charge'];
        }

        // Reset daily spend if needed
        $this->resetDailySpendIfNeeded($ad);

        $billingType = $ad['billing_type'] ?? 'daily_budget';
        $chargeAmount = 0;

        if ($billingType === 'daily_budget') {
            // For daily budget, charge is distributed across impressions
            // Calculate: daily_budget / expected_impressions_per_day
            // For simplicity, charge a small amount per impression (e.g., 0.01% of daily budget)
            $dailyBudget = (float)($ad['daily_budget'] ?? 0);
            $currentSpend = (float)($ad['current_daily_spend'] ?? 0);
            $remainingBudget = $dailyBudget - $currentSpend;
            
            // Charge minimum of 0.01 or 0.1% of daily budget per impression
            $chargeAmount = min(0.01, max(0.01, $dailyBudget * 0.001));
            
            // Don't exceed remaining budget
            if ($chargeAmount > $remainingBudget) {
                $chargeAmount = $remainingBudget;
            }
            
            // Check if this would exceed daily budget
            if ($currentSpend + $chargeAmount > $dailyBudget) {
                $this->autoPauseAd($adId, 'Daily budget exhausted');
                return ['success' => false, 'charged' => 0, 'message' => 'Daily budget exhausted'];
            }
            
        } elseif ($billingType === 'per_impression') {
            $chargeAmount = (float)($ad['per_impression_rate'] ?? 0);
        } else {
            // per_click - no charge on impression, only on click
            return ['success' => true, 'charged' => 0, 'message' => 'No charge for impression (per_click billing)'];
        }

        if ($chargeAmount <= 0) {
            return ['success' => true, 'charged' => 0, 'message' => 'No charge'];
        }

        // Check wallet balance
        $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
        $balance = (float)($wallet['balance'] ?? 0);

        if ($balance < $chargeAmount) {
            $this->autoPauseAd($adId, 'Insufficient wallet balance');
            return ['success' => false, 'charged' => 0, 'message' => 'Insufficient wallet balance'];
        }

        // Deduct from wallet
        try {
            $this->db->beginTransaction();

            $newBalance = $balance - $chargeAmount;
            
            // Update wallet
            $this->db->query(
                "UPDATE seller_wallet SET balance = ?, updated_at = NOW() WHERE seller_id = ?",
                [$newBalance, $ad['seller_id']]
            )->execute();

            // Record transaction
            $this->walletModel->addTransaction([
                'seller_id' => $ad['seller_id'],
                'type' => 'debit',
                'amount' => $chargeAmount,
                'description' => "Ad #{$adId} - Impression charge",
                'balance_after' => $newBalance,
                'status' => 'completed'
            ]);

            // Update ad daily spend (for daily_budget)
            if ($billingType === 'daily_budget') {
                $newDailySpend = $currentSpend + $chargeAmount;
                $this->db->query(
                    "UPDATE ads SET current_daily_spend = ? WHERE id = ?",
                    [$newDailySpend, $adId]
                )->execute();

                // Check if daily budget is exhausted
                if ($newDailySpend >= $dailyBudget) {
                    $this->autoPauseAd($adId, 'Daily budget exhausted');
                }
            }

            $this->db->commit();

            return ['success' => true, 'charged' => $chargeAmount, 'message' => 'Charged successfully'];

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("RealTimeAdBillingService: Error charging impression: " . $e->getMessage());
            return ['success' => false, 'charged' => 0, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Get count of unique ads that this IP has clicked today
     * This is used to enforce the max 10 ads per IP per day limit
     * 
     * @param string $ipAddress IP address
     * @return int Count of unique ads clicked today
     */
    private function getUniqueAdsClickedByIp($ipAddress)
    {
        $today = date('Y-m-d');
        
        $result = $this->db->query(
            "SELECT COUNT(DISTINCT ads_id) as clicked_count
             FROM ads_click_logs
             WHERE ip_address = ?
             AND DATE(clicked_at) = ?",
            [$ipAddress, $today]
        )->single();
        
        return (int)($result['clicked_count'] ?? 0);
    }

    /**
     * Check if this IP has already clicked this specific ad today (previous click, not current)
     * Prevents duplicate charges for the same ad from same IP
     * 
     * @param int $adId Ad ID
     * @param string $ipAddress IP address
     * @return bool True if already clicked today (excluding current click)
     */
    private function hasIpClickedAdToday($adId, $ipAddress)
    {
        $today = date('Y-m-d');
        
        // Check if this IP already clicked this ad today
        // Since the current click is already logged, we check if there's more than 1 click
        // (meaning there was a previous click today)
        $result = $this->db->query(
            "SELECT COUNT(*) as count
             FROM ads_click_logs
             WHERE ads_id = ?
             AND ip_address = ?
             AND DATE(clicked_at) = ?",
            [$adId, $ipAddress, $today]
        )->single();
        
        $clickCount = (int)($result['count'] ?? 0);
        
        // If count > 1, it means there was a previous click today (current click is already logged)
        // If count == 1, it's the first click for this ad today, so we allow charging
        return $clickCount > 1;
    }

    /**
     * Charge for ad click
     * 
     * @param int $adId
     * @param string|null $ipAddress IP address for fraud detection (auto-detected if null)
     * @return array ['success' => bool, 'charged' => float, 'message' => string]
     */
    public function chargeClick($adId, $ipAddress = null)
    {
        $ad = $this->adModel->find($adId);
        
        if (!$ad) {
            return ['success' => false, 'charged' => 0, 'message' => 'Ad not found'];
        }
        
        // Check if ad is active and not auto-paused
        if ($ad['status'] !== 'active' || $ad['auto_paused'] == 1) {
            return ['success' => false, 'charged' => 0, 'message' => 'Ad is stopped or inactive - no charge'];
        }

        $ipAddress = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $ipLimitEnabled = defined('ADS_IP_LIMIT') && constant('ADS_IP_LIMIT') === 'enable';

        if ($ipLimitEnabled) {
            // Check for duplicate/fraud clicks BEFORE charging
            $fraudService = new \App\Services\AdFraudDetectionService();
            $fraudCheck = $fraudService->checkRapidClickFraud($adId, $ipAddress);
            
            // If duplicate click or high fraud score, don't charge
            if ($fraudCheck['is_duplicate'] || ($fraudCheck['is_fraud'] && $fraudCheck['fraud_score'] >= 50)) {
                error_log("RealTimeAdBillingService: Click blocked (fraud/duplicate) for ad #{$adId} - IP: {$ipAddress} - Score: {$fraudCheck['fraud_score']}");
                return ['success' => false, 'charged' => 0, 'message' => 'Duplicate click or fraud detected - no charge'];
            }
        }

        // Check IP limit if enabled (max 10 ads per IP per day that can deduct balance)
        if ($ipLimitEnabled) {
            // Count unique ads this IP has clicked today
            // Note: Current click is already logged, so it's included in the count
            $uniqueAdsClicked = $this->getUniqueAdsClickedByIp($ipAddress);
            
            // If unique count > 10, limit exceeded (10 is the max, so > 10 means exceeded)
            if ($uniqueAdsClicked > 10) {
                error_log("RealTimeAdBillingService: IP limit exceeded for IP: {$ipAddress} - Clicked {$uniqueAdsClicked} unique ads today (max: 10)");
                return ['success' => false, 'charged' => 0, 'message' => 'IP limit reached - Maximum 10 ads per IP per day'];
            }
            
            // Check if this specific ad was already clicked by this IP today (previous click, not current)
            // This prevents charging multiple times for the same ad from same IP
            if ($this->hasIpClickedAdToday($adId, $ipAddress)) {
                error_log("RealTimeAdBillingService: Ad #{$adId} already clicked by IP: {$ipAddress} today - skipping charge");
                return ['success' => false, 'charged' => 0, 'message' => 'This ad already clicked from your IP today'];
            }
        }

        // Check remaining clicks
        $remainingClicks = (int)($ad['remaining_clicks'] ?? 0);
        if ($remainingClicks <= 0) {
            $this->autoPauseAd($adId, 'All clicks exhausted');
            return ['success' => false, 'charged' => 0, 'message' => 'All clicks exhausted'];
        }

        // Charge amount = min_cpc_rate (per_click_rate)
        $chargeAmount = (float)($ad['per_click_rate'] ?? 2.00);

        if ($chargeAmount <= 0) {
            return ['success' => true, 'charged' => 0, 'message' => 'No charge'];
        }

        // Check wallet balance
        $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
        $balance = (float)($wallet['balance'] ?? 0);

        if ($balance < $chargeAmount) {
            $this->autoPauseAd($adId, 'Insufficient wallet balance for click');
            return ['success' => false, 'charged' => 0, 'message' => 'Insufficient wallet balance'];
        }

        // Deduct from wallet
        try {
            $this->db->beginTransaction();

            $newBalance = $balance - $chargeAmount;
            
            // Update wallet
            $this->db->query(
                "UPDATE seller_wallet SET balance = ?, updated_at = NOW() WHERE seller_id = ?",
                [$newBalance, $ad['seller_id']]
            )->execute();

            // Record transaction
            $this->walletModel->addTransaction([
                'seller_id' => $ad['seller_id'],
                'type' => 'debit',
                'amount' => $chargeAmount,
                'description' => "Ad #{$adId} - Click charge",
                'balance_after' => $newBalance,
                'status' => 'completed'
            ]);

            // Decrement remaining_clicks
            $newRemainingClicks = $remainingClicks - 1;
            $this->db->query(
                "UPDATE ads SET remaining_clicks = ? WHERE id = ?",
                [$newRemainingClicks, $adId]
            )->execute();

            // If clicks exhausted, pause ad
            if ($newRemainingClicks <= 0) {
                $this->db->query(
                    "UPDATE ads SET status = 'inactive', auto_paused = 1, notes = CONCAT(COALESCE(notes, ''), ' | Auto-paused: All clicks exhausted') WHERE id = ?",
                    [$adId]
                )->execute();
            }

            // Log in ads_daily_spend_log
            $today = date('Y-m-d');
            $existingLog = $this->db->query(
                "SELECT id, amount, clicks_count FROM ads_daily_spend_log 
                 WHERE ads_id = ? AND spend_date = ?",
                [$adId, $today]
            )->single();

            if ($existingLog) {
                $newAmount = (float)$existingLog['amount'] + $chargeAmount;
                $newClicks = (int)$existingLog['clicks_count'] + 1;
                $this->db->query(
                    "UPDATE ads_daily_spend_log SET amount = ?, clicks_count = ? WHERE id = ?",
                    [$newAmount, $newClicks, $existingLog['id']]
                )->execute();
            } else {
                $this->db->query(
                    "INSERT INTO ads_daily_spend_log (ads_id, spend_date, amount, clicks_count) VALUES (?, ?, ?, 1)",
                    [$adId, $today, $chargeAmount]
                )->execute();
            }


            $this->db->commit();

            return ['success' => true, 'charged' => $chargeAmount, 'message' => 'Charged successfully'];

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("RealTimeAdBillingService: Error charging click: " . $e->getMessage());
            return ['success' => false, 'charged' => 0, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Pause ad when daily limit is reached and unlock unspent money
     */
    private function pauseAdForDailyLimit($adId, $ad)
    {
        try {
            $this->db->beginTransaction();

            // Update status to paused_daily_limit
            $this->db->query(
                "UPDATE ads SET 
                    status = 'paused_daily_limit',
                    auto_paused = 1,
                    notes = CONCAT(COALESCE(notes, ''), ' | Auto-paused: Daily budget limit reached'),
                    updated_at = NOW()
                 WHERE id = ?",
                [$adId]
            )->execute();

            // No balance to unlock - we don't lock balance anymore
            // Balance is charged directly on each click/impression

            $this->db->commit();
            error_log("RealTimeAdBillingService: Paused ad #{$adId} - Daily limit reached");

        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("RealTimeAdBillingService: Error pausing ad for daily limit: " . $e->getMessage());
        }
    }

    /**
     * Reset daily spend if it's a new day
     */
    private function resetDailySpendIfNeeded($ad)
    {
        $today = date('Y-m-d');
        $lastReset = $ad['last_spend_reset_date'] ?? null;

        if ($lastReset !== $today) {
            // Reset both current_daily_spend and current_day_spent
            $this->db->query(
                "UPDATE ads SET 
                    current_daily_spend = 0,
                    current_day_spent = 0,
                    last_spend_reset_date = ? 
                 WHERE id = ?",
                [$today, $ad['id']]
            )->execute();
        }
    }

    /**
     * Auto-pause ad when balance is low or budget exhausted
     */
    private function autoPauseAd($adId, $reason)
    {
        $this->db->query(
            "UPDATE ads SET auto_paused = 1, status = 'inactive', notes = CONCAT(COALESCE(notes, ''), ' | Auto-paused: ', ?) WHERE id = ?",
            [$reason, $adId]
        )->execute();
        
        error_log("RealTimeAdBillingService: Auto-paused ad #{$adId} - {$reason}");
    }

    /**
     * Resume ad (remove auto-pause)
     */
    public function resumeAd($adId)
    {
        $ad = $this->adModel->find($adId);
        
        if (!$ad) {
            return ['success' => false, 'message' => 'Ad not found'];
        }

        // Get billing type and check wallet
        $billingType = $ad['billing_type'] ?? 'daily_budget';
        $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
        $balance = (float)($wallet['balance'] ?? 0);

        // Check if wallet has sufficient balance based on billing type
        if ($billingType === 'daily_budget') {
            $dailyBudget = (float)($ad['daily_budget'] ?? 0);
            $minBalance = max(0.01, $dailyBudget * 0.01);
            if ($balance < $minBalance) {
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }
        } elseif ($billingType === 'per_click') {
            $perClickRate = (float)($ad['per_click_rate'] ?? 0);
            if ($balance < $perClickRate) {
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }
        } elseif ($billingType === 'per_impression') {
            $perImpressionRate = (float)($ad['per_impression_rate'] ?? 0);
            if ($balance < $perImpressionRate) {
                return ['success' => false, 'message' => 'Insufficient wallet balance'];
            }
        }

        // Resume ad (remove auto-pause and set to active)
        $this->db->query(
            "UPDATE ads SET auto_paused = 0, status = 'active' WHERE id = ?",
            [$adId]
        )->execute();

        return ['success' => true, 'message' => 'Ad resumed successfully'];
    }
}

