<?php

namespace App\Controllers\Curior;

use App\Models\Curior\Curior as CuriorModel;

class Profile extends BaseCuriorController
{
    private $curiorModel;

    public function __construct()
    {
        parent::__construct();
        $this->curiorModel = new CuriorModel();
    }

    /**
     * Profile page
     */
    public function index()
    {
        // Ensure we have fresh data including city and branch
        $curior = $this->curiorModel->getById($this->curiorId);
        
        $this->view('curior/profile/index', [
            'curior' => $curior ?: $this->curiorData,
            'page' => 'profile',
            'title' => 'Profile'
        ]);
    }

    /**
     * Update profile
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->setFlash('error', 'Invalid request method');
            $this->redirect('curior/profile');
            return;
        }

        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'branch' => trim($_POST['branch'] ?? '')
        ];

        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone is required';
        }

        if (!empty($errors)) {
            $this->view('curior/profile/index', [
                'curior' => $this->curiorData,
                'errors' => $errors,
                'page' => 'profile',
                'title' => 'Profile'
            ]);
            return;
        }

        if ($this->curiorModel->update($this->curiorId, $data)) {
            \App\Core\Session::set('curior_name', $data['name']);
            \App\Core\Session::set('curior_email', $data['email']);
            $this->setFlash('success', 'Profile updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update profile');
        }

        $this->redirect('curior/profile');
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 400);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->jsonResponse(['success' => false, 'message' => 'All password fields are required']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->jsonResponse(['success' => false, 'message' => 'New passwords do not match']);
            return;
        }

        if (strlen($newPassword) < 6) {
            $this->jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters']);
            return;
        }

        $curior = $this->curiorModel->getById($this->curiorId);
        if (!password_verify($currentPassword, $curior['password'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Current password is incorrect']);
            return;
        }

        if ($this->curiorModel->update($this->curiorId, ['password' => $newPassword])) {
            $this->jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to change password']);
        }
    }
}

