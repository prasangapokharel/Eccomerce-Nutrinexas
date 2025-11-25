<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Ad;
use App\Models\AdPayment;
use App\Models\AdCost;
use App\Models\SellerWallet;

/**
 * Service to handle ad payment processing
 */
class AdPaymentService
{
    private $db;
    private $adModel;
    private $adPaymentModel;
    private $adCostModel;
    private $walletModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adModel = new Ad();
        $this->adPaymentModel = new AdPayment();
        $this->adCostModel = new AdCost();
        $this->walletModel = new SellerWallet();
    }

    /**
     * Process payment for an ad (deduct from wallet if wallet payment)
     */
    public function processPayment($adId, $paymentMethod = 'wallet')
    {
        $ad = $this->adModel->find($adId);
        if (!$ad) {
            throw new \Exception('Ad not found');
        }

        $payment = $this->adPaymentModel->getByAdId($adId);
        if (!$payment) {
            throw new \Exception('Payment record not found');
        }

        if ($payment['payment_status'] === 'paid') {
            return true; // Already paid
        }

        // Get cost amount
        $cost = $this->adCostModel->find($ad['ads_cost_id']);
        $amount = $cost['cost_amount'] ?? $payment['amount'];

        if ($paymentMethod === 'wallet') {
            // Check wallet balance
            $wallet = $this->walletModel->getWalletBySellerId($ad['seller_id']);
            
            if ($wallet['balance'] < $amount) {
                // Mark payment as failed
                $this->adPaymentModel->updateStatus($payment['id'], 'failed');
                throw new \Exception('Insufficient wallet balance');
            }

            // Deduct from wallet
            $newBalance = $wallet['balance'] - $amount;
            $this->db->query(
                "UPDATE seller_wallet 
                 SET balance = ?, updated_at = NOW() 
                 WHERE seller_id = ?",
                [$newBalance, $ad['seller_id']]
            )->execute();

            // Record wallet transaction
            $this->db->query(
                "INSERT INTO seller_wallet_transactions 
                 (seller_id, type, amount, description, balance_after, status, created_at) 
                 VALUES (?, 'debit', ?, ?, ?, 'completed', NOW())",
                [
                    $ad['seller_id'],
                    $amount,
                    "Ad #{$adId} payment - {$cost['duration_days']} days",
                    $newBalance
                ]
            )->execute();
        }

        // Update payment status to paid
        $this->adPaymentModel->updateStatus($payment['id'], 'paid');

        return true;
    }

    /**
     * Check if ad can be served (payment must be paid)
     */
    public function canServeAd($adId)
    {
        $payment = $this->adPaymentModel->getByAdId($adId);
        if (!$payment) {
            return false;
        }

        return $payment['payment_status'] === 'paid';
    }

    /**
     * Get payment details for an ad
     */
    public function getPaymentDetails($adId)
    {
        $ad = $this->adModel->findWithDetails($adId);
        if (!$ad) {
            return null;
        }

        $payment = $this->adPaymentModel->getByAdId($adId);
        $cost = $this->adCostModel->find($ad['ads_cost_id']);

        return [
            'ad' => $ad,
            'payment' => $payment,
            'cost' => $cost,
            'can_serve' => $payment ? $this->canServeAd($adId) : false
        ];
    }
}

