<?php
namespace App\Controllers\Slider;

use App\Core\Controller;
use App\Models\Slider;

class SliderController extends Controller
{
    public function index()
    {
        $sliderModel = new Slider();
        $sliders = $sliderModel->getAllForAdmin();
        $this->view('admin/slider/index', ['sliders' => $sliders, 'title' => 'Manage Sliders']);
    }
    
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'subtitle' => trim($_POST['subtitle'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'button_text' => trim($_POST['button_text'] ?? ''),
                'image_url' => trim($_POST['image_url'] ?? ''),
                'link_url' => trim($_POST['link_url'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            $errors = [];
            
            // Only image_url is required
            if (empty($data['image_url'])) {
                $errors['image_url'] = 'Image URL is required';
            } elseif (!filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                $errors['image_url'] = 'Please enter a valid image URL';
            }
            
            if (empty($errors)) {
                $sliderModel = new Slider();
                if ($sliderModel->create($data)) {
                    $this->setFlash('success', 'Slider created successfully');
                    $this->redirect('admin/slider');
                } else {
                    $this->setFlash('error', 'Failed to create slider');
                }
            }
            
            $this->view('admin/slider/create', ['data' => $data, 'errors' => $errors, 'title' => 'Create Slider']);
        } else {
            $this->view('admin/slider/create', ['title' => 'Create Slider']);
        }
    }
    
    public function edit($id = null)
    {
        if (!$id) {
            $this->redirect('admin/slider');
        }
        
        $sliderModel = new Slider();
        $slider = $sliderModel->find($id);
        
        if (!$slider) {
            $this->setFlash('error', 'Slider not found');
            $this->redirect('admin/slider');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => trim($_POST['title'] ?? ''),
                'subtitle' => trim($_POST['subtitle'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'button_text' => trim($_POST['button_text'] ?? ''),
                'image_url' => trim($_POST['image_url'] ?? ''),
                'link_url' => trim($_POST['link_url'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0)
            ];
            
            $errors = [];
            
            // Only image_url is required
            if (empty($data['image_url'])) {
                $errors['image_url'] = 'Image URL is required';
            } elseif (!filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                $errors['image_url'] = 'Please enter a valid image URL';
            }
            
            if (empty($errors)) {
                if ($sliderModel->update($id, $data)) {
                    $this->setFlash('success', 'Slider updated successfully');
                    $this->redirect('admin/slider');
                } else {
                    $this->setFlash('error', 'Failed to update slider');
                }
            }
            
            $this->view('admin/slider/edit', ['slider' => $slider, 'data' => $data, 'errors' => $errors, 'title' => 'Edit Slider']);
        } else {
            $this->view('admin/slider/edit', ['slider' => $slider, 'title' => 'Edit Slider']);
        }
    }
    
    public function delete($id = null)
    {
        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/slider');
        }
        
        $sliderModel = new Slider();
        $slider = $sliderModel->find($id);
        
        if ($slider) {
            if ($sliderModel->delete($id)) {
                $this->setFlash('success', 'Slider deleted successfully');
            } else {
                $this->setFlash('error', 'Failed to delete slider');
            }
        } else {
            $this->setFlash('error', 'Slider not found');
        }
        
        $this->redirect('admin/slider');
    }
}