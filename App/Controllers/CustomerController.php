<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Customer;

class CustomerController extends Controller
{
    private $customerModel;

    public function __construct()
    {
        parent::__construct();
        $this->customerModel = new Customer();
    }

    /**
     * Display customer list (admin only)
     */
    public function index()
    {
        $this->requireAdmin();
        
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $filters = [
            'search' => isset($_GET['search']) ? trim($_GET['search']) : '',
            'contact_no' => isset($_GET['contact_no']) ? trim($_GET['contact_no']) : ''
        ];
        
        $customers = $this->customerModel->getAllCustomers($filters, $limit, $offset);
        $totalCustomers = $this->customerModel->getTotalCustomers($filters);
        $totalPages = ceil($totalCustomers / $limit);
        
        $this->view('admin/customers/index', [
            'customers' => $customers,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalCustomers' => $totalCustomers,
            'filters' => $filters,
            'title' => 'Manage Customers'
        ]);
    }

    /**
     * Create new customer (admin only)
     */
    public function create()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'contact_no' => trim($_POST['contact_no'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'email' => trim($_POST['email'] ?? '')
            ];
            
            // Validate data
            $validation = $this->customerModel->validate($data);
            
            if (!$validation['valid']) {
                $this->setFlash('error', implode(', ', $validation['errors']));
                $this->redirect('admin/customers/create');
                return;
            }
            
            // Check if contact number already exists
            $existingCustomer = $this->customerModel->getByContactNo($data['contact_no']);
            if ($existingCustomer) {
                $this->setFlash('error', 'Customer with this contact number already exists');
                $this->redirect('admin/customers/create');
                return;
            }
            
            // Check if email already exists (if provided)
            if (!empty($data['email'])) {
                $existingCustomer = $this->customerModel->getByEmail($data['email']);
                if ($existingCustomer) {
                    $this->setFlash('error', 'Customer with this email already exists');
                    $this->redirect('admin/customers/create');
                    return;
                }
            }
            
            // Create customer
            $customerId = $this->customerModel->create($data);
            
            if ($customerId) {
                $this->setFlash('success', 'Customer created successfully');
                $this->redirect('admin/customers');
            } else {
                $this->setFlash('error', 'Failed to create customer');
                $this->redirect('admin/customers/create');
            }
            
        } else {
            $this->view('admin/customers/create', [
                'title' => 'Create New Customer'
            ]);
        }
    }

    /**
     * Edit customer (admin only)
     */
    public function edit($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/customers');
            return;
        }
        
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('admin/customers');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'customer_name' => trim($_POST['customer_name'] ?? ''),
                'contact_no' => trim($_POST['contact_no'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'email' => trim($_POST['email'] ?? '')
            ];
            
            // Validate data
            $validation = $this->customerModel->validate($data);
            
            if (!$validation['valid']) {
                $this->setFlash('error', implode(', ', $validation['errors']));
                $this->redirect('admin/customers/edit/' . $id);
                return;
            }
            
            // Check if contact number already exists for other customers
            $existingCustomer = $this->customerModel->getByContactNo($data['contact_no']);
            if ($existingCustomer && $existingCustomer['id'] != $id) {
                $this->setFlash('error', 'Customer with this contact number already exists');
                $this->redirect('admin/customers/edit/' . $id);
                return;
            }
            
            // Check if email already exists for other customers (if provided)
            if (!empty($data['email'])) {
                $existingCustomer = $this->customerModel->getByEmail($data['email']);
                if ($existingCustomer && $existingCustomer['id'] != $id) {
                    $this->setFlash('error', 'Customer with this email already exists');
                    $this->redirect('admin/customers/edit/' . $id);
                    return;
                }
            }
            
            // Update customer
            $result = $this->customerModel->update($id, $data);
            
            if ($result) {
                $this->setFlash('success', 'Customer updated successfully');
                $this->redirect('admin/customers');
            } else {
                $this->setFlash('error', 'Failed to update customer');
                $this->redirect('admin/customers/edit/' . $id);
            }
            
        } else {
            $this->view('admin/customers/edit', [
                'customer' => $customer,
                'title' => 'Edit Customer'
            ]);
        }
    }

    /**
     * Delete customer (admin only)
     */
    public function delete($id = null)
    {
        $this->requireAdmin();
        
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request');
            $this->redirect('admin/customers');
            return;
        }
        
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('admin/customers');
            return;
        }
        
        if ($this->customerModel->delete($id)) {
            $this->setFlash('success', 'Customer deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete customer');
        }
        
        $this->redirect('admin/customers');
    }

    /**
     * View customer details (admin only)
     */
    public function view($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/customers');
            return;
        }
        
        $customer = $this->customerModel->find($id);
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('admin/customers');
            return;
        }
        
        $this->view('admin/customers/view', [
            'customer' => $customer,
            'title' => 'Customer Details'
        ]);
    }

    /**
     * Search customers via AJAX
     */
    public function search()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }
        
        $query = trim($_GET['q'] ?? '');
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        
        if (empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Search query is required']);
            return;
        }
        
        $customers = $this->customerModel->searchCustomers($query, $limit);
        
        echo json_encode([
            'success' => true,
            'customers' => $customers,
            'count' => count($customers)
        ]);
    }
}
