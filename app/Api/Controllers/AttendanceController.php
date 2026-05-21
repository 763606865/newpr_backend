<?php

namespace App\Api\Controllers;

use App\Resources\Oa\CommunicateResource;
use App\Resources\Oa\ModuleResource;
use Illuminate\Http\JsonResponse;
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

    /**
     * 通讯录
     *
     * GET /api/communicates
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function communicates(Request $request): \Illuminate\Http\JsonResponse
    {
        $companies = $request->user()->companies()->with([
            'departments',
            'departments.employees',
            'departments.employees.position',
        ])->enabled()->get();

        return $this->success(new CommunicateResource($companies));
    }

    /**
     * 菜单功能点
     *
     * GET /api/modules
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function modules(Request $request): \Illuminate\Http\JsonResponse
    {
        $companies = $request->user()->companies()->with([
            'currentPlans',
            'companyPlans',
            'shipCompanyPlans',
        ])->get();
        return $this->success(new ModuleResource($companies));
    }
}
