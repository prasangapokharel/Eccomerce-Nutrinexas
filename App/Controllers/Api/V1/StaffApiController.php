<?php
namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Staff;
use App\Models\Order;
use App\Core\Session;
use Exception;

/**
 * Staff API Controller v1
 * Clean, production-ready API for staff management
 * Version: 1.0.0
 */
class StaffApiController extends Controller
{
    private $staffModel;
    private $orderModel;
    private $version = '1.0.0';

    public function __construct()
    {
        parent::__construct();
        $this->staffModel = new Staff();
        $this->orderModel = new Order();
        
        // Set JSON response headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Version');
        header('X-API-Version: 1.0.0');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Get all staff members
     * GET /api/v1/staff
     */
    public function index()
    {
        try {
            $staff = $this->staffModel->getAll();
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $staff,
                'count' => count($staff),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff', 500);
        }
    }

    /**
     * Get staff by ID
     * GET /api/v1/staff/{id}
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
                'version' => $this->version,
                'data' => $staff,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff', 500);
        }
    }

    /**
     * Get staff orders
     * GET /api/v1/staff/{id}/orders
     */
    public function orders($id)
    {
        try {
            $orders = $this->orderModel->getOrdersByStaff($id);
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $orders,
                'count' => count($orders),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff orders', 500);
        }
    }

    /**
     * Get staff statistics
     * GET /api/v1/staff/{id}/stats
     */
    public function stats($id)
    {
        try {
            $stats = $this->staffModel->getStats($id);
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $stats,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch staff statistics', 500);
        }
    }

    /**
     * Get unassigned orders
     * GET /api/v1/staff/unassigned-orders
     */
    public function unassignedOrders()
    {
        try {
            $orders = $this->orderModel->getUnassignedOrders();
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $orders,
                'count' => count($orders),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch unassigned orders', 500);
        }
    }

    /**
     * Update order status
     * POST /api/v1/staff/update-order-status
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
                        error_log('V1 StaffApiController: Error processing referral earning: ' . $e->getMessage());
                    }
                }
                
                $this->sendResponse([
                    'success' => true,
                    'version' => $this->version,
                    'message' => 'Order status updated successfully',
                    'data' => [
                        'order_id' => $orderId,
                        'status' => $status,
                        'note' => $note
                    ],
                    'timestamp' => date('c')
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
     * POST /api/v1/staff/assign-order
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
                    'version' => $this->version,
                    'message' => 'Order assigned successfully',
                    'data' => [
                        'order_id' => $orderId,
                        'staff_id' => $staffId
                    ],
                    'timestamp' => date('c')
                ]);
            } else {
                $this->sendError('Failed to assign order', 500);
            }
        } catch (Exception $e) {
            $this->sendError('Failed to assign order', 500);
        }
    }

    /**
     * Get API health status
     * GET /api/v1/staff/health
     */
    public function health()
    {
        $this->sendResponse([
            'success' => true,
            'version' => $this->version,
            'status' => 'healthy',
            'timestamp' => date('c'),
            'uptime' => time() - $_SERVER['REQUEST_TIME']
        ]);
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
            'version' => $this->version,
            'error' => $message,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);
        exit();
    }
}
