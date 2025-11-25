<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DeliveryCharge;
use Exception;

class AdminDeliveryController extends Controller
{
    private $deliveryModel;

    public function __construct()
    {
        parent::__construct();
        $this->deliveryModel = new DeliveryCharge();
    }

    /**
     * Display delivery charges management page
     */
    public function index()
    {
        // Check if user is admin
        if (!\App\Core\Session::get('user_id') || \App\Core\Session::get('user_role') !== 'admin') {
            $this->setFlash('error', 'Unauthorized access.');
            $this->redirect('admin/login');
            return;
        }
        
        $charges = $this->deliveryModel->getAllCharges();
        $isFreeDeliveryEnabled = $this->deliveryModel->isFreeDeliveryEnabled();
        
        $this->view('admin/delivery/index', [
            'charges' => $charges,
            'isFreeDeliveryEnabled' => $isFreeDeliveryEnabled,
            'title' => 'Delivery Charges Management'
        ]);
    }

    /**
     * Create new delivery charge
     */
    public function create()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'location_name' => trim($_POST['location_name'] ?? ''),
                'charge' => floatval($_POST['charge'] ?? 0)
            ];
            
            // Validation
            $errors = [];
            if (empty($data['location_name'])) {
                $errors[] = 'Location name is required';
            }
            if ($data['charge'] < 0) {
                $errors[] = 'Charge must be a positive number';
            }
            
            // Check if location already exists
            $existing = $this->deliveryModel->getChargeByLocation($data['location_name']);
            if ($existing) {
                $errors[] = 'Location already exists';
            }
            
            if (empty($errors)) {
                $result = $this->deliveryModel->create($data);
                if ($result) {
                    $this->setFlash('success', 'Delivery charge created successfully');
                } else {
                    $this->setFlash('error', 'Failed to create delivery charge');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/delivery');
        }
        
        $this->view('admin/delivery/create', [
            'title' => 'Add Delivery Charge'
        ]);
    }

    /**
     * Edit delivery charge
     */
    public function edit($id)
    {
        $this->requireAdmin();
        
        $charge = $this->deliveryModel->find($id);
        if (!$charge) {
            $this->setFlash('error', 'Delivery charge not found');
            $this->redirect('admin/delivery');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'location_name' => trim($_POST['location_name'] ?? ''),
                'charge' => floatval($_POST['charge'] ?? 0)
            ];
            
            // Validation
            $errors = [];
            if (empty($data['location_name'])) {
                $errors[] = 'Location name is required';
            }
            if ($data['charge'] < 0) {
                $errors[] = 'Charge must be a positive number';
            }
            
            // Check if location already exists (excluding current record)
            $existing = $this->deliveryModel->getChargeByLocation($data['location_name']);
            if ($existing && $existing['id'] != $id) {
                $errors[] = 'Location already exists';
            }
            
            if (empty($errors)) {
                $result = $this->deliveryModel->update($id, $data);
                if ($result) {
                    $this->setFlash('success', 'Delivery charge updated successfully');
                } else {
                    $this->setFlash('error', 'Failed to update delivery charge');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/delivery');
        }
        
        $this->view('admin/delivery/edit', [
            'charge' => $charge,
            'title' => 'Edit Delivery Charge'
        ]);
    }

    /**
     * Delete delivery charge
     */
    public function delete($id)
    {
        $this->requireAdmin();
        
        $charge = $this->deliveryModel->find($id);
        if (!$charge) {
            $this->setFlash('error', 'Delivery charge not found');
            $this->redirect('admin/delivery');
        }
        
        // Don't allow deletion of "Free" delivery
        if ($charge['location_name'] === 'Free') {
            $this->setFlash('error', 'Cannot delete free delivery option');
            $this->redirect('admin/delivery');
        }
        
        $result = $this->deliveryModel->delete($id);
        if ($result) {
            $this->setFlash('success', 'Delivery charge deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete delivery charge');
        }
        
        $this->redirect('admin/delivery');
    }

    /**
     * Get delivery charges for AJAX
     */
    public function getCharges()
    {
        if ($this->isAjaxRequest()) {
            $charges = $this->deliveryModel->getAllCharges();
            $this->jsonResponse([
                'success' => true,
                'charges' => $charges
            ]);
        } else {
            $this->redirect('admin/delivery');
        }
    }

    /**
     * Quick add delivery charge via AJAX
     */
    public function quickAdd()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/delivery');
            return;
        }
        
        $data = [
            'location_name' => trim($_POST['location_name'] ?? ''),
            'charge' => floatval($_POST['charge'] ?? 0)
        ];
        
        // Validation
        $errors = [];
        if (empty($data['location_name'])) {
            $errors[] = 'Location name is required';
        }
        if ($data['charge'] < 0) {
            $errors[] = 'Charge must be a positive number';
        }
        
        // Check if location already exists
        $existing = $this->deliveryModel->getChargeByLocation($data['location_name']);
        if ($existing) {
            $errors[] = 'Location already exists';
        }
        
        if (empty($errors)) {
            $result = $this->deliveryModel->create($data);
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Delivery charge added successfully',
                    'charge' => $data
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to add delivery charge'
                ]);
            }
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => implode(', ', $errors)
            ]);
        }
    }

    /**
     * Toggle free delivery for all locations
     */
    public function toggleFreeDelivery()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/delivery');
            return;
        }
        
        $freeDelivery = $_POST['free_delivery'] ?? '0';
        $isEnabled = $freeDelivery === '1';
        
        try {
            if ($isEnabled) {
                // Set all delivery charges to 0 using model method
                $result = $this->deliveryModel->enableFreeDelivery();
                if ($result) {
                    $message = 'Free delivery enabled for all locations';
                } else {
                    throw new Exception('Failed to enable free delivery');
                }
            } else {
                // Restore default charges for all cities using model method
                $result = $this->deliveryModel->restoreDefaultCharges();
                if ($result) {
                    $message = 'Free delivery disabled. Default charges restored for all cities';
                } else {
                    throw new Exception('Failed to restore default charges');
                }
            }
            
            $this->jsonResponse([
                'success' => true,
                'message' => $message
            ]);
        } catch (Exception $e) {
            error_log('AdminDeliveryController toggleFreeDelivery error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update free delivery setting: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Set default delivery fee for all locations
     */
    public function setDefaultFee()
    {
        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/delivery');
            return;
        }

        $this->requireAdmin();

        $defaultFee = floatval($_POST['default_fee'] ?? 0);

        // Validation
        if ($defaultFee < 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Delivery fee must be a positive number'
            ]);
            return;
        }

        $result = $this->deliveryModel->setDefaultFeeForAll($defaultFee);
        
        if ($result) {
            $this->jsonResponse([
                'success' => true,
                'message' => "Default delivery fee set to रु{$defaultFee} for all locations successfully"
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to set default delivery fee'
            ]);
        }
    }
}


