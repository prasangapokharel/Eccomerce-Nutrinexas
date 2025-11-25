<?php

namespace App\Controllers;

use App\Models\User;

/**
 * Users API Controller
 * Handles all user-related API endpoints
 */
class UsersApiController extends ApiController {
    
    public function __construct() {
        parent::__construct();
        $this->handleRequest();
    }
    
    /**
     * Route the request to appropriate method
     */
    private function handleRequest() {
        $pathParts = explode('/', $this->endpoint);
        
        if (count($pathParts) < 3 || $pathParts[1] !== 'users') {
            $this->sendError('Invalid endpoint', 404);
        }
        
        $action = $pathParts[2] ?? 'index';
        $id = $pathParts[3] ?? null;
        
        switch ($this->requestMethod) {
            case 'GET':
                if ($id) {
                    $this->show($id);
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                if ($action === 'login') {
                    $this->login();
                } elseif ($action === 'register') {
                    $this->register();
                } elseif ($action === 'logout') {
                    $this->logout();
                } else {
                    $this->store();
                }
                break;
            case 'PUT':
            case 'PATCH':
                $this->update($id);
                break;
            case 'DELETE':
                $this->destroy($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Get all users (with pagination)
     */
    public function index() {
        $this->authenticate();
        $this->requirePermission('users:read');
        
        $page = (int)($this->params['page'] ?? 1);
        $perPage = (int)($this->params['per_page'] ?? 20);
        $search = $this->params['search'] ?? '';
        $role = $this->params['role'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        if ($search) {
            $whereConditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if ($role) {
            $whereConditions[] = "role = ?";
            $params[] = $role;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $query = "SELECT id, name, email, phone, role, status, created_at, updated_at 
                  FROM users {$whereClause} 
                  ORDER BY created_at DESC";
        
        $result = $this->paginate($query, $params, $page, $perPage);
        
        $this->sendResponse($result);
    }
    
    /**
     * Get a specific user
     */
    public function show($id) {
        $this->authenticate();
        
        // Users can view their own profile, admins can view any
        if ($this->user['id'] != $id && !in_array('admin', json_decode($this->user['abilities'] ?? '[]', true))) {
            $this->requirePermission('users:read');
        }
        
        $sql = "SELECT id, name, email, phone, role, status, created_at, updated_at 
                FROM users WHERE id = ?";
        
        $user = $this->db->query($sql, [$id])->fetch();
        
        if (!$user) {
            $this->sendError('User not found', 404);
        }
        
        $this->sendResponse($user);
    }
    
    /**
     * Create a new user
     */
    public function store() {
        $this->authenticate();
        $this->requirePermission('users:create');
        
        $this->validateRequired($this->params, ['name', 'email', 'password']);
        
        // Check if email already exists
        $existingUser = $this->db->query("SELECT id FROM users WHERE email = ?", [$this->params['email']])->fetch();
        if ($existingUser) {
            $this->sendError('Email already exists', 400);
        }
        
        // Validate email format
        if (!filter_var($this->params['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
        }
        
        // Validate password strength
        if (strlen($this->params['password']) < 6) {
            $this->sendError('Password must be at least 6 characters long', 400);
        }
        
        $userData = [
            'name' => $this->sanitizeInput($this->params['name']),
            'email' => $this->sanitizeInput($this->params['email']),
            'phone' => $this->sanitizeInput($this->params['phone'] ?? ''),
            'password' => password_hash($this->params['password'], PASSWORD_DEFAULT),
            'role' => $this->sanitizeInput($this->params['role'] ?? 'customer'),
            'status' => $this->sanitizeInput($this->params['status'] ?? 'active'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO users (name, email, phone, password, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, array_values($userData));
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            
            // Get the created user (without password)
            $createdUser = $this->db->query(
                "SELECT id, name, email, phone, role, status, created_at, updated_at FROM users WHERE id = ?", 
                [$userId]
            )->fetch();
            
            $this->sendResponse($createdUser, 201);
        } else {
            $this->sendError('Failed to create user', 500);
        }
    }
    
    /**
     * Update a user
     */
    public function update($id) {
        $this->authenticate();
        
        // Users can update their own profile, admins can update any
        if ($this->user['id'] != $id && !in_array('admin', json_decode($this->user['abilities'] ?? '[]', true))) {
            $this->requirePermission('users:update');
        }
        
        // Check if user exists
        $existingUser = $this->db->query("SELECT id FROM users WHERE id = ?", [$id])->fetch();
        if (!$existingUser) {
            $this->sendError('User not found', 404);
        }
        
        $updateData = [];
        $params = [];
        
        // Fields that can be updated
        $updatableFields = ['name', 'phone', 'role', 'status'];
        
        foreach ($updatableFields as $field) {
            if (isset($this->params[$field])) {
                $updateData[] = "{$field} = ?";
                $params[] = $this->sanitizeInput($this->params[$field]);
            }
        }
        
        // Handle password update separately
        if (isset($this->params['password']) && !empty($this->params['password'])) {
            if (strlen($this->params['password']) < 6) {
                $this->sendError('Password must be at least 6 characters long', 400);
            }
            $updateData[] = "password = ?";
            $params[] = password_hash($this->params['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updateData)) {
            $this->sendError('No valid fields to update', 400);
        }
        
        $updateData[] = "updated_at = ?";
        $params[] = date('Y-m-d H:i:s');
        $params[] = $id;
        
        $sql = "UPDATE users SET " . implode(', ', $updateData) . " WHERE id = ?";
        
        $result = $this->db->query($sql, $params);
        
        if ($result) {
            // Get the updated user
            $updatedUser = $this->db->query(
                "SELECT id, name, email, phone, role, status, created_at, updated_at FROM users WHERE id = ?", 
                [$id]
            )->fetch();
            
            $this->sendResponse($updatedUser);
        } else {
            $this->sendError('Failed to update user', 500);
        }
    }
    
    /**
     * Delete a user
     */
    public function destroy($id) {
        $this->authenticate();
        $this->requirePermission('users:delete');
        
        // Check if user exists
        $existingUser = $this->db->query("SELECT id FROM users WHERE id = ?", [$id])->fetch();
        if (!$existingUser) {
            $this->sendError('User not found', 404);
        }
        
        // Prevent self-deletion
        if ($this->user['id'] == $id) {
            $this->sendError('Cannot delete your own account', 400);
        }
        
        $sql = "DELETE FROM users WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        
        if ($result) {
            $this->sendResponse(['message' => 'User deleted successfully']);
        } else {
            $this->sendError('Failed to delete user', 500);
        }
    }
    
    /**
     * User login
     */
    public function login() {
        $this->validateRequired($this->params, ['email', 'password']);
        
        $email = $this->sanitizeInput($this->params['email']);
        $password = $this->params['password'];
        
        // Get user by email
        $sql = "SELECT id, name, email, password, role, status FROM users WHERE email = ?";
        $user = $this->db->query($sql, [$email])->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            $this->sendError('Invalid credentials', 401);
        }
        
        if ($user['status'] !== 'active') {
            $this->sendError('Account is not active', 403);
        }
        
        // Generate API token
        $token = $this->generateApiToken($user['id']);
        
        // Remove password from response
        unset($user['password']);
        
        $this->sendResponse([
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful'
        ]);
    }
    
    /**
     * User registration
     */
    public function register() {
        $this->validateRequired($this->params, ['name', 'email', 'password']);
        
        // Check if email already exists
        $existingUser = $this->db->query("SELECT id FROM users WHERE email = ?", [$this->params['email']])->fetch();
        if ($existingUser) {
            $this->sendError('Email already exists', 400);
        }
        
        // Validate email format
        if (!filter_var($this->params['email'], FILTER_VALIDATE_EMAIL)) {
            $this->sendError('Invalid email format', 400);
        }
        
        // Validate password strength
        if (strlen($this->params['password']) < 6) {
            $this->sendError('Password must be at least 6 characters long', 400);
        }
        
        $userData = [
            'name' => $this->sanitizeInput($this->params['name']),
            'email' => $this->sanitizeInput($this->params['email']),
            'phone' => $this->sanitizeInput($this->params['phone'] ?? ''),
            'password' => password_hash($this->params['password'], PASSWORD_DEFAULT),
            'role' => 'customer',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $sql = "INSERT INTO users (name, email, phone, password, role, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $result = $this->db->query($sql, array_values($userData));
        
        if ($result) {
            $userId = $this->db->lastInsertId();
            
            // Generate API token
            $token = $this->generateApiToken($userId);
            
            // Get the created user (without password)
            $createdUser = $this->db->query(
                "SELECT id, name, email, phone, role, status, created_at, updated_at FROM users WHERE id = ?", 
                [$userId]
            )->fetch();
            
            $this->sendResponse([
                'user' => $createdUser,
                'token' => $token,
                'message' => 'Registration successful'
            ], 201);
        } else {
            $this->sendError('Failed to create user', 500);
        }
    }
    
    /**
     * User logout
     */
    public function logout() {
        $this->authenticate();
        
        $token = $this->headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $token, $matches)) {
            $tokenValue = $matches[1];
            
            // Revoke the token
            $sql = "DELETE FROM api_tokens WHERE token = ?";
            $this->db->query($sql, [$tokenValue]);
        }
        
        $this->sendResponse(['message' => 'Logged out successfully']);
    }
    
    /**
     * Generate API token for user
     */
    private function generateApiToken($userId) {
        $token = bin2hex(random_bytes(32));
        $name = 'API Token - ' . date('Y-m-d H:i:s');
        $abilities = json_encode(['*']); // Full access for now
        
        $sql = "INSERT INTO api_tokens (user_id, token, name, abilities, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";
        
        $this->db->query($sql, [$userId, $token, $name, $abilities]);
        
        return $token;
    }
}
?>

