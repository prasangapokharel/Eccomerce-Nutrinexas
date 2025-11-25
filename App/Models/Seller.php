<?php

namespace App\Models;

use App\Core\Model;

class Seller extends Model
{
    protected $table = 'sellers';
    protected $primaryKey = 'id';

    /**
     * Find seller by email (without status filter to check approval)
     */
    public function findByEmail($email)
    {
        return $this->db->query("SELECT * FROM {$this->table} WHERE email = ?", [$email])->single();
    }

    /**
     * Authenticate seller (returns seller even if not approved, check in controller)
     */
    public function authenticate($email, $password)
    {
        $seller = $this->findByEmail($email);
        
        if ($seller && password_verify($password, $seller['password'])) {
            return $seller;
        }
        
        return false;
    }

    /**
     * Create new seller (override to handle default values)
     */
    public function create($data)
    {
        // Set default values for new sellers
        if (!isset($data['status'])) {
            $data['status'] = 'inactive';
        }
        if (!isset($data['is_approved'])) {
            $data['is_approved'] = 0;
        }
        if (!isset($data['commission_rate'])) {
            $data['commission_rate'] = 10.00;
        }
        
        return parent::create($data);
    }

    /**
     * Get seller statistics
     */
    public function getStats($sellerId)
    {
        $stats = [
            'total_products' => 0,
            'products_count' => 0,
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'total_revenue' => 0,
            'total_earnings' => 0,
            'wallet_balance' => 0,
            'low_stock_products' => 0
        ];

        try {
            // Total products
            $result = $this->db->query("SELECT COUNT(*) as count FROM products WHERE seller_id = ?", [$sellerId])->single();
            $stats['total_products'] = $result['count'] ?? 0;
            $stats['products_count'] = $stats['total_products'];

            // Total orders
            $result = $this->db->query(
                "SELECT COUNT(DISTINCT o.id) as count 
                 FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE oi.seller_id = ? AND o.status != 'cancelled'",
                [$sellerId]
            )->single();
            $stats['total_orders'] = $result['count'] ?? 0;

            // Completed orders
            $result = $this->db->query(
                "SELECT COUNT(DISTINCT o.id) as count 
                 FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE oi.seller_id = ? 
                   AND o.status IN ('completed', 'delivered')
                   AND o.payment_status = 'paid'",
                [$sellerId]
            )->single();
            $stats['completed_orders'] = $result['count'] ?? 0;

            // Total revenue
            $result = $this->db->query(
                "SELECT COALESCE(SUM(oi.total), 0) as total 
                 FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE oi.seller_id = ? 
                   AND o.payment_status = 'paid' 
                   AND o.status != 'cancelled'",
                [$sellerId]
            )->single();
            $stats['total_revenue'] = $result['total'] ?? 0;

            // Pending orders
            $result = $this->db->query(
                "SELECT COUNT(DISTINCT o.id) as count 
                 FROM orders o
                 INNER JOIN order_items oi ON o.id = oi.order_id
                 WHERE oi.seller_id = ? 
                   AND o.status IN ('pending', 'processing')",
                [$sellerId]
            )->single();
            $stats['pending_orders'] = $result['count'] ?? 0;

            // Wallet snapshot
            $wallet = (new SellerWallet())->getWalletBySellerId($sellerId);
            $stats['wallet_balance'] = isset($wallet['balance']) ? (float)$wallet['balance'] : 0;
            $stats['total_earnings'] = isset($wallet['total_earnings'])
                ? (float)$wallet['total_earnings']
                : (float)$stats['total_revenue'];

            // Low stock products
            $result = $this->db->query("SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND stock_quantity < 10 AND stock_quantity > 0", [$sellerId])->single();
            $stats['low_stock_products'] = $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log('Seller stats error: ' . $e->getMessage());
        }

        return $stats;
    }
}

