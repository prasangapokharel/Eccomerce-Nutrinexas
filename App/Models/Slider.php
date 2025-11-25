<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Slider extends Model
{
    protected $table = 'sliders';
    protected $primaryKey = 'id';
    
    private $cache;
    private $cachePrefix = 'slider_';
    private $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get all active sliders
     *
     * @return array
     */
    public function all()
    {
        $cacheKey = $this->cachePrefix . 'all_active';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC";
            return $this->db->query($sql)->all();
        }, $this->cacheTTL);
    }
    
    /**
     * Get active sliders with limit
     *
     * @param int|null $limit
     * @return array
     */
    public function getActiveSliders($limit = null)
    {
        $cacheKey = $this->cachePrefix . 'active_' . ($limit ?? 'all');
        return $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC";
            if ($limit) {
                $sql .= " LIMIT ?";
                return $this->db->query($sql, [$limit])->all();
            }
            return $this->db->query($sql)->all();
        }, $this->cacheTTL);
    }
    
    /**
     * Get all sliders for admin
     *
     * @return array
     */
    public function getAllForAdmin()
    {
        $cacheKey = $this->cachePrefix . 'admin_all';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, created_at DESC";
            return $this->db->query($sql)->all();
        }, $this->cacheTTL);
    }
    
    /**
     * Find slider by ID
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $cacheKey = $this->cachePrefix . 'id_' . $id;
        return $this->cache->remember($cacheKey, function () use ($id) {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            return $this->db->query($sql, [$id])->single();
        }, $this->cacheTTL);
    }
    
    /**
     * Create new slider
     *
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        // Check which columns exist in the database
        $columns = $this->db->query("SHOW COLUMNS FROM {$this->table}")->all();
        $columnNames = array_column($columns, 'Field');
        
        // Build dynamic INSERT query based on existing columns
        $insertColumns = [];
        $placeholders = [];
        $params = [];
        
        if (in_array('title', $columnNames)) {
            $insertColumns[] = 'title';
            $placeholders[] = '?';
            $params[] = !empty($data['title']) ? $data['title'] : null;
        }
        
        if (in_array('subtitle', $columnNames)) {
            $insertColumns[] = 'subtitle';
            $placeholders[] = '?';
            $params[] = !empty($data['subtitle']) ? $data['subtitle'] : null;
        }
        
        if (in_array('description', $columnNames)) {
            $insertColumns[] = 'description';
            $placeholders[] = '?';
            $params[] = !empty($data['description']) ? $data['description'] : null;
        }
        
        if (in_array('button_text', $columnNames)) {
            $insertColumns[] = 'button_text';
            $placeholders[] = '?';
            $params[] = !empty($data['button_text']) ? $data['button_text'] : null;
        }
        
        $insertColumns[] = 'image_url';
        $placeholders[] = '?';
        $params[] = $data['image_url'];
        
        if (in_array('link_url', $columnNames)) {
            $insertColumns[] = 'link_url';
            $placeholders[] = '?';
            $params[] = !empty($data['link_url']) ? $data['link_url'] : null;
        }
        
        $insertColumns[] = 'is_active';
        $placeholders[] = '?';
        $params[] = $data['is_active'] ?? 1;
        
        $insertColumns[] = 'sort_order';
        $placeholders[] = '?';
        $params[] = $data['sort_order'] ?? 0;
        
        if (in_array('created_at', $columnNames)) {
            $insertColumns[] = 'created_at';
            $placeholders[] = 'NOW()';
        }
        
        if (in_array('updated_at', $columnNames)) {
            $insertColumns[] = 'updated_at';
            $placeholders[] = 'NOW()';
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $result = $this->db->query($sql, $params)->execute();

        if ($result) {
            $this->invalidateCache();
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Update slider
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        // Check which columns exist in the database
        $columns = $this->db->query("SHOW COLUMNS FROM {$this->table}")->all();
        $columnNames = array_column($columns, 'Field');
        
        // Build dynamic UPDATE query based on existing columns
        $setParts = [];
        $params = [];
        
        if (in_array('title', $columnNames)) {
            $setParts[] = 'title = ?';
            $params[] = !empty($data['title']) ? $data['title'] : null;
        }
        
        if (in_array('subtitle', $columnNames)) {
            $setParts[] = 'subtitle = ?';
            $params[] = !empty($data['subtitle']) ? $data['subtitle'] : null;
        }
        
        if (in_array('description', $columnNames)) {
            $setParts[] = 'description = ?';
            $params[] = !empty($data['description']) ? $data['description'] : null;
        }
        
        if (in_array('button_text', $columnNames)) {
            $setParts[] = 'button_text = ?';
            $params[] = !empty($data['button_text']) ? $data['button_text'] : null;
        }
        
        $setParts[] = 'image_url = ?';
        $params[] = $data['image_url'];
        
        if (in_array('link_url', $columnNames)) {
            $setParts[] = 'link_url = ?';
            $params[] = !empty($data['link_url']) ? $data['link_url'] : null;
        }
        
        $setParts[] = 'is_active = ?';
        $params[] = $data['is_active'] ?? 1;
        
        $setParts[] = 'sort_order = ?';
        $params[] = $data['sort_order'] ?? 0;
        
        $setParts[] = 'updated_at = NOW()';
        $params[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?";
        
        $result = $this->db->query($sql, $params)->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }
    
    /**
     * Delete slider
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }
    
    /**
     * Update sort order
     *
     * @param int $id
     * @param int $sortOrder
     * @return bool
     */
    public function updateSortOrder($id, $sortOrder)
    {
        $sql = "UPDATE {$this->table} SET sort_order = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$sortOrder, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }
    
    /**
     * Toggle slider status
     *
     * @param int $id
     * @return bool
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Get next sort order
     *
     * @return int
     */
    public function getNextSortOrder()
    {
        $sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM {$this->table}";
        $result = $this->db->query($sql)->single();
        return $result ? (int)$result['next_order'] : 1;
    }

    /**
     * Reorder sliders
     *
     * @param array $orderData Array of ['id' => sort_order]
     * @return bool
     */
    public function reorderSliders($orderData)
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($orderData as $id => $sortOrder) {
                $sql = "UPDATE {$this->table} SET sort_order = ? WHERE id = ?";
                $this->db->query($sql, [$sortOrder, $id])->execute();
            }
            
            $this->db->commit();
            $this->invalidateCache();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get slider statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        $cacheKey = $this->cachePrefix . 'stats';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive
                    FROM {$this->table}";
            return $this->db->query($sql)->single();
        }, $this->cacheTTL);
    }

    /**
     * Invalidate cache entries
     *
     * @param int|null $id
     */
    private function invalidateCache($id = null)
    {
        if ($id) {
            $this->cache->delete($this->cachePrefix . 'id_' . $id);
        }
        
        // Invalidate list caches
        $this->cache->deletePattern($this->cachePrefix . 'all_*');
        $this->cache->deletePattern($this->cachePrefix . 'active_*');
        $this->cache->deletePattern($this->cachePrefix . 'admin_*');
        $this->cache->delete($this->cachePrefix . 'stats');
    }
}