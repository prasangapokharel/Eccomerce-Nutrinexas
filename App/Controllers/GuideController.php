<?php

namespace App\Controllers;

use App\Core\Controller;

class GuideController extends Controller
{
    /**
     * Display NX Guide page
     */
    public function index()
    {
        $this->view('guide/index', [
            'title' => 'NX Guide - Tutorials & Help'
        ]);
    }
}

