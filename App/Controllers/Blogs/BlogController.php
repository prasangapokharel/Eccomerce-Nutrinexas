<?php

namespace App\Controllers\Blogs;

use App\Core\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\User;
use Exception;

class BlogController extends Controller
{
    private $blogModel;
    private $categoryModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->blogModel = new Blog();
        $this->categoryModel = new BlogCategory();
        $this->userModel = new User();
    }

    /**
     * Display blog index page
     */
    public function index($page = 1)
    {
        try {
            $page = max(1, (int)$page);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Use published posts for public index and correct count method
            $posts = $this->blogModel->getPublishedBlogs($limit, $offset);
            $totalPosts = $this->blogModel->getPublishedCount();
            $totalPages = ceil($totalPosts / $limit);
            
            $featuredPosts = $this->blogModel->getFeaturedPosts(3);
            $categories = $this->categoryModel->getActiveCategories();
            // Fallback to recent blogs as "popular" list
            $popularPosts = $this->blogModel->getRecentBlogs(5);

            $this->view('home/blog', [
                'posts' => $posts,
                'featuredPosts' => $featuredPosts,
                'categories' => $categories,
                'popularPosts' => $popularPosts,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalPosts' => $totalPosts,
                'title' => 'Blog - Health & Nutrition Articles | NutriNexas'
            ]);
        } catch (Exception $e) {
            error_log('Blog index error: ' . $e->getMessage());
            $this->setFlash('error', 'Unable to load blog posts. Please try again later.');
            $this->redirect('');
        }
    }

    /**
     * Display single blog post
     */
    public function show($slug)
    {
        try {
            // Align with Blog model method name
            $post = $this->blogModel->getBySlug($slug);
            
            if (!$post) {
                $this->view('errors/404', [
                    'title' => 'Post Not Found'
                ]);
                return;
            }

            // Fallback: ensure category_name and category_slug exist even if cached data lacked join
            if ((!isset($post['category_name']) || !isset($post['category_slug'])) && !empty($post['category_id'])) {
                $category = $this->categoryModel->find($post['category_id']);
                if ($category) {
                    $post['category_name'] = $category['name'] ?? $post['category_name'] ?? null;
                    $post['category_slug'] = $category['slug'] ?? $post['category_slug'] ?? null;
                }
            }

            // Fallback: ensure author first_name/last_name exist even if cached data lacked join
            if ((!isset($post['first_name']) || !isset($post['last_name'])) && !empty($post['author_id'])) {
                $author = $this->userModel->find($post['author_id']);
                if ($author) {
                    $post['first_name'] = $author['first_name'] ?? ($post['first_name'] ?? null);
                    $post['last_name'] = $author['last_name'] ?? ($post['last_name'] ?? null);
                }
            }

            // Track blog view (increment view count if IP hasn't viewed before)
            $userIP = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? 'unknown';
            $this->blogModel->trackView($post['id'], $userIP);

            // Use category_id for related posts to match DB schema
            $relatedPosts = $this->blogModel->getRelatedBlogs($post['id'], $post['category_id'] ?? null, 4);
            $categories = $this->categoryModel->getActiveCategories();
            $popularPosts = $this->blogModel->getRecentBlogs(5);

            // Load tags for the post
            $tags = $this->getPostTags($post['id']);
            $post['tags'] = $tags;

            // Generate structured data for SEO
            // No direct structured data method in Blog model; pass null for now
            $structuredData = null;

            $this->view('blog/view', [
                'post' => $post,
                'relatedPosts' => $relatedPosts,
                'categories' => $categories,
                'popularPosts' => $popularPosts,
                'structuredData' => $structuredData,
                'title' => $post['meta_title'] ?: $post['title']
            ]);
        } catch (Exception $e) {
            error_log('Blog show error: ' . $e->getMessage());
            $this->setFlash('error', 'Unable to load blog post. Please try again later.');
            $this->redirect('blog');
        }
    }

    /**
     * Display posts by category
     */
    public function category($slug, $page = 1)
    {
        try {
            $category = $this->categoryModel->getCategoryBySlug($slug);
            
            if (!$category) {
                $this->view('errors/404', [
                    'title' => 'Category Not Found'
                ]);
                return;
            }

            $page = max(1, (int)$page);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Blog model expects category name string
            $posts = $this->blogModel->getByCategory($category['name'], $limit, $offset);
            $categories = $this->categoryModel->getActiveCategories();
            $popularPosts = $this->blogModel->getRecentBlogs(5);

            $this->view('blog/category', [
                'category' => $category,
                'posts' => $posts,
                'categories' => $categories,
                'popularPosts' => $popularPosts,
                'currentPage' => $page,
                'title' => $category['meta_title'] ?: $category['name'] . ' Articles | NutriNexas Blog'
            ]);
        } catch (Exception $e) {
            error_log('Blog category error: ' . $e->getMessage());
            $this->setFlash('error', 'Unable to load category posts. Please try again later.');
            $this->redirect('blog');
        }
    }

    /**
     * Search blog posts
     */
    public function search()
    {
        try {
            $query = trim($_GET['q'] ?? '');
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $posts = [];
            if (!empty($query)) {
                // Use Blog model's searchBlogs method
                $posts = $this->blogModel->searchBlogs($query, $limit, $offset);
            }

            $categories = $this->categoryModel->getActiveCategories();
            $popularPosts = $this->blogModel->getRecentBlogs(5);

            $this->view('blog/search', [
                'query' => $query,
                'posts' => $posts,
                'categories' => $categories,
                'popularPosts' => $popularPosts,
                'currentPage' => $page,
                'title' => 'Search Results for "' . htmlspecialchars($query) . '" | NutriNexas Blog'
            ]);
        } catch (Exception $e) {
            error_log('Blog search error: ' . $e->getMessage());
            $this->setFlash('error', 'Unable to search posts. Please try again later.');
            $this->redirect('blog');
        }
    }

    /**
     * Admin: Display all posts
     */
    public function adminIndex()
    {
        $posts = $this->blogModel->getAllForAdmin();
        
        // Prepare author full name for each post
        foreach ($posts as &$post) {
            $post['full_author_name'] = $this->blogModel->getAuthorFullName($post);
        }
        unset($post); // Unset reference to avoid unintended modifications

        $this->view('admin/blog/index', [
            'posts' => $posts,
            'title' => 'Manage Blog Posts'
        ]);
    }

    /**
     * Admin: Create new post
     */
    public function create()
    {
        $this->requireAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('admin/blog');
                return;
            }
            
            // Ensure we have a valid author_id
            $authorId = $_SESSION['user_id'] ?? null;
            if (!$authorId) {
                $this->setFlash('error', 'You must be logged in to create a blog post');
                $this->redirect('admin/login');
                return;
            }

            // Verify the user exists in the database
            $user = $this->userModel->find($authorId);
            if (!$user) {
                $this->setFlash('error', 'Invalid user session. Please log in again.');
                $this->redirect('admin/login');
                return;
            }

            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'featured_image' => trim($_POST['featured_image'] ?? ''),
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'author_id' => $authorId,
                'status' => $_POST['status'] ?? 'draft',
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                'og_title' => trim($_POST['og_title'] ?? ''),
                'og_description' => trim($_POST['og_description'] ?? ''),
                'og_image' => trim($_POST['og_image'] ?? ''),
                'canonical_url' => trim($_POST['canonical_url'] ?? ''),
                'focus_keyword' => trim($_POST['focus_keyword'] ?? '')
            ];

            $errors = [];

            if (empty($data['title'])) {
                $errors['title'] = 'Title is required';
            }

            if (empty($data['content'])) {
                $errors['content'] = 'Content is required';
            }

            if (empty($errors)) {
                // Use Blog model's createBlog method
                if ($this->blogModel->createBlog($data)) {
                    $this->setFlash('success', 'Blog post created successfully');
                    $this->redirect('admin/blog');
                } else {
                    $this->setFlash('error', 'Failed to create blog post');
                }
            }

            $categories = $this->categoryModel->getActiveCategories();
            $this->view('admin/blog/post', [
                'data' => [
                    'categories' => $categories,
                    'post_data' => $data,
                    'errors' => $errors
                ],
                'title' => 'Create New Blog Post'
            ]);
        } else {
            $categories = $this->categoryModel->getActiveCategories();
            $this->view('admin/blog/post', [
                'data' => [
                    'categories' => $categories
                ],
                'title' => 'Create New Blog Post'
            ]);
        }
    }

    /**
     * Admin: Edit post
     */
    public function edit($id = null)
    {
        $this->requireAdmin();
        
        if (!$id) {
            $this->redirect('admin/blog');
            return;
        }

        // Align with Blog model method name
        $post = $this->blogModel->getById($id);
        if (!$post) {
            $this->setFlash('error', 'Post not found');
            $this->redirect('admin/blog');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // CSRF validation
            if (!$this->validateCSRF()) {
                $this->setFlash('error', 'Invalid security token. Please try again.');
                $this->redirect('admin/blog/edit/' . $id);
                return;
            }
            
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'slug' => trim($_POST['slug'] ?? ''),
                'excerpt' => trim($_POST['excerpt'] ?? ''),
                'content' => $_POST['content'] ?? '',
                'featured_image' => trim($_POST['featured_image'] ?? ''),
                'category_id' => !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
                'status' => $_POST['status'] ?? 'draft',
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                'meta_title' => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords' => trim($_POST['meta_keywords'] ?? ''),
                'og_title' => trim($_POST['og_title'] ?? ''),
                'og_description' => trim($_POST['og_description'] ?? ''),
                'og_image' => trim($_POST['og_image'] ?? ''),
                'canonical_url' => trim($_POST['canonical_url'] ?? ''),
                'focus_keyword' => trim($_POST['focus_keyword'] ?? '')
            ];

            $errors = [];

            if (empty($data['title'])) {
                $errors['title'] = 'Title is required';
            }

            if (empty($data['content'])) {
                $errors['content'] = 'Content is required';
            }

            if (empty($errors)) {
                // Use Blog model's updateBlog method
                if ($this->blogModel->updateBlog($id, $data)) {
                    $this->setFlash('success', 'Blog post updated successfully');
                    $this->redirect('admin/blog');
                } else {
                    $this->setFlash('error', 'Failed to update blog post');
                }
            }

            $categories = $this->categoryModel->getActiveCategories();
            $this->view('admin/blog/edit', [
                'post' => $post,
                'categories' => $categories,
                'data' => $data,
                'errors' => $errors,
                'title' => 'Edit Blog Post'
            ]);
        } else {
            $categories = $this->categoryModel->getActiveCategories();
            $this->view('admin/blog/edit', [
                'post' => $post,
                'categories' => $categories,
                'title' => 'Edit Blog Post'
            ]);
        }
    }

    /**
     * Admin: Delete post
     */
    public function delete($id = null)
    {
        $this->requireAdmin();
        
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/blog');
            return;
        }
        
        // CSRF validation
        if (!$this->validateCSRF()) {
            $this->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('admin/blog');
            return;
        }

        $post = $this->blogModel->find($id);

        if ($post) {
            // Use Blog model's deleteBlog method
            if ($this->blogModel->deleteBlog($id)) {
                $this->setFlash('success', 'Blog post deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete blog post');
            }
        } else {
            $this->setFlash('error', 'Post not found');
        }

        $this->redirect('admin/blog');
    }

    /**
     * Admin: Bulk delete posts
     */
    public function bulkDelete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            } else {
                $this->redirect('admin/blog');
            }
            return;
        }

        // Handle both AJAX and form submissions
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? $_POST['ids'] ?? [];
        
        if (!is_array($ids) || empty($ids)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No posts selected for deletion'], 400);
            } else {
                $this->setFlash('error', 'No posts selected for deletion');
                $this->redirect('admin/blog');
            }
            return;
        }

        // Convert to integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; });

        if (empty($ids)) {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => 'No valid post IDs provided'], 400);
            } else {
                $this->setFlash('error', 'No valid post IDs provided');
                $this->redirect('admin/blog');
            }
            return;
        }

        try {
            // Use BulkActionService to delete blog posts
            $bulkService = new \App\Services\BulkActionService();
            $result = $bulkService->bulkDelete(\App\Models\Blog::class, $ids);

            if ($result['success']) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => $result['message'],
                        'deleted_count' => $result['count']
                    ]);
                } else {
                    $this->setFlash('success', $result['message']);
                    $this->redirect('admin/blog');
                }
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']], 400);
                } else {
                    $this->setFlash('error', $result['message']);
                    $this->redirect('admin/blog');
                }
            }
        } catch (Exception $e) {
            error_log('Bulk delete blog error: ' . $e->getMessage());
            $errorMessage = 'An error occurred: ' . $e->getMessage();
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(['success' => false, 'message' => $errorMessage], 500);
            } else {
                $this->setFlash('error', $errorMessage);
                $this->redirect('admin/blog');
            }
        }
    }

    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get tags for a blog post
     */
    private function getPostTags($postId)
    {
        try {
            $db = \App\Core\Database::getInstance();
            $sql = "SELECT bt.id, bt.name, bt.slug 
                    FROM blog_tags bt 
                    INNER JOIN blog_post_tags bpt ON bt.id = bpt.tag_id 
                    WHERE bpt.post_id = ? 
                    ORDER BY bt.name ASC";
            return $db->query($sql, [$postId])->all();
        } catch (Exception $e) {
            error_log('Error loading blog tags: ' . $e->getMessage());
            return [];
        }
    }
}
