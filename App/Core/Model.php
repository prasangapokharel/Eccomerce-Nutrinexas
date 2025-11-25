<?php
namespace App\Core;

class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Find a record by ID
     *
     * @param int $id
     * @return array|false
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql)->bind([$id])->single();
    }

    /**
     * Get all records
     *
     * @return array
     */
    public function all()
    {
        $sql = "SELECT * FROM {$this->table}";
        return $this->db->query($sql)->all();
    }

    /**
     * Find records by a field
     *
     * @param string $field
     * @param mixed $value
     * @return array
     */
    public function findBy($field, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->query($sql)->bind([$value])->all();
    }

    /**
     * Find a single record by a field
     *
     * @param string $field
     * @param mixed $value
     * @return array|false
     */
    public function findOneBy($field, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ?";
        return $this->db->query($sql)->bind([$value])->single();
    }

    /**
     * Create a new record
     *
     * @param array $data
     * @return int|false
     */
    public function create($data)
    {
        // Remove the primary key from the data array if it exists
        if (array_key_exists($this->primaryKey, $data)) {
            unset($data[$this->primaryKey]);
        }

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $result = $this->db->query($sql)
            ->bind(array_values($data))
            ->execute();
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data)
    {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
        
        $values = array_values($data);
        $values[] = $id;
        
        return $this->db->query($sql)
            ->bind($values)
            ->execute();
    }

    /**
     * Delete a record
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        return $this->db->query($sql)
            ->bind([$id])
            ->execute();
    }

    /**
     * Count records
     *
     * @param string $where
     * @param array $params
     * @return int
     */
    public function count($where = '', $params = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        
        $result = $this->db->query($sql)
            ->bind($params)
            ->single();
        
        return (int) $result['count'];
    }

    /**
     * Execute a custom query
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public function query($sql, $params = [])
    {
        return $this->db->query($sql)
            ->bind($params)
            ->all();
    }

    /**
     * Execute a custom query and get a single result
     *
     * @param string $sql
     * @param array $params
     * @return array|false
     */
    public function querySingle($sql, $params = [])
    {
        return $this->db->query($sql)
            ->bind($params)
            ->single();
    }

    /**
     * Begin a transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollBack()
    {
        return $this->db->rollBack();
    }
    
    /**
     * Check if currently in a transaction
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->db->inTransaction();
    }
    
    /**
     * Get the database instance
     *
     * @return \App\Core\Database
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Get the primary key
     *
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
}
