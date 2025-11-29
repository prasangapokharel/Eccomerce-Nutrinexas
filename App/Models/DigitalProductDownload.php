<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class DigitalProductDownload extends Model
{
    protected $table = 'digital_product_download';

    public function __construct()
    {
        parent::__construct();
    }

    public function getByUserAndProduct($userId, $productId)
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} 
             WHERE user_id = ? AND product_id = ? 
             AND expire_date >= CURDATE()
             ORDER BY created_at DESC 
             LIMIT 1",
            [$userId, $productId]
        )->single();

        return $result ?: null;
    }

    public function create($data)
    {
        $fields = ['user_id', 'product_id', 'order_id', 'expire_date', 'max_download'];
        $values = [];
        $placeholders = [];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $values[] = $data[$field];
                $placeholders[] = '?';
            } else {
                $values[] = null;
                $placeholders[] = '?';
            }
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, $values)->execute();
        return $this->db->lastInsertId();
    }

    public function incrementDownloadCount($id)
    {
        return $this->db->query(
            "UPDATE {$this->table} 
             SET download_count = download_count + 1 
             WHERE id = ? AND download_count < max_download",
            [$id]
        )->execute();
    }

    public function canDownload($userId, $productId)
    {
        $result = $this->db->query(
            "SELECT * FROM {$this->table} 
             WHERE user_id = ? AND product_id = ? 
             AND expire_date >= CURDATE()
             AND download_count < max_download
             ORDER BY created_at DESC 
             LIMIT 1",
            [$userId, $productId]
        )->single();

        return $result ?: null;
    }

    public function getUserDownloads($userId)
    {
        return $this->db->query(
            "SELECT d.*, p.product_name, dp.file_download_link 
             FROM {$this->table} d
             INNER JOIN products p ON d.product_id = p.id
             LEFT JOIN digital_product dp ON d.product_id = dp.product_id
             WHERE d.user_id = ? AND d.expire_date >= CURDATE()
             ORDER BY d.created_at DESC",
            [$userId]
        )->all();
    }
}

