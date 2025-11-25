<?php

namespace App\Controllers\Curior;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Curior\Curior as CuriorModel;

class BaseCuriorController extends Controller
{
    protected $curiorId;
    protected $curiorData;

    public function __construct()
    {
        parent::__construct();
        $this->checkCuriorAuth();
    }

    /**
     * Check if curior is authenticated
     */
    protected function checkCuriorAuth()
    {
        $curiorId = Session::get('curior_id');
        
        if (!$curiorId) {
            $this->setFlash('error', 'Please login to access courier dashboard');
            $this->redirect('curior/login');
            exit;
        }

        $this->curiorId = $curiorId;
        
        $curiorModel = new CuriorModel();
        $this->curiorData = $curiorModel->getById($curiorId);
        
        if (!$this->curiorData || $this->curiorData['status'] !== 'active') {
            Session::remove('curior_id');
            Session::remove('curior_name');
            Session::remove('curior_email');
            $this->setFlash('error', 'Your courier account is not active');
            $this->redirect('curior/login');
            exit;
        }
    }

    /**
     * Require curior authentication
     */
    protected function requireCurior()
    {
        if (!$this->curiorId) {
            $this->setFlash('error', 'Please login to access this page');
            $this->redirect('curior/login');
            exit;
        }
    }
}

