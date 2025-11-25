<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\ApiKey;

class BaseApiController extends Controller
{
    protected $apiKey;
    protected $currentUser;
    protected $permissions;
    
    public function __construct()
    {
        parent::__construct();
        $this->apiKey = new ApiKey();
        $this->authenticate();
    }
    
    /**
     * Authenticate API request
     */
    protected function authenticate()
    {
        $headers = getallheaders();
        $apiKey = null;
        
        // Check for API key in headers
        if (isset($headers['X-API-Key'])) {
            $apiKey = $headers['X-API-Key'];
        } elseif (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (strpos($auth, 'Bearer ') === 0) {
                $apiKey = substr($auth, 7);
            }
        } elseif (isset($_GET['api_key'])) {
            $apiKey = $_GET['api_key'];
        }
        
        if (!$apiKey) {
            $this->jsonResponse(['error' => 'API key required'], 401);
            exit;
        }
        
        $keyData = $this->apiKey->validateKey($apiKey);
        
        if (!$keyData) {
            $this->jsonResponse(['error' => 'Invalid API key'], 401);
            exit;
        }
        
        $this->currentUser = $keyData;
        $this->permissions = $keyData['permissions'];
    }
    
    /**
     * Check if current API key has permission
     */
    protected function hasPermission($permission)
    {
        return in_array($permission, $this->permissions);
    }
    
    /**
     * Require specific permission
     */
    protected function requirePermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            $this->jsonResponse(['error' => 'Insufficient permissions'], 403);
            exit;
        }
    }
    
    /**
     * Send JSON response
     */
    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Get request data
     */
    protected function getRequestData()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonResponse(['error' => 'Invalid JSON data'], 400);
        }
        
        return $data;
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($data, $requiredFields)
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->jsonResponse([
                'error' => 'Missing required fields',
                'missing_fields' => $missing
            ], 400);
        }
    }
    
    /**
     * Get pagination parameters
     */
    protected function getPaginationParams()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        
        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    /**
     * Format pagination response
     */
    protected function formatPaginatedResponse($data, $total, $pagination)
    {
        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $pagination['page'],
                'per_page' => $pagination['limit'],
                'total' => $total,
                'total_pages' => ceil($total / $pagination['limit']),
                'has_next' => $pagination['page'] < ceil($total / $pagination['limit']),
                'has_prev' => $pagination['page'] > 1
            ]
        ];
    }
}




























