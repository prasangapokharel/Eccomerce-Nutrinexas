<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\Staff;
use App\Models\Order;
use App\Core\Session;
use Exception;

/**
 * Staff API Controller
 * Clean, production-ready API for staff management
 */
class StaffApiController extends Controller
{
    private $staffModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->staffModel = new Staff();
        $this->orderModel = new Order();
        
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Get all staff members
     * GET /api/staff
     */
    public function index()
    {
        try {
            $staff = $this->staffModel->getAll();
            
            $this->sendResponse([
                'success' => true,
                'data' => $staff,
                'count' => count($staff)
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff', 500);
        }
    }

    /**
     * Get staff by ID
     * GET /api/staff/{id}
     */
    public function show($id)
    {
        try {
            $staff = $this->staffModel->find($id);
            
            if (!$staff) {
                $this->sendError('Staff not found', 404);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'data' => $staff
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff', 500);
        }
    }

    /**
     * Get staff orders
     * GET /api/staff/{id}/orders
     */
    public function orders($id)
    {
        try {
            $orders = $this->orderModel->getOrdersByStaff($id);
            
            $this->sendResponse([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff orders', 500);
        }
    }

    /**
     * Get staff statistics
     * GET /api/staff/{id}/stats
     */
    public function stats($id)
    {
        try {
            $stats = $this->staffModel->getStats($id);
            
            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff statistics', 500);
        }
    }

    /**
     * Get unassigned orders
     * GET /api/staff/unassigned-orders
     */
    public function unassignedOrders()
    {
        try {
            $orders = $this->orderModel->getUnassignedOrders();
            
            $this->sendResponse([
                'success' => true,
                'data' => $orders,
                'count' => count($orders)
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch unassigned orders', 500);
        }
    }

    /**
     * Update order status
     * POST /api/staff/update-order-status
     */
    public function updateOrderStatus()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['order_id']) || !isset($input['status'])) {
                $this->sendError('Order ID and status are required', 400);
                return;
            }

            $orderId = $input['order_id'];
            $status = $input['status'];
            $note = $input['note'] ?? '';

            $result = $this->orderModel->updateOrderStatus($orderId, $status);
            
            if ($result) {
                // Process referral earnings if order is marked as delivered
                if ($status === 'delivered') {
                    try {
                        $referralService = new \App\Services\ReferralEarningService();
                        $referralService->processReferralEarning($orderId);
                    } catch (\Exception $e) {
                        error_log('StaffApiController: Error processing referral earning: ' . $e->getMessage());
                    }
                }
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Order status updated successfully'
                ]);
            } else {
                $this->sendError('Failed to update order status', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Failed to update order status', 500);
        }
    }

    /**
     * Assign order to staff
     * POST /api/staff/assign-order
     */
    public function assignOrder()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['order_id']) || !isset($input['staff_id'])) {
                $this->sendError('Order ID and Staff ID are required', 400);
                return;
            }

            $orderId = $input['order_id'];
            $staffId = $input['staff_id'];

            $result = $this->orderModel->update($orderId, ['staff_id' => $staffId]);
            
            if ($result) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Order assigned successfully'
                ]);
            } else {
                $this->sendError('Failed to assign order', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Failed to assign order', 500);
        }
    }

    /**
     * Send success response
     */
    private function sendResponse($data, $code = 200)
    {
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }

    /**
     * Send error response
     */
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ], JSON_PRETTY_PRINT);
        exit();
    }
}
