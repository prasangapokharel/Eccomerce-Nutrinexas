<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\SiteWideSale;
use App\Models\Product;

class AdminSaleController extends Controller
{
    private $saleModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->saleModel = new SiteWideSale();
        $this->productModel = new Product();
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
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token');
                $this->redirect('admin/sales');
                return;
            }
            
            $data = [
                'sale_name' => trim($_POST['sale_name'] ?? ''),
                'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'note' => trim($_POST['note'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validation
            $errors = [];
            if (empty($data['sale_name'])) {
                $errors[] = 'Sale name is required';
            }
            
            if ($data['discount_percent'] <= 0 || $data['discount_percent'] >= 100) {
                $errors[] = 'Discount must be between 1 and 99 percent';
            }
            
            if (empty($data['start_date']) || empty($data['end_date'])) {
                $errors[] = 'Start and end dates are required';
            }
            
            if (!empty($data['start_date']) && !empty($data['end_date']) && $data['start_date'] >= $data['end_date']) {
                $errors[] = 'End date must be after start date';
            }
            
            if (empty($errors)) {
                $saleId = $this->saleModel->createSale($data);
                
                if ($saleId) {
                    $this->setFlash('success', 'Sale created and applied to all products successfully!');
                } else {
                    $this->setFlash('error', 'Failed to create sale');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
            
            $this->redirect('admin/sales');
        } else {
            $this->view('admin/sales/create', [
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
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token');
                $this->redirect('admin/sales');
                return;
            }
            
            $data = [
                'sale_name' => trim($_POST['sale_name'] ?? ''),
                'discount_percent' => floatval($_POST['discount_percent'] ?? 0),
                'start_date' => trim($_POST['start_date'] ?? ''),
                'end_date' => trim($_POST['end_date'] ?? ''),
                'note' => trim($_POST['note'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            // Validation
            $errors = [];
            if (empty($data['sale_name'])) {
                $errors[] = 'Sale name is required';
            }
            
            if ($data['discount_percent'] <= 0 || $data['discount_percent'] >= 100) {
                $errors[] = 'Discount must be between 1 and 99 percent';
            }
            
            if (empty($errors)) {
                if ($this->saleModel->updateSale($id, $data)) {
                    $this->setFlash('success', 'Sale updated successfully!');
                } else {
                    $this->setFlash('error', 'Failed to update sale');
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
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token');
            $this->redirect('admin/sales');
            return;
        }
        
        if ($this->saleModel->deleteSale($id)) {
            $this->setFlash('success', 'Sale deleted and removed from all products');
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
        
        $data = $sale;
        $data['is_active'] = $newStatus;
        
        if ($this->saleModel->updateSale($id, $data)) {
            $this->jsonResponse(['success' => true, 'message' => 'Sale status updated']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status']);
        }
    }
}

