<?php
namespace App\Models\Curior;

use App\Core\Model;

class CourierLocation extends Model
{
    protected $table = 'courier_locations';
    protected $primaryKey = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    public function logLocation($curiorId, $orderId, $latitude, $longitude, $address = null)
    {
        $sql = "INSERT INTO {$this->table} (curior_id, order_id, latitude, longitude, address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        return $this->db->query($sql, [
            $curiorId,
            $orderId,
            $latitude,
            $longitude,
            $address
        ])->execute();
    }

    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC";
        return $this->db->query($sql, [$orderId])->all();
    }

    public function getByCuriorId($curiorId, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table} WHERE curior_id = ? ORDER BY created_at DESC LIMIT ?";
        return $this->db->query($sql, [$curiorId, $limit])->all();
    }

    public function getLatestLocation($curiorId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE curior_id = ? ORDER BY created_at DESC LIMIT 1";
        return $this->db->query($sql, [$curiorId])->single();
    }
}

