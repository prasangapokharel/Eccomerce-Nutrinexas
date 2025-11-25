<?php
namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'id';
    
    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $sql = "SELECT value FROM {$this->table} WHERE `key` = ?";
        $result = $this->db->query($sql)->bind([$key])->single();
        
        if ($result) {
            // Try to decode JSON value
            $value = $result['value'];
            $decoded = json_decode($value, true);
            
            // Return decoded value if it's valid JSON, otherwise return the raw value
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }
        
        return $default;
    }
    
    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set($key, $value)
    {
        // Encode arrays and objects as JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        // Check if setting already exists
        $sql = "SELECT id FROM {$this->table} WHERE `key` = ?";
        $existing = $this->db->query($sql)->bind([$key])->single();
        
        if ($existing) {
            // Update existing setting
            $sql = "UPDATE {$this->table} SET value = ?, updated_at = NOW() WHERE `key` = ?";
            return $this->db->query($sql)->bind([$value, $key])->execute();
        } else {
            // Create new setting
            $sql = "INSERT INTO {$this->table} (`key`, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
            return $this->db->query($sql)->bind([$key, $value])->execute();
        }
    }
    
    /**
     * Delete a setting
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $sql = "DELETE FROM {$this->table} WHERE `key` = ?";
        return $this->db->query($sql)->bind([$key])->execute();
    }
    
    /**
     * Get all settings
     *
     * @return array
     */
    public function getAll()
    {
        $sql = "SELECT * FROM {$this->table}";
        $results = $this->db->query($sql)->all();
        
        $settings = [];
        foreach ($results as $row) {
            // Try to decode JSON values
            $value = $row['value'];
            $decoded = json_decode($value, true);
            
            // Store decoded value if it's valid JSON, otherwise store the raw value
            $settings[$row['key']] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $value;
        }
        
        return $settings;
    }
}