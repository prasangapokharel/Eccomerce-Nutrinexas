<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class DigitalProduct extends Model
{
    protected $table = 'digital_product';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByProductId($productId)
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} WHERE product_id = ? LIMIT 1",
            [$productId]
        )->single();

        return $result ?: null;
    }

    public function create($data)
    {
        $fields = ['product_id', 'file_download_link', 'file_size'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = $data[$field];
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values)->execute();
        return $this->db->lastInsertId();
    }

    public function updateByProductId($productId, $data)
    {
        $fields = [];
        $values = [];

        foreach (['file_download_link', 'file_size'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $productId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE product_id = ?";
        
        return $this->db->query($sql, $values)->execute();
    }

    public function deleteByProductId($productId)
    {
        return $this->db->query(
            "DELETE FROM {$this->table} WHERE product_id = ?",
            [$productId]
        )->execute();
    }
}

