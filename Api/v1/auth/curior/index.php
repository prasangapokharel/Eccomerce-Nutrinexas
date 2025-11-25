<?php
/**
* Curior Authentication API v1
* Clean, minimal API for curior login with bearer token
* 
* @package NutriNexus\Api\V1\Auth\Curior
* @version 1.0.0
*/

// Set security headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Version');
header('X-API-Version: 1.0.0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Bootstrap application
require_once '../../../../App/bootstrap.php';

use App\Models\Curior;
use App\Core\Session;

/**
 * Curior Authentication API v1 Controller
 */
class CuriorAuthApiV1
{
    private $curiorModel;
    private $version = '1.0.0';

    public function __construct()
    {
        $this->curiorModel = new Curior();
    }

    /**
     * Curior login with bearer token
     * POST /api/v1/auth/curior/login
     */
    public function login()
    {
        try {
            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            if (!isset($input['email']) || !isset($input['password'])) {
                $this->sendError('Email and password are required', 400);
            }

            $email = trim($input['email']);
            $password = $input['password'];

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Invalid email format', 400);
            }

            // Authenticate curior
            $curior = $this->curiorModel->findByEmail($email);
            
            if (!$curior) {
                $this->sendError('Invalid credentials', 401);
            }

            // Verify password
            if (!password_verify($password, $curior['password'])) {
                $this->sendError('Invalid credentials', 401);
            }

            // Check if curior is active
            if ($curior['status'] !== 'active') {
                $this->sendError('Account is not active', 403);
            }

            // Generate bearer token
            $token = $this->generateBearerToken($curior['id'], $curior['email']);
            
            // Update last login
            $this->updateLastLogin($curior['id']);

            $response = [
                'curior_info' => [
                    'id' => $curior['id'],
                    'name' => $curior['name'],
                    'email' => $curior['email'],
                    'status' => $curior['status']
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 hour
                'login_time' => date('c')
            ];

            $this->sendResponse($response);
            
        } catch (Exception $e) {
            error_log("Curior auth API error: " . $e->getMessage());
            $this->sendError('Authentication failed', 500);
        }
    }

    /**
     * Generate bearer token
     */
    private function generateBearerToken($curiorId, $email)
    {
        $payload = [
            'curior_id' => $curiorId,
            'email' => $email,
            'type' => 'curior',
            'iat' => time(),
            'exp' => time() + 3600 // 1 hour
        ];
        
        // Create JWT-like token (simplified)
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload_encoded = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $header . '.' . $payload_encoded, 'nutrinexus_secret_key', true);
        $signature_encoded = base64_encode($signature);
        
        return $header . '.' . $payload_encoded . '.' . $signature_encoded;
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin($curiorId)
    {
        try {
            $this->curiorModel->update($curiorId, [
                'last_login' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log error but don't fail login
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }

    /**
     * Send success response
     */
    private function sendResponse($data)
    {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'version' => $this->version,
            'timestamp' => date('c'),
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Send error response
     */
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'version' => $this->version,
            'timestamp' => date('c'),
            'error' => $message
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
}

/**
 * API Router
 */
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse the request URI
$path = parse_url($requestUri, PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

// Route to curior auth endpoints
if (count($pathSegments) >= 5 && 
    $pathSegments[0] === 'api' && 
    $pathSegments[1] === 'v1' && 
    $pathSegments[2] === 'auth' && 
    $pathSegments[3] === 'curior') {
    
    $action = $pathSegments[4] ?? '';
    $controller = new CuriorAuthApiV1();
    
    switch ($action) {
        case 'login':
            if ($method === 'POST') {
                $controller->login();
            } else {
                $controller->sendError('Method not allowed', 405);
            }
            break;
            
        default:
            $controller->sendError('Invalid endpoint', 404);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'version' => '1.0.0',
        'timestamp' => date('c'),
        'error' => 'Invalid endpoint'
    ]);
}
