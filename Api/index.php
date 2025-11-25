<?php
/**
 * NutriNexas API Router
 * Main entry point for all API requests
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kathmandu');

// Load required files
require_once '../App/Core/Database.php';
require_once '../App/Config/config.php';
require_once '../App/Controllers/ApiController.php';
require_once '../App/Controllers/UsersApiController.php';
require_once '../App/Controllers/ProductsApiController.php';
require_once '../App/Controllers/OrdersApiController.php';
require_once '../App/Controllers/CheckoutApiController.php';
require_once '../App/Controllers/ApiKeyController.php';

// Handle API routing
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Remove 'api' from path if present
if ($pathParts[0] === 'api') {
    array_shift($pathParts);
}

// Reconstruct endpoint for controllers
$endpoint = implode('/', $pathParts);

// Route to appropriate controller based on first path segment
if (empty($pathParts)) {
    // API home/status
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'NutriNexas API v1.0',
        'endpoints' => [
            'users' => [
                'GET /api/users' => 'Get all users',
                'GET /api/users/{id}' => 'Get specific user',
                'POST /api/users' => 'Create new user',
                'POST /api/users/login' => 'User login',
                'POST /api/users/register' => 'User registration',
                'POST /api/users/logout' => 'User logout',
                'PUT /api/users/{id}' => 'Update user',
                'DELETE /api/users/{id}' => 'Delete user'
            ],
            'products' => [
                'GET /api/products' => 'Get all products',
                'GET /api/products/{id}' => 'Get specific product',
                'POST /api/products' => 'Create new product',
                'PUT /api/products/{id}' => 'Update product',
                'DELETE /api/products/{id}' => 'Delete product'
            ],
            'orders' => [
                'GET /api/orders' => 'Get all orders',
                'GET /api/orders/{id}' => 'Get specific order',
                'POST /api/orders' => 'Create new order',
                'PUT /api/orders/{id}' => 'Update order',
                'DELETE /api/orders/{id}' => 'Delete order'
            ],
            'checkout' => [
                'GET /api/checkout' => 'Get checkout summary',
                'GET /api/checkout/cart' => 'Get shopping cart',
                'GET /api/checkout/payment-methods' => 'Get payment methods',
                'GET /api/checkout/shipping-options' => 'Get shipping options',
                'POST /api/checkout/add-to-cart' => 'Add item to cart',
                'POST /api/checkout/update-cart' => 'Update cart item',
                'POST /api/checkout/remove-from-cart' => 'Remove item from cart',
                'POST /api/checkout/apply-coupon' => 'Apply coupon code',
                'POST /api/checkout/place-order' => 'Place order',
                'DELETE /api/checkout/clear-cart' => 'Clear entire cart'
            ],
            'api_keys' => [
                'POST /api/keys/generate' => 'Generate new API key',
                'GET /api/keys/list' => 'List user API keys',
                'PUT /api/keys/update' => 'Update API key',
                'DELETE /api/keys/revoke' => 'Revoke API key',
                'GET /api/keys/stats' => 'Get API usage statistics'
            ]
        ],
        'documentation' => 'API documentation available at /api/docs',
        'timestamp' => date('c')
    ]);
    exit;
}

// Route to specific controllers
switch ($pathParts[0]) {
    case 'users':
        new \App\Controllers\UsersApiController();
        break;
        
    case 'products':
        new \App\Controllers\ProductsApiController();
        break;
        
    case 'orders':
        new \App\Controllers\OrdersApiController();
        break;
        
    case 'checkout':
        new \App\Controllers\CheckoutApiController();
        break;
        
    case 'keys':
        new \App\Controllers\ApiKeyController();
        break;
        
    case 'docs':
    case 'documentation':
        // API Documentation
        header('Content-Type: text/html');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>NutriNexas API Documentation</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #333; text-align: center; margin-bottom: 30px; }
                .endpoint-group { margin-bottom: 40px; }
                .endpoint-group h2 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
                .endpoint { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; margin-bottom: 15px; }
                .method { display: inline-block; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; margin-right: 10px; }
                .get { background: #28a745; color: white; }
                .post { background: #007bff; color: white; }
                .put { background: #ffc107; color: black; }
                .delete { background: #dc3545; color: white; }
                .url { font-family: monospace; font-size: 14px; color: #495057; }
                .description { margin-top: 10px; color: #6c757d; }
                .params { margin-top: 10px; }
                .params h4 { color: #495057; margin-bottom: 10px; }
                .param { background: #e9ecef; padding: 8px; border-radius: 4px; margin-bottom: 5px; font-family: monospace; }
                .response { margin-top: 10px; }
                .response h4 { color: #495057; margin-bottom: 10px; }
                .code-block { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 15px; font-family: monospace; font-size: 12px; overflow-x: auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>NutriNexas API Documentation</h1>
                
                <div class="endpoint-group">
                    <h2>Authentication</h2>
                    <p>Most endpoints require authentication using Bearer tokens. Include the token in the Authorization header:</p>
                    <div class="code-block">Authorization: Bearer YOUR_TOKEN_HERE</div>
                    
                    <h3>Getting a Token</h3>
                    <p>Use the login or register endpoint to get an API token.</p>
                </div>
                
                <div class="endpoint-group">
                    <h2>Users Endpoints</h2>
                    
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="url">/api/users/register</span>
                        <div class="description">Register a new user account</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">name: string - User's full name</div>
                            <div class="param">email: string - User's email address</div>
                            <div class="param">password: string - User's password (min 6 characters)</div>
                            <div class="param">phone: string (optional) - User's phone number</div>
                        </div>
                        <div class="response">
                            <h4>Response:</h4>
                            <div class="code-block">{
  "success": true,
  "data": {
    "user": { ... },
    "token": "api_token_here",
    "message": "Registration successful"
  }
}</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="url">/api/users/login</span>
                        <div class="description">Authenticate user and get API token</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">email: string - User's email address</div>
                            <div class="param">password: string - User's password</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/users</span>
                        <div class="description">Get all users (requires admin permission)</div>
                        <div class="params">
                            <h4>Query Parameters:</h4>
                            <div class="param">page: int - Page number (default: 1)</div>
                            <div class="param">per_page: int - Items per page (default: 20, max: 100)</div>
                            <div class="param">search: string - Search in name, email, phone</div>
                            <div class="param">role: string - Filter by user role</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/users/{id}</span>
                        <div class="description">Get specific user (own profile or admin permission)</div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="url">/api/users/{id}</span>
                        <div class="description">Update user (own profile or admin permission)</div>
                        <div class="params">
                            <h4>Updatable Fields:</h4>
                            <div class="param">name: string - User's full name</div>
                            <div class="param">phone: string - User's phone number</div>
                            <div class="param">password: string - New password (min 6 characters)</div>
                            <div class="param">role: string - User role (admin only)</div>
                            <div class="param">status: string - User status (admin only)</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="url">/api/users/{id}</span>
                        <div class="description">Delete user (requires admin permission)</div>
                    </div>
                </div>
                
                <div class="endpoint-group">
                    <h2>Products Endpoints</h2>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/products</span>
                        <div class="description">Get all products with filtering and pagination</div>
                        <div class="params">
                            <h4>Query Parameters:</h4>
                            <div class="param">page: int - Page number (default: 1)</div>
                            <div class="param">per_page: int - Items per page (default: 20, max: 100)</div>
                            <div class="param">search: string - Search in product name, description</div>
                            <div class="param">category: string - Filter by category</div>
                            <div class="param">min_price: float - Minimum price filter</div>
                            <div class="param">max_price: float - Maximum price filter</div>
                            <div class="param">in_stock: boolean - Filter by stock availability</div>
                            <div class="param">featured: boolean - Filter featured products</div>
                            <div class="param">sort_by: string - Sort field (product_name, price, created_at, sales_count, stock_quantity)</div>
                            <div class="param">sort_order: string - Sort direction (ASC, DESC)</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/products/{id}</span>
                        <div class="description">Get specific product with details and related products</div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="url">/api/products</span>
                        <div class="description">Create new product (requires admin permission)</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">product_name: string - Product name</div>
                            <div class="param">price: float - Product price</div>
                            <div class="param">category: string - Product category</div>
                            <div class="param">description: string - Product description</div>
                            <div class="param">stock_quantity: int - Available stock</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="url">/api/products/{id}</span>
                        <div class="description">Update product (requires admin permission)</div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="url">/api/products/{id}</span>
                        <div class="description">Delete product (requires admin permission)</div>
                    </div>
                </div>
                
                <div class="endpoint-group">
                    <h2>Orders Endpoints</h2>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/orders</span>
                        <div class="description">Get all orders (requires admin permission)</div>
                        <div class="params">
                            <h4>Query Parameters:</h4>
                            <div class="param">page: int - Page number (default: 1)</div>
                            <div class="param">per_page: int - Items per page (default: 20, max: 100)</div>
                            <div class="param">status: string - Filter by order status</div>
                            <div class="param">customer_id: int - Filter by customer</div>
                            <div class="param">start_date: string - Filter by start date (YYYY-MM-DD)</div>
                            <div class="param">end_date: string - Filter by end date (YYYY-MM-DD)</div>
                            <div class="param">min_amount: float - Minimum order amount</div>
                            <div class="param">max_amount: float - Maximum order amount</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/orders/{id}</span>
                        <div class="description">Get specific order (own order or admin permission)</div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="url">/api/orders</span>
                        <div class="description">Create new order (authenticated users)</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">items: array - Array of order items</div>
                            <div class="param">address_id: int - Shipping address ID</div>
                            <div class="param">payment_method: string - Payment method (default: cash_on_delivery)</div>
                            <div class="param">notes: string - Order notes</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="url">/api/orders/{id}</span>
                        <div class="description">Update order status (requires admin permission)</div>
                        <div class="params">
                            <h4>Updatable Fields:</h4>
                            <div class="param">status: string - Order status (pending, confirmed, processing, shipped, delivered, cancelled)</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="url">/api/orders/{id}</span>
                        <div class="description">Delete order (requires admin permission, only pending orders)</div>
                    </div>
                </div>
                
                <div class="endpoint-group">
                    <h2>API Key Management</h2>
                    
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="url">/api/keys/generate</span>
                        <div class="description">Generate a new API key for the authenticated user</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">name: string - API key name/description</div>
                            <h4>Optional Parameters:</h4>
                            <div class="param">abilities: array - Array of permissions (read, write, delete, admin)</div>
                            <div class="param">expires_at: string - Expiration date (ISO 8601 format)</div>
                        </div>
                        <div class="response">
                            <h4>Response:</h4>
                            <div class="code-block">{
  "success": true,
  "data": {
    "message": "API key generated successfully",
    "api_key": "generated_token_here",
    "name": "My App API Key",
    "abilities": ["read", "write"],
    "expires_at": "2026-01-27T00:00:00Z"
  }
}</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/keys/list</span>
                        <div class="description">List all API keys for the authenticated user</div>
                        <div class="response">
                            <h4>Response:</h4>
                            <div class="code-block">{
  "success": true,
  "data": {
    "api_keys": [
      {
        "id": 1,
        "name": "My App API Key",
        "abilities": ["read", "write"],
        "last_used_at": "2025-01-27T10:30:00Z",
        "expires_at": "2026-01-27T00:00:00Z",
        "created_at": "2025-01-27T09:00:00Z",
        "token_preview": "a1b2c3d4..."
      }
    ],
    "total": 1
  }
}</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="url">/api/keys/update</span>
                        <div class="description">Update API key abilities, name, or expiration</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">key_id: int - API key ID to update</div>
                            <h4>Optional Parameters:</h4>
                            <div class="param">abilities: array - New permissions array</div>
                            <div class="param">name: string - New API key name</div>
                            <div class="param">expires_at: string - New expiration date</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="url">/api/keys/revoke</span>
                        <div class="description">Revoke/delete an API key</div>
                        <div class="params">
                            <h4>Required Parameters:</h4>
                            <div class="param">key_id: int - API key ID to revoke</div>
                        </div>
                    </div>
                    
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="url">/api/keys/stats</span>
                        <div class="description">Get API usage statistics for the authenticated user</div>
                        <div class="response">
                            <h4>Response:</h4>
                            <div class="code-block">{
  "success": true,
  "data": {
    "total_api_calls": 150,
    "endpoint_stats": [
      {
        "endpoint": "/api/products",
        "count": 75
      }
    ],
    "recent_activity": [
      {
        "endpoint": "/api/products",
        "method": "GET",
        "status_code": 200,
        "created_at": "2025-01-27T10:30:00Z"
      }
    ]
  }
}</div>
                        </div>
                    </div>
                </div>
                
                <div class="endpoint-group">
                    <h2>Response Format</h2>
                    <p>All API responses follow this format:</p>
                    <div class="code-block">{
  "success": true|false,
  "data": { ... } | null,
  "error": "Error message" | null,
  "timestamp": "ISO 8601 timestamp",
  "endpoint": "Requested endpoint"
}</div>
                    
                    <h3>Error Codes</h3>
                    <ul>
                        <li><strong>400</strong> - Bad Request (validation errors)</li>
                        <li><strong>401</strong> - Unauthorized (missing or invalid token)</li>
                        <li><strong>403</strong> - Forbidden (insufficient permissions)</li>
                        <li><strong>404</strong> - Not Found (resource doesn't exist)</li>
                        <li><strong>405</strong> - Method Not Allowed (invalid HTTP method)</li>
                        <li><strong>500</strong> - Internal Server Error</li>
                    </ul>
                </div>
                
                <div class="endpoint-group">
                    <h2>Rate Limiting</h2>
                    <p>API requests are limited to 100 requests per minute per user. Exceeding this limit will result in a 429 status code.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        break;
        
    default:
        // 404 Not Found
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found',
            'timestamp' => date('c'),
            'endpoint' => $endpoint
        ]);
        break;
}
?>
