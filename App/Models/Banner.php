<?php

namespace App\Models;

use App\Core\Model;

class Banner extends Model
{
    protected $table = 'banners';
    protected $primaryKey = 'id';

    /**
     * Get all active banners
     *
     * @return array
     */
    public function getActiveBanners()
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY created_at DESC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get all banners with pagination
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllBanners($limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        return $this->db->query($sql)->bind([$limit, $offset])->all();
    }

    /**
     * Get total count of banners
     *
     * @return int
     */
    public function getTotalBanners()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->db->query($sql)->single();
        return (int)($result['total'] ?? 0);
    }

    /**
     * Get banner by ID
     *
     * @param int $id
     * @return array|false
     */
    public function getBannerById($id)
    {
        return $this->find($id);
    }

    /**
     * Create a new banner
     *
     * @param array $data
     * @return int|false
     */
    public function createBanner($data)
    {
        $sql = "INSERT INTO {$this->table} (image_url, link_url, status, clicks, views) 
                VALUES (?, ?, ?, ?, ?)";
        
        $params = [
            $data['image_url'] ?? '',
            $data['link_url'] ?? null,
            $data['status'] ?? 'active',
            $data['clicks'] ?? 0,
            $data['views'] ?? 0
        ];

        if ($this->db->query($sql)->bind($params)->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update banner
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateBanner($id, $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['image_url'])) {
            $fields[] = 'image_url = ?';
            $params[] = $data['image_url'];
        }

        if (isset($data['link_url'])) {
            $fields[] = 'link_url = ?';
            $params[] = $data['link_url'];
        }

        if (isset($data['status'])) {
            $fields[] = 'status = ?';
            $params[] = $data['status'];

            if ($data['status'] === 'active') {
                $fields[] = 'auto_paused = 0';
            }
        }

        if (isset($data['clicks'])) {
            $fields[] = 'clicks = ?';
            $params[] = $data['clicks'];
        }

        if (isset($data['views'])) {
            $fields[] = 'views = ?';
            $params[] = $data['views'];
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';
        $params[] = $id;

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql)->bind($params)->execute();
    }

    /**
     * Delete banner
     *
     * @param int $id
     * @return bool
     */
    public function deleteBanner($id)
    {
        return $this->delete($id);
    }

    /**
     * Increment banner views
     *
     * @param int $id
     * @return bool
     */
    public function incrementViews($id)
    {
        $sql = "UPDATE {$this->table} SET views = views + 1 WHERE id = ?";
        return $this->db->query($sql)->bind([$id])->execute();
    }

    /**
     * Increment banner clicks
     *
     * @param int $id
     * @return bool
     */
    public function incrementClicks($id)
    {
        $sql = "UPDATE {$this->table} SET clicks = clicks + 1 WHERE id = ?";
        return $this->db->query($sql)->bind([$id])->execute();
    }

    /**
     * Toggle banner status
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus($id)
    {
        $banner = $this->find($id);
        if (!$banner) {
            return false;
        }

        $newStatus = $banner['status'] === 'active' ? 'inactive' : 'active';
        return $this->updateBanner($id, ['status' => $newStatus]);
    }
}

