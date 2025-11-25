<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Curior\Curior as CuriorModel;
use App\Models\Order;
use App\Services\CuriorPasswordResetService;
use App\Traits\AdminCRUDTrait;

class AdminCuriorController extends Controller
{
    use AdminCRUDTrait;
    private $curiorModel;
    private $orderModel;
    private $passwordResetService;

    public function __construct()
    {
        parent::__construct();
        $this->curiorModel = new CuriorModel();
        $this->orderModel = new Order();
        $this->passwordResetService = new CuriorPasswordResetService($this->curiorModel);
        $this->requireAdmin();
    }

    /**
     * List all curiors
     */
    public function index()
    {
        $this->requireAdmin();
        
        $curiors = $this->curiorModel->getAllCuriors();
        $stats = $this->curiorModel->getStats();
        
        $this->view('admin/curior/index', [
            'curiors' => $curiors,
            'stats' => $stats,
            'title' => 'Curior Management'
        ]);
    }

    /**
     * Create new curior
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'status' => $_POST['status'] ?? 'active'
            ];

            // Validate data
            $errors = [];

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            }

            if (empty($data['phone'])) {
                $errors['phone'] = 'Phone is required';
            } elseif ($this->curiorModel->getByPhone($data['phone'])) {
                $errors['phone'] = 'Phone number already exists';
            }

            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif (!empty($data['email']) && $this->curiorModel->getByEmail($data['email'])) {
                $errors['email'] = 'Email already exists';
            }

            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }

            if (empty($errors)) {
                if ($this->curiorModel->create($data)) {
                    $this->setFlash('success', 'Curior created successfully');
                    $this->redirect('admin/curior');
                } else {
                    $this->setFlash('error', 'Failed to create curior');
                }
            }

            $this->view('admin/curior/create', [
                'data' => $data,
                'errors' => $errors,
                'title' => 'Create Curior'
            ]);
        } else {
            $this->view('admin/curior/create', [
                'title' => 'Create Curior'
            ]);
        }
    }

    /**
     * Edit curior
     */
    public function edit($id = null)
    {
        if (!$id) {
            $this->setFlash('error', 'Invalid curior ID');
            $this->redirect('admin/curior');
            return;
        }

        $curior = $this->curiorModel->getById($id);
        if (!$curior) {
            $this->setFlash('error', 'Curior not found');
            $this->redirect('admin/curior');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'status' => $_POST['status'] ?? 'active'
            ];

            // Check if password is being changed
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }

            // Validate data
            $errors = [];

            if (empty($data['name'])) {
                $errors['name'] = 'Name is required';
            }

            if (empty($data['phone'])) {
                $errors['phone'] = 'Phone is required';
            } else {
                $existing = $this->curiorModel->getByPhone($data['phone']);
                if ($existing && $existing['id'] != $id) {
                    $errors['phone'] = 'Phone number already exists';
                }
            }

            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif (!empty($data['email'])) {
                $existing = $this->curiorModel->getByEmail($data['email']);
                if ($existing && $existing['id'] != $id) {
                    $errors['email'] = 'Email already exists';
                }
            }

            if (isset($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }

            if (empty($errors)) {
                if ($this->curiorModel->update($id, $data)) {
                    $this->setFlash('success', 'Curior updated successfully');
                    $this->redirect('admin/curior');
                } else {
                    $this->setFlash('error', 'Failed to update curior');
                }
            }

            $this->view('admin/curior/edit', [
                'curior' => $curior,
                'data' => $data,
                'errors' => $errors,
                'title' => 'Edit Curior'
            ]);
        } else {
            $this->view('admin/curior/edit', [
                'curior' => $curior,
                'title' => 'Edit Curior'
            ]);
        }
    }

    /**
     * Delete curior
     */
    public function delete($id = null)
    {
        if (!$id) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid curior ID']);
                return;
            }
            $this->setFlash('error', 'Invalid curior ID');
            $this->redirect('admin/curior');
            return;
        }

        $curior = $this->curiorModel->getById($id);
        if (!$curior) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Curior not found']);
                return;
            }
            $this->setFlash('error', 'Curior not found');
            $this->redirect('admin/curior');
            return;
        }

        if ($this->curiorModel->delete($id)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => true, 'message' => 'Curior deleted successfully']);
                return;
            }
            $this->setFlash('success', 'Curior deleted successfully');
        } else {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete curior']);
                return;
            }
            $this->setFlash('error', 'Failed to delete curior');
        }

        if (!$this->isAjaxRequest()) {
            $this->redirect('admin/curior');
        }
    }

    /**
     * Send password reset link to selected curior.
     */
    public function sendResetLink($id = null)
    {
        if (!$id) {
            $this->respondReset(false, 'Invalid curior ID');
            return;
        }

        $curior = $this->curiorModel->getById($id);
        if (!$curior) {
            $this->respondReset(false, 'Curior not found');
            return;
        }

        if (empty($curior['email'])) {
            $this->respondReset(false, 'This curior does not have an email address.');
            return;
        }

        try {
            $token = $this->passwordResetService->generateToken((int) $curior['id']);
            if (!$token) {
                $this->respondReset(false, 'Failed to generate reset token.');
                return;
            }

            $sent = $this->passwordResetService->sendResetEmail($curior, $token);
            if (!$sent) {
                $this->respondReset(false, 'Failed to send reset email. Please check email settings.');
                return;
            }

            $this->respondReset(true, 'Password reset link sent to ' . $curior['email']);
        } catch (\Throwable $e) {
            error_log('AdminCuriorController::sendResetLink error: ' . $e->getMessage());
            $this->respondReset(false, 'Unexpected error occurred.');
        }
    }

    private function respondReset(bool $success, string $message): void
    {
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(['success' => $success, 'message' => $message]);
            return;
        }

        $this->setFlash($success ? 'success' : 'error', $message);
        $this->redirect('admin/curior');
    }

    /**
     * Toggle curior status
     */
    public function toggleStatus($id = null)
    {
        if (!$id) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid curior ID']);
            return;
        }

        $curior = $this->curiorModel->getById($id);
        if (!$curior) {
            $this->jsonResponse(['success' => false, 'message' => 'Curior not found']);
            return;
        }

        $newStatus = $curior['status'] === 'active' ? 'inactive' : 'active';
        
        if ($this->curiorModel->update($id, ['status' => $newStatus])) {
            $this->jsonResponse(['success' => true, 'message' => 'Status updated successfully', 'new_status' => $newStatus]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Failed to update status']);
        }
    }

    /**
     * Assign order to curior (AJAX)
     */
    public function assignCurior()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->isAjaxRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);
            return;
        }

        if (!$this->validateCSRF()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
            return;
        }

        $orderId = (int)($_POST['order_id'] ?? 0);
        $curiorId = (int)($_POST['curior_id'] ?? 0);

        if ($orderId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid order ID'], 400);
            return;
        }

        if ($curiorId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Please select a curior'], 400);
            return;
        }

        try {
            $order = $this->orderModel->find($orderId);
            if (!$order) {
                $this->jsonResponse(['success' => false, 'message' => 'Order not found'], 404);
                return;
            }

            $curior = $this->curiorModel->getById($curiorId);
            if (!$curior) {
                $this->jsonResponse(['success' => false, 'message' => 'Curior not found'], 404);
                return;
            }

            $this->orderModel->assignCuriorToOrder($orderId, $curiorId);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Order assigned to curior successfully',
                'curior_name' => $curior['name']
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('AdminCuriorController assign error: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Failed to assign order'], 500);
        }
    }
}
