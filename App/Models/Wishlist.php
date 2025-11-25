<?php
namespace App\Models;

use App\Core\Model;

class Wishlist extends Model
{
    protected $table = 'wishlist';
    protected $primaryKey = 'id';

    /**
     * Get wishlist items by user ID
     *
     * @param int $userId
     * @return array
     */
    public function getByUserId($userId)
    {
        $sql = "SELECT w.*, p.product_name, p.price, p.stock_quantity, p.description, p.slug, p.category, p.image
                FROM {$this->table} w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC";
        
        return $this->db->query($sql)->bind([$userId])->all();
    }

    /**
     * Check if product is in user's wishlist
     *
     * @param int $userId
     * @param int $productId
     * @return bool
     */
    public function isInWishlist($userId, $productId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql)->bind([$userId, $productId])->single();
        return $result && $result['count'] > 0;
    }

    /**
     * Get wishlist item
     *
     * @param int $userId
     * @param int $productId
     * @return array|false
     */
    public function getWishlistItem($userId, $productId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->db->query($sql)->bind([$userId, $productId])->single();
    }

    /**
     * Get wishlist count for user
     *
     * @param int $userId
     * @return int
     */
    public function getWishlistCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = ?";
        $result = $this->db->query($sql)->bind([$userId])->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get all product IDs in user's wishlist
     *
     * @param int $userId
     * @return array
     */
    public function getUserWishlistProductIds($userId)
    {
        $sql = "SELECT product_id FROM {$this->table} WHERE user_id = ?";
        $results = $this->db->query($sql)->bind([$userId])->all();
        return array_column($results, 'product_id');
    }

    /**
     * Get wishlist items for user and specific products (optimized for N+1 prevention)
     *
     * @param int $userId
     * @param array $productIds
     * @return array
     */
    public function getByUserAndProducts($userId, array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $sql = "SELECT product_id FROM {$this->table} 
                WHERE user_id = ? AND product_id IN ({$placeholders})";
        
        $params = array_merge([$userId], $productIds);
        return $this->db->query($sql)->bind($params)->all();
    }
}
