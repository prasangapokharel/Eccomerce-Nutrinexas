<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\{{modelName}};

/**
 * {{className}} Controller
 * 
 * @package App\Controllers
 */
class {{className}} extends Controller
{
    protected $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = new {{modelName}}();
    }

    /**
     * Display a listing of the resource
     */
    public function index()
    {
        try {
            $page = (int) ($_GET['page'] ?? 1);
            $limit = (int) ($_GET['limit'] ?? 10);
            $search = $_GET['search'] ?? '';

            if ($search) {
                $data = $this->model->search($search);
            } else {
                $data = $this->model->getPaginated($page, $limit);
            }

            $total = $this->model->getCount();
            $totalPages = ceil($total / $limit);

            $this->view('{{viewPath}}/index', [
                'data' => $data,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $total,
                    'limit' => $limit
                ],
                'search' => $search
            ]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        try {
            $this->view('{{viewPath}}/create');
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Store a newly created resource in storage
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Method not allowed');
            }

            $data = $this->validateInput($_POST);
            
            if ($this->model->createRecord($data)) {
                $this->setFlash('success', '{{modelName}} created successfully');
                $this->redirect('/{{routePrefix}}');
            } else {
                throw new \Exception('Failed to create {{modelName}}');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/{{routePrefix}}/create');
        }
    }

    /**
     * Display the specified resource
     */
    public function show($id)
    {
        try {
            $item = $this->model->getById($id);
            
            if (!$item) {
                throw new \Exception('{{modelName}} not found');
            }

            $this->view('{{viewPath}}/show', ['item' => $item]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit($id)
    {
        try {
            $item = $this->model->getById($id);
            
            if (!$item) {
                throw new \Exception('{{modelName}} not found');
            }

            $this->view('{{viewPath}}/edit', ['item' => $item]);
        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Update the specified resource in storage
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Method not allowed');
            }

            $item = $this->model->getById($id);
            
            if (!$item) {
                throw new \Exception('{{modelName}} not found');
            }

            $data = $this->validateInput($_POST);
            
            if ($this->model->updateRecord($id, $data)) {
                $this->setFlash('success', '{{modelName}} updated successfully');
                $this->redirect('/{{routePrefix}}/' . $id);
            } else {
                throw new \Exception('Failed to update {{modelName}}');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('/{{routePrefix}}/' . $id . '/edit');
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Method not allowed');
            }

            $item = $this->model->getById($id);
            
            if (!$item) {
                throw new \Exception('{{modelName}} not found');
            }

            if ($this->model->deleteRecord($id)) {
                $this->setFlash('success', '{{modelName}} deleted successfully');
            } else {
                throw new \Exception('Failed to delete {{modelName}}');
            }
        } catch (\Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('/{{routePrefix}}');
    }

    /**
     * Validate input data
     */
    protected function validateInput(array $data): array
    {
        $validated = [];
        
        {{validationRules}}
        
        return $validated;
    }

    /**
     * Handle errors
     */
    protected function handleError(\Exception $e): void
    {
        error_log($e->getMessage());
        $this->setFlash('error', 'An error occurred: ' . $e->getMessage());
        $this->redirect('/{{routePrefix}}');
    }

    /**
     * Set flash message
     */
    protected function setFlash(string $type, string $message): void
    {
        \App\Core\Session::setFlash($type, $message);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void
    {
        header("Location: $url");
        exit;
    }
}
