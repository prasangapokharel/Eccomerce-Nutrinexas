<?php

namespace App\Controllers\Curior;

use App\Models\Curior\CourierLocation as LocationModel;
use App\Models\Order;

class Location extends BaseCuriorController
{
    private $locationModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->locationModel = new LocationModel();
        $this->orderModel = new Order();
    }

    /**
     * Update courier location
     */
    public function update()
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
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Location updated successfully',
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to update location']);
            }
        } catch (\Exception $e) {
            error_log('Curior Location: Update error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error updating location']);
        }
    }

    /**
     * Get location history for an order
     */
    public function getHistory($orderId = null)
    {
        if (!$orderId) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID'], 400);
            return;
        }

        $order = $this->orderModel->find($orderId);
        if (!$order || $order['curior_id'] != $this->curiorId) {
            $this->jsonResponse(['success' => false, 'message' => 'Order not found or not assigned to you']);
            return;
        }

        $locations = $this->locationModel->getByOrderId($orderId);
        
        $this->jsonResponse([
            'success' => true,
            'locations' => $locations
        ]);
    }

    /**
     * Get latest location
     */
    public function getLatest()
    {
        if (!$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        $location = $this->locationModel->getLatestLocation($this->curiorId);
        
        $this->jsonResponse([
            'success' => true,
            'location' => $location
        ]);
    }
}

