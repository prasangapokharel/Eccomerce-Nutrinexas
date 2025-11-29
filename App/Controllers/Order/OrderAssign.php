<?php

namespace App\Controllers\Order;

use App\Core\Database;
use App\Models\Order;
use Exception;

/**
 * Handles automatic courier assignment logic shared across controllers.
 */
class OrderAssign
{
    private Database $db;
    private Order $orderModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->orderModel = new Order();
    }

    /**
     * Assign courier for a seller-triggered event (e.g. ready_for_pickup).
     */
    public function assignForSeller(int $orderId, ?int $sellerId = null): ?array
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return null;
        }

        if (!empty($order['curior_id'])) {
            return $this->getCourierById((int) $order['curior_id']);
        }

        $sellerCity = $this->getSellerCity($orderId, $sellerId);
        return $this->assignByCity($orderId, $sellerCity);
    }

    /**
     * Assign courier based on provided city, with fallback to any active courier.
     * Matches courier city with seller city (like Daraz/Pathao Parcel).
     */
    public function assignByCity(int $orderId, ?string $city = null): ?array
    {
        if (empty($city)) {
            error_log("OrderAssign: No city provided for order #{$orderId}, cannot assign courier");
            return null;
        }

        // Find courier in same city as seller (exact match required)
        $courier = $this->findCourier($city);
        if (empty($courier) || empty($courier['id'])) {
            error_log("OrderAssign: No active courier found in city '{$city}' for order #{$orderId}");
            return null;
        }

        // Get current order status to preserve it
        $order = $this->orderModel->find($orderId);
        $currentStatus = $order['status'] ?? 'ready_for_pickup';
        
        // Assign courier to order (preserve status, don't auto-update to shipped)
        if ($this->orderModel->assignCuriorToOrder($orderId, (int) $courier['id'], false)) {
            // Ensure status remains ready_for_pickup after assignment
            if ($currentStatus === 'ready_for_pickup') {
                $this->orderModel->update($orderId, ['status' => 'ready_for_pickup']);
            }
            
            error_log("OrderAssign: Successfully assigned courier #{$courier['id']} ({$courier['name']}, city: {$courier['city']}) to order #{$orderId} (seller city: {$city}, status: {$currentStatus})");
            return $courier;
        }

        error_log("OrderAssign: Failed to assign courier #{$courier['id']} to order #{$orderId}");
        return null;
    }

    private function getSellerCity(int $orderId, ?int $sellerId): ?string
    {
        $params = [$orderId];
        $sql = "SELECT s.city 
                FROM order_items oi
                INNER JOIN products p ON oi.product_id = p.id
                LEFT JOIN sellers s ON COALESCE(oi.seller_id, p.seller_id) = s.id
                WHERE oi.order_id = ?
                AND s.city IS NOT NULL AND TRIM(s.city) != ''";

        if ($sellerId) {
            $sql .= " AND COALESCE(oi.seller_id, p.seller_id) = ?";
            $params[] = $sellerId;
        }

        $sql .= " LIMIT 1";

        $row = $this->db->query($sql, $params)->single();
        return isset($row['city']) ? trim((string) $row['city']) : null;
    }

    /**
     * Find courier in same city as seller (exact match required - like Daraz/Pathao Parcel)
     * If multiple couriers in same city, distribute orders evenly
     * 
     * @param string|null $city Seller city
     * @return array|null Courier data or null if not found
     */
    private function findCourier(?string $city): ?array
    {
        if (empty($city)) {
            return null;
        }

        // Find all active couriers in the same city
        $couriers = $this->db->query(
            "SELECT id, name, city 
             FROM curiors 
             WHERE status = 'active' 
             AND LOWER(TRIM(city)) = LOWER(TRIM(?))
             ORDER BY id ASC",
            [$city]
        )->all();

        if (empty($couriers)) {
            error_log("OrderAssign: No active couriers found in city '{$city}'");
            return null;
        }

        // If only one courier, return it
        if (count($couriers) === 1) {
            return $couriers[0];
        }

        // If multiple couriers in same city, find the one with least assigned orders
        // This distributes orders evenly among couriers in the same city
        $courierCounts = [];
        foreach ($couriers as $courier) {
            $count = $this->db->query(
                "SELECT COUNT(*) as count 
                 FROM orders 
                 WHERE curior_id = ? 
                 AND status IN ('ready_for_pickup', 'picked_up', 'in_transit')",
                [$courier['id']]
            )->single()['count'] ?? 0;
            
            $courierCounts[$courier['id']] = [
                'courier' => $courier,
                'count' => $count
            ];
        }

        // Sort by order count (ascending) and return the courier with least orders
        uasort($courierCounts, function($a, $b) {
            return $a['count'] <=> $b['count'];
        });

        $selectedCourier = reset($courierCounts);
        error_log("OrderAssign: Selected courier #{$selectedCourier['courier']['id']} ({$selectedCourier['courier']['name']}) with {$selectedCourier['count']} active orders from " . count($couriers) . " couriers in city '{$city}'");
        
        return $selectedCourier['courier'];
    }

    private function getCourierById(int $curiorId): ?array
    {
        return $this->db->query(
            "SELECT id, name, city FROM curiors WHERE id = ? LIMIT 1",
            [$curiorId]
        )->single();
    }
}

