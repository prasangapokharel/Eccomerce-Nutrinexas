<?php

namespace App\Models;

use App\Core\Model;

class BlogCategory extends Model
{
    protected $table = 'blog_categories';
    protected $primaryKey = 'id';

    /**
     * Get all active categories
     *
     * @return array
     */
    public function getActiveCategories()
    {
        return $this->findBy('is_active', 1);
    }

    /**
     * Get category by slug
     *
     * @param string $slug
     * @return array|false
     */
    public function getCategoryBySlug($slug)
    {
        $sql = "SELECT * FROM blog_categories WHERE slug = ? AND is_active = 1";
        return $this->db->query($sql, [$slug])->single();
    }

    /**
     * Generate unique slug
     *
     * @param string $name
     * @return string
     */
    public function generateSlug($name)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if slug exists
     *
     * @param string $slug
     * @return bool
     */
    private function slugExists($slug)
    {
        $result = $this->findOneBy('slug', $slug);
        return $result !== false;
    }
}
