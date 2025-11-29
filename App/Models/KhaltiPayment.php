<?php

namespace App\Models;

use App\Core\Model;

class KhaltiPayment extends Model
{
    protected $table = 'khalti_payments';
    protected $primaryKey = 'id';

    /**
     * Create Khalti payment record
     * Checks for existing payment and updates if found, otherwise creates new
     * Status must be one of: 'pending', 'completed', 'failed' (per database enum)
     */
    public function createPayment($data)
    {
        try {
            // Normalize status to match database enum values
            $status = $data['status'] ?? 'pending';
            if (!in_array($status, ['pending', 'completed', 'failed'])) {
                $status = 'pending'; // Default to pending if invalid status
            }
            
            // Check if payment already exists for this order
            $existingPayment = $this->getByOrderId($data['order_id']);
            
            if ($existingPayment) {
                // Update existing payment record
                $updateData = [
                    'amount' => $data['amount'],
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if (isset($data['pidx']) && !empty($data['pidx'])) {
                    $updateData['pidx'] = $data['pidx'];
                }
                
                if (isset($data['transaction_id']) && !empty($data['transaction_id'])) {
                    $updateData['transaction_id'] = $data['transaction_id'];
                }
                
                if (isset($data['response_data'])) {
                    $updateData['response_data'] = is_string($data['response_data']) 
                        ? $data['response_data'] 
                        : json_encode($data['response_data']);
                }
                
                $this->update($existingPayment['id'], $updateData);
                return $existingPayment['id'];
            }
            
            // Create new payment record - match exact database schema
            $insertData = [
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'],
                'amount' => $data['amount'],
                'pidx' => $data['pidx'] ?? null,
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => $status,
                'response_data' => isset($data['response_data']) 
                    ? (is_string($data['response_data']) ? $data['response_data'] : json_encode($data['response_data']))
                    : null
            ];
            
            // Use direct SQL to ensure proper insertion
            $sql = "INSERT INTO {$this->table} (user_id, order_id, amount, pidx, transaction_id, status, response_data, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $result = $this->db->query($sql)->bind([
                $insertData['user_id'],
                $insertData['order_id'],
                $insertData['amount'],
                $insertData['pidx'],
                $insertData['transaction_id'],
                $insertData['status'],
                $insertData['response_data']
            ])->execute();
            
            return $result ? $this->db->lastInsertId() : false;
            
        } catch (\Exception $e) {
            error_log('KhaltiPayment createPayment error: ' . $e->getMessage());
            error_log('KhaltiPayment createPayment trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Update payment status by order ID
     */
    public function updateStatusByOrderId($orderId, $status, $pidx = null)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW()";
        $params = [$status];
        
        if ($pidx) {
            $sql .= ", pidx = ?";
            $params[] = $pidx;
        }
        
        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;
        
        return $this->db->query($sql)->bind($params)->execute();
    }

    /**
     * Get payment by order ID
     */
    public function getByOrderId($orderId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE order_id = ?";
        return $this->db->query($sql)->bind([$orderId])->single();
    }
}
