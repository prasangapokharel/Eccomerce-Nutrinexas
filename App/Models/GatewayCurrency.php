<?php
namespace App\Models;

use App\Core\Model;

class GatewayCurrency extends Model
{
    protected $table = 'gateway_currencies';
    protected $primaryKey = 'id';

    /**
     * Get currencies for a specific gateway
     */
    public function getByGatewayId($gatewayId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE gateway_id = ? AND is_active = 1";
        $result = $this->query($sql, [$gatewayId]);
        return is_array($result) ? $result : [];
    }

    /**
     * Get currency by gateway and currency code
     */
    public function getByCurrencyCode($gatewayId, $currencyCode)
    {
        $sql = "SELECT * FROM {$this->table} WHERE gateway_id = ? AND currency_code = ?";
        $result = $this->query($sql, [$gatewayId, $currencyCode]);
        
        if (is_array($result) && !empty($result)) {
            return isset($result[0]) ? $result[0] : $result;
        }
        
        return null;
    }

    /**
     * Create or update currency for gateway
     */
    public function createOrUpdate($gatewayId, $currencyData)
    {
        $existing = $this->getByCurrencyCode($gatewayId, $currencyData['currency_code']);
        
        if ($existing) {
            return $this->updateCurrency($existing['id'], $currencyData);
        } else {
            return $this->createCurrency($gatewayId, $currencyData);
        }
    }

    /**
     * Create new currency
     */
    public function createCurrency($gatewayId, $data)
    {
        $sql = "INSERT INTO {$this->table} 
                (gateway_id, currency_code, currency_symbol, conversion_rate, min_limit, max_limit, percentage_charge, fixed_charge, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->query($sql, [
            $gatewayId,
            $data['currency_code'],
            $data['currency_symbol'],
            $data['conversion_rate'],
            $data['min_limit'] ?? null,
            $data['max_limit'] ?? null,
            $data['percentage_charge'] ?? 0,
            $data['fixed_charge'] ?? 0,
            $data['is_active'] ?? 1
        ]);
        
        return $result !== false;
    }

    /**
     * Update currency
     */
    public function updateCurrency($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                currency_code = ?, currency_symbol = ?, conversion_rate = ?, 
                min_limit = ?, max_limit = ?, percentage_charge = ?, fixed_charge = ?, is_active = ?
                WHERE id = ?";
        
        $result = $this->query($sql, [
            $data['currency_code'],
            $data['currency_symbol'],
            $data['conversion_rate'],
            $data['min_limit'] ?? null,
            $data['max_limit'] ?? null,
            $data['percentage_charge'] ?? 0,
            $data['fixed_charge'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ]);
        
        return $result !== false;
    }

    /**
     * Delete currency
     */
    public function deleteCurrency($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Toggle currency status
     */
    public function toggleStatus($id)
    {
        $sql = "UPDATE {$this->table} SET is_active = NOT is_active WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result !== false;
    }

    /**
     * Update currencies for a gateway
     */
    public function updateGatewayCurrencies($gatewayId, $currencies)
    {
        // First, delete existing currencies for this gateway
        $this->query("DELETE FROM {$this->table} WHERE gateway_id = ?", [$gatewayId]);
        
        // Then insert new currencies
        foreach ($currencies as $currency) {
            if (!empty($currency['currency_code'])) {
                $this->createCurrency($gatewayId, $currency);
            }
        }
        
        return true;
    }

    /**
     * Get all supported currencies
     */
    public function getAllCurrencies()
    {
        return [
            'NPR' => ['name' => 'Nepalese Rupee', 'symbol' => '₹'],
            'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
            'EUR' => ['name' => 'Euro', 'symbol' => '€'],
            'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
            'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹'],
            'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥'],
            'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥'],
            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$'],
            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$']
        ];
    }
}