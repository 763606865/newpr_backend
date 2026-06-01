<?php

namespace App\Rc\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * GET /rc/home
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|View
    {
        return view('welcome');
    }
}
