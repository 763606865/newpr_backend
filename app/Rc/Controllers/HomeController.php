<?php

namespace App\Rc\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * RC 模块首页
     *
     * GET /rc
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|View
    {
        return view('welcome');
    }
}
