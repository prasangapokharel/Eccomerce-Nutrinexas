<?php

namespace App\Controllers\Seller;

use App\Models\Seller;
use Exception;

class Profile extends BaseSellerController
{
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->sellerModel = new Seller();
    }

    /**
     * Profile page
     */
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate();
            return;
        }

        $seller = $this->sellerModel->find($this->sellerId);

        $this->view('seller/profile/index', [
            'title' => 'Profile',
            'seller' => $seller
        ]);
    }

    /**
     * Handle profile update
     */
    private function handleUpdate()
    {
        try {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'company_name' => trim($_POST['company_name'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'logo_url' => trim($_POST['logo_url'] ?? ''),
                'cover_banner_url' => trim($_POST['cover_banner_url'] ?? ''),
                'description' => trim($_POST['description'] ?? '')
            ];

            // Handle social media (JSON)
            $socialMedia = [];
            if (!empty($_POST['facebook'])) $socialMedia['facebook'] = trim($_POST['facebook']);
            if (!empty($_POST['instagram'])) $socialMedia['instagram'] = trim($_POST['instagram']);
            if (!empty($_POST['twitter'])) $socialMedia['twitter'] = trim($_POST['twitter']);
            if (!empty($_POST['linkedin'])) $socialMedia['linkedin'] = trim($_POST['linkedin']);
            if (!empty($_POST['youtube'])) $socialMedia['youtube'] = trim($_POST['youtube']);
            $data['social_media'] = !empty($socialMedia) ? json_encode($socialMedia) : null;

            // Handle working hours (JSON)
            $workingHours = [];
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            foreach ($days as $day) {
                $closed = isset($_POST["working_hours_{$day}_closed"]) && $_POST["working_hours_{$day}_closed"] == '1';
                if (!$closed) {
                    $workingHours[$day] = [
                        'open' => $_POST["working_hours_{$day}_open"] ?? '09:00',
                        'close' => $_POST["working_hours_{$day}_close"] ?? '18:00',
                        'closed' => 0
                    ];
                } else {
                    $workingHours[$day] = [
                        'open' => '09:00',
                        'close' => '18:00',
                        'closed' => 1
                    ];
                }
            }
            $data['working_hours'] = !empty($workingHours) ? json_encode($workingHours) : null;

            // Update password if provided
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['password_confirm']) {
                    $this->setFlash('error', 'Passwords do not match');
                    $this->redirect('seller/profile');
                    return;
                }
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $result = $this->sellerModel->update($this->sellerId, $data);
            
            if ($result) {
                // Update session data
                \App\Core\Session::set('seller_name', $data['name']);
                if (!empty($data['logo_url'])) {
                    \App\Core\Session::set('seller_logo_url', $data['logo_url']);
                }
                
                $this->setFlash('success', 'Profile updated successfully');
            } else {
                $this->setFlash('error', 'Failed to update profile');
            }
        } catch (Exception $e) {
            error_log('Update profile error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating profile');
        }

        $this->redirect('seller/profile');
    }
}

