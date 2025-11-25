<?php

namespace App\Models;

use App\Core\Model;
use Exception;
use PDO;
use PDOException;

class DeliveryCharge extends Model
{
    protected $table = 'delivery_charges';
    protected $primaryKey = 'id';

    /**
     * Get all delivery charges
     *
     * @return array
     */
    public function getAllCharges()
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY location_name ASC";
        return $this->db->query($sql)->all();
    }

    /**
     * Get delivery charge by location name
     *
     * @param string $location
     * @return array|false
     */
    public function getChargeByLocation($location)
    {
        $sql = "SELECT * FROM {$this->table} WHERE location_name = ?";
        return $this->db->query($sql)->bind([$location])->single();
    }

    /**
     * Get free delivery charge
     *
     * @return array|false
     */
    public function getFreeDeliveryCharge()
    {
        $sql = "SELECT * FROM {$this->table} WHERE location_name = 'Free'";
        return $this->db->query($sql)->single();
    }

    /**
     * Set all delivery charges to 0 (Free delivery)
     *
     * @return bool
     */
    public function enableFreeDelivery()
    {
        try {
            $sql = "UPDATE {$this->table} SET charge = 0 WHERE location_name != 'Free'";
            $this->db->query($sql)->execute();
            return true;
        } catch (Exception $e) {
            error_log('DeliveryCharge enableFreeDelivery error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore default delivery charges for all cities
     *
     * @return bool
     */
    public function restoreDefaultCharges()
    {
        try {
            $defaultCharges = [
                'Kathmandu' => 150,
                'Pokhara' => 200,
                'Lalitpur' => 150,
                'Bharatpur' => 250,
                'Biratnagar' => 300,
                'Birgunj' => 350,
                'Butwal' => 300,
                'Dharan' => 280,
                'Nepalgunj' => 400,
                'Itahari' => 280,
                'Hetauda' => 200,
                'Janakpur' => 350,
                'Lahan' => 300,
                'Rajbiraj' => 300,
                'Kalaiya' => 300,
                'Kirtipur' => 150,
                'Dhangadhi' => 450,
                'Tulsipur' => 350,
                'Jitpur Simara' => 250,
                'Tikapur' => 400,
                'Gulariya' => 400,
                'Patan' => 150,
                'Madhyapur Thimi' => 150,
                'Birendranagar' => 400
            ];
            
            foreach ($defaultCharges as $location => $charge) {
                $existing = $this->getChargeByLocation($location);
                if ($existing) {
                    $this->update($existing['id'], ['charge' => $charge]);
                } else {
                    $this->create(['location_name' => $location, 'charge' => $charge]);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log('DeliveryCharge restoreDefaultCharges error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all cities with their current charges
     *
     * @return array
     */
    public function getAllCitiesWithCharges()
    {
        $sql = "SELECT location_name, charge FROM {$this->table} WHERE location_name != 'Free' ORDER BY location_name ASC";
        return $this->db->query($sql)->all();
    }

    /**
     * Set default delivery fee for all locations
     */
    public function setDefaultFeeForAll($defaultFee)
    {
        try {
            $sql = "UPDATE delivery_charges SET charge = :charge";
            $stmt = $this->db->getPdo()->prepare($sql);
            $stmt->bindParam(':charge', $defaultFee, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error setting default fee for all: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if free delivery is currently enabled
     *
     * @return bool
     */
    public function isFreeDeliveryEnabled()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE location_name != 'Free' AND charge = 0";
        $result = $this->db->query($sql)->single();
        return $result['count'] > 0;
    }
}