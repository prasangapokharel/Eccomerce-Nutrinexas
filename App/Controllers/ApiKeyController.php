<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ApiKey;
use App\Core\Session;

class ApiKeyController extends Controller
{
    private $apiKeyModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->apiKeyModel = new ApiKey();
    }
    
    /**
     * Generate a new API key (for testing - no limits)
     */
    public function generate()
    {
        // Generate a test API key with full permissions
        $apiKey = $this->apiKeyModel->generateKey(
            null, // No user ID for test key
            'Test API Key - No Limits',
            ['read', 'write', 'admin'] // Full permissions
        );
        
        if ($apiKey) {
            $this->setFlash('success', 'API Key generated successfully!');
            $this->view('api/generated', [
                'apiKey' => $apiKey,
                'title' => 'API Key Generated'
            ]);
        } else {
            $this->setFlash('error', 'Failed to generate API key');
            $this->redirect('api/manage');
        }
    }
    
    /**
     * API key management page
     */
    public function manage()
    {
        if (!Session::has('user_id')) {
            $this->redirect('auth/login');
            return;
        }
        
        $userId = Session::get('user_id');
        $apiKeys = $this->apiKeyModel->getUserKeys($userId);
        
        $this->view('api/manage', [
            'apiKeys' => $apiKeys,
            'title' => 'API Key Management'
        ]);
    }
    
    /**
     * Create new API key
     */
    public function create()
    {
        if (!Session::has('user_id')) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Invalid request method'], 405);
            return;
        }
        
        $userId = Session::get('user_id');
        $name = $_POST['name'] ?? 'API Key ' . date('Y-m-d H:i:s');
        $permissions = $_POST['permissions'] ?? ['read', 'write'];
        
        if (is_string($permissions)) {
            $permissions = explode(',', $permissions);
        }
        
        $apiKey = $this->apiKeyModel->generateKey($userId, $name, $permissions);
        
        if ($apiKey) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'API key created successfully',
                'data' => $apiKey
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to create API key'], 500);
        }
    }
    
    /**
     * Deactivate API key
     */
    public function deactivate($id)
    {
        if (!Session::has('user_id')) {
            $this->jsonResponse(['error' => 'Authentication required'], 401);
            return;
        }
        
        $userId = Session::get('user_id');
        $result = $this->apiKeyModel->deactivateKey($id, $userId);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'API key deactivated successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to deactivate API key'], 500);
        }
    }
    
    /**
     * JSON response helper
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}