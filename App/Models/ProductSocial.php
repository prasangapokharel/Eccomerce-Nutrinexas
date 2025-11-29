<?php

namespace App\Models;

use App\Core\Model;

/**
 * ProductSocial Model
 * Tracks product likes/unlikes by users
 * 1 = like, 0 = unlike (default)
 */
class ProductSocial extends Model
{
    protected $table = 'products_social';
    protected $primaryKey = 'id';

    /**
     * Like or unlike a product
     * 
     * @param int $productId
     * @param int $userId
     * @param int $like 1 for like, 0 for unlike
     * @return bool Success status
     */
    public function toggleLike($productId, $userId, $like = 1)
    {
        if (!$productId || !$userId) {
            return false;
        }

        $like = $like ? 1 : 0; // Ensure it's 0 or 1

        try {
            // Check if record exists
            $existing = $this->getByProductAndUser($productId, $userId);

            if ($existing) {
                // Update existing record
                $sql = "UPDATE {$this->table} SET `like` = ?, updated_at = NOW() 
                        WHERE product_id = ? AND user_id = ?";
                return $this->db->query($sql, [$like, $productId, $userId])->execute();
            } else {
                // Create new record
                $sql = "INSERT INTO {$this->table} (product_id, user_id, `like`, created_at, updated_at) 
                        VALUES (?, ?, ?, NOW(), NOW())";
                return $this->db->query($sql, [$productId, $userId, $like])->execute();
            }
        } catch (\Exception $e) {
            error_log('ProductSocial toggleLike error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Like a product
     * 
     * @param int $productId
     * @param int $userId
     * @return bool Success status
     */
    public function likeProduct($productId, $userId)
    {
        return $this->toggleLike($productId, $userId, 1);
    }

    /**
     * Unlike a product
     * 
     * @param int $productId
     * @param int $userId
     * @return bool Success status
     */
    public function unlikeProduct($productId, $userId)
    {
        return $this->toggleLike($productId, $userId, 0);
    }

    /**
     * Get like status for a product and user
     * 
     * @param int $productId
     * @param int $userId
     * @return array|null Record or null if not found
     */
    public function getByProductAndUser($productId, $userId)
    {
        if (!$productId || !$userId) {
            return null;
        }

        try {
            $sql = "SELECT * FROM {$this->table} WHERE product_id = ? AND user_id = ? LIMIT 1";
            return $this->db->query($sql, [$productId, $userId])->single();
        } catch (\Exception $e) {
            error_log('ProductSocial getByProductAndUser error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has liked a product
     * 
     * @param int $productId
     * @param int $userId
     * @return bool True if liked, false otherwise
     */
    public function isLiked($productId, $userId)
    {
        $record = $this->getByProductAndUser($productId, $userId);
        return $record && (int)$record['like'] === 1;
    }

    /**
     * Get like count for a product
     * 
     * @param int $productId
     * @return int Like count
     */
    public function getLikeCount($productId)
    {
        if (!$productId) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ? AND `like` = 1";
            $result = $this->db->query($sql, [$productId])->single();
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            error_log('ProductSocial getLikeCount error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get unlike count for a product
     * 
     * @param int $productId
     * @return int Unlike count
     */
    public function getUnlikeCount($productId)
    {
        if (!$productId) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE product_id = ? AND `like` = 0";
            $result = $this->db->query($sql, [$productId])->single();
            return (int)($result['count'] ?? 0);
        } catch (\Exception $e) {
            error_log('ProductSocial getUnlikeCount error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get like counts for multiple products
     * 
     * @param array $productIds
     * @return array Associative array with product_id => like_count
     */
    public function getLikeCounts(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT product_id, COUNT(*) as count 
                    FROM {$this->table} 
                    WHERE product_id IN ({$placeholders}) AND `like` = 1
                    GROUP BY product_id";
            $results = $this->db->query($sql, $productIds)->all();
            
            $counts = [];
            foreach ($results as $row) {
                $counts[$row['product_id']] = (int)$row['count'];
            }
            
            // Fill in 0 for products with no likes
            foreach ($productIds as $id) {
                if (!isset($counts[$id])) {
                    $counts[$id] = 0;
                }
            }
            
            return $counts;
        } catch (\Exception $e) {
            error_log('ProductSocial getLikeCounts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's liked products
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array Product IDs
     */
    public function getUserLikedProducts($userId, $limit = 50, $offset = 0)
    {
        if (!$userId) {
            return [];
        }

        try {
            $sql = "SELECT product_id FROM {$this->table} 
                    WHERE user_id = ? AND `like` = 1 
                    ORDER BY updated_at DESC 
                    LIMIT ? OFFSET ?";
            $results = $this->db->query($sql, [$userId, $limit, $offset])->all();
            
            return array_column($results, 'product_id');
        } catch (\Exception $e) {
            error_log('ProductSocial getUserLikedProducts error: ' . $e->getMessage());
            return [];
        }
    }
}




