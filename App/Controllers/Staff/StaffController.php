<?php
namespace App\Controllers\Staff;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Staff;
use App\Models\Order;

class StaffController extends Controller
{
    private $staffModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->staffModel = new Staff();
        $this->orderModel = new Order();
    }

    /**
     * Staff login page
     */
    public function login()
    {
        // If already logged in, redirect to dashboard
        if (Session::has('staff_id')) {
            $this->redirect('staff/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $this->setFlash('error', 'Please fill in all fields');
                $this->view('staff/login', [
                    'title' => 'Staff Login'
                ]);
                return;
            }

            $staff = $this->staffModel->verifyCredentials($email, $password);
            
            if ($staff) {
                if ($staff['status'] === 'inactive') {
                    $this->setFlash('error', 'Your account is inactive. Please contact administrator.');
                    $this->view('staff/login', [
                        'title' => 'Staff Login'
                    ]);
                    return;
                }

                Session::set('staff_id', $staff['id']);
                Session::set('staff_name', $staff['name']);
                Session::set('staff_email', $staff['email']);
                
                $this->setFlash('success', 'Welcome back, ' . $staff['name'] . '!');
                $this->redirect('staff/dashboard');
            } else {
                $this->setFlash('error', 'Invalid email or password');
                $this->view('staff/login', [
                    'title' => 'Staff Login'
                ]);
            }
        } else {
            $this->view('staff/login', [
                'title' => 'Staff Login'
            ]);
        }
    }

    /**
     * Staff dashboard
     */
    public function dashboard()
    {
        $this->requireStaff();
        
        $staffId = Session::get('staff_id');
        
        // Get orders assigned to this staff member
        $orders = $this->orderModel->getOrdersByStaff($staffId);
        
        // Get order statistics
        $stats = $this->orderModel->getStaffStats($staffId);
        
        $this->view('staff/dashboard', [
            'orders' => $orders,
            'stats' => $stats,
            'title' => 'Staff Dashboard'
        ]);
    }

    /**
     * View order details
     */
    public function viewOrder($orderId = null)
    {
        $this->requireStaff();
        
        if (!$orderId) {
            $this->setFlash('error', 'Invalid order ID');
            $this->redirect('staff/dashboard');
            return;
        }

        $order = $this->orderModel->getOrderWithDetails($orderId);
        
        if (!$order) {
            $this->setFlash('error', 'Order not found');
            $this->redirect('staff/dashboard');
            return;
        }

        // Verify the order is assigned to this staff member
        if ($order['staff_id'] != Session::get('staff_id')) {
            $this->setFlash('error', 'You are not authorized to view this order');
            $this->redirect('staff/dashboard');
            return;
        }

        $this->view('staff/order', [
            'order' => $order,
            'title' => 'Order Details'
        ]);
    }


    /**
     * Update order status
     */
    public function updateOrderStatus()
    {
        $this->requireStaff();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $action = trim($_POST['action'] ?? '');
            $packageCount = (int)($_POST['package_count'] ?? 0);
            
            if ($orderId <= 0 || empty($status)) {
                $this->setFlash('error', 'Invalid order ID or status');
                $this->redirect('staff/dashboard');
                return;
            }
            
            // Verify the order exists and is assigned to a staff member
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->setFlash('error', 'Order not found');
                $this->redirect('staff/dashboard');
                return;
            }
            
            // More flexible: Allow staff to update orders even if not assigned to them
            // This allows for real-world scenarios where staff need to help each other
            $currentStaffId = Session::get('staff_id');
            $isAssignedToMe = ($order['staff_id'] == $currentStaffId);
            
            // If order is not assigned to this staff member, allow update but log it
            if (!$isAssignedToMe && !empty($order['staff_id'])) {
                error_log("Staff #{$currentStaffId} updating order #{$orderId} assigned to staff #{$order['staff_id']}");
            }
            
            // If order has no staff assigned, assign it to current staff
            if (!$order['staff_id']) {
                $this->orderModel->update($orderId, ['staff_id' => $currentStaffId]);
            }
            
            // Update order status and package count
            $result = $this->orderModel->updateStatusAndPackageCount($orderId, $status, $packageCount);
            
            if ($result) {
                // Process referral earnings if order is marked as delivered
                if ($status === 'delivered') {
                    try {
                        $referralService = new \App\Services\ReferralEarningService();
                        $referralService->processReferralEarning($orderId);
                    } catch (\Exception $e) {
                        error_log('StaffController: Error processing referral earning: ' . $e->getMessage());
                    }
                }
                
                $this->setFlash('success', 'Order status updated successfully!');
            } else {
                $this->setFlash('error', 'Failed to update order status');
            }
            
            $this->redirect('staff/dashboard');
        } else {
            $this->setFlash('error', 'Invalid request method');
            $this->redirect('staff/dashboard');
        }
    }

    /**
     * Validate workflow chain
     */
    private function validateWorkflow($currentStatus, $newStatus, $action)
    {
        // Simple workflow: any status can be marked as 'processing' (packed)
        // except already processed orders
        if ($newStatus === 'processing') {
            return !in_array($currentStatus, ['shipped', 'delivered', 'cancelled']);
        }
        
        return false;
    }

    /**
     * Logout
     */
    public function logout()
    {
        Session::destroy();
        $this->setFlash('success', 'You have been logged out successfully');
        $this->redirect('staff/login');
    }

    /**
     * Get order count for alert system
     */
    public function getOrderCount()
    {
        if (!Session::has('staff_id')) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $staffId = Session::get('staff_id');
        $db = \App\Core\Database::getInstance();
        $sql = "SELECT COUNT(*) as count FROM orders WHERE staff_id = ?";
        $result = $db->query($sql, [$staffId])->single();
        
        $this->jsonResponse(['success' => true, 'count' => $result['count']]);
    }

    /**
     * Require staff to be logged in
     */
    private function requireStaff()
    {
        if (!Session::has('staff_id')) {
            // Check if this is an AJAX request
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                $this->jsonResponse(['success' => false, 'message' => 'Please login to access this page']);
                return;
            }
            
            $this->setFlash('error', 'Please login to access this page');
            $this->redirect('staff/login');
        }
    }
}
