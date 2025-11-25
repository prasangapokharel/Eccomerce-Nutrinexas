<?php

namespace App\Models;

use App\Core\Model;

class AdType extends Model
{
    protected $table = 'ads_types';

    /**
     * Get all ad types
     */
    public function getAll()
    {
        return $this->getDb()->query("SELECT * FROM ads_types ORDER BY name")->all();
    }

    /**
     * Get ad type by name
     */
    public function findByName($name)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads_types WHERE name = ?",
            [$name]
        )->single();
    }

    /**
     * Find ad type by ID
     */
    public function find($id)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads_types WHERE id = ?",
            [$id]
        )->single();
    }
}

