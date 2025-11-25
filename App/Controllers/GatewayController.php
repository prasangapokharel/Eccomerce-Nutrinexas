<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\PaymentGateway;
use App\Models\GatewayCurrency;

class GatewayController extends Controller
{
    private $gatewayModel;
    private $currencyModel;

    public function __construct()
    {
        parent::__construct();
        $this->gatewayModel = new PaymentGateway();
        $this->currencyModel = new GatewayCurrency();
    }

    /**
     * Admin: List all payment gateways
     */
    public function index()
    {
        $this->requireAdmin();
        
        $gateways = $this->gatewayModel->all();
        
        $this->view('admin/payment/index', [
            'gateways' => $gateways,
            'title' => 'Payment Gateways'
        ]);
    }

    /**
     * Admin: Manual payment methods (Bank Transfer, COD)
     */
    public function manual()
    {
        $this->requireAdmin();
        
        $manualGateways = $this->gatewayModel->getGatewaysByType(['manual', 'cod']);
        
        $this->view('admin/payment/manual', [
            'gateways' => $manualGateways,
            'title' => 'Manual Payment Methods'
        ]);
    }

    /**
     * Admin: Merchant payment methods (Digital wallets)
     */
    public function merchant()
    {
        $this->requireAdmin();
        
        $merchantGateways = $this->gatewayModel->getGatewaysByType(['digital']);
        
        $this->view('admin/payment/merchant', [
            'gateways' => $merchantGateways,
            'title' => 'Merchant Payment Gateways'
        ]);
    }

    /**
     * Admin: Edit gateway
     */
    public function edit($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/payment');
            return;
        }

        $gateway = $this->gatewayModel->getGatewayWithCurrencies($id);
        
        if (!$gateway) {
            $this->setFlash('error', 'Gateway not found');
            $this->redirect('admin/payment');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->updateGateway($id);
            return;
        }

        $this->view('admin/payment/edit', [
            'gateway' => $gateway,
            'title' => 'Edit Payment Gateway - ' . $gateway['name']
        ]);
    }

    /**
     * Admin: Create new gateway
     */
    public function create()
    {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->createGateway();
            return;
        }

        $this->view('admin/payment/create', [
            'title' => 'Create Payment Gateway'
        ]);
    }

    /**
     * Admin: Toggle gateway status
     */
    public function toggleStatus($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid gateway ID'], 400);
            return;
        }

        $result = $this->gatewayModel->toggleStatus($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Gateway status updated']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }

    /**
     * Admin: Toggle test mode
     */
    public function toggleTestMode($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid gateway ID'], 400);
            return;
        }

        $result = $this->gatewayModel->toggleTestMode($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Test mode updated']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update test mode'], 500);
        }
    }

    /**
     * Admin: Delete gateway
     */
    public function delete($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid gateway ID']);
                return;
            }
            $this->setFlash('error', 'Invalid gateway ID');
            $this->redirect('admin/payment');
            return;
        }

        $gateway = $this->gatewayModel->find($id);
        
        if (!$gateway) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Gateway not found']);
                return;
            }
            $this->setFlash('error', 'Gateway not found');
            $this->redirect('admin/payment');
            return;
        }

        // Don't allow deletion of COD (Cash on Delivery) as it's the only default gateway
        if ($gateway['type'] === 'cod') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Cannot delete Cash on Delivery - it is a required default payment method']);
                return;
            }
            $this->setFlash('error', 'Cannot delete Cash on Delivery - it is a required default payment method');
            $this->redirect('admin/payment');
            return;
        }

        if ($this->gatewayModel->deleteGateway($id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Gateway deleted successfully']);
                return;
            }
            $this->setFlash('success', 'Gateway deleted successfully');
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete gateway']);
                return;
            }
            $this->setFlash('error', 'Failed to delete gateway');
        }

        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/payment');
        }
    }

    /**
     * Update gateway data
     */
    private function updateGateway($id)
    {
        // Validate required fields
        $errors = [];
        
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $type = $_POST['type'] ?? '';
        
        if (empty($name)) {
            $errors[] = 'Gateway name is required';
        }
        
        if (empty($slug)) {
            $errors[] = 'Gateway slug is required';
        }
        
        if (empty($type)) {
            $errors[] = 'Gateway type is required';
        }
        
        // Check if slug already exists (excluding current gateway)
        if ($this->gatewayModel->existsBySlug($slug, $id)) {
            $errors[] = 'Gateway slug already exists';
        }
        
        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            $this->redirect('admin/payment/edit/' . $id);
            return;
        }
        
        $data = [
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'description' => trim($_POST['description'] ?? ''),
            'logo' => trim($_POST['logo'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_test_mode' => isset($_POST['is_test_mode']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0)
        ];

        // Handle parameters based on gateway type
        $parameters = [];
        
        if ($data['type'] === 'digital') {
            // Digital wallet parameters - handle both Khalti and eSewa
            $parameters = [
                // Common parameters
                'secret_key' => trim($_POST['secret_key'] ?? ''),
                'merchant_id' => trim($_POST['merchant_id'] ?? ''),
                'api_key' => trim($_POST['api_key'] ?? ''),
                'merchant_username' => trim($_POST['merchant_username'] ?? ''),
                'merchant_password' => trim($_POST['merchant_password'] ?? ''),
                
                // eSewa specific parameters
                'test_merchant_id' => trim($_POST['test_merchant_id'] ?? ''),
                'test_secret_key' => trim($_POST['test_secret_key'] ?? ''),
                'test_token' => trim($_POST['test_token'] ?? ''),
                'test_client_id' => trim($_POST['test_client_id'] ?? ''),
                'test_client_secret' => trim($_POST['test_client_secret'] ?? ''),
                'live_merchant_id' => trim($_POST['live_merchant_id'] ?? ''),
                'live_secret_key' => trim($_POST['live_secret_key'] ?? ''),
                'live_token' => trim($_POST['live_token'] ?? ''),
                'live_client_id' => trim($_POST['live_client_id'] ?? ''),
                'live_client_secret' => trim($_POST['live_client_secret'] ?? ''),
                'test_payment_url' => trim($_POST['test_payment_url'] ?? ''),
                'test_status_url' => trim($_POST['test_status_url'] ?? ''),
                'live_payment_url' => trim($_POST['live_payment_url'] ?? ''),
                'live_status_url' => trim($_POST['live_status_url'] ?? ''),
                
                // Khalti specific parameters
                'test_secret_key' => trim($_POST['test_secret_key'] ?? ''),
                'test_api_url' => trim($_POST['test_api_url'] ?? ''),
                'live_api_url' => trim($_POST['live_api_url'] ?? '')
            ];
        } elseif ($data['type'] === 'manual') {
            // Manual payment parameters
            $parameters = [
                'bank_name' => trim($_POST['bank_name'] ?? ''),
                'account_number' => trim($_POST['account_number'] ?? ''),
                'account_name' => trim($_POST['account_name'] ?? ''),
                'branch' => trim($_POST['branch'] ?? ''),
                'swift_code' => trim($_POST['swift_code'] ?? '')
            ];
        }

        $data['parameters'] = $parameters;

        // Handle file upload for logo
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'public/images/payment-gateways/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
            $fileName = $data['slug'] . '_logo.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $filePath)) {
                $data['logo'] = ASSETS_URL . '/images/payment-gateways/' . $fileName;
            }
        }

        // Update gateway
        if ($this->gatewayModel->updateGateway($id, $data)) {
            // Handle currency updates
            if (isset($_POST['currencies']) && is_array($_POST['currencies'])) {
                $currencies = [];
                foreach ($_POST['currencies'] as $currency) {
                    if (!empty($currency['currency_code'])) {
                        $currencies[] = [
                            'currency_code' => $currency['currency_code'],
                            'currency_symbol' => $currency['currency_symbol'] ?? '₹',
                            'conversion_rate' => (float)($currency['conversion_rate'] ?? 1.0),
                            'min_limit' => !empty($currency['min_limit']) ? (float)$currency['min_limit'] : null,
                            'max_limit' => !empty($currency['max_limit']) ? (float)$currency['max_limit'] : null,
                            'percentage_charge' => (float)($currency['percentage_charge'] ?? 0),
                            'fixed_charge' => (float)($currency['fixed_charge'] ?? 0),
                            'is_active' => 1
                        ];
                    }
                }
                
                if (!empty($currencies)) {
                    $this->currencyModel->updateGatewayCurrencies($id, $currencies);
                }
            }
            
            $this->setFlash('success', 'Gateway updated successfully');
        } else {
            $this->setFlash('error', 'Failed to update gateway');
        }

        $this->redirect('admin/payment/edit/' . $id);
    }

    /**
     * Create new gateway
     */
    private function createGateway()
    {
        $data = [
            'name' => $this->post('name'),
            'slug' => $this->post('slug'),
            'type' => $this->post('type'),
            'description' => $this->post('description'),
            'is_active' => $this->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->post('sort_order', 0)
        ];

        // Handle parameters based on gateway type
        $parameters = [];
        
        if ($data['type'] === 'digital') {
            $parameters = [
                'secret_key' => $this->post('secret_key'),
                'merchant_id' => $this->post('merchant_id'),
                'api_key' => $this->post('api_key'),
            ];
        } elseif ($data['type'] === 'manual') {
            $parameters = [
                'bank_name' => $this->post('bank_name'),
                'account_number' => $this->post('account_number'),
                'account_name' => $this->post('account_name'),
                'branch' => $this->post('branch'),
                'swift_code' => $this->post('swift_code')
            ];
        }

        $data['parameters'] = $parameters;

        if ($this->gatewayModel->createGateway($data)) {
            $this->setFlash('success', 'Gateway created successfully');
            $this->redirect('admin/payment');
        } else {
            $this->setFlash('error', 'Failed to create gateway');
            $this->redirect('admin/payment/create');
        }
    }

    /**
     * Get active gateways for checkout
     */
    public function getActiveGateways()
    {
        header('Content-Type: application/json');
        
        $gateways = $this->gatewayModel->getActiveGateways();
        
        echo json_encode([
            'success' => true,
            'gateways' => $gateways
        ]);
    }
}