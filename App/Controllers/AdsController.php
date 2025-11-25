<?php

namespace App\Controllers;

use App\Core\Controller;

class AdsController extends Controller
{
    public function index()
    {
        $this->view('ads/index', [
            'title' => 'NutriNexus Ads - Book Banner Slots'
        ]);
    }
}


