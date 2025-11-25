<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Withdrawal extends Model
{
    protected $table = 'withdrawals';
    protected $primaryKey = 'id';
    
    private $cache;
    private $cachePrefix = 'withdrawal_';
    private $cacheTTL = 1800; // 30 minutes

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get pending withdrawal total for user
     *
     * @param int $userId
     * @return float
     */
    public function getPendingTotalByUserId($userId)
    {
        $cacheKey = $this->cachePrefix . 'pending_total_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND status = 'pending'";
            $result = $this->db->query($sql, [$userId])->single();
            return $result ? (float)$result['total'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get completed withdrawal total for user
     *
     * @param int $userId
     * @return float
     */
    public function getCompletedTotalByUserId($userId)
    {
        $cacheKey = $this->cachePrefix . 'completed_total_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND status = 'completed'";
            $result = $this->db->query($sql, [$userId])->single();
            return $result ? (float)$result['total'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get withdrawals by user ID
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByUserId($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$userId, $limit, $offset])->all();
    }

    /**
     * Get withdrawal by ID
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * Create new withdrawal
     *
     * @param array $data
     * @return int|false
     */
    public function createWithdrawal($data)
    {
        $sql = "INSERT INTO {$this->table} (user_id, amount, payment_method, account_details, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->db->query($sql, [
            $data['user_id'],
            $data['amount'],
            $data['payment_method'],
            $data['account_details'],
            $data['status'] ?? 'pending'
        ])->execute();

        if ($result) {
            $this->invalidateUserCache($data['user_id']);
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update withdrawal status
     *
     * @param int $id
     * @param string $status
     * @param string|null $adminNote
     * @return bool
     */
    public function updateStatus($id, $status, $adminNote = null)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($adminNote) {
            $sql .= ", admin_note = ?";
            $params[] = $adminNote;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        
        $result = $this->db->query($sql, $params)->execute();

        if ($result) {
            // Get user_id for cache invalidation
            $withdrawal = $this->find($id);
            if ($withdrawal) {
                $this->invalidateUserCache($withdrawal['user_id']);
            }
        }
        return $result;
    }

    /**
     * Get all withdrawals for admin
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllForAdmin($limit = 50, $offset = 0)
    {
        $sql = "SELECT w.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} w 
                LEFT JOIN users u ON w.user_id = u.id 
                ORDER BY w.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$limit, $offset])->all();
    }

    /**
     * Get withdrawals by status
     *
     * @param string $status
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByStatus($status, $limit = 50, $offset = 0)
    {
        $sql = "SELECT w.*, u.first_name, u.last_name, u.email 
                FROM {$this->table} w 
                LEFT JOIN users u ON w.user_id = u.id 
                WHERE w.status = ? 
                ORDER BY w.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$status, $limit, $offset])->all();
    }

    /**
     * Get withdrawal statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        $cacheKey = $this->cachePrefix . 'stats';
        return $this->cache->remember($cacheKey, function () {
            $stats = [];

            // Total pending
            $result = $this->db->query("SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE status = 'pending'")->single();
            $stats['pending_total'] = $result ? (float)$result['total'] : 0;

            // Total completed
            $result = $this->db->query("SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE status = 'completed'")->single();
            $stats['completed_total'] = $result ? (float)$result['total'] : 0;

            // Total rejected
            $result = $this->db->query("SELECT COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE status = 'rejected'")->single();
            $stats['rejected_total'] = $result ? (float)$result['total'] : 0;

            // Count by status
            $result = $this->db->query("SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status")->all();
            $stats['counts'] = [];
            foreach ($result as $row) {
                $stats['counts'][$row['status']] = (int)$row['count'];
            }

            return $stats;
        }, $this->cacheTTL);
    }

    /**
     * Get user withdrawal history with pagination
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserHistory($userId, $limit = 10, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$userId, $limit, $offset])->all();
    }

    /**
     * Get total withdrawal count for user
     *
     * @param int $userId
     * @return int
     */
    public function getUserWithdrawalCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?";
        $result = $this->db->query($sql, [$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get withdrawal with user details
     *
     * @param int $id
     * @return array|false
     */
    public function getWithUserDetails($id)
    {
        $sql = "SELECT w.*, u.first_name, u.last_name, u.email, u.phone, u.username 
                FROM {$this->table} w 
                LEFT JOIN users u ON w.user_id = u.id 
                WHERE w.id = ?";
        return $this->db->query($sql, [$id])->single();
    }

    /**
     * Get user withdrawal statistics
     *
     * @param int $userId
     * @return array
     */
    public function getUserStats($userId)
    {
        $cacheKey = $this->cachePrefix . 'user_stats_' . $userId;
        return $this->cache->remember($cacheKey, function () use ($userId) {
            $stats = [];

            // Total withdrawals
            $result = $this->db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ?", [$userId])->single();
            $stats['total_withdrawals'] = $result ? (int)$result['count'] : 0;
            $stats['total_amount'] = $result ? (float)$result['total'] : 0;

            // Pending withdrawals
            $result = $this->db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND status = 'pending'", [$userId])->single();
            $stats['pending_withdrawals'] = $result ? (int)$result['count'] : 0;
            $stats['pending_amount'] = $result ? (float)$result['total'] : 0;

            // Completed withdrawals
            $result = $this->db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND status = 'completed'", [$userId])->single();
            $stats['completed_withdrawals'] = $result ? (int)$result['count'] : 0;
            $stats['completed_amount'] = $result ? (float)$result['total'] : 0;

            // Rejected withdrawals
            $result = $this->db->query("SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total FROM {$this->table} WHERE user_id = ? AND status = 'rejected'", [$userId])->single();
            $stats['rejected_withdrawals'] = $result ? (int)$result['count'] : 0;
            $stats['rejected_amount'] = $result ? (float)$result['total'] : 0;

            return $stats;
        }, $this->cacheTTL);
    }

    /**
     * Get recent withdrawals by user ID
     *
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentByUserId($userId, $limit = 5)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        return $this->db->query($sql, [$userId, $limit])->all();
    }

    /**
     * Check if user can make withdrawal
     *
     * @param int $userId
     * @param float $amount
     * @return array
     */
    public function canWithdraw($userId, $amount)
    {
        $errors = [];
        
        // Check minimum withdrawal amount
        $minAmount = 100; // Minimum withdrawal amount
        if ($amount < $minAmount) {
            $errors[] = "Minimum withdrawal amount is Rs. {$minAmount}";
        }
        
        // Check pending withdrawals
        $pendingTotal = $this->getPendingTotalByUserId($userId);
        if ($pendingTotal > 0) {
            $errors[] = "You have a pending withdrawal of Rs. " . number_format($pendingTotal, 2);
        }
        
        // Check user balance (this would need to be implemented based on your user balance system)
        // $userBalance = $this->getUserBalance($userId);
        // if ($amount > $userBalance) {
        //     $errors[] = "Insufficient balance";
        // }
        
        return [
            'can_withdraw' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Invalidate user-specific cache
     *
     * @param int $userId
     */
    private function invalidateUserCache($userId)
    {
        $this->cache->delete($this->cachePrefix . 'pending_total_' . $userId);
        $this->cache->delete($this->cachePrefix . 'completed_total_' . $userId);
        $this->cache->delete($this->cachePrefix . 'user_stats_' . $userId);
        $this->cache->delete($this->cachePrefix . 'stats');
    }
}