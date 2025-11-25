<?php

namespace App\Controllers\Curior;

use App\Models\Order as OrderModel;
use App\Models\Curior\OrderActivity;
use App\Models\Curior\CourierSettlement;
use App\Models\Curior\CourierLocation;
use App\Core\Database;

class Order extends BaseCuriorController
{
    private $orderModel;
    private $activityModel;
    private $settlementModel;
    private $locationModel;
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new OrderModel();
        $this->activityModel = new OrderActivity();
        $this->settlementModel = new CourierSettlement();
        $this->locationModel = new CourierLocation();
        $this->db = Database::getInstance();
    }

    /**
     * View order details
     */
    public function viewOrderDetails($id = null)
    {
        if (!$id) {
            $this->setFlash('error', 'Invalid order ID');
            $this->redirect('curior/dashboard');
            return;
        }

        $order = $this->orderModel->getOrderWithDetails($id);
        
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->setFlash('error', 'Order not found or not assigned to you');
            $this->redirect('curior/dashboard');
            return;
        }

        $this->view('curior/orders/view', [
            'order' => $order,
            'page' => 'orders',
            'title' => 'Order Details'
        ]);
    }

    /**
     * Get orders list (AJAX)
     */
    public function list()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $status = $_GET['status'] ?? null;
        $orders = $this->orderModel->getOrdersByCurior($this->curiorId);
        
        if ($status) {
            $orders = array_filter($orders, function($order) use ($status) {
                return $order['status'] === $status;
            });
        }

        $this->jsonResponse([
            'success' => true,
            'orders' => array_values($orders)
        ]);
    }

    /**
     * Confirm pickup from seller location
     */
    public function confirmPickup()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $scanCode = trim($_POST['scan_code'] ?? '');
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

        if ($scanCode && $order['invoice'] !== $scanCode && (string)$order['id'] !== $scanCode) {
            $this->jsonResponse(['success' => false, 'message' => 'Scanned code does not match order']);
            return;
        }

        $allowedStatuses = ['processing', 'confirmed', 'shipped'];
        if (!in_array($order['status'], $allowedStatuses)) {
            $this->jsonResponse(['success' => false, 'message' => 'Order must be in processing, confirmed, or shipped status']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $updateData = [
                'status' => 'picked_up',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $this->logOrderActivity($orderId, 'pickup_confirmed', $notes);
                $this->notifyOrderUpdate($orderId, 'pickup_confirmed');
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Pickup confirmed successfully']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to confirm pickup']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Pickup confirmation error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error confirming pickup']);
        }
    }

    /**
     * Update order status to in-transit
     */
    public function updateTransit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');
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

        $allowedStatuses = ['picked_up', 'shipped', 'in_transit'];
        if (!in_array($order['status'], $allowedStatuses)) {
            $this->jsonResponse(['success' => false, 'message' => 'Order must be picked up or shipped']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $updateData = [
                'status' => 'in_transit',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $activityData = ['location' => $location, 'notes' => $notes];
                $this->logOrderActivity($orderId, 'in_transit', json_encode($activityData));
                $this->notifyOrderUpdate($orderId, 'in_transit');
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Order status updated to in-transit']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update status']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Transit update error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating status']);
        }
    }

    /**
     * Mark delivery attempted (customer unavailable)
     */
    public function attemptDelivery()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Customer unavailable');
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

        if (!in_array($order['status'], ['in_transit', 'picked_up', 'shipped'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Order must be in transit']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $activityData = [
                'reason' => $reason,
                'notes' => $notes,
                'attempted_at' => date('Y-m-d H:i:s')
            ];

            $this->logOrderActivity($orderId, 'delivery_attempted', json_encode($activityData));
            $this->notifyOrderUpdate($orderId, 'delivery_attempted', $activityData);
            
            $this->db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Delivery attempt logged successfully']);
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Delivery attempt error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error logging delivery attempt']);
        }
    }

    /**
     * Mark order as delivered
     */
    public function confirmDelivery()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $otp = trim($_POST['otp'] ?? '');
        $signature = $_POST['signature'] ?? '';
        $deliveryProof = $_FILES['delivery_proof'] ?? null;
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

        if (!in_array($order['status'], ['in_transit', 'picked_up', 'shipped'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Order must be in transit']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $deliveryProofPath = null;
            if ($deliveryProof && $deliveryProof['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'delivery_proofs' . DIRECTORY_SEPARATOR;
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = 'jpg';
                $filename = 'delivery_' . $orderId . '_' . time() . '.' . $extension;
                $filepath = $uploadDir . $filename;
                
                $compressed = \App\Helpers\ImageCompressor::compressToMaxSize(
                    $deliveryProof['tmp_name'],
                    $filepath,
                    300
                );
                
                if ($compressed) {
                    $deliveryProofPath = 'uploads/delivery_proofs/' . $filename;
                } else {
                    error_log('Failed to compress delivery proof for order #' . $orderId);
                }
            }

            $updateData = [
                'status' => 'delivered',
                'delivered_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($order['payment_status'] === 'pending') {
                $updateData['payment_status'] = 'paid';
            }

            if ($this->orderModel->update($orderId, $updateData)) {
                $activityData = [
                    'otp' => $otp,
                    'signature' => $signature,
                    'delivery_proof' => $deliveryProofPath,
                    'notes' => $notes,
                    'delivered_at' => date('Y-m-d H:i:s')
                ];

                $this->logOrderActivity($orderId, 'delivered', json_encode($activityData));
                $this->notifyOrderUpdate($orderId, 'delivered', $activityData);
                
                $this->processPostDelivery($orderId);
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Order delivered successfully']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update order status']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Delivery confirmation error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error confirming delivery']);
        }
    }

    /**
     * Accept return pickup
     */
    public function acceptReturn()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
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

        if ($order['status'] !== 'return_requested') {
            $this->jsonResponse(['success' => false, 'message' => 'Order return not requested']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $updateData = [
                'status' => 'return_picked_up',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $this->logOrderActivity($orderId, 'return_picked_up', $notes);
                $this->notifyOrderUpdate($orderId, 'return_picked_up');
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Return pickup confirmed']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to confirm return pickup']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Return pickup error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error confirming return pickup']);
        }
    }

    /**
     * Update return transit status
     */
    public function updateReturnTransit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $location = trim($_POST['location'] ?? '');

        if ($orderId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID']);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        if ($order['status'] !== 'return_picked_up') {
            $this->jsonResponse(['success' => false, 'message' => 'Return must be picked up first']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $updateData = [
                'status' => 'return_in_transit',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $this->logOrderActivity($orderId, 'return_in_transit', json_encode(['location' => $location]));
                $this->notifyOrderUpdate($orderId, 'return_in_transit');
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Return transit updated']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update return transit']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Return transit error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating return transit']);
        }
    }

    /**
     * Complete return drop-off to seller
     */
    public function completeReturn()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
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

        if ($order['status'] !== 'return_in_transit') {
            $this->jsonResponse(['success' => false, 'message' => 'Return must be in transit']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $updateData = [
                'status' => 'returned',
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->orderModel->update($orderId, $updateData)) {
                $this->logOrderActivity($orderId, 'returned', $notes);
                $this->notifyOrderUpdate($orderId, 'returned');
                
                $this->processReturnRefund($orderId);
                
                $this->db->commit();
                $this->jsonResponse(['success' => true, 'message' => 'Return completed successfully']);
            } else {
                $this->db->rollback();
                $this->jsonResponse(['success' => false, 'message' => 'Failed to complete return']);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Return completion error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error completing return']);
        }
    }

    /**
     * Log order activity
     */
    private function logOrderActivity($orderId, $action, $data = '')
    {
        try {
            $this->activityModel->logEntry($orderId, $action, $data, 'curior_' . $this->curiorId);
        } catch (\Exception $e) {
            error_log('Failed to log order activity: ' . $e->getMessage());
        }
    }

    /**
     * Handle COD collection
     */
    public function handleCODCollection()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $codAmount = floatval($_POST['cod_amount'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($orderId <= 0 || $codAmount <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID or COD amount']);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        try {
            $this->db->beginTransaction();

            $existing = $this->settlementModel->getByOrderId($orderId);
            
            if ($existing) {
                $this->settlementModel->updateSettlement($existing['id'], [
                    'cod_amount' => $codAmount,
                    'status' => 'collected',
                    'collected_at' => date('Y-m-d H:i:s'),
                    'notes' => $notes
                ]);
            } else {
                $this->settlementModel->createSettlement($this->curiorId, $orderId, $codAmount, 'collected');
            }

            $this->orderModel->update($orderId, [
                'payment_status' => 'paid',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->logOrderActivity($orderId, 'cod_collected', json_encode([
                'amount' => $codAmount,
                'notes' => $notes
            ]));

            $this->db->commit();
            $this->jsonResponse(['success' => true, 'message' => 'COD collected successfully']);
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('Curior Order: Collect COD error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error collecting COD']);
        }
    }

    /**
     * Update location during delivery
     */
    public function updateLocation()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $latitude = floatval($_POST['latitude'] ?? 0);
        $longitude = floatval($_POST['longitude'] ?? 0);
        $address = trim($_POST['address'] ?? '');

        if ($orderId <= 0 || $latitude == 0 || $longitude == 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid location data']);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        try {
            if ($this->locationModel->logLocation($this->curiorId, $orderId, $latitude, $longitude, $address)) {
                $this->logOrderActivity($orderId, 'location_updated', json_encode([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => $address
                ]));
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Location updated successfully',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update location']);
            }
        } catch (\Exception $e) {
            error_log('Curior Order: Update location error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating location']);
        }
    }

    /**
     * Notify order update
     */
    private function notifyOrderUpdate($orderId, $action, $data = [])
    {
        try {
            $order = $this->orderModel->find($orderId);
            if (!$order) return;

            $notificationService = new \App\Services\OrderNotificationService();
            $oldStatus = $order['status'];
            $newStatus = $this->getStatusFromAction($action);
            
            if ($newStatus) {
                $notificationService->sendStatusChangeSMS($orderId, $oldStatus, $newStatus);
            }
        } catch (\Exception $e) {
            error_log('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Get status from action
     */
    private function getStatusFromAction($action)
    {
        $statusMap = [
            'pickup_confirmed' => 'picked_up',
            'in_transit' => 'in_transit',
            'delivery_attempted' => 'delivery_attempted',
            'delivered' => 'delivered',
            'return_picked_up' => 'return_picked_up',
            'return_in_transit' => 'return_in_transit',
            'returned' => 'returned'
        ];
        
        return $statusMap[$action] ?? null;
    }

    /**
     * Process post-delivery tasks
     */
    private function processPostDelivery($orderId)
    {
        try {
            $referralService = new \App\Services\ReferralEarningService();
            $referralService->processReferralEarning($orderId);
        } catch (\Exception $e) {
            error_log('Curior Order: Error processing referral earning: ' . $e->getMessage());
        }
        
        try {
            $sellerBalanceService = new \App\Services\SellerBalanceService();
            $balanceResult = $sellerBalanceService->processBalanceRelease($orderId);
            
            if ($balanceResult['success']) {
                error_log("Seller balance released for order #{$orderId}: रु " . ($balanceResult['total_released'] ?? 0));
            }
        } catch (\Exception $e) {
            error_log("Seller balance service error for order #{$orderId}: " . $e->getMessage());
        }
    }

    /**
     * Process return refund
     */
    private function processReturnRefund($orderId)
    {
        try {
            $order = $this->orderModel->find($orderId);
            if (!$order) return;

            $db = Database::getInstance();
            $db->query(
                "UPDATE orders SET refund_status = 'processing', updated_at = NOW() WHERE id = ?",
                [$orderId]
            )->execute();
            
            error_log("Return refund processing initiated for order #{$orderId}");
        } catch (\Exception $e) {
            error_log('Curior Order: Error processing return refund: ' . $e->getMessage());
        }
    }
}

