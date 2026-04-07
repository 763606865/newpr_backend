<?php

namespace App\Api\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * GET /api/home
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|\Illuminate\View\View
    {
        return view('welcome');
    }
}
