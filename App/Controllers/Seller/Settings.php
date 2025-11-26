<?php

namespace App\Controllers\Seller;

use App\Models\Seller;
use Exception;

class Settings extends BaseSellerController
{
    private $sellerModel;

    public function __construct()
    {
        parent::__construct();
        $this->sellerModel = new Seller();
    }

    /**
     * Settings page
     */
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate();
            return;
        }

        $this->view('seller/settings/index', [
            'title' => 'Settings',
            'seller' => $this->sellerData
        ]);
    }

    /**
     * Handle settings update
     */
    private function handleUpdate()
    {
        try {
            // Only update fields that are provided (all optional except name)
            $data = [];
            
            if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
                $data['name'] = trim($_POST['name']);
            }
            if (isset($_POST['phone'])) {
                $data['phone'] = trim($_POST['phone'] ?? '');
            }
            if (isset($_POST['company_name'])) {
                $data['company_name'] = trim($_POST['company_name'] ?? '');
            }
            if (isset($_POST['address'])) {
                $data['address'] = trim($_POST['address'] ?? '');
            }
            if (isset($_POST['city'])) {
                $data['city'] = trim($_POST['city'] ?? '');
            }
            if (isset($_POST['logo_url'])) {
                $data['logo_url'] = trim($_POST['logo_url'] ?? '');
            }
            if (isset($_POST['cover_banner_url'])) {
                $data['cover_banner_url'] = trim($_POST['cover_banner_url'] ?? '');
            }
            if (isset($_POST['description'])) {
                $data['description'] = trim($_POST['description'] ?? '');
            }

            // Handle social media (JSON) - only if provided
            if (isset($_POST['facebook']) || isset($_POST['instagram']) || isset($_POST['twitter']) || isset($_POST['linkedin']) || isset($_POST['youtube'])) {
                $socialMedia = [];
                if (!empty($_POST['facebook'])) $socialMedia['facebook'] = trim($_POST['facebook']);
                if (!empty($_POST['instagram'])) $socialMedia['instagram'] = trim($_POST['instagram']);
                if (!empty($_POST['twitter'])) $socialMedia['twitter'] = trim($_POST['twitter']);
                if (!empty($_POST['linkedin'])) $socialMedia['linkedin'] = trim($_POST['linkedin']);
                if (!empty($_POST['youtube'])) $socialMedia['youtube'] = trim($_POST['youtube']);
                $data['social_media'] = !empty($socialMedia) ? json_encode($socialMedia) : null;
            }

            // Handle working hours (JSON) - only if provided
            if (isset($_POST['working_hours_monday_open']) || isset($_POST['working_hours_monday_closed'])) {
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
            }

            // Update password if provided
            if (!empty($_POST['password'])) {
                if ($_POST['password'] !== $_POST['password_confirm']) {
                    $this->setFlash('error', 'Passwords do not match');
                    $this->redirect('seller/settings');
                    return;
                }
                $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            // Only update if there's data to update
            if (empty($data)) {
                $this->setFlash('error', 'No changes to save');
                $this->redirect('seller/settings');
                return;
            }

            $result = $this->sellerModel->update($this->sellerId, $data);
            
            if ($result) {
                // Update session data
                if (isset($data['name'])) {
                    \App\Core\Session::set('seller_name', $data['name']);
                }
                if (isset($data['logo_url']) && !empty($data['logo_url'])) {
                    \App\Core\Session::set('seller_logo_url', $data['logo_url']);
                }
                
                $this->setFlash('success', 'Settings updated successfully');
                // Reload seller data
                $this->sellerData = $this->sellerModel->find($this->sellerId);
            } else {
                $this->setFlash('error', 'Failed to update settings');
            }
        } catch (Exception $e) {
            error_log('Update settings error: ' . $e->getMessage());
            $this->setFlash('error', 'Error updating settings');
        }

        $this->redirect('seller/settings');
    }
}

