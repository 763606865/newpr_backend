<?php

namespace App\B\Controllers;

use App\B\Requests\EmployeeRequest;
use App\Enums\EmployeeStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    /**
     * 职工列表
     *
     * GET /b/employees
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $this->company()->id;
        $keyword = trim((string) $request->input('keyword', ''));
        $status = $request->input('status');

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->with([
                'user:id,name,nickname,phone,email,avatar',
                'department:id,name',
                'position:id,name',
            ])
            ->when($keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery->where('real_name', 'like', "%{$keyword}%")
                        ->orWhere('mobile', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('employee_no', 'like', "%{$keyword}%")
                        ->orWhereHas('user', function ($userQuery) use ($keyword): void {
                            $userQuery->where('name', 'like', "%{$keyword}%")
                                ->orWhere('nickname', 'like', "%{$keyword}%")
                                ->orWhere('phone', 'like', "%{$keyword}%");
                        });
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', (int) $status))
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 20));

        return $this->success($employees);
    }

    /**
     * 创建页数据
     *
     * GET /b/employees/create
     *
     * @throws \Exception
     */
    public function create(): JsonResponse
    {
        $companyId = (int) $this->company()->id;

        return $this->success([
            'departments' => Department::query()
                ->where('company_id', $companyId)
                ->orderBy('sort')
                ->orderBy('id')
                ->get(['id', 'parent_id', 'name', 'sort']),
            'positions' => Position::query()
                ->where('company_id', $companyId)
                ->orderBy('sort')
                ->orderBy('id')
                ->get(['id', 'name', 'code', 'sort']),
            'status_options' => [
                ['value' => EmployeeStatus::Active->value, 'label' => EmployeeStatus::Active->getLabel()],
                ['value' => EmployeeStatus::Dismissed->value, 'label' => EmployeeStatus::Dismissed->getLabel()],
            ],
        ]);
    }

    /**
     * 用户远程搜索（用于职工绑定用户）
     *
     * GET /b/employees/search-users
     *
     * @throws \Exception
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $perPage = max(1, min(50, (int) $request->input('per_page', 20)));

        if ($keyword === '') {
            return $this->success([
                'items' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 0],
            ]);
        }

        $users = User::query()
            ->select(['id', 'name', 'nickname', 'phone', 'email', 'avatar'])
            ->where(function ($query) use ($keyword): void {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('nickname', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->success([
            'items' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * 创建职工
     *
     * POST /b/employees
     *
     * @throws \Exception
     */
    public function store(EmployeeRequest $request): JsonResponse
    {
        $companyId = (int) $this->company()->id;
        $validated = $request->validated();
        $validated['company_id'] = $companyId;

        if (blank($validated['employee_no'] ?? null)) {
            $validated['employee_no'] = $this->generateEmployeeNo($companyId);
        }

        $user = User::query()->find($validated['user_id']);
        $validated['real_name'] = $validated['real_name'] ?: ($user?->name ?? '');
        $validated['avatar'] = $validated['avatar'] ?: ($user?->avatar ?? '');
        $validated['mobile'] = $validated['mobile'] ?: ($user?->phone ?? null);
        $validated['email'] = $validated['email'] ?: ($user?->email ?? null);

        $employee = Employee::query()->create($validated)->load([
            'user:id,name,nickname,phone,email,avatar',
            'department:id,name',
            'position:id,name',
        ]);

        return $this->success($employee);
    }

    /**
     * 职工详情
     *
     * GET /b/employees/{id}
     *
     * @throws \Exception
     */
    public function show(string $id): JsonResponse
    {
        $employee = $this->findEmployeeInCurrentCompany($id);

        if (! $employee) {
            return $this->error('职工不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success($employee->load([
            'user:id,name,nickname,phone,email,avatar',
            'department:id,name',
            'position:id,name',
        ]));
    }

    /**
     * 编辑页数据
     *
     * GET /b/employees/{id}/edit
     *
     * @throws \Exception
     */
    public function edit(string $id): JsonResponse
    {
        return $this->show($id);
    }

    /**
     * 更新职工
     *
     * PUT /b/employees/{id}
     *
     * @throws \Exception
     */
    public function update(EmployeeRequest $request, string $id): JsonResponse
    {
        $employee = $this->findEmployeeInCurrentCompany($id);

        if (! $employee) {
            return $this->error('职工不存在。', Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validated();
        $user = User::query()->find($validated['user_id']);
        $validated['real_name'] = $validated['real_name'] ?: ($user?->name ?? $employee->real_name);
        $validated['avatar'] = $validated['avatar'] ?: ($user?->avatar ?? $employee->avatar ?? '');
        $validated['mobile'] = $validated['mobile'] ?: ($user?->phone ?? $employee->mobile);
        $validated['email'] = $validated['email'] ?: ($user?->email ?? $employee->email);

        $employee->fill($validated)->save();

        return $this->success($employee->refresh()->load([
            'user:id,name,nickname,phone,email,avatar',
            'department:id,name',
            'position:id,name',
        ]));
    }

    /**
     * 删除职工
     *
     * DELETE /b/employees/{id}
     *
     * @throws \Exception
     */
    public function destroy(string $id): JsonResponse
    {
        $employee = $this->findEmployeeInCurrentCompany($id);

        if (! $employee) {
            return $this->error('职工不存在。', Response::HTTP_NOT_FOUND);
        }

        $employee->delete();

        return $this->success();
    }

    private function findEmployeeInCurrentCompany(string $id): ?Employee
    {
        /** @var Employee|null $employee */
        $employee = Employee::query()
            ->where('company_id', (int) $this->company()->id)
            ->whereKey($id)
            ->first();

        return $employee;
    }

    private function generateEmployeeNo(int $companyId): string
    {
        do {
            $employeeNo = 'EMP'.now()->format('YmdHis').str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (Employee::query()->withTrashed()->where('company_id', $companyId)->where('employee_no', $employeeNo)->exists());

        return $employeeNo;
    }
}
