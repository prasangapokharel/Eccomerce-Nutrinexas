<?php
namespace App\Models;

use App\Core\Model;

/**
 * {{className}} Model
 * 
 * @package App\Models
 */
class {{className}} extends Model
{
    /**
     * The table associated with the model
     *
     * @var string
     */
    protected $table = '{{tableName}}';

    /**
     * The primary key for the model
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable
     *
     * @var array
     */
    protected $fillable = [
        {{fillableFields}}
    ];

    /**
     * The attributes that should be hidden for serialization
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all {{tableName}} records
     *
     * @return array
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * Get {{tableName}} by ID
     *
     * @param int $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->find($id);
    }

    /**
     * Create new {{tableName}} record
     *
     * @param array $data
     * @return int|false
     */
    public function createRecord(array $data)
    {
        return $this->insert($data);
    }

    /**
     * Update {{tableName}} record
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateRecord($id, array $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Delete {{tableName}} record
     *
     * @param int $id
     * @return bool
     */
    public function deleteRecord($id)
    {
        return $this->delete($id);
    }

    /**
     * Search {{tableName}} records
     *
     * @param string $query
     * @param array $fields
     * @return array
     */
    public function search($query, array $fields = [])
    {
        if (empty($fields)) {
            $fields = $this->fillable;
        }

        $conditions = [];
        $params = [];

        foreach ($fields as $field) {
            $conditions[] = "$field LIKE ?";
            $params[] = "%$query%";
        }

        $whereClause = implode(' OR ', $conditions);
        
        return $this->findWhere($whereClause, $params);
    }

    /**
     * Get paginated {{tableName}} records
     *
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getPaginated($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        
        return $this->findAll(null, $limit, $offset);
    }

    /**
     * Count total {{tableName}} records
     *
     * @return int
     */
    public function getCount()
    {
        $result = $this->query("SELECT COUNT(*) as count FROM {$this->table}");
        return $result[0]['count'] ?? 0;
    }
}
