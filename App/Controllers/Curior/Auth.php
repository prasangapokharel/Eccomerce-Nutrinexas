<?php

namespace App\Controllers\Curior;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Curior\Curior as CuriorModel;
use App\Services\CuriorPasswordResetService;

class Auth extends Controller
{
    private $curiorModel;
    private $passwordResetService;

    public function __construct()
    {
        parent::__construct();
        $this->curiorModel = new CuriorModel();
        $this->passwordResetService = new CuriorPasswordResetService($this->curiorModel);
    }

    /**
     * Curior login page
     */
    public function login()
    {
        if (Session::has('curior_id')) {
            $this->redirect('curior/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLogin();
            return;
        }

        $this->view('curior/auth/login', [
            'title' => 'Courier Login'
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
            $this->setFlash('error', 'Please fill in all fields');
            $this->view('curior/auth/login', [
                'title' => 'Courier Login'
            ]);
            return;
        }

        $curior = $this->curiorModel->verifyCredentials($email, $password);
        
        if ($curior) {
            if ($curior['status'] === 'inactive') {
                $this->setFlash('error', 'Your account is inactive. Please contact administrator.');
                $this->view('curior/auth/login', [
                    'title' => 'Courier Login'
                ]);
                return;
            }

            Session::set('curior_id', $curior['id']);
            Session::set('curior_name', $curior['name']);
            Session::set('curior_email', $curior['email']);
            
            $this->setFlash('success', 'Welcome back, ' . $curior['name'] . '!');
            $this->redirect('curior/dashboard');
        } else {
            $this->setFlash('error', 'Invalid email or password');
            $this->view('curior/auth/login', [
                'title' => 'Courier Login'
            ]);
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        Session::destroy();
        $this->setFlash('success', 'You have been logged out successfully');
        $this->redirect('curior/login');
    }

    /**
     * Show/reset password request form
     */
    public function forgotPassword()
    {
        if (Session::has('curior_id')) {
            $this->redirect('curior/dashboard');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'Please enter a valid email address');
                $this->view('curior/auth/forgot-password', ['title' => 'Reset Password', 'email' => $email]);
                return;
            }

            $curior = $this->curiorModel->getByEmail($email);

            if (!$curior) {
                // Do not disclose account existence
                $this->setFlash('success', 'If an account exists for that email, a reset link has been sent.');
                $this->redirect('curior/login');
                return;
            }

            if (empty($curior['email'])) {
                $this->setFlash('error', 'This curior does not have an email on file. Please contact support.');
                $this->view('curior/auth/forgot-password', ['title' => 'Reset Password', 'email' => $email]);
                return;
            }

            try {
                $token = $this->passwordResetService->generateToken((int) $curior['id']);
                $sent = $this->passwordResetService->sendResetEmail($curior, $token);

                if ($sent) {
                    $this->setFlash('success', 'Password reset instructions have been sent to the registered email.');
                } else {
                    $this->setFlash('error', 'Failed to send password reset email. Please try again later.');
                }
            } catch (\Throwable $e) {
                error_log('Curior forgot password error: ' . $e->getMessage());
                $this->setFlash('error', 'Unable to process the request right now. Please try again.');
            }

            $this->redirect('curior/login');
            return;
        }

        $this->view('curior/auth/forgot-password', [
            'title' => 'Reset Password'
        ]);
    }

    /**
     * Display the reset password form and update password.
     */
    public function resetPassword($token = null)
    {
        if (empty($token)) {
            $this->redirect('curior/login');
        }

        $curior = $this->curiorModel->findByResetToken($token);

        if (!$curior || empty($curior['reset_token_expires_at']) || strtotime($curior['reset_token_expires_at']) < time()) {
            $this->setFlash('error', 'Invalid or expired password reset link.');
            $this->redirect('curior/forgot-password');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            $errors = [];

            if (strlen($password) < 6) {
                $errors['password'] = 'Password must be at least 6 characters.';
            }

            if ($password !== $confirm) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (!empty($errors)) {
                $this->view('curior/auth/reset-password', [
                    'title' => 'Create New Password',
                    'token' => $token,
                    'errors' => $errors
                ]);
                return;
            }

            try {
                $updated = $this->curiorModel->updatePassword((int) $curior['id'], $password);
                if ($updated) {
                    $this->curiorModel->clearResetToken((int) $curior['id']);
                    $this->passwordResetService->sendPasswordChangedEmail($curior);
                    $this->setFlash('success', 'Password updated successfully. You can now sign in.');
                    $this->redirect('curior/login');
                    return;
                }

                $this->setFlash('error', 'Failed to update password. Please try again.');
            } catch (\Throwable $e) {
                error_log('Curior reset password error: ' . $e->getMessage());
                $this->setFlash('error', 'Unable to update password right now.');
            }

            $this->view('curior/auth/reset-password', [
                'title' => 'Create New Password',
                'token' => $token
            ]);
            return;
        }

        $this->view('curior/auth/reset-password', [
            'title' => 'Create New Password',
            'token' => $token
        ]);
    }
}

