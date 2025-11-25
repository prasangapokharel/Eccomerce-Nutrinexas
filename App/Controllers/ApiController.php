<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

/**
 * Base API Controller
 * Provides common functionality for all API endpoints
 */
class ApiController extends Controller {
    protected $db;
    protected $user;
    protected $requestMethod;
    protected $endpoint;
    protected $params;
    protected $headers;
    
    public function __construct() {
        parent::__construct();
        
        // Initialize database connection
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            // Fallback: try to create instance through reflection
            try {
                $reflection = new \ReflectionClass('\App\Core\Database');
                $this->db = $reflection->newInstanceWithoutConstructor();
            } catch (Exception $e2) {
                error_log("Failed to initialize database in ApiController: " . $e2->getMessage());
                $this->db = null;
            }
        }
        
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->endpoint = $this->getEndpoint();
        $this->params = $this->getParams();
        $this->headers = $this->getHeaders();
        
        // Set CORS headers
        $this->setCorsHeaders();
        
        // Handle preflight requests
        if ($this->requestMethod === 'OPTIONS') {
            $this->sendResponse(['message' => 'OK'], 200);
            exit;
        }
    }
    
    /**
     * Get the current API endpoint
     */
    protected function getEndpoint() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        return trim($path, '/');
    }
    
    /**
     * Get request parameters
     */
    protected function getParams() {
        $params = [];
        
        // GET parameters
        if ($this->requestMethod === 'GET') {
            $params = $_GET;
        }
        
        // POST/PUT/PATCH parameters
        if (in_array($this->requestMethod, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            if ($input) {
                $jsonData = json_decode($input, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $params = $jsonData;
                } else {
                    $params = $_POST;
                }
            } else {
                $params = $_POST;
            }
        }
        
        // URL parameters (for REST endpoints)
        $pathParts = explode('/', $this->endpoint);
        if (count($pathParts) > 2) {
            $params['id'] = $pathParts[2] ?? null;
        }
        
        return $params;
    }
    
    /**
     * Get request headers
     */
    protected function getHeaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
    
    /**
     * Set CORS headers
     */
    protected function setCorsHeaders() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Content-Type: application/json; charset=utf-8');
    }
    
    /**
     * Authenticate API request
     */
    protected function authenticate() {
        $authHeader = $this->headers['Authorization'] ?? '';
        
        error_log("API Authentication - Auth Header: " . $authHeader);
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            error_log("API Authentication - No valid token provided");
            $this->sendError('Unauthorized: No valid token provided', 401);
        }
        
        $token = $matches[1];
        error_log("API Authentication - Token: " . substr($token, 0, 16) . "...");
        
        // Verify token in database
        $sql = "SELECT t.*, u.* FROM api_tokens t 
                JOIN users u ON t.user_id = u.id 
                WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())";
        
        $result = $this->db->query($sql, [$token])->fetch();
        
        if (!$result) {
            error_log("API Authentication - Invalid or expired token");
            $this->sendError('Unauthorized: Invalid or expired token', 401);
        }
        
        error_log("API Authentication - User authenticated: " . ($result['email'] ?? 'Unknown'));
        
        // Update last used timestamp
        $this->db->query("UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?", [$token]);
        
        $this->user = $result;
        return $result;
    }
    
    /**
     * Check if user has required permissions
     */
    protected function requirePermission($permission) {
        if (!$this->user) {
            $this->authenticate();
        }
        
        $abilities = json_decode($this->user['abilities'] ?? '[]', true);
        
        if (!in_array($permission, $abilities) && !in_array('*', $abilities)) {
            $this->sendError('Forbidden: Insufficient permissions', 403);
        }
    }
    
    /**
     * Log API request
     */
    protected function logApiRequest($statusCode, $responseData, $responseTime = null) {
        $logData = [
            'user_id' => $this->user['id'] ?? null,
            'endpoint' => $this->endpoint,
            'method' => $this->requestMethod,
            'request_data' => json_encode($this->params),
            'response_data' => json_encode($responseData),
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        $sql = "INSERT INTO api_logs (user_id, endpoint, method, request_data, response_data, status_code, response_time, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, array_values($logData));
    }
    
    /**
     * Send successful response
     */
    protected function sendResponse($data, $statusCode = 200) {
        $startTime = microtime(true);
        
        $response = [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c'),
            'endpoint' => $this->endpoint
        ];
        
        http_response_code($statusCode);
        echo json_encode($response, JSON_PRETTY_PRINT);
        
        $responseTime = microtime(true) - $startTime;
        $this->logApiRequest($statusCode, $response, $responseTime);
    }
    
    /**
     * Send error response
     */
    protected function sendError($message, $statusCode = 400, $errors = null) {
        $startTime = microtime(true);
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c'),
            'endpoint' => $this->endpoint
        ];
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        http_response_code($statusCode);
        echo json_encode($response, JSON_PRETTY_PRINT);
        
        $responseTime = microtime(true) - $startTime;
        $this->logApiRequest($statusCode, $response, $responseTime);
    }
    
    /**
     * Validate required parameters
     */
    protected function validateRequired($params, $required) {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendError('Missing required parameters: ' . implode(', ', $missing), 400);
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     */
    protected function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeInput($value);
            }
        } else {
            $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Show API tester interface
     */
    public function showTester() {
        $testerPath = dirname(dirname(__DIR__)) . '/api/test.html';
        if (file_exists($testerPath)) {
            readfile($testerPath);
        } else {
            echo "API Tester not found";
        }
    }
    
    /**
     * Show API key test interface
     */
    public function showTestKey() {
        // Use the correct path - go up from App/Controllers to App, then to root, then to api
        $testPath = dirname(dirname(dirname(__DIR__))) . '/api/test-key.php';
        
        if (file_exists($testPath)) {
            readfile($testPath);
        } else {
            echo "<h1>API Key Test</h1>";
            echo "<p>❌ Test file not found at: $testPath</p>";
            echo "<p>Current file: " . __FILE__ . "</p>";
            echo "<p>Current directory: " . __DIR__ . "</p>";
            echo "<p>Looking for file at: $testPath</p>";
            
            // Alternative: Create a simple inline test
            echo "<hr>";
            echo "<h2>Simple Database Test</h2>";
            echo "<p>Testing if we can access the database through the controller...</p>";
            
            try {
                if (isset($this->db) && $this->db) {
                    echo "<p>✅ Database connection available through controller</p>";
                    
                    // Test if api_tokens table exists
                    $tablesSql = "SHOW TABLES LIKE 'api_tokens'";
                    $result = $this->db->query($tablesSql);
                    
                    if (method_exists($result, 'fetchAll')) {
                        $tables = $result->fetchAll();
                    } elseif (method_exists($result, 'fetch_all')) {
                        $tables = $result->fetch_all();
                    } elseif (is_array($result)) {
                        $tables = $result;
                    } else {
                        echo "<p>⚠️ Query result type: " . gettype($result) . "</p>";
                        $tables = [];
                    }
                    
                    if (count($tables) > 0) {
                        echo "<p>✅ api_tokens table exists</p>";
                    } else {
                        echo "<p>❌ api_tokens table does not exist</p>";
                    }
                } else {
                    echo "<p>❌ No database connection available</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    /**
     * Paginate results
     */
    protected function paginate($query, $params, $page = 1, $perPage = 20) {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM ($query) as count_table";
        $totalResult = $this->db->query($countQuery, $params)->fetch();
        $total = $totalResult['total'];
        
        // Get paginated results
        $paginatedQuery = $query . " LIMIT ? OFFSET ?";
        $paginatedParams = array_merge($params, [$perPage, $offset]);
        $results = $this->db->query($paginatedQuery, $paginatedParams)->fetchAll();
        
        return [
            'data' => $results,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_next' => $page < ceil($total / $perPage),
                'has_prev' => $page > 1
            ]
        ];
    }
}
?>

