<?php

namespace App\Controllers\Staff;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Staff;
use App\Models\Order;
use Exception;

class AdminStaffController extends Controller
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
     * Staff management dashboard
     */
    public function index()
    {
        $this->requireAdmin();
        
        $staff = $this->staffModel->getAllStaff();
        $stats = $this->getStaffStats();
        
        $this->view('admin/staff/index', [
            'staff' => $staff,
            'stats' => $stats,
            'title' => 'Staff Management'
        ]);
    }

    /**
     * Create new staff member
     */
    public function create()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $status = $_POST['status'] ?? 'active';

            if (empty($name) || empty($email) || empty($password)) {
                $this->setFlash('error', 'Name, email, and password are required');
                $this->redirect('admin/staff/create');
                return;
            }

            if (strlen($password) < 6) {
                $this->setFlash('error', 'Password must be at least 6 characters long');
                $this->redirect('admin/staff/create');
                return;
            }

            // Check if email already exists
            if ($this->staffModel->getByEmail($email)) {
                $this->setFlash('error', 'Email already exists');
                $this->redirect('admin/staff/create');
                return;
            }

            $assignmentType = $_POST['assignment_type'] ?? 'city';
            $assignedCities = $_POST['assigned_cities'] ?? [];
            $assignedProducts = $_POST['assigned_products'] ?? [];

            $staffData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'address' => $address,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Only add assignment columns if they exist
            $db = \App\Core\Database::getInstance();
            $hasAssignmentType = $db->query("SHOW COLUMNS FROM staff LIKE 'assignment_type'")->single();
            $hasAssignedCities = $db->query("SHOW COLUMNS FROM staff LIKE 'assigned_cities'")->single();
            $hasAssignedProducts = $db->query("SHOW COLUMNS FROM staff LIKE 'assigned_products'")->single();
            
            if ($hasAssignmentType) {
                $staffData['assignment_type'] = $assignmentType;
            }
            if ($hasAssignedCities) {
                $staffData['assigned_cities'] = json_encode($assignedCities);
            }
            if ($hasAssignedProducts) {
                $staffData['assigned_products'] = json_encode($assignedProducts);
            }

            if ($this->staffModel->create($staffData)) {
                $this->setFlash('success', 'Staff member created successfully');
                $this->redirect('admin/staff');
            } else {
                $this->setFlash('error', 'Failed to create staff member');
                $this->redirect('admin/staff/create');
            }
        } else {
            $this->view('admin/staff/create', [
                'title' => 'Create Staff Member'
            ]);
        }
    }

    /**
     * Edit staff member
     */
    public function edit($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->setFlash('error', 'Invalid staff ID');
            $this->redirect('admin/staff');
            return;
        }

        $staff = $this->staffModel->getById($id);
        if (!$staff) {
            $this->setFlash('error', 'Staff member not found');
            $this->redirect('admin/staff');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? '';

            if (empty($name) || empty($email)) {
                $this->setFlash('error', 'Name and email are required');
                $this->redirect('admin/staff/edit/' . $id);
                return;
            }

            // Check if email already exists for another staff member
            $existingStaff = $this->staffModel->getByEmail($email);
            if ($existingStaff && $existingStaff['id'] != $id) {
                $this->setFlash('error', 'Email already exists');
                $this->redirect('admin/staff/edit/' . $id);
                return;
            }

            $assignmentType = $_POST['assignment_type'] ?? 'city';
            $assignedCities = $_POST['assigned_cities'] ?? [];
            $assignedProducts = $_POST['assigned_products'] ?? [];

            $updateData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Only add assignment columns if they exist
            $db = \App\Core\Database::getInstance();
            $hasAssignmentType = $db->query("SHOW COLUMNS FROM staff LIKE 'assignment_type'")->single();
            $hasAssignedCities = $db->query("SHOW COLUMNS FROM staff LIKE 'assigned_cities'")->single();
            $hasAssignedProducts = $db->query("SHOW COLUMNS FROM staff LIKE 'assigned_products'")->single();
            
            if ($hasAssignmentType) {
                $updateData['assignment_type'] = $assignmentType;
            }
            if ($hasAssignedCities) {
                $updateData['assigned_cities'] = json_encode($assignedCities);
            }
            if ($hasAssignedProducts) {
                $updateData['assigned_products'] = json_encode($assignedProducts);
            }

            // Only update password if provided
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $this->setFlash('error', 'Password must be at least 6 characters long');
                    $this->redirect('admin/staff/edit/' . $id);
                    return;
                }
                $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($this->staffModel->update($id, $updateData)) {
                $this->setFlash('success', 'Staff member updated successfully');
                $this->redirect('admin/staff');
            } else {
                $this->setFlash('error', 'Failed to update staff member');
                $this->redirect('admin/staff/edit/' . $id);
            }
        } else {
            $this->view('admin/staff/edit', [
                'staff' => $staff,
                'title' => 'Edit Staff Member'
            ]);
        }
    }

    /**
     * Delete staff member
     */
    public function delete($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->setFlash('error', 'Invalid staff ID');
            $this->redirect('admin/staff');
            return;
        }

        $staff = $this->staffModel->getById($id);
        if (!$staff) {
            $this->setFlash('error', 'Staff member not found');
            $this->redirect('admin/staff');
            return;
        }

        // Check if staff has assigned orders
        $assignedOrders = $this->orderModel->getOrdersByStaff($id);
        if (!empty($assignedOrders)) {
            $this->setFlash('error', 'Cannot delete staff member with assigned orders. Please reassign orders first.');
            $this->redirect('admin/staff');
            return;
        }

        if ($this->staffModel->delete($id)) {
            $this->setFlash('success', 'Staff member deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete staff member');
        }
        
        $this->redirect('admin/staff');
    }

    /**
     * Assign order to staff
     */
    public function assignOrder()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderId = (int)($_POST['order_id'] ?? 0);
            $staffId = (int)($_POST['staff_id'] ?? 0);
            
            if ($orderId <= 0 || $staffId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid order or staff ID']);
                return;
            }
            
            // Verify order exists
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found']);
                return;
            }
            
            // Verify staff exists
            $staff = $this->staffModel->getById($staffId);
            if (!$staff) {
                $this->jsonResponse(['success' => false, 'message' => 'Staff member not found']);
                return;
            }
            
            // Update order with staff assignment
            if ($this->orderModel->update($orderId, ['staff_id' => $staffId])) {
                $this->jsonResponse(['success' => true, 'message' => 'Order assigned to staff successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to assign order']);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
        }
    }

    /**
     * Get unassigned orders for assignment
     */
    public function getUnassignedOrders()
    {
        $this->requireAdmin();
        
        $orders = $this->orderModel->getUnassignedOrders();
        
        $this->jsonResponse(['success' => true, 'orders' => $orders]);
    }

    /**
     * Bulk assign orders to staff
     */
    public function bulkAssignOrders()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orderIds = json_decode($_POST['order_ids'] ?? '[]', true);
            $staffId = (int)($_POST['staff_id'] ?? 0);
            
            if (empty($orderIds) || $staffId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid order IDs or staff ID']);
                return;
            }
            
            // Verify staff exists
            $staff = $this->staffModel->getById($staffId);
            if (!$staff) {
                $this->jsonResponse(['success' => false, 'message' => 'Staff member not found']);
                return;
            }
            
            $assignedCount = 0;
            $errors = [];
            
            foreach ($orderIds as $orderId) {
                // Verify order exists and is unassigned
                $order = $this->orderModel->find($orderId);
                if ($order && (!$order['staff_id'] || $order['staff_id'] == 0)) {
                    if ($this->orderModel->update($orderId, ['staff_id' => $staffId])) {
                        $assignedCount++;
                    } else {
                        $errors[] = "Failed to assign order #{$orderId}";
                    }
                } else {
                    $errors[] = "Order #{$orderId} not found or already assigned";
                }
            }
            
            if ($assignedCount > 0) {
                $message = "Successfully assigned {$assignedCount} order(s)";
                if (!empty($errors)) {
                    $message .= ". Some errors: " . implode(', ', $errors);
                }
                $this->jsonResponse(['success' => true, 'message' => $message, 'assigned_count' => $assignedCount]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'No orders were assigned. ' . implode(', ', $errors)]);
            }
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
        }
    }

    /**
     * Get staff statistics
     */
    private function getStaffStats()
    {
        $stats = $this->staffModel->getStats();
        
        // Rename keys to match the expected format
        return [
            'total_staff' => $stats['total'],
            'active_staff' => $stats['active'],
            'inactive_staff' => $stats['inactive']
        ];
    }

    /**
     * Get products for assignment
     */
    public function getProducts()
    {
        $this->requireAdmin();
        
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT id, product_name as name FROM products WHERE status = 'active' ORDER BY product_name";
            $products = $db->query($sql)->all();
            
            $this->jsonResponse(['success' => true, 'products' => $products]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error loading products: ' . $e->getMessage()]);
        }
    }

    /**
     * Get cities from orders
     */
    public function getCities()
    {
        $this->requireAdmin();
        
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT DISTINCT 
                        CASE 
                            WHEN address LIKE '%Kathmandu%' THEN 'Kathmandu'
                            WHEN address LIKE '%Lalitpur%' THEN 'Lalitpur'
                            WHEN address LIKE '%Bhaktapur%' THEN 'Bhaktapur'
                            WHEN address LIKE '%Pokhara%' THEN 'Pokhara'
                            WHEN address LIKE '%Bharatpur%' THEN 'Bharatpur'
                            WHEN address LIKE '%Biratnagar%' THEN 'Biratnagar'
                            WHEN address LIKE '%Birgunj%' THEN 'Birgunj'
                            WHEN address LIKE '%Dharan%' THEN 'Dharan'
                            WHEN address LIKE '%Butwal%' THEN 'Butwal'
                            WHEN address LIKE '%Hetauda%' THEN 'Hetauda'
                            WHEN address LIKE '%Nepalgunj%' THEN 'Nepalgunj'
                            WHEN address LIKE '%Itahari%' THEN 'Itahari'
                            WHEN address LIKE '%Tulsipur%' THEN 'Tulsipur'
                            WHEN address LIKE '%Kalaiya%' THEN 'Kalaiya'
                            WHEN address LIKE '%Jitpur%' THEN 'Jitpur'
                            WHEN address LIKE '%Madhyapur Thimi%' THEN 'Madhyapur Thimi'
                            WHEN address LIKE '%Birendranagar%' THEN 'Birendranagar'
                            WHEN address LIKE '%Ghorahi%' THEN 'Ghorahi'
                            WHEN address LIKE '%Tikapur%' THEN 'Tikapur'
                            WHEN address LIKE '%Kirtipur%' THEN 'Kirtipur'
                        END as city
                    FROM orders 
                    WHERE address IS NOT NULL 
                    AND address != ''
                    HAVING city IS NOT NULL
                    ORDER BY city";
            
            $cities = $db->query($sql)->all();
            
            // If no cities found from orders, provide default cities
            if (empty($cities)) {
                $defaultCities = [
                    ['city' => 'Kathmandu'],
                    ['city' => 'Lalitpur'],
                    ['city' => 'Bhaktapur'],
                    ['city' => 'Pokhara'],
                    ['city' => 'Bharatpur'],
                    ['city' => 'Biratnagar'],
                    ['city' => 'Birgunj'],
                    ['city' => 'Dharan'],
                    ['city' => 'Butwal'],
                    ['city' => 'Hetauda']
                ];
                $cities = $defaultCities;
            }
            
            $this->jsonResponse(['success' => true, 'cities' => $cities]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error loading cities: ' . $e->getMessage()]);
        }
    }

    /**
     * Require admin access
     */
    public function requireAdmin()
    {
        if (!Session::has('user_id') || Session::get('user_role') !== 'admin') {
            $this->setFlash('error', 'Access denied');
            $this->redirect('auth/login');
        }
    }
}
