<?php

namespace App\Controllers\Category;

use App\Core\Controller;
use App\Models\Category;
use Exception;

class CategoryController extends Controller
{
    private $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new Category();
    }

    /**
     * Display all categories page
     */
    public function publicIndex()
    {
        try {
            // Initialize performance cache
            if (!class_exists('App\Helpers\PerformanceCache')) {
                require_once ROOT_DIR . '/App/Helpers/PerformanceCache.php';
            }
            \App\Helpers\PerformanceCache::init();
            
            // Check cache
            $cacheKey = 'categories_index';
            $cachedData = \App\Helpers\PerformanceCache::getStaticContent($cacheKey);
            if ($cachedData) {
                $this->view('categories/index', $cachedData);
                return;
            }
            
            $categories = $this->categoryModel->getActiveCategories();
            
            $viewData = [
                'categories' => $categories,
                'title' => 'Product Categories - NutriNexas'
            ];
            
            // Cache for 1 hour
            \App\Helpers\PerformanceCache::cacheStaticContent($cacheKey, $viewData, 3600);
            
            $this->view('categories/index', $viewData);
        } catch (Exception $e) {
            $environment = $_ENV['ENVIRONMENT'] ?? getenv('ENVIRONMENT') ?: 'production';
            if ($environment === 'development' || $environment === 'dev') {
                error_log('CategoryController publicIndex error: ' . $e->getMessage());
            }
            $this->setFlash('error', 'Unable to load categories. Please try again later.');
            $this->redirect('');
        }
    }
}
