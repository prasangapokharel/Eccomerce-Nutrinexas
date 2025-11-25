<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;

class SupportController extends Controller
{
    public function index()
    {
        $this->view('support/index', [
            'title' => 'Help & Support - NutriNexus'
        ]);
    }
}

