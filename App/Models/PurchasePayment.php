<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class PurchasePayment extends Model
{
    protected $table = 'purchase_payments';
    protected $primaryKey = 'payment_id';
    private $cache;

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get all payments with purchase information
     */
    public function getAllPayments()
    {
        $cacheKey = 'purchase_payments_all';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT pp.*, p.purchase_id, p.total_amount, wp.product_name, s.supplier_name
                FROM {$this->table} pp
                LEFT JOIN purchases p ON pp.purchase_id = p.purchase_id
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                ORDER BY pp.payment_date DESC";
        
        $result = $this->query($sql);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300); // Cache for 5 minutes
        return $data;
    }

    /**
     * Get payment by ID
     */
    public function getPaymentById($id)
    {
        $cacheKey = "purchase_payment_{$id}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT pp.*, p.purchase_id, p.total_amount, wp.product_name, s.supplier_name
                FROM {$this->table} pp
                LEFT JOIN purchases p ON pp.purchase_id = p.purchase_id
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE pp.payment_id = ?";
        
        $result = $this->query($sql, [$id]);
        
        $data = null;
        if (is_array($result) && !empty($result)) {
            $data = isset($result[0]) ? $result[0] : $result;
        }
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Get payments by purchase ID
     */
    public function getPaymentsByPurchase($purchaseId)
    {
        $cacheKey = "purchase_payments_{$purchaseId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT * FROM {$this->table} WHERE purchase_id = ? ORDER BY payment_date DESC";
        $result = $this->query($sql, [$purchaseId]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Create new payment
     */
    public function createPayment($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (purchase_id, payment_method, amount, payment_date, reference_number, notes, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $result = $this->query($sql, [
            $data['purchase_id'],
            $data['payment_method'],
            $data['amount'],
            $data['payment_date'] ?? date('Y-m-d H:i:s'),
            $data['reference_number'] ?? null,
            $data['notes'] ?? null
        ]);
        
        if ($result !== false) {
            $this->clearPaymentCache();
            $this->updatePurchaseStatus($data['purchase_id']);
        }
        
        return $result !== false;
    }

    /**
     * Update payment
     */
    public function updatePayment($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                payment_method = ?, amount = ?, payment_date = ?, 
                reference_number = ?, notes = ? 
                WHERE payment_id = ?";
        
        $result = $this->query($sql, [
            $data['payment_method'],
            $data['amount'],
            $data['payment_date'],
            $data['reference_number'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
        
        if ($result !== false) {
            $this->clearPaymentCache();
        }
        
        return $result !== false;
    }

    /**
     * Delete payment
     */
    public function deletePayment($id)
    {
        // Get payment details before deletion
        $payment = $this->getPaymentById($id);
        
        $sql = "DELETE FROM {$this->table} WHERE payment_id = ?";
        $result = $this->query($sql, [$id]);
        
        if ($result !== false) {
            $this->clearPaymentCache();
            if ($payment && isset($payment['purchase_id'])) {
                $this->updatePurchaseStatus($payment['purchase_id']);
            }
        }
        
        return $result !== false;
    }

    /**
     * Get total paid amount for a purchase
     */
    public function getTotalPaidAmount($purchaseId)
    {
        $cacheKey = "total_paid_{$purchaseId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT SUM(amount) as total_paid FROM {$this->table} WHERE purchase_id = ?";
        $result = $this->query($sql, [$purchaseId]);
        
        $total = 0;
        if (is_array($result) && !empty($result)) {
            $row = isset($result[0]) ? $result[0] : $result;
            $total = isset($row['total_paid']) ? (float)$row['total_paid'] : 0;
        }
        
        Cache::set($cacheKey, $total, 300);
        return $total;
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats()
    {
        $cacheKey = 'payment_stats';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_payment_amount,
                SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_payments,
                SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as bank_transfer_payments,
                SUM(CASE WHEN payment_method = 'check' THEN amount ELSE 0 END) as check_payments,
                SUM(CASE WHEN payment_method = 'digital_wallet' THEN amount ELSE 0 END) as digital_wallet_payments
                FROM {$this->table}";
        
        $result = $this->query($sql);
        
        $data = [
            'total_payments' => 0,
            'total_payment_amount' => 0,
            'cash_payments' => 0,
            'bank_transfer_payments' => 0,
            'check_payments' => 0,
            'digital_wallet_payments' => 0
        ];
        
        if (is_array($result) && !empty($result)) {
            $data = isset($result[0]) ? $result[0] : $result;
        }
        
        $this->cache->set($cacheKey, $data, 600); // Cache for 10 minutes
        return $data;
    }

    /**
     * Get recent payments
     */
    public function getRecentPayments($limit = 10)
    {
        $cacheKey = "recent_payments_{$limit}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT pp.*, p.purchase_id, wp.product_name, s.supplier_name
                FROM {$this->table} pp
                LEFT JOIN purchases p ON pp.purchase_id = p.purchase_id
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                ORDER BY pp.payment_date DESC
                LIMIT ?";
        
        $result = $this->query($sql, [$limit]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Update purchase status based on payments
     */
    public function updatePurchaseStatus($purchaseId)
    {
        // Get total amount and paid amount
        $purchase = $this->query("SELECT total_amount FROM purchases WHERE purchase_id = ?", [$purchaseId]);
        $totalPaid = $this->getTotalPaidAmount($purchaseId);
        
        if (is_array($purchase) && !empty($purchase)) {
            $purchaseData = isset($purchase[0]) ? $purchase[0] : $purchase;
            $totalAmount = (float)$purchaseData['total_amount'];
            
            $status = 'remaining';
            if ($totalPaid >= $totalAmount) {
                $status = 'paid';
            } elseif ($totalPaid > 0) {
                $status = 'partial';
            }
            
            $this->query("UPDATE purchases SET status = ?, updated_at = NOW() WHERE purchase_id = ?", [$status, $purchaseId]);
        }
    }

    /**
     * Get payments by date range
     */
    public function getPaymentsByDateRange($startDate, $endDate)
    {
        $sql = "SELECT pp.*, p.purchase_id, wp.product_name, s.supplier_name
                FROM {$this->table} pp
                LEFT JOIN purchases p ON pp.purchase_id = p.purchase_id
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE pp.payment_date BETWEEN ? AND ?
                ORDER BY pp.payment_date DESC";
        
        $result = $this->query($sql, [$startDate, $endDate]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get payments by method
     */
    public function getPaymentsByMethod($method)
    {
        $cacheKey = "payments_method_{$method}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== false) {
            return $cached;
        }

        $sql = "SELECT pp.*, p.purchase_id, wp.product_name, s.supplier_name
                FROM {$this->table} pp
                LEFT JOIN purchases p ON pp.purchase_id = p.purchase_id
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE pp.payment_method = ?
                ORDER BY pp.payment_date DESC";
        
        $result = $this->query($sql, [$method]);
        $data = is_array($result) ? $result : [];
        
        $this->cache->set($cacheKey, $data, 300);
        return $data;
    }

    /**
     * Get outstanding payments
     */
    public function getOutstandingPayments()
    {
        $sql = "SELECT p.*, wp.product_name, s.supplier_name,
                (SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id) as paid_amount,
                (p.total_amount - COALESCE((SELECT SUM(amount) FROM purchase_payments pp WHERE pp.purchase_id = p.purchase_id), 0)) as remaining_amount
                FROM purchases p
                LEFT JOIN wholesale_products wp ON p.product_id = wp.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
                WHERE p.status IN ('partial', 'remaining')
                ORDER BY p.purchase_date DESC";
        
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Clear payment-related cache
     */
    private function clearPaymentCache()
    {
        $this->cache->delete('purchase_payments_all');
        $this->cache->delete('payment_stats');
        $this->cache->deletePattern('purchase_payment_*');
        $this->cache->deletePattern('purchase_payments_*');
        $this->cache->deletePattern('recent_payments_*');
        $this->cache->deletePattern('total_paid_*');
        $this->cache->deletePattern('payments_method_*');
    }
}