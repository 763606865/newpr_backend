<?php

namespace App\B\Controllers;

use App\B\Requests\PositionRequest;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PositionController extends Controller
{
    /**
     * 岗位列表
     *
     * GET /b/positions
     *
     * @throws \Exception
     */
    public function index(): JsonResponse
    {
        $positions = $this->company()->positions()
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return $this->success($positions);
    }

    /**
     * 创建页数据
     *
     * GET /b/positions/create
     *
     * @throws \Exception
     */
    public function create(): JsonResponse
    {
        return $this->success();
    }

    /**
     * 创建岗位
     *
     * POST /b/positions
     *
     * @throws \Exception
     */
    public function store(PositionRequest $request): JsonResponse
    {
        $position = $this->company()->positions()->create($request->validated());

        return $this->success($position);
    }

    /**
     * 岗位详情
     *
     * GET /b/positions/{id}
     *
     * @throws \Exception
     */
    public function show(string $id): JsonResponse
    {
        $position = $this->findPositionInCurrentCompany($id);

        if (! $position) {
            return $this->error('岗位不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success($position);
    }

    /**
     * 编辑页数据
     *
     * GET /b/positions/{id}/edit
     *
     * @throws \Exception
     */
    public function edit(string $id): JsonResponse
    {
        return $this->show($id);
    }

    /**
     * 更新岗位
     *
     * PUT /b/positions/{id}
     *
     * @throws \Exception
     */
    public function update(PositionRequest $request, string $id): JsonResponse
    {
        $position = $this->findPositionInCurrentCompany($id);

        if (! $position) {
            return $this->error('岗位不存在。', Response::HTTP_NOT_FOUND);
        }

        $position->fill($request->validated())->save();

        return $this->success($position->refresh());
    }

    /**
     * 删除岗位
     *
     * DELETE /b/positions/{id}
     *
     * @throws \Exception
     */
    public function destroy(string $id): JsonResponse
    {
        $position = $this->findPositionInCurrentCompany($id);

        if (! $position) {
            return $this->error('岗位不存在。', Response::HTTP_NOT_FOUND);
        }

        $hasEmployees = Employee::query()
            ->where('company_id', $this->company()->id)
            ->where('position_id', $position->id)
            ->exists();

        if ($hasEmployees) {
            return $this->error('该岗位下存在员工，无法删除。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $position->delete();

        return $this->success();
    }

    private function findPositionInCurrentCompany(string $id): ?Position
    {
        return $this->company()->positions()->whereKey($id)->first();
    }
}
