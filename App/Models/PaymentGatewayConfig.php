<?php

namespace App\Models;

use App\Core\Database;

class PaymentGatewayConfig
{
    protected $table = 'payment_gateways';
    protected $primaryKey = 'id';
    protected $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get gateway configuration by slug
     */
    public function getBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = ? AND is_active = 1";
        $result = $this->db->query($sql)->bind([$slug])->single();
        
        if ($result && isset($result['parameters'])) {
            $result['parameters'] = json_decode($result['parameters'], true);
        }
        
        return $result;
    }
    
    /**
     * Get Khalti configuration
     */
    public function getKhaltiConfig()
    {
        $gateway = $this->getBySlug('khalti');
        if (!$gateway) {
            return null;
        }
        
        $params = $gateway['parameters'] ?? [];
        
        return [
            'secret_key' => $params['secret_key'] ?? getenv('KHALTI_SECRET_KEY'),
            'is_test_mode' => $gateway['is_test_mode'] ?? false,
            'is_active' => $gateway['is_active'] ?? false
        ];
    }
    
    /**
     * Get eSewa configuration
     */
    public function getEsewaConfig()
    {
        $gateway = $this->getBySlug('esewa');
        if (!$gateway) {
            return null;
        }
        
        $params = $gateway['parameters'] ?? [];
        
        return [
            'merchant_id' => $params['merchant_id'] ?? getenv('ESEWA_MERCHANT_ID'),
            'secret_key' => $params['secret_key'] ?? getenv('ESEWA_SECRET_KEY'),
            'is_test_mode' => $gateway['is_test_mode'] ?? false,
            'is_active' => $gateway['is_active'] ?? false
        ];
    }
    
    /**
     * Update gateway parameters
     */
    public function updateParameters($slug, $parameters)
    {
        $sql = "UPDATE {$this->table} SET parameters = ?, updated_at = NOW() WHERE slug = ?";
        return $this->db->query($sql)->bind([json_encode($parameters), $slug])->execute();
    }
    
    /**
     * Create default payment gateways if they don't exist
     */
    public function createDefaultGateways()
    {
        $defaultGateways = [
            [
                'name' => 'Khalti',
                'slug' => 'khalti',
                'type' => 'digital',
                'description' => 'Khalti Digital Wallet Payment',
                'parameters' => [
                    'secret_key' => '',
                    'test_secret_key' => ''
                ],
                'is_active' => 1,
                'is_test_mode' => 1,
                'sort_order' => 1
            ],
            [
                'name' => 'eSewa',
                'slug' => 'esewa',
                'type' => 'digital',
                'description' => 'eSewa Digital Wallet Payment',
                'parameters' => [
                    'merchant_id' => '',
                    'secret_key' => '',
                    'test_merchant_id' => '',
                    'test_secret_key' => ''
                ],
                'is_active' => 1,
                'is_test_mode' => 1,
                'sort_order' => 2
            ],
            [
                'name' => 'Cash on Delivery',
                'slug' => 'cod',
                'type' => 'cod',
                'description' => 'Pay when your order is delivered',
                'parameters' => [
                    'delivery_charge' => 0
                ],
                'is_active' => 1,
                'is_test_mode' => 0,
                'sort_order' => 3
            ]
        ];
        
        foreach ($defaultGateways as $gateway) {
            // Check if gateway already exists
            $existing = $this->db->query("SELECT id FROM {$this->table} WHERE slug = ?", [$gateway['slug']])->single();
            
            if (!$existing) {
                $sql = "INSERT INTO {$this->table} (name, slug, type, description, parameters, is_active, is_test_mode, sort_order, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                $this->db->query($sql)->bind([
                    $gateway['name'],
                    $gateway['slug'],
                    $gateway['type'],
                    $gateway['description'],
                    json_encode($gateway['parameters']),
                    $gateway['is_active'],
                    $gateway['is_test_mode'],
                    $gateway['sort_order']
                ])->execute();
                
                echo "✓ Created default gateway: {$gateway['name']}\n";
            } else {
                echo "✓ Gateway already exists: {$gateway['name']}\n";
            }
        }
    }
    
    /**
     * Validate gateway configuration
     */
    public function validateConfig($slug, $parameters)
    {
        $errors = [];
        
        switch ($slug) {
            case 'khalti':
                if (empty($parameters['secret_key'])) {
                    $errors[] = 'Khalti secret key is required';
                }
                break;
                
            case 'esewa':
                if (empty($parameters['merchant_id'])) {
                    $errors[] = 'eSewa merchant ID is required';
                }
                if (empty($parameters['secret_key'])) {
                    $errors[] = 'eSewa secret key is required';
                }
                break;
                
            case 'cod':
                if (isset($parameters['delivery_charge']) && !is_numeric($parameters['delivery_charge'])) {
                    $errors[] = 'Delivery charge must be a valid number';
                }
                break;
        }
        
        return $errors;
    }
    
    /**
     * Get all active gateways
     */
    public function getActiveGateways()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC";
        $result = $this->db->query($sql)->all();
        
        foreach ($result as &$gateway) {
            if (isset($gateway['parameters'])) {
                $gateway['parameters'] = json_decode($gateway['parameters'], true);
            }
        }
        
        return $result;
    }
    
    /**
     * Toggle gateway status
     */
    public function toggleStatus($slug)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active, updated_at = NOW() WHERE slug = ?";
        return $this->db->query($sql)->bind([$slug])->execute();
    }
    
    /**
     * Toggle test mode
     */
    public function toggleTestMode($slug)
    {
        $sql = "UPDATE {$this->table} SET is_test_mode = NOT is_test_mode, updated_at = NOW() WHERE slug = ?";
        return $this->db->query($sql)->bind([$slug])->execute();
    }
}
