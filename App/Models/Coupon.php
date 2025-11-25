<?php

namespace App\Models;

use App\Core\Model;

class Coupon extends Model
{
    protected $table = 'coupons';
    
    /**
     * Get all coupons with pagination
     */
    public function getAllCoupons($limit = 20, $offset = 0)
    {
        $sql = "SELECT c.*, 
                       COUNT(cu.id) as total_uses,
                       SUM(cu.discount_amount) as total_discount_given
                FROM {$this->table} c
                LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                GROUP BY c.id
                ORDER BY c.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total coupons count
     */
    public function getTotalCoupons()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get active coupons count
     */
    public function getActiveCouponsCount()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get recent coupons
     */
    public function getRecentCoupons($limit = 5)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get public coupons for display
     */
    public function getPublicCoupons()
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND status = 'public'
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC";
        
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get coupon by code
     */
    public function getCouponByCode($code)
    {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':code', $code);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get coupon by ID
     */
    public function getCouponById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new coupon
     */
    public function createCoupon($data)
    {
        $fields = ['code', 'description', 'discount_type', 'discount_value', 
                   'min_order_amount', 'max_discount_amount', 'usage_limit_per_user', 
                   'usage_limit_global', 'applicable_products', 'is_active', 'status', 'expires_at'];
        $placeholders = [];
        $params = [];
        
        // Add seller_id if provided
        if (isset($data['seller_id'])) {
            $fields[] = 'seller_id';
        }
        
        foreach ($fields as $field) {
            $placeholders[] = ":{$field}";
            if ($field === 'code') {
                $params[":{$field}"] = strtoupper($data['code']);
            } elseif ($field === 'seller_id') {
                $params[":{$field}"] = $data['seller_id'];
            } elseif (in_array($field, ['min_order_amount', 'max_discount_amount', 'usage_limit_per_user', 'usage_limit_global', 'expires_at', 'description', 'applicable_products'])) {
                $params[":{$field}"] = !empty($data[$field]) ? $data[$field] : null;
            } elseif ($field === 'is_active') {
                $params[":{$field}"] = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;
            } else {
                $params[":{$field}"] = $data[$field] ?? null;
            }
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $this->db->getPdo()->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * Create coupon (alias for compatibility)
     */
    public function create($data)
    {
        return $this->createCoupon($data);
    }
    
    /**
     * Update coupon
     */
    public function updateCoupon($id, $data)
    {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = [
            'code', 'description', 'discount_type', 'discount_value',
            'min_order_amount', 'max_discount_amount', 'usage_limit_per_user',
            'usage_limit_global', 'applicable_products', 'is_active', 'status', 'expires_at'
        ];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                if ($field === 'code') {
                    $params[":{$field}"] = strtoupper($data[$field]);
                } elseif (in_array($field, ['min_order_amount', 'max_discount_amount', 'usage_limit_per_user', 'usage_limit_global', 'expires_at'])) {
                    $params[":{$field}"] = !empty($data[$field]) ? $data[$field] : null;
                } else {
                    $params[":{$field}"] = $data[$field];
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->getPdo()->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Delete coupon
     */
    public function deleteCoupon($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->getPdo()->prepare($sql);
        
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Validate coupon for use
     */
    public function validateCoupon($code, $userId, $orderAmount, $productIds = [])
    {
        $coupon = $this->getCouponByCode($code);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code'];
        }
        
        // Check if coupon is active
        if (!$coupon['is_active']) {
            return ['valid' => false, 'message' => 'This coupon is no longer active'];
        }
        
        // Check expiry date
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) <= time()) {
            return ['valid' => false, 'message' => 'This coupon has expired'];
        }
        
        // Check minimum order amount
        if ($coupon['min_order_amount'] && $orderAmount < $coupon['min_order_amount']) {
            return ['valid' => false, 'message' => "Minimum order amount of ₹{$coupon['min_order_amount']} required"];
        }
        
        // Check global usage limit
        if ($coupon['usage_limit_global']) {
            if ($coupon['used_count'] >= $coupon['usage_limit_global']) {
                return ['valid' => false, 'message' => 'This coupon has reached its usage limit'];
            }
        }
        
        // Check per-user usage limit
        if ($coupon['usage_limit_per_user'] && $userId) {
            $userUsageCount = $this->getUserCouponUsageCount($coupon['id'], $userId);
            if ($userUsageCount >= $coupon['usage_limit_per_user']) {
                return ['valid' => false, 'message' => 'You have reached the usage limit for this coupon'];
            }
        }
        
        // Check applicable products
        if ($coupon['applicable_products'] && !empty($productIds)) {
            $applicableProducts = json_decode($coupon['applicable_products'], true);
            if ($applicableProducts && !empty($applicableProducts)) {
                $hasApplicableProduct = false;
                foreach ($productIds as $productId) {
                    if (in_array($productId, $applicableProducts)) {
                        $hasApplicableProduct = true;
                        break;
                    }
                }
                if (!$hasApplicableProduct) {
                    return ['valid' => false, 'message' => 'This coupon is not applicable to the products in your cart'];
                }
            }
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }
    
    /**
     * Calculate discount amount
     */
    public function calculateDiscount($coupon, $orderAmount)
    {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($orderAmount * $coupon['discount_value']) / 100;
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Apply maximum discount limit if set
        if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
            $discount = $coupon['max_discount_amount'];
        }
        
        // Ensure discount doesn't exceed order amount
        if ($discount > $orderAmount) {
            $discount = $orderAmount;
        }
        
        return round($discount, 2);
    }
    
    /**
     * Record coupon usage
     */
    public function recordCouponUsage($couponId, $userId, $orderId, $discountAmount)
    {
        $sql = "INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount) 
                VALUES (:coupon_id, :user_id, :order_id, :discount_amount)";
        
        $stmt = $this->db->getPdo()->prepare($sql);
        $result = $stmt->execute([
            ':coupon_id' => $couponId,
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':discount_amount' => $discountAmount
        ]);
        
        if ($result) {
            // Update coupon used count
            $updateSql = "UPDATE {$this->table} SET used_count = used_count + 1 WHERE id = :id";
            $updateStmt = $this->db->getPdo()->prepare($updateSql);
            $updateStmt->execute([':id' => $couponId]);
        }
        
        return $result;
    }
    
    /**
     * Get user coupon usage count
     */
    public function getUserCouponUsageCount($couponId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id";
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute([
            ':coupon_id' => $couponId,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Get coupon statistics
     */
    public function getCouponStats($id)
    {
        $sql = "SELECT c.*,
                       COUNT(cu.id) as total_uses,
                       COUNT(DISTINCT cu.user_id) as unique_users,
                       SUM(cu.discount_amount) as total_discount_given,
                       AVG(cu.discount_amount) as avg_discount_per_use,
                       MIN(cu.used_at) as first_used,
                       MAX(cu.used_at) as last_used
                FROM {$this->table} c
                LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
                WHERE c.id = :id
                GROUP BY c.id";
        
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Get coupon usage history
     */
    public function getCouponUsageHistory($couponId, $limit = 50)
    {
        $sql = "SELECT cu.*, u.first_name, u.email, o.order_number
                FROM coupon_usage cu
                JOIN users u ON cu.user_id = u.id
                JOIN orders o ON cu.order_id = o.id
                WHERE cu.coupon_id = :coupon_id
                ORDER BY cu.used_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->getPdo()->prepare($sql);
        $stmt->bindValue(':coupon_id', $couponId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
