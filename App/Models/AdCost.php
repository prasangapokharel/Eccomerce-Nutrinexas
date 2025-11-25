<?php

namespace App\Models;

use App\Core\Model;

class AdCost extends Model
{
    protected $table = 'ads_costs';

    /**
     * Get all ad costs
     */
    public function getAll()
    {
        return $this->getDb()->query(
            "SELECT ac.*, at.name as ad_type_name
             FROM ads_costs ac
             LEFT JOIN ads_types at ON ac.ads_type_id = at.id
             ORDER BY at.name, ac.duration_days"
        )->all();
    }

    /**
     * Get costs by ad type
     */
    public function getByAdType($adsTypeId)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads_costs WHERE ads_type_id = ? ORDER BY duration_days",
            [$adsTypeId]
        )->all();
    }

    public function getByAdTypeAndTier($adsTypeId, string $tier)
    {
        return $this->getDb()->query(
            "SELECT * FROM ads_costs WHERE ads_type_id = ? AND tier = ? LIMIT 1",
            [$adsTypeId, $tier]
        )->single();
    }

    /**
     * Get cost by ID
     */
    public function find($id)
    {
        return $this->getDb()->query(
            "SELECT ac.*, at.name as ad_type_name
             FROM ads_costs ac
             LEFT JOIN ads_types at ON ac.ads_type_id = at.id
             WHERE ac.id = ?",
            [$id]
        )->single();
    }
}


