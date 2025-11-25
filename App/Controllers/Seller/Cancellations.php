<?php

namespace App\Controllers\Seller;

use App\Models\CancelLog;
use Exception;

class Cancellations extends BaseSellerController
{
    private $cancelLogModel;

    public function __construct()
    {
        parent::__construct();
        $this->cancelLogModel = new CancelLog();
    }

    /**
     * List all cancellation requests for this seller
     */
    public function index()
    {
        $status = $_GET['status'] ?? '';
        
        $cancels = $this->getCancellations($status);

        $this->view('seller/cancellations/index', [
            'title' => 'Cancellation Requests',
            'cancels' => $cancels,
            'statusFilter' => $status
        ]);
    }

    /**
     * View cancellation details
     */
    public function detail($id)
    {
        $cancel = $this->cancelLogModel->find($id);
        
        if (!$cancel || ($cancel['seller_id'] ?? null) != $this->sellerId) {
            $this->setFlash('error', 'Cancellation request not found');
            $this->redirect('seller/cancellations');
            return;
        }

        $this->view('seller/cancellations/detail', [
            'title' => 'Cancellation Details',
            'cancel' => $cancel
        ]);
    }

    /**
     * Update cancellation status (Seller can update to provide feedback)
     */
    public function updateStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/cancellations');
            return;
        }

        $cancel = $this->cancelLogModel->find($id);
        
        if (!$cancel || ($cancel['seller_id'] ?? null) != $this->sellerId) {
            $this->setFlash('error', 'Cancellation request not found');
            $this->redirect('seller/cancellations');
            return;
        }

        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['processing', 'refunded', 'failed'];
        
        if (!in_array($status, $allowedStatuses)) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('seller/cancellations/detail/' . $id);
            return;
        }

        try {
            $result = $this->cancelLogModel->updateStatus($id, $status);
            
            if ($result) {
                $this->setFlash('success', 'Status updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update status');
            }
        } catch (Exception $e) {
            error_log('Update cancel status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating status');
        }
        
        $this->redirect('seller/cancellations/detail/' . $id);
    }

    /**
     * Get cancellations with filter
     */
    private function getCancellations($status)
    {
        $sql = "SELECT c.*, o.invoice, o.customer_name, u.email as customer_email, o.total_amount, o.status as order_status
                FROM order_cancel_log c
                LEFT JOIN orders o ON c.order_id = o.id
                LEFT JOIN users u ON o.user_id = u.id
                WHERE c.seller_id = ?";
        
        $params = [$this->sellerId];
        
        if (!empty($status)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        $db = \App\Core\Database::getInstance();
        return $db->query($sql, $params)->all();
    }
}

