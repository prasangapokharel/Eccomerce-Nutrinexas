<?php

namespace App\Controllers\Curior;

use App\Models\Order;
use App\Core\Database;

class Pickup extends BaseCuriorController
{
    private $orderModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->db = Database::getInstance();
    }

    /**
     * Pickup management page
     */
    public function index()
    {
        $sql = "SELECT o.*, 
                       o.customer_name as order_customer_name,
                       CONCAT(u.first_name, ' ', u.last_name) as user_full_name,
                       u.email as customer_email,
                       pm.name as payment_method
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.id
                WHERE o.curior_id = ? 
                AND o.status IN ('processing', 'confirmed', 'shipped')
                ORDER BY o.created_at DESC";
        
        $orders = $this->db->query($sql, [$this->curiorId])->all();
        
        $this->view('curior/pickup/index', [
            'orders' => $orders,
            'page' => 'pickup',
            'title' => 'Pickup Management'
        ]);
    }

    /**
     * Mark order as picked with proof
     */
    public function markPicked()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $pickupProof = $_FILES['pickup_proof'] ?? null;
        $notes = trim($_POST['notes'] ?? '');

        if ($orderId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID']);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $proofPath = null;
            if ($pickupProof && $pickupProof['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pickup_proofs' . DIRECTORY_SEPARATOR;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = 'jpg';
                $filename = 'pickup_' . $orderId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                $compressed = \App\Helpers\ImageCompressor::compressToMaxSize(
                    $pickupProof['tmp_name'],
                    $filepath,
                    300
                );
                
                if ($compressed) {
                    $proofPath = 'uploads/pickup_proofs/' . $filename;
                } else {
                    error_log('Failed to compress pickup proof for order #' . $orderId);
                }
            }

            $updateData = [
                'status' => 'picked_up',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $activityModel = new \App\Models\Curior\OrderActivity();
                $activityData = [
                    'proof' => $proofPath,
                    'notes' => $notes,
                    'picked_at' => date('Y-m-d H:i:s')
                ];
                $activityModel->logEntry($orderId, 'pickup_confirmed', json_encode($activityData), 'curior_' . $this->curiorId);
                
                $notificationService = new \App\Services\OrderNotificationService();
                $notificationService->sendStatusChangeSMS($orderId, $order['status'], 'picked_up');
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Pickup confirmed successfully']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to confirm pickup']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Pickup: Mark picked error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error confirming pickup']);
        }
    }
}

