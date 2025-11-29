<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\SiteWideSale;

class AdminSaleController extends Controller
{
    private $saleModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->saleModel = new SiteWideSale();
    }

    /**
     * List all sales
     */
    public function index()
    {
        $sales = $this->saleModel->getAllSales();
        $activeSale = $this->saleModel->getActiveSale();
        
        $this->view('admin/sales/index', [
            'sales' => $sales,
            'activeSale' => $activeSale,
            'title' => 'Site-Wide Sales Management'
        ]);
    }

    /**
     * Create new sale
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token');
                $this->redirect('admin/sales');
                return;
            }
            
            // Check if active sale already exists
            if ($this->saleModel->hasActiveSale()) {
                $this->setFlash('error', 'An active sale already exists. Only one sale can be active at a time.');
                $this->redirect('admin/sales');
                return;
            }
            
            $data = [
                'sale_name' => trim($_POST['sale_name'] ?? 'Site-Wide Sale'),
                'sale_percent' => floatval($_POST['sale_percent'] ?? 0),
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'is_active' => 1
            ];
            
            // Validation
            $errors = [];
            if ($data['sale_percent'] <= 0 || $data['sale_percent'] >= 100) {
                $errors[] = 'Sale percent must be between 1 and 99';
            }
            
            if (empty($data['start_date']) || empty($data['end_date'])) {
                $errors[] = 'Start and end dates are required';
            }
            
            if (!empty($data['start_date']) && !empty($data['end_date']) && $data['start_date'] >= $data['end_date']) {
                $errors[] = 'End date must be after start date';
            }
            
            if (empty($errors)) {
                try {
                    $saleId = $this->saleModel->createSale($data);
                    
                    if ($saleId) {
                        $this->setFlash('success', 'Sale created successfully! Products with sale="on" will show discounted prices.');
                    } else {
                        // Check if it's because of active sale or other error
                        if ($this->saleModel->hasActiveSale()) {
                            $this->setFlash('error', 'An active sale already exists. Only one sale can be active at a time.');
                        } else {
                            $this->setFlash('error', 'Failed to create sale. Please check the form data and try again.');
                        }
                    }
                } catch (\Exception $e) {
                    error_log('Sale creation error: ' . $e->getMessage());
                    $this->setFlash('error', 'Failed to create sale: ' . $e->getMessage());
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/sales');
        } else {
            $activeSale = $this->saleModel->getActiveSale();
            $this->view('admin/sales/create', [
                'activeSale' => $activeSale,
                'title' => 'Create Site-Wide Sale'
            ]);
        }
    }

    /**
     * Update sale
     */
    public function update($id)
    {
        if (!$id) {
            $this->redirect('admin/sales');
        }
        
        $sale = $this->saleModel->getSaleById($id);
        if (!$sale) {
            $this->setFlash('error', 'Sale not found');
            $this->redirect('admin/sales');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token');
                $this->redirect('admin/sales');
                return;
            }
            
            $data = [
                'sale_name' => trim($_POST['sale_name'] ?? 'Site-Wide Sale'),
                'sale_percent' => floatval($_POST['sale_percent'] ?? 0),
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validation
            $errors = [];
            if ($data['sale_percent'] <= 0 || $data['sale_percent'] >= 100) {
                $errors[] = 'Sale percent must be between 1 and 99';
            }
            
            if (empty($errors)) {
                if ($this->saleModel->updateSale($id, $data)) {
                    $this->setFlash('success', 'Sale updated successfully!');
                } else {
                    $this->setFlash('error', 'Failed to update sale. Another active sale may exist.');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/sales');
        } else {
            $this->view('admin/sales/edit', [
                'sale' => $sale,
                'title' => 'Edit Sale'
            ]);
        }
    }

    /**
     * Delete sale
     */
    public function delete($id)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/sales');
        }
        
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token');
            $this->redirect('admin/sales');
            return;
        }
        
        if ($this->saleModel->deleteSale($id)) {
            $this->setFlash('success', 'Sale deleted successfully');
        } else {
            $this->setFlash('error', 'Failed to delete sale');
        }
        
        $this->redirect('admin/sales');
    }

    /**
     * Toggle sale status
     */
    public function toggleStatus($id)
    {
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid sale ID']);
            return;
        }
        
        $sale = $this->saleModel->getSaleById($id);
        if (!$sale) {
            $this->jsonResponse(['success' => false, 'message' => 'Sale not found']);
            return;
        }
        
        $newStatus = $sale['is_active'] ? 0 : 1;
        
        // If activating, check if another active sale exists
        if ($newStatus == 1) {
            $activeSale = $this->saleModel->getActiveSale();
            if ($activeSale && $activeSale['id'] != $id) {
                $this->jsonResponse(['success' => false, 'message' => 'Another active sale already exists']);
                return;
            }
        }
        
        $data = [
            'sale_name' => $sale['sale_name'],
            'sale_percent' => $sale['sale_percent'] ?? $sale['discount_percent'] ?? 0,
            'start_date' => $sale['start_date'],
            'end_date' => $sale['end_date'],
            'is_active' => $newStatus,
            'note' => $sale['note'] ?? null
        ];
        
        if ($this->saleModel->updateSale($id, $data)) {
            $this->jsonResponse(['success' => true, 'message' => 'Sale status updated', 'is_active' => $newStatus]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status']);
        }
    }
}
