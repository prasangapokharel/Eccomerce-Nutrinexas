<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected $table = 'customers';
    
    /**
     * Get all customers with optional filtering
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllCustomers($filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        $conditions = [];
        
        if (!empty($filters['search'])) {
            $conditions[] = "(customer_name LIKE ? OR contact_no LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['contact_no'])) {
            $conditions[] = "contact_no LIKE ?";
            $params[] = '%' . $filters['contact_no'] . '%';
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
     * Get customer by contact number
     *
     * @param string $contactNo
     * @return array|false
     */
    public function getByContactNo($contactNo)
    {
        $sql = "SELECT * FROM {$this->table} WHERE contact_no = ? LIMIT 1";
        $result = $this->db->query($sql)->bind([$contactNo])->single();
        return $result;
    }
    
    /**
     * Get customer by email
     *
     * @param string $email
     * @return array|false
     */
    public function getByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $result = $this->db->query($sql)->bind([$email])->single();
        return $result;
    }
    
    /**
     * Search customers by name or contact
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function searchCustomers($query, $limit = 10)
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE customer_name LIKE ? OR contact_no LIKE ? 
                ORDER BY customer_name ASC 
                LIMIT ?";
        
        $searchTerm = '%' . $query . '%';
        $result = $this->db->query($sql)->bind([$searchTerm, $searchTerm, $limit])->all();
        return is_array($result) ? $result : [];
    }
    
    /**
     * Get total customer count
     *
     * @param array $filters
     * @return int
     */
    public function getTotalCustomers($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        $conditions = [];
        
        if (!empty($filters['search'])) {
            $conditions[] = "(customer_name LIKE ? OR contact_no LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if (!empty($filters['contact_no'])) {
            $conditions[] = "contact_no LIKE ?";
            $params[] = '%' . $filters['contact_no'] . '%';
        }
        
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $result = $this->db->query($sql)->bind($params)->single();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Create a new customer
     *
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::create($data);
    }
    
    /**
     * Update customer
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::update($id, $data);
    }
    
    /**
     * Validate customer data
     *
     * @param array $data
     * @return array
     */
    public function validate($data)
    {
        $errors = [];
        
        if (empty($data['customer_name'])) {
            $errors[] = 'Customer name is required';
        }
        
        if (empty($data['contact_no'])) {
            $errors[] = 'Contact number is required';
        } elseif (!$this->isValidPhoneNumber($data['contact_no'])) {
            $errors[] = 'Invalid contact number format';
        }
        
        if (empty($data['address'])) {
            $errors[] = 'Address is required';
        }
        
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate phone number format for Nepal
     *
     * @param string $phone
     * @return bool
     */
    private function isValidPhoneNumber($phone)
    {
        // Check for valid Nepal phone number format
        return preg_match('/^\+977[78]\d{8}$/', $phone) || 
               preg_match('/^9[78]\d{8}$/', $phone) ||
               preg_match('/^0?9[78]\d{8}$/', $phone);
    }
}
