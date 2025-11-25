<?php
namespace App\Controllers\Api\V1;

use App\Core\Controller;
use App\Models\Curior\Curior as CuriorModel;
use App\Models\Order;
use App\Core\Session;
use Exception;

/**
 * Curior API Controller v1
 * Clean, production-ready API for curior management
 * Version: 1.0.0
 */
class CuriorApiController extends Controller
{
    private $curiorModel;
    private $orderModel;
    private $version = '1.0.0';

    public function __construct()
    {
        parent::__construct();
        $this->curiorModel = new CuriorModel();
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
     * Get all curiors
     * GET /api/v1/curiors
     */
    public function index()
    {
        try {
            $curiors = $this->curiorModel->getAll();
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $curiors,
                'count' => count($curiors),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch curiors', 500);
        }
    }

    /**
     * Get curior by ID
     * GET /api/v1/curiors/{id}
     */
    public function show($id)
    {
        try {
            $curior = $this->curiorModel->find($id);
            
            if (!$curior) {
                $this->sendError('Curior not found', 404);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $curior,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch curior', 500);
        }
    }

    /**
     * Get curior orders
     * GET /api/v1/curiors/{id}/orders
     */
    public function orders($id)
    {
        try {
            $orders = $this->orderModel->getOrdersByCurior($id);
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $orders,
                'count' => count($orders),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch curior orders', 500);
        }
    }

    /**
     * Get curior statistics
     * GET /api/v1/curiors/{id}/stats
     */
    public function stats($id)
    {
        try {
            $stats = $this->orderModel->getCuriorStats($id);
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $stats,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch curior statistics', 500);
        }
    }

    /**
     * Get curior performance
     * GET /api/v1/curiors/{id}/performance
     */
    public function performance($id)
    {
        try {
            $stats = $this->orderModel->getCuriorStats($id);
            
            // Calculate performance metrics
            $totalOrders = $stats['total_orders'] ?? 0;
            $deliveredOrders = $stats['delivered_orders'] ?? 0;
            $deliveryRate = $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 2) : 0;
            
            $performance = [
                'total_orders' => $totalOrders,
                'delivered_orders' => $deliveredOrders,
                'delivery_rate' => $deliveryRate,
                'processing_orders' => $stats['processing_orders'] ?? 0,
                'dispatched_orders' => $stats['dispatched_orders'] ?? 0,
                'performance_score' => $this->calculatePerformanceScore($deliveryRate, $totalOrders)
            ];
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $performance,
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch curior performance', 500);
        }
    }

    /**
     * Get orders ready for delivery
     * GET /api/v1/curiors/ready-for-delivery
     */
    public function readyForDelivery()
    {
        try {
            $orders = $this->orderModel->getOrdersByStatus('processing');
            
            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'data' => $orders,
                'count' => count($orders),
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to fetch orders ready for delivery', 500);
        }
    }

    /**
     * Update delivery status
     * POST /api/v1/curiors/update-delivery-status
     */
    public function updateDeliveryStatus()
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
                        error_log('V1 CuriorApiController: Error processing referral earning: ' . $e->getMessage());
                    }
                }
                
                $this->sendResponse([
                    'success' => true,
                    'version' => $this->version,
                    'message' => 'Delivery status updated successfully',
                    'data' => [
                        'order_id' => $orderId,
                        'status' => $status,
                        'note' => $note
                    ],
                    'timestamp' => date('c')
                ]);
            } else {
                    $this->sendError('Failed to update delivery status', 500);
            }
        } catch (Exception $e) {
                    $this->sendError('Failed to update delivery status', 500);
        }
    }

    /**
     * Assign order to curior
     * POST /api/v1/curiors/assign-order
     */
    public function assignOrder()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['order_id']) || !isset($input['curior_id'])) {
                $this->sendError('Order ID and Curior ID are required', 400);
                return;
            }

            $orderId = (int) $input['order_id'];
            $curiorId = (int) $input['curior_id'];

            try {
                $this->orderModel->assignCuriorToOrder($orderId, $curiorId);
            } catch (\InvalidArgumentException $e) {
                $this->sendError($e->getMessage(), 422);
                return;
            }

            $this->sendResponse([
                'success' => true,
                'version' => $this->version,
                'message' => 'Order assigned to curior successfully',
                'data' => [
                    'order_id' => $orderId,
                    'curior_id' => $curiorId
                ],
                'timestamp' => date('c')
            ]);
        } catch (Exception $e) {
            $this->sendError('Failed to assign order to curior', 500);
        }
    }

    /**
     * Get API health status
     * GET /api/v1/curiors/health
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
     * Calculate performance score
     */
    private function calculatePerformanceScore($deliveryRate, $totalOrders)
    {
        $baseScore = $deliveryRate;
        $volumeBonus = min($totalOrders * 0.1, 20); // Max 20 points for volume
        return min(round($baseScore + $volumeBonus, 1), 100);
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
