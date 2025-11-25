<?php
namespace App\Models\Curior;

use App\Core\Model;

class OrderActivity extends Model
{
    protected $table = 'order_activities';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function logEntry($orderId, $action, $data = '', $createdBy = null)
    {
        $dataJson = is_array($data) ? json_encode($data) : $data;
        $sql = "INSERT INTO {$this->table} (order_id, action, data, created_by, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $this->db->query($sql, [$orderId, $action, $dataJson, $createdBy])->execute();
        return $this->db->lastInsertId();
    }
    
    public function logActivity($orderId, $action, $data = '', $createdBy = null)
    {
        return $this->logEntry($orderId, $action, $data, $createdBy);
    }

    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC";
        return $this->db->query($sql, [$orderId])->all();
    }

    public function getByAction($action, $limit = 100)
    {
        $sql = "SELECT * FROM {$this->table} WHERE action = ? ORDER BY created_at DESC LIMIT ?";
        return $this->db->query($sql, [$action, $limit])->all();
    }
}

