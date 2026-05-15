<?php

namespace App\B\Controllers;

use App\B\Requests\CompanyRequest;
use App\Services\BUserService;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * 企业列表
     *
     * GET /b/companies
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->user();

        return $this->success($user->companies()->get());
    }

    /**
     * Show the form for creating a new resource.
     *
     * GET /b/companies/create
     *
     * @throws \Exception
     */
    public function create(Request $request): JsonResponse
    {
        return $this->success();
    }

    /**
     * 企业入驻
     *
     * POST /b/companies
     *
     * @throws \Exception
     */
    public function store(CompanyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $this->user();
        $company = CompanyService::make()->create($validated);
        BUserService::make()->syncCompany($user, $company);

        return $this->success($company);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
