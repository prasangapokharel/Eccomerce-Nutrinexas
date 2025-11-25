<?php
namespace App\Controllers\Api;

use App\Models\User;
use App\Models\ApiKey;
use App\Core\Session;

class AuthApiController extends BaseApiController
{
    private $userModel;
    private $apiKeyModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->apiKeyModel = new ApiKey();
    }
    
    /**
     * Register new user
     * POST /api/auth/register
     */
    public function register()
    {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['first_name', 'last_name', 'email', 'password']);
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Invalid email format'], 400);
        }
        
        // Check if email already exists
        if ($this->userModel->findByEmail($data['email'])) {
            $this->jsonResponse(['error' => 'Email already registered'], 400);
        }
        
        // Validate password strength
        if (strlen($data['password']) < 6) {
            $this->jsonResponse(['error' => 'Password must be at least 6 characters'], 400);
        }
        
        // Create user
        $userData = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => trim($data['email']),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => $data['phone'] ?? null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $this->userModel->create($userData);
        
        if (!$userId) {
            $this->jsonResponse(['error' => 'Failed to create user'], 500);
        }
        
        // Generate API key for new user
        $apiKey = $this->apiKeyModel->generateKey($userId, 'Default API Key', ['read', 'write']);
        
        $this->jsonResponse([
            'message' => 'User registered successfully',
            'data' => [
                'user_id' => $userId,
                'email' => $userData['email'],
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'api_key' => $apiKey['key']
            ]
        ], 201);
    }
    
    /**
     * Login user
     * POST /api/auth/login
     */
    public function login()
    {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['email', 'password']);
        
        $user = $this->userModel->findByEmail($data['email']);
        
        if (!$user || !password_verify($data['password'], $user['password'])) {
            $this->jsonResponse(['error' => 'Invalid credentials'], 401);
        }
        
        if (!$user['is_active']) {
            $this->jsonResponse(['error' => 'Account is deactivated'], 401);
        }
        
        // Generate new API key
        $apiKey = $this->apiKeyModel->generateKey($user['id'], 'Login API Key', ['read', 'write']);
        
        $this->jsonResponse([
            'message' => 'Login successful',
            'data' => [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'api_key' => $apiKey['key']
            ]
        ]);
    }
    
    /**
     * Logout user (deactivate current API key)
     * POST /api/auth/logout
     */
    public function logout()
    {
        $this->requirePermission('write');
        
        $userId = $this->currentUser['user_id'];
        $keyId = $this->currentUser['id'];
        
        $result = $this->apiKeyModel->deactivateKey($keyId, $userId);
        
        if ($result) {
            $this->jsonResponse(['message' => 'Logged out successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to logout'], 500);
        }
    }
    
    /**
     * Get current user profile
     * GET /api/auth/profile
     */
    public function profile()
    {
        $this->requirePermission('read');
        
        $userId = $this->currentUser['user_id'];
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            $this->jsonResponse(['error' => 'User not found'], 404);
        }
        
        $this->jsonResponse([
            'data' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone'],
                'is_active' => (bool)$user['is_active'],
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at']
            ]
        ]);
    }
    
    /**
     * Update user profile
     * PUT /api/auth/profile
     */
    public function updateProfile()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $userId = $this->currentUser['user_id'];
        
        $updateData = [];
        
        if (isset($data['first_name'])) {
            $updateData['first_name'] = trim($data['first_name']);
        }
        
        if (isset($data['last_name'])) {
            $updateData['last_name'] = trim($data['last_name']);
        }
        
        if (isset($data['phone'])) {
            $updateData['phone'] = trim($data['phone']);
        }
        
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(['error' => 'Invalid email format'], 400);
            }
            
            // Check if email is already taken by another user
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $userId) {
                $this->jsonResponse(['error' => 'Email already taken'], 400);
            }
            
            $updateData['email'] = trim($data['email']);
        }
        
        if (empty($updateData)) {
            $this->jsonResponse(['error' => 'No valid fields to update'], 400);
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        $result = $this->userModel->update($userId, $updateData);
        
        if ($result) {
            $updatedUser = $this->userModel->find($userId);
            $this->jsonResponse([
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $updatedUser['id'],
                    'email' => $updatedUser['email'],
                    'first_name' => $updatedUser['first_name'],
                    'last_name' => $updatedUser['last_name'],
                    'phone' => $updatedUser['phone'],
                    'updated_at' => $updatedUser['updated_at']
                ]
            ]);
        } else {
            $this->jsonResponse(['error' => 'Failed to update profile'], 500);
        }
    }
    
    /**
     * Change password
     * PUT /api/auth/change-password
     */
    public function changePassword()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $this->validateRequired($data, ['current_password', 'new_password']);
        
        $userId = $this->currentUser['user_id'];
        $user = $this->userModel->find($userId);
        
        // Verify current password
        if (!password_verify($data['current_password'], $user['password'])) {
            $this->jsonResponse(['error' => 'Current password is incorrect'], 400);
        }
        
        // Validate new password
        if (strlen($data['new_password']) < 6) {
            $this->jsonResponse(['error' => 'New password must be at least 6 characters'], 400);
        }
        
        $result = $this->userModel->update($userId, [
            'password' => password_hash($data['new_password'], PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            $this->jsonResponse(['message' => 'Password changed successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to change password'], 500);
        }
    }
    
    /**
     * Get user's API keys
     * GET /api/auth/api-keys
     */
    public function getApiKeys()
    {
        $this->requirePermission('read');
        
        $userId = $this->currentUser['user_id'];
        $apiKeys = $this->apiKeyModel->getUserKeys($userId);
        
        $this->jsonResponse(['data' => $apiKeys]);
    }
    
    /**
     * Generate new API key
     * POST /api/auth/api-keys
     */
    public function generateApiKey()
    {
        $this->requirePermission('write');
        
        $data = $this->getRequestData();
        $name = $data['name'] ?? 'API Key ' . date('Y-m-d H:i:s');
        $permissions = $data['permissions'] ?? ['read', 'write'];
        
        $userId = $this->currentUser['user_id'];
        $apiKey = $this->apiKeyModel->generateKey($userId, $name, $permissions);
        
        if ($apiKey) {
            $this->jsonResponse([
                'message' => 'API key generated successfully',
                'data' => $apiKey
            ], 201);
        } else {
            $this->jsonResponse(['error' => 'Failed to generate API key'], 500);
        }
    }
}
























