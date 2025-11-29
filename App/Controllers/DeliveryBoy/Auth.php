<?php

namespace App\Controllers\DeliveryBoy;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use Exception;

class Auth extends Controller
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Delivery boy login page
     */
    public function login()
    {
        if (Session::has('delivery_boy_id')) {
            $this->redirect('deliveryboy/dashboard');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $this->setFlash('error', 'Email and password are required');
                $this->view('deliveryboy/auth/login', ['title' => 'Delivery Boy Login']);
                return;
            }
            
            $staff = $this->db->query(
                "SELECT * FROM seller_staff WHERE email = ? AND role = 'delivery_boy' AND status = 'active'",
                [$email]
            )->single();
            
            if ($staff && password_verify($password, $staff['password'])) {
                Session::set('delivery_boy_id', $staff['id']);
                Session::set('delivery_boy_name', $staff['name']);
                Session::set('delivery_boy_email', $staff['email']);
                Session::set('delivery_boy_city', $staff['city']);
                Session::set('delivery_boy_seller_id', $staff['seller_id']);
                
                $this->setFlash('success', 'Login successful');
                $this->redirect('deliveryboy/dashboard');
            } else {
                $this->setFlash('error', 'Invalid email or password');
                $this->view('deliveryboy/auth/login', ['title' => 'Delivery Boy Login']);
            }
        } else {
            $this->view('deliveryboy/auth/login', ['title' => 'Delivery Boy Login']);
        }
    }

    /**
     * Delivery boy logout
     */
    public function logout()
    {
        Session::remove('delivery_boy_id');
        Session::remove('delivery_boy_name');
        Session::remove('delivery_boy_email');
        Session::remove('delivery_boy_city');
        Session::remove('delivery_boy_seller_id');
        
        $this->setFlash('success', 'Logged out successfully');
        $this->redirect('deliveryboy/login');
    }
}

