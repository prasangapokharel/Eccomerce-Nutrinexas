<?php
namespace App\Models;

use App\Core\Model;

class PaymentGateway extends Model
{
    protected $table = 'payment_gateways';
    protected $primaryKey = 'id';

    /**
     * Get all active payment gateways
     */
    public function getActiveGateways()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC";
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Get gateway by ID (returns array)
     */
    public function getGatewayById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1";
        $result = $this->query($sql, [$id]);
        
        // If query returns multiple rows, get the first one
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Get any gateway by ID (including inactive)
     */
    public function findGatewayById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->query($sql, [$id]);
        
        // If query returns multiple rows, get the first one
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Get gateways by type
     */
    public function getGatewaysByType($types)
    {
        if (empty($types)) {
            return [];
        }
        
        // Convert single type to array
        if (!is_array($types)) {
            $types = [$types];
        }
        
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $sql = "SELECT * FROM {$this->table} WHERE type IN ({$placeholders}) AND is_active = 1 ORDER BY sort_order ASC";
        $result = $this->query($sql, $types);
        return is_array($result) ? $result : [];
    }

    /**
     * Get gateway by slug
     */
    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ?";
        $result = $this->query($sql, [$slug]);
        
        // If query returns multiple rows, get the first one
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Get gateway with currencies
     */
    public function getGatewayWithCurrencies($id)
    {
        $sql = "SELECT g.*, 
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', gc.id,
                        'currency_code', gc.currency_code,
                        'currency_symbol', gc.currency_symbol,
                        'conversion_rate', gc.conversion_rate,
                        'min_limit', gc.min_limit,
                        'max_limit', gc.max_limit,
                        'percentage_charge', gc.percentage_charge,
                        'fixed_charge', gc.fixed_charge,
                        'is_active', gc.is_active
                    )
                ) as currencies
                FROM {$this->table} g
                LEFT JOIN gateway_currencies gc ON g.id = gc.gateway_id
                WHERE g.id = ?
                GROUP BY g.id";
        
        $result = $this->query($sql, [$id]);
        
        // Get first row if multiple returned
        if (is_array($result) && !empty($result)) {
            $gateway = isset($result[0]) ? $result[0] : $result;
            
            if ($gateway && isset($gateway['currencies']) && $gateway['currencies']) {
                $gateway['currencies'] = json_decode('[' . $gateway['currencies'] . ']', true);
            } else {
                $gateway['currencies'] = [];
            }
            
            return $gateway;
        }
        
        return null;
    }

    /**
     * Update gateway parameters
     */
    public function updateParameters($id, $parameters)
    {
        $sql = "UPDATE {$this->table} SET parameters = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [json_encode($parameters), $id]);
        return $result !== false;
    }

    /**
     * Toggle gateway status
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Toggle test mode
     */
    public function toggleTestMode($id)
    {
        $sql = "UPDATE {$this->table} SET is_test_mode = NOT is_test_mode, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Create new gateway
     */
    public function createGateway($data)
    {
        $sql = "INSERT INTO {$this->table} (name, slug, type, description, parameters, is_active, sort_order, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->query($sql, [
            $data['name'],
            $data['slug'],
            $data['type'],
            $data['description'] ?? '',
            json_encode($data['parameters'] ?? []),
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
        
        return $result !== false;
    }

    /**
     * Update gateway
     */
    public function updateGateway($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                name = ?, slug = ?, type = ?, description = ?, 
                logo = ?, parameters = ?, is_active = ?, is_test_mode = ?, sort_order = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $result = $this->query($sql, [
            $data['name'],
            $data['slug'],
            $data['type'],
            $data['description'] ?? '',
            $data['logo'] ?? null,
            json_encode($data['parameters'] ?? []),
            $data['is_active'] ?? 1,
            $data['is_test_mode'] ?? 0,
            $data['sort_order'] ?? 0,
            $id
        ]);
        
        return $result !== false;
    }

    /**
     * Update gateway logo
     */
    public function updateLogo($id, $logoUrl)
    {
        $sql = "UPDATE {$this->table} SET logo = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [$logoUrl, $id]);
        return $result !== false;
    }

    /**
     * Delete gateway
     */
    public function deleteGateway($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Get all gateways (including inactive) for admin
     */
    public function getAllGateways()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort_order ASC, name ASC";
        $result = $this->query($sql);
        return is_array($result) ? $result : [];
    }

    /**
     * Check if gateway exists by slug
     */
    public function existsBySlug($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->query($sql, $params);
        
        if (is_array($result) && !empty($result)) {
            $row = isset($result[0]) ? $result[0] : $result;
            return isset($row['count']) && $row['count'] > 0;
        }
        
        return false;
    }

    /**
     * Get gateway count by type
     */
    public function getCountByType($type)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE type = ? AND is_active = 1";
        $result = $this->query($sql, [$type]);
        
        if (is_array($result) && !empty($result)) {
            $row = isset($result[0]) ? $result[0] : $result;
            return isset($row['count']) ? (int)$row['count'] : 0;
        }
        
        return 0;
    }

    /**
     * Update gateway sort order
     */
    public function updateSortOrder($id, $sortOrder)
    {
        $sql = "UPDATE {$this->table} SET sort_order = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->query($sql, [$sortOrder, $id]);
        return $result !== false;
    }

    /**
     * Get next sort order
     */
    public function getNextSortOrder()
    {
        $sql = "SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM {$this->table}";
        $result = $this->query($sql);
        
        if (is_array($result) && !empty($result)) {
            $row = isset($result[0]) ? $result[0] : $result;
            return isset($row['next_order']) ? (int)$row['next_order'] : 1;
        }
        
        return 1;
    }

    /**
     * Validate gateway parameters based on type
     */
    public function validateGatewayParameters($type, $parameters)
    {
        $errors = [];
        
        switch ($type) {
            case 'manual':
                // Manual gateways might need account details
                if (empty($parameters['account_name'])) {
                    $errors[] = 'Account name is required for manual gateways';
                }
                if (empty($parameters['account_number'])) {
                    $errors[] = 'Account number is required for manual gateways';
                }
                break;
                
            case 'automatic':
                // Automatic gateways need API credentials
                if (empty($parameters['api_key'])) {
                    $errors[] = 'API key is required for automatic gateways';
                }
                if (empty($parameters['secret_key'])) {
                    $errors[] = 'Secret key is required for automatic gateways';
                }
                break;
                
            case 'cod':
                // COD might need delivery charge settings
                if (isset($parameters['delivery_charge']) && !is_numeric($parameters['delivery_charge'])) {
                    $errors[] = 'Delivery charge must be a valid number';
                }
                break;
        }
        
        return $errors;
    }
}