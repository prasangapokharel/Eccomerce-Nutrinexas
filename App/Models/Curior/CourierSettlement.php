<?php
namespace App\Models\Curior;

use App\Core\Model;

class CourierSettlement extends Model
{
    protected $table = 'courier_settlements';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function createSettlement($curiorId, $orderId, $codAmount, $status = 'pending')
    {
        $sql = "INSERT INTO {$this->table} (curior_id, order_id, cod_amount, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        return $this->db->query($sql, [$curiorId, $orderId, $codAmount, $status])->execute();
    }

    public function updateSettlement($id, $data)
    {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        return $this->db->query($sql, $values)->execute();
    }

    public function getByCuriorId($curiorId, $status = null)
    {
        $sql = "SELECT cs.*, o.invoice, o.customer_name 
                FROM {$this->table} cs
                LEFT JOIN orders o ON cs.order_id = o.id
                WHERE cs.curior_id = ?";
        
        $params = [$curiorId];
        
        if ($status) {
            $sql .= " AND cs.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY cs.created_at DESC";
        
        return $this->db->query($sql, $params)->all();
    }

    public function getTotalCollected($curiorId, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT SUM(cod_amount) as total FROM {$this->table} 
                WHERE curior_id = ? AND status = 'collected'";
        
        $params = [$curiorId];
        
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $result = $this->db->query($sql, $params)->single();
        return $result['total'] ?? 0;
    }

    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ?";
        return $this->db->query($sql, [$orderId])->single();
    }
}

