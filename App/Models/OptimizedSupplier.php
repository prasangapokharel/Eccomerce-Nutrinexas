<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use InvalidArgumentException;
use PDO;
use PDOException;

/**
 * Optimized Supplier Model
 * 
 * Handles supplier data operations with proper type safety, error handling,
 * and clean code principles following PSR-12 standards.
 * 
 * @package App\Models
 * @author Nutrinexus Team
 * @version 1.0.0
 */
class OptimizedSupplier
{
    private Database $database;
    private const TABLE_NAME = 'suppliers';
    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 100;

    /**
     * Constructor with dependency injection
     * 
     * @param Database $database Database connection instance
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Create a new supplier
     * 
     * @param array $supplierData Supplier data array
     * @return int The ID of the created supplier
     * @throws InvalidArgumentException When required fields are missing
     * @throws PDOException When database operation fails
     */
    public function createSupplier(array $supplierData): int
    {
        $this->validateSupplierData($supplierData);
        
        $sanitizedData = $this->sanitizeSupplierData($supplierData);
        
        $sql = "INSERT INTO " . self::TABLE_NAME . " 
                (supplier_name, phone, email, address, contact_person, status) 
                VALUES (:supplier_name, :phone, :email, :address, :contact_person, :status)";
        
        $params = [
            'supplier_name' => $sanitizedData['supplier_name'],
            'phone' => $sanitizedData['phone'],
            'email' => $sanitizedData['email'],
            'address' => $sanitizedData['address'],
            'contact_person' => $sanitizedData['contact_person'],
            'status' => $sanitizedData['status'] ?? 'active'
        ];

        $this->database->query($sql, $params);
        return (int) $this->database->lastInsertId();
    }

    /**
     * Get supplier by ID
     * 
     * @param int $supplierId Supplier ID
     * @return array|null Supplier data or null if not found
     * @throws InvalidArgumentException When supplier ID is invalid
     */
    public function getSupplierById(int $supplierId): ?array
    {
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('Supplier ID must be a positive integer');
        }

        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE supplier_id = :supplier_id";
        $result = $this->database->query($sql, ['supplier_id' => $supplierId]);
        
        return $result->single() ?: null;
    }

    /**
     * Get all suppliers with pagination and filtering
     * 
     * @param array $filters Optional filters (status, search)
     * @param int $page Page number (1-based)
     * @param int $pageSize Number of items per page
     * @return array Paginated suppliers data
     * @throws InvalidArgumentException When pagination parameters are invalid
     */
    public function getAllSuppliers(array $filters = [], int $page = 1, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        $this->validatePaginationParams($page, $pageSize);
        
        $whereClause = $this->buildWhereClause($filters);
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM " . self::TABLE_NAME . " 
                {$whereClause['clause']} 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $params = array_merge($whereClause['params'], [
            'limit' => $pageSize,
            'offset' => $offset
        ]);

        $result = $this->database->query($sql, $params);
        $suppliers = $result->fetchAll();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM " . self::TABLE_NAME . " {$whereClause['clause']}";
        $countResult = $this->database->query($countSql, $whereClause['params']);
        $totalCount = $countResult->single()['total'];

        return [
            'data' => $suppliers,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $pageSize,
                'total' => (int) $totalCount,
                'total_pages' => (int) ceil($totalCount / $pageSize)
            ]
        ];
    }

    /**
     * Get active suppliers only
     * 
     * @return array List of active suppliers
     */
    public function getActiveSuppliers(): array
    {
        $sql = "SELECT supplier_id, supplier_name, phone, email 
                FROM " . self::TABLE_NAME . " 
                WHERE status = 'active' 
                ORDER BY supplier_name ASC";
        
        $result = $this->database->query($sql);
        return $result->fetchAll();
    }

    /**
     * Update supplier data
     * 
     * @param int $supplierId Supplier ID
     * @param array $updateData Data to update
     * @return bool True if update successful
     * @throws InvalidArgumentException When data is invalid
     * @throws PDOException When database operation fails
     */
    public function updateSupplier(int $supplierId, array $updateData): bool
    {
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('Supplier ID must be a positive integer');
        }

        if (empty($updateData)) {
            throw new InvalidArgumentException('Update data cannot be empty');
        }

        $sanitizedData = $this->sanitizeSupplierData($updateData, false);
        $setClause = $this->buildSetClause($sanitizedData);
        
        $sql = "UPDATE " . self::TABLE_NAME . " 
                SET {$setClause['clause']}, updated_at = NOW() 
                WHERE supplier_id = :supplier_id";
        
        $params = array_merge($setClause['params'], ['supplier_id' => $supplierId]);

        $result = $this->database->query($sql, $params);
        return $result->rowCount() > 0;
    }

    /**
     * Delete supplier (soft delete by setting status to inactive)
     * 
     * @param int $supplierId Supplier ID
     * @return bool True if deletion successful
     * @throws InvalidArgumentException When supplier ID is invalid
     */
    public function deleteSupplier(int $supplierId): bool
    {
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('Supplier ID must be a positive integer');
        }

        $sql = "UPDATE " . self::TABLE_NAME . " 
                SET status = 'inactive', updated_at = NOW() 
                WHERE supplier_id = :supplier_id";
        
        $result = $this->database->query($sql, ['supplier_id' => $supplierId]);
        return $result->rowCount() > 0;
    }

    /**
     * Toggle supplier status
     * 
     * @param int $supplierId Supplier ID
     * @return bool True if toggle successful
     * @throws InvalidArgumentException When supplier ID is invalid
     */
    public function toggleSupplierStatus(int $supplierId): bool
    {
        if ($supplierId <= 0) {
            throw new InvalidArgumentException('Supplier ID must be a positive integer');
        }

        $sql = "UPDATE " . self::TABLE_NAME . " 
                SET status = CASE 
                    WHEN status = 'active' THEN 'inactive' 
                    ELSE 'active' 
                END, 
                updated_at = NOW() 
                WHERE supplier_id = :supplier_id";
        
        $result = $this->database->query($sql, ['supplier_id' => $supplierId]);
        return $result->rowCount() > 0;
    }

    /**
     * Get supplier statistics
     * 
     * @return array Supplier statistics
     */
    public function getSupplierStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_suppliers,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_suppliers,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_suppliers
                FROM " . self::TABLE_NAME;
        
        $result = $this->database->query($sql);
        return $result->single();
    }

    /**
     * Check if supplier exists by name
     * 
     * @param string $supplierName Supplier name
     * @param int|null $excludeId Supplier ID to exclude from check
     * @return bool True if supplier exists
     */
    public function supplierExistsByName(string $supplierName, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM " . self::TABLE_NAME . " 
                WHERE supplier_name = :supplier_name";
        $params = ['supplier_name' => trim($supplierName)];

        if ($excludeId !== null) {
            $sql .= " AND supplier_id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $result = $this->database->query($sql, $params);
        return (int) $result->single()['count'] > 0;
    }

    /**
     * Validate supplier data
     * 
     * @param array $data Supplier data
     * @throws InvalidArgumentException When validation fails
     */
    private function validateSupplierData(array $data): void
    {
        if (empty($data['supplier_name'])) {
            throw new InvalidArgumentException('Supplier name is required');
        }

        if (strlen(trim($data['supplier_name'])) < 2) {
            throw new InvalidArgumentException('Supplier name must be at least 2 characters long');
        }

        if (strlen(trim($data['supplier_name'])) > 255) {
            throw new InvalidArgumentException('Supplier name cannot exceed 255 characters');
        }

        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $data['phone'])) {
            throw new InvalidArgumentException('Invalid phone number format');
        }

        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            throw new InvalidArgumentException('Status must be either active or inactive');
        }
    }

    /**
     * Sanitize supplier data
     * 
     * @param array $data Raw supplier data
     * @param bool $isCreate Whether this is for creation (requires all fields)
     * @return array Sanitized data
     */
    private function sanitizeSupplierData(array $data, bool $isCreate = true): array
    {
        $sanitized = [];

        if ($isCreate || isset($data['supplier_name'])) {
            $sanitized['supplier_name'] = trim($data['supplier_name']);
        }

        if ($isCreate || isset($data['phone'])) {
            $sanitized['phone'] = isset($data['phone']) ? trim($data['phone']) : null;
        }

        if ($isCreate || isset($data['email'])) {
            $sanitized['email'] = isset($data['email']) ? trim($data['email']) : null;
        }

        if ($isCreate || isset($data['address'])) {
            $sanitized['address'] = isset($data['address']) ? trim($data['address']) : null;
        }

        if ($isCreate || isset($data['contact_person'])) {
            $sanitized['contact_person'] = isset($data['contact_person']) ? trim($data['contact_person']) : null;
        }

        if ($isCreate || isset($data['status'])) {
            $sanitized['status'] = $data['status'] ?? 'active';
        }

        return $sanitized;
    }

    /**
     * Build WHERE clause for filtering
     * 
     * @param array $filters Filter conditions
     * @return array WHERE clause and parameters
     */
    private function buildWhereClause(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = "status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(supplier_name LIKE :search OR contact_person LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        $clause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        return ['clause' => $clause, 'params' => $params];
    }

    /**
     * Build SET clause for updates
     * 
     * @param array $data Update data
     * @return array SET clause and parameters
     */
    private function buildSetClause(array $data): array
    {
        $setParts = [];
        $params = [];

        foreach ($data as $field => $value) {
            $setParts[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }

        return ['clause' => implode(', ', $setParts), 'params' => $params];
    }

    /**
     * Validate pagination parameters
     * 
     * @param int $page Page number
     * @param int $pageSize Page size
     * @throws InvalidArgumentException When parameters are invalid
     */
    private function validatePaginationParams(int $page, int $pageSize): void
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Page number must be greater than 0');
        }

        if ($pageSize < 1 || $pageSize > self::MAX_PAGE_SIZE) {
            throw new InvalidArgumentException("Page size must be between 1 and " . self::MAX_PAGE_SIZE);
        }
    }
}
