<?php
namespace App\Models;

use App\Core\Model;
use Exception;

class SMSTemplate extends Model
{
    protected $table = 'sms_templates';
    
    const CATEGORIES = [
        'promotional' => 'Promotional',
        'transactional' => 'Transactional',
        'notification' => 'Notification',
        'reminder' => 'Reminder',
        'welcome' => 'Welcome',
        'order_update' => 'Order Update'
    ];
    
    const STATUSES = [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'draft' => 'Draft'
    ];

    /**
     * Get all templates with pagination and filtering
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $category
     * @param bool|null $isActive
     * @return array
     */
    public function getAllTemplates($limit = 20, $offset = 0, $category = null, $isActive = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $conditions = [];
        
        if ($category !== null) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        
        if ($isActive !== null) {
            $conditions[] = "is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $result = $this->db->query($sql)->bind($params)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get users with phone numbers for SMS functionality
     *
     * @return array
     */
    public function getUsersWithPhones()
    {
        $sql = "SELECT id, first_name, last_name, email, phone, CONCAT(first_name, ' ', last_name) as full_name 
                FROM users 
                WHERE phone IS NOT NULL AND phone != '' 
                ORDER BY first_name ASC";
        
        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get phone numbers from orders for SMS functionality
     *
     * @return array
     */
    public function getOrderPhoneNumbers()
    {
        $sql = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.phone, 
                       CONCAT(u.first_name, ' ', u.last_name) as name
                FROM users u
                INNER JOIN orders o ON u.id = o.user_id
                WHERE u.phone IS NOT NULL AND u.phone != ''
                ORDER BY u.first_name ASC";
        
        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get all customers with phone numbers for SMS marketing
     *
     * @return array
     */
    public function getAllCustomersForSMS()
    {
        $sql = "SELECT id, customer_name, contact_no, email 
                FROM customers 
                WHERE contact_no IS NOT NULL AND contact_no != '' 
                ORDER BY customer_name ASC";
        
        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get customers count for SMS marketing
     *
     * @return int
     */
    public function getCustomersCountForSMS()
    {
        $sql = "SELECT COUNT(*) as count FROM customers WHERE contact_no IS NOT NULL AND contact_no != ''";
        $result = $this->db->query($sql)->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get total count of templates
     *
     * @param string|null $category
     * @param bool|null $isActive
     * @return int
     */
    public function getTotalTemplates($category = null, $isActive = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $conditions = [];
        
        if ($category !== null) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        
        if ($isActive !== null) {
            $conditions[] = "is_active = ?";
            $params[] = $isActive ? 1 : 0;
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $result = $this->db->query($sql)->bind($params)->single();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Get active templates by category
     *
     * @param string $category
     * @return array
     */
    public function getActiveTemplatesByCategory($category)
    {
        $sql = "SELECT * FROM {$this->table} WHERE category = ? AND is_active = 1 ORDER BY name ASC";
        $result = $this->db->query($sql)->bind([$category])->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get all active templates
     *
     * @return array
     */
    public function getActiveTemplates()
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY category ASC, name ASC";
        $result = $this->db->query($sql)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Process template with variables
     *
     * @param int $templateId
     * @param array $variables
     * @return string|false
     */
    public function processTemplate($templateId, $variables = [])
    {
        $template = $this->find($templateId);
        
        if (!$template) {
            return false;
        }
        
        $content = $template['content'];
        
        // Replace variables in the format {{variable_name}}
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Replace common variables if not provided
        $defaultVariables = [
            'store_name' => 'NutriNexas',
            'website' => 'nutrinexas.com',
            'support_phone' => '+977-9811388848',
            'current_date' => date('Y-m-d'),
            'current_time' => date('H:i:s')
        ];
        
        foreach ($defaultVariables as $key => $value) {
            if (!isset($variables[$key])) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
        }
        
        return $content;
    }

    /**
     * Validate template content
     *
     * @param string $content
     * @return array
     */
    public function validateTemplate($content)
    {
        $errors = [];
        $valid = true;
        
        if (empty($content)) {
            $errors[] = 'Template content cannot be empty';
            $valid = false;
        }
        
        if (strlen($content) > 1600) {
            $errors[] = 'Template content is too long (max 1600 characters)';
            $valid = false;
        }
        
        // Check for unclosed template variables
        $openBraces = substr_count($content, '{{');
        $closeBraces = substr_count($content, '}}');
        
        if ($openBraces !== $closeBraces) {
            $errors[] = 'Template has unclosed variable brackets';
            $valid = false;
        }
        
        return [
            'valid' => $valid,
            'errors' => $errors
        ];
    }

    /**
     * Create a new template
     *
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert variables array to JSON if it's an array
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }
        
        return parent::create($data);
    }

    /**
     * Update template
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert variables array to JSON if it's an array
        if (isset($data['variables']) && is_array($data['variables'])) {
            $data['variables'] = json_encode($data['variables']);
        }
        
        return parent::update($id, $data);
    }

    /**
     * Toggle template active status
     *
     * @param int $id
     * @return bool
     */
    public function toggleActive($id)
    {
        $template = $this->find($id);
        
        if (!$template) {
            return false;
        }
        
        $newStatus = $template['is_active'] ? 0 : 1;
        
        return $this->update($id, ['is_active' => $newStatus]);
    }

    /**
     * Log SMS sending
     *
     * @param array $data
     * @return int|false
     */
    public function logSMS($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Convert provider_response to JSON if it's an array
        if (isset($data['provider_response']) && is_array($data['provider_response'])) {
            $data['provider_response'] = json_encode($data['provider_response']);
        }
        
        $sql = "INSERT INTO sms_logs (user_id, phone_number, template_id, campaign_id, message, status, provider_response, cost, error_message, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['user_id'] ?? null,
            $data['phone_number'] ?? '',
            $data['template_id'] ?? null,
            $data['campaign_id'] ?? null,
            $data['message'] ?? '',
            $data['status'] ?? 'failed',
            $data['provider_response'] ?? null,
            $data['cost'] ?? 0.00,
            $data['error_message'] ?? null,
            $data['created_at']
        ];
        
        try {
            $this->db->query($sql)->bind($params)->execute();
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('SMS log error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get SMS logs with filtering
     *
     * @param int $limit
     * @param int $offset
     * @param array $filters
     * @return array
     */
    public function getSMSLogs($limit = 20, $offset = 0, $filters = [])
    {
        $sql = "SELECT sl.*, u.full_name as user_name, st.name as template_name 
                FROM sms_logs sl 
                LEFT JOIN users u ON sl.user_id = u.id 
                LEFT JOIN {$this->table} st ON sl.template_id = st.id";
        
        $params = [];
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "sl.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['user_id'])) {
            $conditions[] = "sl.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['phone_number'])) {
            $conditions[] = "sl.phone_number LIKE ?";
            $params[] = '%' . $filters['phone_number'] . '%';
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(sl.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(sl.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY sl.created_at DESC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $result = $this->db->query($sql)->bind($params)->all();
        return is_array($result) ? $result : [];
    }

    /**
     * Get SMS statistics
     *
     * @param array $filters
     * @return array
     */
    public function getSMSStats($filters = [])
    {
        $sql = "SELECT 
                    COUNT(*) as total_sent,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                    SUM(cost) as total_cost
                FROM sms_logs";
        
        $params = [];
        $conditions = [];
        
        if (!empty($filters['status'])) {
            $conditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $result = $this->db->query($sql)->bind($params)->single();
        
        if (!$result) {
            return [
                'total_sent' => 0,
                'delivered' => 0,
                'failed' => 0,
                'total_cost' => 0.00,
                'delivery_rate' => 0.0
            ];
        }
        
        $totalSent = (int)$result['total_sent'];
        $delivered = (int)$result['delivered'];
        $deliveryRate = $totalSent > 0 ? ($delivered / $totalSent) * 100 : 0;
        
        return [
            'total_sent' => $totalSent,
            'delivered' => $delivered,
            'failed' => (int)$result['failed'],
            'total_cost' => (float)$result['total_cost'],
            'delivery_rate' => round($deliveryRate, 2)
        ];
    }

    /**
     * Create default templates if none exist
     *
     * @return bool
     */
    public function createDefaultTemplates()
    {
        // Check if templates already exist
        $existingCount = $this->getTotalTemplates();
        
        if ($existingCount > 0) {
            return true; // Templates already exist
        }
        
        $defaultTemplates = [
            [
                'name' => 'Welcome Message',
                'category' => 'welcome',
                'content' => 'Welcome to {{store_name}}! Thank you for joining us. Your account has been created successfully. Start shopping now: {{website}}',
                'variables' => json_encode(['store_name', 'website']),
                'is_active' => 1,
                'priority' => 1
            ],
            [
                'name' => 'Order Confirmation',
                'category' => 'order_update',
                'content' => 'Hi {{first_name}}, your order #{{order_id}} has been confirmed! Total: Rs.{{total_amount}}. Track your order: {{tracking_url}}',
                'variables' => json_encode(['first_name', 'order_id', 'total_amount', 'tracking_url']),
                'is_active' => 1,
                'priority' => 1
            ],
            [
                'name' => 'Flash Sale Alert',
                'category' => 'promotional',
                'content' => '🔥 FLASH SALE! Get up to {{discount}}% OFF on all supplements! Limited time offer. Shop now: {{shop_url}} Use code: {{promo_code}}',
                'variables' => json_encode(['discount', 'shop_url', 'promo_code']),
                'is_active' => 1,
                'priority' => 1
            ],
            [
                'name' => 'Order Shipped',
                'category' => 'notification',
                'content' => 'Great news {{first_name}}! Your order #{{order_id}} has been shipped. Track: {{tracking_url}} Expected delivery: {{delivery_date}}',
                'variables' => json_encode(['first_name', 'order_id', 'tracking_url', 'delivery_date']),
                'is_active' => 1,
                'priority' => 1
            ],
            [
                'name' => 'Cart Reminder',
                'category' => 'reminder',
                'content' => 'Hi {{first_name}}, you left {{item_count}} items in your cart! Complete your purchase now and get FREE shipping: {{cart_url}}',
                'variables' => json_encode(['first_name', 'item_count', 'cart_url']),
                'is_active' => 1,
                'priority' => 1
            ]
        ];
        
        $created = 0;
        foreach ($defaultTemplates as $template) {
            if ($this->create($template)) {
                $created++;
            }
        }
        
        return $created > 0;
    }
}
