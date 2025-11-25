<?php

namespace App\Services;

use App\Core\Model;
use App\Core\Database;
use Exception;

class BulkActionService
{
    /**
     * Perform bulk delete operation
     *
     * @param string $modelClass Model class name (e.g., 'App\Models\Product')
     * @param array $ids Array of IDs to delete
     * @param array $conditions Additional WHERE conditions (e.g., ['status' => 'active'])
     * @return array
     */
    public function bulkDelete(string $modelClass, array $ids, array $conditions = []): array
    {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Validate model class
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                throw new Exception("Invalid model class: {$modelClass}");
            }

            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();
            $primaryKey = $model->getPrimaryKey();

            // Validate and filter IDs
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });

            if (empty($ids)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'No valid IDs provided'
                ];
            }

            // Build WHERE clause
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $whereClause = "{$primaryKey} IN ({$placeholders})";
            $params = $ids;

            // Add additional conditions
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $whereClause .= " AND {$field} = ?";
                    $params[] = $value;
                }
            }

            // Delete records
            $sql = "DELETE FROM {$table} WHERE {$whereClause}";
            $deleted = 0;

            // Delete individually to ensure accurate count
            foreach ($ids as $id) {
                $deleteParams = [$id];
                if (!empty($conditions)) {
                    $deleteParams = array_merge($deleteParams, array_values($conditions));
                }
                
                $deleteWhere = "{$primaryKey} = ?";
                if (!empty($conditions)) {
                    foreach ($conditions as $field => $value) {
                        $deleteWhere .= " AND {$field} = ?";
                    }
                }

                $result = $db->query(
                    "DELETE FROM {$table} WHERE {$deleteWhere}",
                    $deleteParams
                )->execute();

                if ($result) {
                    $deleted++;
                }
            }

            $db->commit();

            return [
                'success' => true,
                'count' => $deleted,
                'message' => "{$deleted} record(s) deleted successfully."
            ];
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('BulkActionService::bulkDelete error: ' . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Bulk delete failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform bulk status update (activate/deactivate)
     *
     * @param string $modelClass Model class name
     * @param array $ids Array of IDs to update
     * @param mixed $statusValue Status value to set
     * @param string $statusColumn Column name for status (default: 'status')
     * @param array $conditions Additional WHERE conditions
     * @return array
     */
    public function bulkUpdateStatus(
        string $modelClass,
        array $ids,
        $statusValue,
        string $statusColumn = 'status',
        array $conditions = []
    ): array {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Validate model class
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                throw new Exception("Invalid model class: {$modelClass}");
            }

            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();
            $primaryKey = $model->getPrimaryKey();

            // Validate and filter IDs
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });

            if (empty($ids)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'No valid IDs provided'
                ];
            }

            // Build WHERE clause
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $whereClause = "{$primaryKey} IN ({$placeholders})";
            $params = array_merge($ids, [$statusValue]);

            // Add additional conditions
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $whereClause .= " AND {$field} = ?";
                    $params[] = $value;
                }
            }

            // Update records
            $sql = "UPDATE {$table} SET {$statusColumn} = ? WHERE {$whereClause}";
            $result = $db->query($sql, $params)->execute();

            if ($result) {
                $db->commit();
                $count = count($ids);
                return [
                    'success' => true,
                    'count' => $count,
                    'message' => "{$count} record(s) updated successfully."
                ];
            } else {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'Failed to update records'
                ];
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('BulkActionService::bulkUpdateStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform bulk activate operation
     *
     * @param string $modelClass Model class name
     * @param array $ids Array of IDs to activate
     * @param string $statusColumn Column name for status
     * @param array $conditions Additional WHERE conditions
     * @return array
     */
    public function bulkActivate(
        string $modelClass,
        array $ids,
        string $statusColumn = 'status',
        array $conditions = []
    ): array {
        return $this->bulkUpdateStatus($modelClass, $ids, 'active', $statusColumn, $conditions);
    }

    /**
     * Perform bulk deactivate operation
     *
     * @param string $modelClass Model class name
     * @param array $ids Array of IDs to deactivate
     * @param string $statusColumn Column name for status
     * @param array $conditions Additional WHERE conditions
     * @return array
     */
    public function bulkDeactivate(
        string $modelClass,
        array $ids,
        string $statusColumn = 'status',
        array $conditions = []
    ): array {
        return $this->bulkUpdateStatus($modelClass, $ids, 'inactive', $statusColumn, $conditions);
    }

    /**
     * Perform bulk update with custom data
     *
     * @param string $modelClass Model class name
     * @param array $ids Array of IDs to update
     * @param array $data Data to update
     * @param array $conditions Additional WHERE conditions
     * @return array
     */
    public function bulkUpdate(
        string $modelClass,
        array $ids,
        array $data,
        array $conditions = []
    ): array {
        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Validate model class
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                throw new Exception("Invalid model class: {$modelClass}");
            }

            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();
            $primaryKey = $model->getPrimaryKey();

            // Validate and filter IDs
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });

            if (empty($ids)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'No valid IDs provided'
                ];
            }

            if (empty($data)) {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'No data provided for update'
                ];
            }

            // Build SET clause
            $setClause = [];
            $params = [];
            foreach ($data as $field => $value) {
                $setClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $setClause = implode(', ', $setClause);

            // Build WHERE clause
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $whereClause = "{$primaryKey} IN ({$placeholders})";
            $params = array_merge($params, $ids);

            // Add additional conditions
            if (!empty($conditions)) {
                foreach ($conditions as $field => $value) {
                    $whereClause .= " AND {$field} = ?";
                    $params[] = $value;
                }
            }

            // Update records
            $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
            $result = $db->query($sql, $params)->execute();

            if ($result) {
                $db->commit();
                $count = count($ids);
                return [
                    'success' => true,
                    'count' => $count,
                    'message' => "{$count} record(s) updated successfully."
                ];
            } else {
                $db->rollBack();
                return [
                    'success' => false,
                    'count' => 0,
                    'message' => 'Failed to update records'
                ];
            }
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('BulkActionService::bulkUpdate error: ' . $e->getMessage());
            return [
                'success' => false,
                'count' => 0,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate if all IDs exist in the database
     *
     * @param string $modelClass Model class name
     * @param array $ids Array of IDs to validate
     * @return bool
     */
    public function validateIds(string $modelClass, array $ids): bool
    {
        try {
            if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
                return false;
            }

            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();
            $primaryKey = $model->getPrimaryKey();

            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });

            if (empty($ids)) {
                return false;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$primaryKey} IN ({$placeholders})";
            
            $result = $model->getDb()->query($sql, $ids)->single();
            $count = (int)($result['count'] ?? 0);

            return $count === count($ids);
        } catch (Exception $e) {
            error_log('BulkActionService::validateIds error: ' . $e->getMessage());
            return false;
        }
    }

}

