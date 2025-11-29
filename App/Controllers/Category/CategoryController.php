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
            $categories = $this->categoryModel->getActiveCategories();
            
            $this->view('categories/index', [
                'categories' => $categories,
                'title' => 'Product Categories - NutriNexas'
            ]);
        } catch (Exception $e) {
            error_log('CategoryController publicIndex error: ' . $e->getMessage());
            $this->setFlash('error', 'Unable to load categories. Please try again later.');
            $this->redirect('');
        }
    }
}
