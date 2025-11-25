<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Cache;

class Blog extends Model
{
    protected $table = 'blog_posts';
    protected $primaryKey = 'id';
    
    private $cache;
    private $cachePrefix = 'blog_';
    private $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct();
        $this->cache = new Cache();
    }

    /**
     * Get all published blogs with pagination
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPublishedBlogs($limit = 10, $offset = 0)
    {
        $cacheKey = $this->cachePrefix . 'published_' . $limit . '_' . $offset;
        return $this->cache->remember($cacheKey, function () use ($limit, $offset) {
            $sql = "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, 
                           u.first_name, u.last_name 
                    FROM {$this->table} bp 
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                    LEFT JOIN users u ON bp.author_id = u.id 
                    WHERE bp.status = 'published' 
                    ORDER BY bp.created_at DESC 
                    LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get featured blog posts for homepage
     *
     * @param int $limit
     * @return array
     */
    public function getFeaturedPosts($limit = 3)
    {
        $cacheKey = $this->cachePrefix . 'featured_' . $limit;
        return $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = "SELECT id, title, slug, excerpt, featured_image, created_at 
                    FROM {$this->table} 
                    WHERE status = 'published' 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return $this->db->query($sql, [$limit])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get blog by slug
     *
     * @param string $slug
     * @return array|false
     */
    public function getBySlug($slug)
    {
        $cacheKey = $this->cachePrefix . 'slug_' . $slug;
        return $this->cache->remember($cacheKey, function () use ($slug) {
            $sql = "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, 
                           u.first_name, u.last_name 
                    FROM {$this->table} bp 
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                    LEFT JOIN users u ON bp.author_id = u.id 
                    WHERE bp.slug = ? AND bp.status = 'published'";
            return $this->db->query($sql, [$slug])->single();
        }, $this->cacheTTL);
    }

    /**
     * Track blog view and increment view count if IP hasn't viewed before
     *
     * @param int $postId
     * @param string $ipAddress
     * @return bool
     */
    public function trackView($postId, $ipAddress)
    {
        try {
            // Check if this IP has already viewed this post
            $existingView = $this->db->query(
                "SELECT id FROM blog_views WHERE post_id = ? AND ip_address = ?",
                [$postId, $ipAddress]
            )->single();

            // If IP hasn't viewed this post before, record the view and increment count
            if (!$existingView) {
                // Insert new view record
                $this->db->query(
                    "INSERT INTO blog_views (post_id, ip_address) VALUES (?, ?)",
                    [$postId, $ipAddress]
                )->execute();

                // Increment the views_count in blog_posts table
                $this->db->query(
                    "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = ?",
                    [$postId]
                )->execute();

                // Clear cache for this blog post
                $this->cache->delete($this->cachePrefix . 'id_' . $postId);
                $this->cache->delete($this->cachePrefix . 'all');
                
                return true;
            }
            
            return false; // View already recorded for this IP
        } catch (Exception $e) {
            error_log("Error tracking blog view: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get blog by ID
     *
     * @param int $id
     * @return array|false
     */
    public function getById($id)
    {
        $cacheKey = $this->cachePrefix . 'id_' . $id;
        return $this->cache->remember($cacheKey, function () use ($id) {
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";
            return $this->db->query($sql, [$id])->single();
        }, $this->cacheTTL);
    }

    /**
     * Get recent blogs
     *
     * @param int $limit
     * @return array
     */
    public function getRecentBlogs($limit = 5)
    {
        $cacheKey = $this->cachePrefix . 'recent_' . $limit;
        return $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = "SELECT bp.id, bp.title, bp.slug, bp.excerpt, bp.featured_image, bp.created_at, 
                           bc.name AS category_name, bc.slug AS category_slug, 
                           u.first_name, u.last_name 
                    FROM {$this->table} bp 
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                    LEFT JOIN users u ON bp.author_id = u.id 
                    WHERE bp.status = 'published' 
                    ORDER BY bp.created_at DESC 
                    LIMIT ?";
            return $this->db->query($sql, [$limit])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get blogs by category name (joins blog_categories via category_id)
     *
     * @param string $categoryName
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByCategory($categoryName, $limit = 10, $offset = 0)
    {
        $cacheKey = $this->cachePrefix . 'category_' . $categoryName . '_' . $limit . '_' . $offset;
        return $this->cache->remember($cacheKey, function () use ($categoryName, $limit, $offset) {
            $sql = "SELECT bp.*, bc.name AS category_name 
                    FROM {$this->table} bp 
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                    WHERE bc.name = ? AND bp.status = 'published' 
                    ORDER BY bp.created_at DESC 
                    LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$categoryName, $limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Search blogs
     *
     * @param string $searchTerm
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function searchBlogs($searchTerm, $limit = 10, $offset = 0)
    {
        $searchPattern = "%{$searchTerm}%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE (title LIKE ? OR content LIKE ? OR excerpt LIKE ?) 
                AND status = 'published' 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        return $this->db->query($sql, [$searchPattern, $searchPattern, $searchPattern, $limit, $offset])->all();
    }

    /**
     * Get total count of published blogs
     *
     * @return int
     */
    public function getPublishedCount()
    {
        $cacheKey = $this->cachePrefix . 'published_count';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = 'published'";
            $result = $this->db->query($sql)->single();
            return $result ? (int)$result['count'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get total count by category name (joins blog_categories)
     *
     * @param string $categoryName
     * @return int
     */
    public function getCountByCategory($categoryName)
    {
        $cacheKey = $this->cachePrefix . 'category_count_' . $categoryName;
        return $this->cache->remember($cacheKey, function () use ($categoryName) {
            $sql = "SELECT COUNT(*) as count 
                    FROM {$this->table} bp 
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                    WHERE bc.name = ? AND bp.status = 'published'";
            $result = $this->db->query($sql, [$categoryName])->single();
            return $result ? (int)$result['count'] : 0;
        }, $this->cacheTTL);
    }

    /**
     * Get all blog categories (from blog_categories table)
     *
     * @return array
     */
    public function getCategories()
    {
        $cacheKey = $this->cachePrefix . 'categories';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT name FROM blog_categories WHERE is_active = 1 ORDER BY name ASC";
            return array_column($this->db->query($sql)->all(), 'name');
        }, $this->cacheTTL);
    }

    /**
     * Create a new blog
     *
     * @param array $data
     * @return int|false
     */
    public function createBlog($data)
    {
        $sql = "INSERT INTO {$this->table} (title, slug, content, excerpt, featured_image, category_id, author_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $result = $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'] ?? null,
            $data['featured_image'] ?? null,
            $data['category_id'] ?? null,
            $data['author_id'],
            $data['status'] ?? 'draft'
        ])->execute();

        if ($result) {
            $this->invalidateCache();
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update a blog
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateBlog($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, 
                category_id = ?, status = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $result = $this->db->query($sql, [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['excerpt'] ?? null,
            $data['featured_image'] ?? null,
            $data['category_id'] ?? null,
            $data['status'] ?? 'draft',
            $id
        ])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Delete a blog
     *
     * @param int $id
     * @return bool
     */
    public function deleteBlog($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql, [$id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Update blog status
     *
     * @param int $id
     * @param string $status
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$status, $id])->execute();

        if ($result) {
            $this->invalidateCache($id);
        }
        return $result;
    }

    /**
     * Get related blogs
     *
     * @param int $blogId
     * @param int|null $categoryId
     * @param int $limit
     * @return array
     */
    public function getRelatedBlogs($blogId, $categoryId, $limit = 3)
    {
        if (empty($categoryId)) {
            return [];
        }
        $cacheKey = $this->cachePrefix . 'related_' . $blogId . '_' . (int)$categoryId . '_' . $limit;
        return $this->cache->remember($cacheKey, function () use ($blogId, $categoryId, $limit) {
            $sql = "SELECT id, title, slug, excerpt, featured_image, published_at, created_at FROM {$this->table} 
                    WHERE id != ? AND category_id = ? AND status = 'published' 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return $this->db->query($sql, [$blogId, $categoryId, $limit])->all();
        }, $this->cacheTTL);
    }

    /**
     * Generate slug from title
     *
     * @param string $title
     * @return string
     */
    public function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
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
     * @param int|null $excludeId
     * @return bool
     */
    private function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->query($sql, $params)->single();
        return $result['count'] > 0;
    }

    /**
     * Get all posts (published and draft)
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllPosts($limit = 10, $offset = 0)
    {
        $cacheKey = $this->cachePrefix . 'all_' . $limit . '_' . $offset;
        return $this->cache->remember($cacheKey, function () use ($limit, $offset) {
            $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            return $this->db->query($sql, [$limit, $offset])->all();
        }, $this->cacheTTL);
    }

    /**
     * Get all posts for admin (no pagination, all posts)
     *
     * @return array
     */
    public function getAllForAdmin()
    {
        $cacheKey = $this->cachePrefix . 'admin_all';
        return $this->cache->remember($cacheKey, function () {
            $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
            return $this->db->query($sql)->all();
        }, $this->cacheTTL);
    }

    /**
     * Get author full name for a blog post
     *
     * @param array $post
     * @return string
     */
    public function getAuthorFullName($post)
    {
        if (isset($post['author_id']) && !empty($post['author_id'])) {
            // If author_id exists, get user details
            $userModel = new \App\Models\User();
            $user = $userModel->find($post['author_id']);
            if ($user) {
                return $user['first_name'] . ' ' . $user['last_name'];
            }
        }
        
        // Fallback to author_name if available
        if (isset($post['author_name']) && !empty($post['author_name'])) {
            return $post['author_name'];
        }
        
        return 'Unknown Author';
    }

    /**
     * Invalidate cache entries
     *
     * @param int|null $id
     */
    private function invalidateCache($id = null)
    {
        if ($id) {
            $this->cache->delete($this->cachePrefix . 'id_' . $id);
        }
        
        // Invalidate list caches
        $this->cache->deletePattern($this->cachePrefix . 'published_*');
        $this->cache->deletePattern($this->cachePrefix . 'recent_*');
        $this->cache->deletePattern($this->cachePrefix . 'category_*');
        $this->cache->deletePattern($this->cachePrefix . 'related_*');
        $this->cache->delete($this->cachePrefix . 'published_count');
        $this->cache->delete($this->cachePrefix . 'categories');
    }

    /**
     * Get all active (published) blog posts for sitemap generation
     *
     * @return array
     */
    public function getAllActiveBlogs()
    {
        $sql = "SELECT id, title, slug, updated_at FROM {$this->table} WHERE status = 'published' ORDER BY updated_at DESC";
        return $this->db->query($sql)->all();
    }
}