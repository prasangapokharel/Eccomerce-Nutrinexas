<?php

namespace App\Models;

use App\Core\Model;

class AdPayment extends Model
{
    protected $table = 'ads_payments';

    /**
     * Get payments by seller
     */
    public function getBySellerId($sellerId)
    {
        return $this->getDb()->query(
            "SELECT ap.*, a.id as ad_id, a.status as ad_status
             FROM ads_payments ap
             LEFT JOIN ads a ON ap.ads_id = a.id
             WHERE ap.seller_id = ?
             ORDER BY ap.created_at DESC",
            [$sellerId]
        )->all();
    }

    /**
     * Get payment by ad ID
     */
    public function getByAdId($adsId)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads_payments WHERE ads_id = ? ORDER BY created_at DESC LIMIT 1",
            [$adsId]
        )->single();
    }

    /**
     * Create payment record
     */
    public function create($data)
    {
        $this->getDb()->query(
            "INSERT INTO ads_payments (seller_id, ads_id, amount, payment_method, payment_status) 
             VALUES (?, ?, ?, ?, ?)",
            [
                $data['seller_id'],
                $data['ads_id'],
                $data['amount'],
                $data['payment_method'] ?? 'wallet',
                $data['payment_status'] ?? 'pending'
            ]
        )->execute();
        return $this->getDb()->lastInsertId();
    }

    /**
     * Update payment status
     */
    public function updateStatus($id, $status)
    {
        return $this->getDb()->query(
            "UPDATE ads_payments SET payment_status = ?, updated_at = NOW() WHERE id = ?",
            [$status, $id]
        )->execute();
    }
}

