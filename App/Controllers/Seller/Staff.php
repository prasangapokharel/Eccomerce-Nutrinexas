<?php

namespace App\Controllers\Seller;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use Exception;

class Staff extends BaseSellerController
{
    private $db;
    private $sellerId;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
        $this->sellerId = Session::get('seller_id');
        
        if (!$this->sellerId) {
            $this->redirect('seller/login');
        }
    }

    /**
     * List all staff (delivery boys) for this seller
     */
    public function index()
    {
        $staff = $this->db->query(
            "SELECT * FROM seller_staff 
             WHERE seller_id = ? AND role = 'delivery_boy'
             ORDER BY created_at DESC",
            [$this->sellerId]
        )->all();
        
        $this->view('seller/staff/index', [
            'title' => 'Staff Management',
            'staff' => $staff,
            'page' => 'staff'
        ]);
    }

    /**
     * Create new delivery boy
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $city = trim($_POST['city'] ?? '');
            
            if (empty($name) || empty($email) || empty($password) || empty($city)) {
                $this->setFlash('error', 'Name, email, password, and city are required');
                $this->redirect('seller/staff/create');
                return;
            }
            
            if (strlen($password) < 6) {
                $this->setFlash('error', 'Password must be at least 6 characters');
                $this->redirect('seller/staff/create');
                return;
            }
            
            // Check if email already exists
            $existing = $this->db->query(
                "SELECT id FROM seller_staff WHERE email = ?",
                [$email]
            )->single();
            
            if ($existing) {
                $this->setFlash('error', 'Email already exists');
                $this->redirect('seller/staff/create');
                return;
            }
            
            try {
                $this->db->query(
                    "INSERT INTO seller_staff (seller_id, name, email, phone, password, city, role, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'delivery_boy', 'active', NOW(), NOW())",
                    [$this->sellerId, $name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $city]
                );
                
                $this->setFlash('success', 'Delivery boy created successfully');
                $this->redirect('seller/staff');
            } catch (Exception $e) {
                error_log('Create delivery boy error: ' . $e->getMessage());
                $this->setFlash('error', 'Failed to create delivery boy');
                $this->redirect('seller/staff/create');
            }
        } else {
            $this->view('seller/staff/create', [
                'title' => 'Create Delivery Boy',
                'page' => 'staff'
            ]);
        }
    }

    /**
     * Delete delivery boy
     */
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('seller/staff');
            return;
        }
        
        try {
            $this->db->query(
                "DELETE FROM seller_staff WHERE id = ? AND seller_id = ? AND role = 'delivery_boy'",
                [$id, $this->sellerId]
            );
            
            $this->setFlash('success', 'Delivery boy deleted successfully');
        } catch (Exception $e) {
            error_log('Delete delivery boy error: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete delivery boy');
        }
        
        $this->redirect('seller/staff');
    }
}

