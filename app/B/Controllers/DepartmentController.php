<?php

namespace App\B\Controllers;

use App\B\Requests\DepartmentRequest;
use App\Exceptions\BadRequestException;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    /**
     * 部门列表
     *
     * GET /b/departments
     *
     * @param Request $request
     * @return JsonResponse
     * @throws BadRequestException
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->company();

        $departments = $company->departments()
            ->when($request->boolean('with_employees'), fn ($query) => $query->with('employees'))
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        if ($request->boolean('flat')) {
            return $this->success($departments);
        }

        return $this->success($this->buildDepartmentTree($departments));
    }

    /**
     * 创建部门
     *
     * POST /b/departments
     *
     * @param DepartmentRequest $request
     * @return JsonResponse
     * @throws BadRequestException
     * @throws \Exception
     */
    public function store(DepartmentRequest $request): JsonResponse
    {
        $company = $this->company();

        $validated = $request->validated();
        $department = $company->departments()->create($validated);

        return $this->success($department);
    }

    /**
     * 部门详情
     *
     * GET /b/departments/{id}
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function show(string $id): JsonResponse
    {
        $department = $this->findDepartmentInCurrentCompany($id);

        if (! $department) {
            return $this->error('部门不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success($department);
    }

    /**
     * 编辑页数据
     *
     * GET /b/departments/{id}/edit
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function edit(string $id): JsonResponse
    {
        return $this->show($id);
    }

    /**
     * 更新部门
     *
     * PUT /b/departments/{id}
     *
     * @param DepartmentRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(DepartmentRequest $request, string $id): JsonResponse
    {
        $department = $this->findDepartmentInCurrentCompany($id);

        if (! $department) {
            return $this->error('部门不存在。', Response::HTTP_NOT_FOUND);
        }

        $department->fill($request->validated())->save();

        return $this->success($department->refresh());
    }

    /**
     * 删除部门
     *
     * DELETE /b/departments/{id}
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(string $id): JsonResponse
    {
        $department = $this->findDepartmentInCurrentCompany($id);

        if (! $department) {
            return $this->error('部门不存在。', Response::HTTP_NOT_FOUND);
        }

        if ($department->children()->exists()) {
            return $this->error('请先删除子部门。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $department->delete();

        return $this->success();
    }

    private function findDepartmentInCurrentCompany(string $id): ?Department
    {
        $company = $this->company();

        return $company->departments()->whereKey($id)->first();
    }

    private function buildDepartmentTree(Collection $departments, int $parentId = 0): Collection
    {
        return $departments
            ->where('parent_id', $parentId)
            ->values()
            ->map(function (Department $department) use ($departments): Department {
                $children = $this->buildDepartmentTree($departments, (int) $department->id);
                $department->setRelation('children', $children);

                return $department;
            });
    }
}
