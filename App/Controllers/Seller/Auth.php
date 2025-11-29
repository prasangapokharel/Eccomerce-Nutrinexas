<?php

namespace App\Controllers\Seller;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Seller;
use Exception;

class Auth extends Controller
{
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->sellerModel = new Seller();
    }

    /**
     * Login page
     */
    public function login()
    {
        if (Session::has('seller_id')) {
            $this->redirect('seller/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }

        $this->view('seller/auth/login', [
            'title' => 'Seller Login'
        ]);
    }

    /**
     * Handle login
     */
    private function handleLogin()
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->setFlash('error', 'Email and password are required');
            $this->redirect('seller/login');
            return;
        }

        $seller = $this->sellerModel->authenticate($email, $password);

        if ($seller) {
            // Check if seller is approved
            if (!$seller['is_approved']) {
                $this->setFlash('error', 'Your account is pending approval. Please wait for admin approval before logging in.');
                $this->redirect('seller/login');
                return;
            }
            
            // Check if seller is suspended
            if ($seller['status'] === 'suspended') {
                $this->setFlash('error', 'Your seller account has been suspended. Please contact support.');
                $this->redirect('seller/login');
                return;
            }
            
            // Check if seller status is active
            if ($seller['status'] !== 'active') {
                $this->setFlash('error', 'Your account is ' . $seller['status'] . '. Please contact support.');
                $this->redirect('seller/login');
                return;
            }
            
            Session::set('seller_id', $seller['id']);
            Session::set('seller_name', $seller['name']);
            Session::set('seller_email', $seller['email']);
            if (!empty($seller['logo_url'])) {
                Session::set('seller_logo_url', $seller['logo_url']);
            }
            $this->setFlash('success', 'Welcome back!');
            $this->redirect('seller/dashboard');
        } else {
            $this->setFlash('error', 'Invalid email or password');
            $this->redirect('seller/login');
        }
    }

    /**
     * Registration page
     */
    public function register()
    {
        if (Session::has('seller_id')) {
            $this->redirect('seller/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleRegister();
            return;
        }

        $this->view('seller/auth/register', [
            'title' => 'Seller Registration'
        ]);
    }

    /**
     * Handle registration
     */
    private function handleRegister()
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['password_confirm'] ?? '';
        $companyName = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Document URLs (CDN links)
        $logoUrl = trim($_POST['logo_url'] ?? '');
        $citizenshipUrl = trim($_POST['citizenship_document_url'] ?? '');
        $panVatType = $_POST['pan_vat_type'] ?? null;
        $panVatNumber = trim($_POST['pan_vat_number'] ?? '');
        $panVatUrl = trim($_POST['pan_vat_document_url'] ?? '');
        $paymentMethod = $_POST['payment_method'] ?? null;
        $paymentDetails = trim($_POST['payment_details'] ?? '');

        $errors = [];

        // Validation
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        if (empty($phone)) {
            $errors[] = 'Phone number is required';
        }
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        if (empty($citizenshipUrl)) {
            $errors[] = 'Citizenship document URL is required';
        } elseif (!filter_var($citizenshipUrl, FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid citizenship document URL';
        }

        // Check if email already exists
        $existingSeller = $this->sellerModel->findByEmail($email);
        if ($existingSeller) {
            $errors[] = 'Email already registered';
        }

        if (!empty($errors)) {
            $this->view('seller/auth/register', [
                'title' => 'Seller Registration',
                'errors' => $errors,
                'formData' => $_POST
            ]);
            return;
        }

        try {
            $sellerData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'company_name' => $companyName,
                'address' => $address,
                'logo_url' => $logoUrl,
                'citizenship_document_url' => $citizenshipUrl,
                'pan_vat_type' => $panVatType,
                'pan_vat_number' => $panVatNumber,
                'pan_vat_document_url' => $panVatUrl,
                'payment_method' => $paymentMethod,
                'payment_details' => $paymentDetails,
                'status' => 'inactive',
                'is_approved' => 0
            ];

            $sellerId = $this->sellerModel->create($sellerData);

            if ($sellerId) {
                $this->setFlash('success', 'Registration successful! Your account is pending approval. You will be notified once approved.');
                $this->redirect('seller/login');
            } else {
                $this->setFlash('error', 'Registration failed. Please try again.');
                $this->redirect('seller/register');
            }
        } catch (Exception $e) {
            error_log('Seller registration error: ' . $e->getMessage());
            $this->setFlash('error', 'Error during registration. Please try again.');
            $this->redirect('seller/register');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        Session::remove('seller_id');
        Session::remove('seller_name');
        Session::remove('seller_email');
        Session::remove('seller_logo_url');
        $this->setFlash('success', 'Logged out successfully');
        $this->redirect('seller/login');
    }
}

