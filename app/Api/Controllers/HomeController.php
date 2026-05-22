<?php

namespace App\Api\Controllers;

use App\Resources\Oa\CompanyResource;
use App\Resources\Oa\ModuleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * GET /api/home
     */
    public function index(Request $request): \Illuminate\Contracts\View\View|View
    {
        return view('welcome');
    }

    /**
     * 通讯录
     *
     * GET /api/communicates
     *
     * @throws \Exception
     */
    public function communicates(Request $request): JsonResponse
    {
        $company = $this->employee()->company()->with([
            'departments',
            'departments.employees',
            'departments.employees.position',
        ])->first();

        return $this->success(new CompanyResource($company));
    }

    /**
     * 菜单功能点
     *
     * GET /api/modules
     *
     * @throws \Exception
     */
    public function modules(Request $request): JsonResponse
    {
        $company = $this->employee()->company()->with([
            'currentPlans',
            'companyPlans',
            'shipCompanyPlans',
        ])->first();

        return $this->success(new ModuleResource($company));
    }
}
