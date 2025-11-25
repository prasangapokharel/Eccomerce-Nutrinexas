<?php

namespace App\Controllers\Curior;

use App\Models\Curior\CourierSettlement as SettlementModel;
use App\Models\Order;

class Settlement extends BaseCuriorController
{
    private $settlementModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->settlementModel = new SettlementModel();
        $this->orderModel = new Order();
    }

    /**
     * View settlements
     */
    public function index()
    {
        $status = $_GET['status'] ?? null;
        $settlements = $this->settlementModel->getByCuriorId($this->curiorId, $status);
        $totalCollected = $this->settlementModel->getTotalCollected($this->curiorId);
        
        $this->view('curior/settlements/index', [
            'settlements' => $settlements,
            'totalCollected' => $totalCollected,
            'status' => $status,
            'page' => 'settlement',
            'title' => 'COD Settlements'
        ]);
    }

    /**
     * Mark COD as collected
     */
    public function collectCod()
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

        if ($order['payment_method_id'] != 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Order is not COD']);
            return;
        }

        try {
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

            $this->jsonResponse(['success' => true, 'message' => 'COD collected successfully']);
        } catch (\Exception $e) {
            error_log('Curior Settlement: Collect COD error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error collecting COD']);
        }
    }
}

