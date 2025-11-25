<?php
namespace App\Models;

use App\Core\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    
    // Transaction types
    const TYPE_REFERRAL_EARNING = 'referral_earning';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_WITHDRAWAL_REJECTED = 'withdrawal_rejected';
    const TYPE_REFERRAL_CANCELLED = 'referral_cancelled';
    
    /**
     * Get transactions by user ID
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByUserId($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->query($sql)->bind([$userId, $limit, $offset])->all();
    }
    
    /**
     * Get transaction count by user ID
     *
     * @param int $userId
     * @return int
     */
    public function getCountByUserId($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Create a new transaction
     *
     * @param array $data
     * @return int|bool
     */
    public function createTransaction($data)
    {
        return $this->create($data);
    }
    
    /**
     * Record a referral earning transaction
     *
     * @param int $userId
     * @param float $amount
     * @param int $referralEarningId
     * @param int $orderId
     * @return int|bool
     */
    public function recordReferralEarning($userId, $amount, $referralEarningId, $orderId)
    {
        $data = [
            'user_id' => $userId,
            'amount' => $amount,
            'type' => self::TYPE_REFERRAL_EARNING,
            'reference_id' => $referralEarningId,
            'reference_type' => 'referral_earning',
            'description' => 'Referral commission from order #' . $orderId,
            'balance_after' => $this->getCurrentBalance($userId) + $amount
        ];
        
        return $this->createTransaction($data);
    }
    
    /**
     * Record a withdrawal transaction
     *
     * @param int $userId
     * @param float $amount
     * @param int $withdrawalId
     * @return int|bool
     */
    public function recordWithdrawal($userId, $amount, $withdrawalId)
    {
        $data = [
            'user_id' => $userId,
            'amount' => -$amount, // Negative amount for withdrawal
            'type' => self::TYPE_WITHDRAWAL,
            'reference_id' => $withdrawalId,
            'reference_type' => 'withdrawal',
            'description' => 'Withdrawal request #' . $withdrawalId,
            'balance_after' => $this->getCurrentBalance($userId) - $amount
        ];
        
        return $this->createTransaction($data);
    }
    
    /**
     * Record a withdrawal rejection transaction
     *
     * @param int $userId
     * @param float $amount
     * @param int $withdrawalId
     * @return int|bool
     */
    public function recordWithdrawalRejected($userId, $amount, $withdrawalId)
    {
        $data = [
            'user_id' => $userId,
            'amount' => $amount, // Positive amount for rejected withdrawal (refund)
            'type' => self::TYPE_WITHDRAWAL_REJECTED,
            'reference_id' => $withdrawalId,
            'reference_type' => 'withdrawal',
            'description' => 'Withdrawal request #' . $withdrawalId . ' rejected',
            'balance_after' => $this->getCurrentBalance($userId) + $amount
        ];
        
        return $this->createTransaction($data);
    }
    
    /**
     * Record a cancelled referral earning transaction
     *
     * @param int $userId
     * @param float $amount
     * @param int $referralEarningId
     * @param int $orderId
     * @return int|bool
     */
    public function recordReferralCancelled($userId, $amount, $referralEarningId, $orderId)
    {
        $data = [
            'user_id' => $userId,
            'amount' => -$amount, // Negative amount for cancelled earning
            'type' => self::TYPE_REFERRAL_CANCELLED,
            'reference_id' => $referralEarningId,
            'reference_type' => 'referral_earning',
            'description' => 'Referral commission cancelled for order #' . $orderId,
            'balance_after' => $this->getCurrentBalance($userId) - $amount
        ];
        
        return $this->createTransaction($data);
    }
    
    /**
     * Get current balance for a user
     *
     * @param int $userId
     * @return float
     */
    private function getCurrentBalance($userId)
    {
        $sql = "SELECT balance_after FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC, id DESC 
                LIMIT 1";
        
        $result = $this->db->query($sql)->bind([$userId])->single();
        
        if ($result) {
            return (float)$result['balance_after'];
        }
        
        // If no transactions yet, get balance from user table
        $userModel = new User();
        $user = $userModel->find($userId);
        return $user ? (float)($user['referral_earnings'] ?? 0) : 0;
    }
}
