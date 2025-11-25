<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CancelLog;
use App\Models\Order;
use Exception;

class CancelController extends Controller
{
    private $cancelLogModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->cancelLogModel = new CancelLog();
        $this->orderModel = new Order();
    }

    /**
     * Admin: List all cancel requests
     */
    public function adminIndex()
    {
        $this->requireAdmin();
        
        try {
            // Pagination
            $page = max(1, (int)($_GET['page'] ?? 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;

            // Get all cancellations first
            $allCancels = $this->cancelLogModel->getAllWithOrders();
            
            // Get total count
            $totalCount = count($allCancels);
            $totalPages = ceil($totalCount / $perPage);
            
            // Apply pagination
            $cancels = array_slice($allCancels, $offset, $perPage);
            
            $this->view('admin/cancels/index', [
                'title' => 'Order Cancellations',
                'cancels' => $cancels,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'perPage' => $perPage
            ]);
        } catch (Exception $e) {
            error_log('Cancel index error: ' . $e->getMessage());
            error_log('Cancel index stack trace: ' . $e->getTraceAsString());
            
            // Return empty array if table doesn't exist or query fails
            $this->view('admin/cancels/index', [
                'title' => 'Order Cancellations',
                'cancels' => [],
                'currentPage' => 1,
                'totalPages' => 0,
                'totalCount' => 0,
                'perPage' => 10,
                'error' => 'Failed to load cancellations: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Admin: Update cancel status
     */
    public function updateStatus($id)
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/cancels');
            return;
        }
        
        $status = $_POST['status'] ?? '';
        $allowedStatuses = ['processing', 'refunded', 'failed'];
        
        if (!in_array($status, $allowedStatuses)) {
            $this->setFlash('error', 'Invalid status');
            $this->redirect('admin/cancels');
            return;
        }
        
        try {
            $result = $this->cancelLogModel->updateStatus($id, $status);
            
            if ($result) {
                $this->setFlash('success', 'Cancel status updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update status');
            }
        } catch (Exception $e) {
            error_log('Update cancel status error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating status: ' . $e->getMessage());
        }
        
        $this->redirect('admin/cancels');
    }

    /**
     * Admin: View cancel details
     */
    public function viewCancel($id)
    {
        $this->requireAdmin();
        
        try {
            $cancel = $this->cancelLogModel->find($id);
            
            if (!$cancel) {
                $this->setFlash('error', 'Cancel request not found');
                $this->redirect('admin/cancels');
                return;
            }
            
            $this->view('admin/cancels/view', [
                'title' => 'Cancel Request Details',
                'cancel' => $cancel
            ]);
        } catch (Exception $e) {
            error_log('View cancel error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to load cancel details');
            $this->redirect('admin/cancels');
        }
    }
}

