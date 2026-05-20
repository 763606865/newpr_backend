<?php

namespace App\B\Controllers;

use App\B\Requests\LeaveTypeRequest;
use App\Enums\LeaveTypeDeductionType;
use App\Exceptions\BadRequestException;
use App\Models\Oa\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeaveTypeController extends Controller
{
    /**
     * 假期类型列表
     *
     * GET /b/leave-types
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $leaveTypes = $this->buildQuery($request)
            ->paginate($this->getPerPage($request));

        return $this->success($leaveTypes);
    }

    /**
     * 创建页数据
     *
     * GET /b/leave-types/create
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(): JsonResponse
    {
        return $this->success([
            'deduction_type_options' => [
                ['value' => LeaveTypeDeductionType::Full->value, 'label' => LeaveTypeDeductionType::Full->getLabel()],
                ['value' => LeaveTypeDeductionType::Half->value, 'label' => LeaveTypeDeductionType::Half->getLabel()],
                ['value' => LeaveTypeDeductionType::None->value, 'label' => LeaveTypeDeductionType::None->getLabel()],
            ],
            'unit_type_options' => [
                ['value' => 1, 'label' => '按天'],
                ['value' => 2, 'label' => '按小时'],
            ],
            'status_options' => [
                ['value' => 1, 'label' => '启用'],
                ['value' => 0, 'label' => '停用'],
            ],
        ]);
    }

    /**
     * 创建假期类型
     *
     * POST /b/leave-types
     *
     * @param LeaveTypeRequest $request
     * @return JsonResponse
     * @throws BadRequestException
     */
    public function store(LeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->company()->leaveTypes()->create($request->validated());

        return $this->success($leaveType);
    }

    /**
     * 假期类型详情
     *
     * GET /b/leave-types/{id}
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function show(string $id): JsonResponse
    {
        $leaveType = $this->findLeaveTypeInCurrentCompany($id);

        if (! $leaveType) {
            return $this->error('假期类型不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success($leaveType);
    }

    /**
     * 编辑页数据
     *
     * GET /b/leave-types/{id}/edit
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
     * 更新假期类型
     *
     * PUT /b/leave-types/{id}
     *
     * @param LeaveTypeRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(LeaveTypeRequest $request, string $id): JsonResponse
    {
        $leaveType = $this->findLeaveTypeInCurrentCompany($id);

        if (! $leaveType) {
            return $this->error('假期类型不存在。', Response::HTTP_NOT_FOUND);
        }

        $leaveType->fill($request->validated())->save();

        return $this->success($leaveType->refresh());
    }

    /**
     * 删除假期类型
     *
     * DELETE /b/leave-types/{id}
     *
     * @throws \Exception
     */
    public function destroy(string $id): JsonResponse
    {
        $leaveType = $this->findLeaveTypeInCurrentCompany($id);

        if (! $leaveType) {
            return $this->error('假期类型不存在。', Response::HTTP_NOT_FOUND);
        }

        $hasLeaveBalances = $leaveType->leaveBalances()
            ->exists();

        if ($hasLeaveBalances) {
            return $this->error('该假期类型已有关联额度记录，无法删除。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $leaveType->delete();

        return $this->success();
    }

    private function findLeaveTypeInCurrentCompany(string $id): ?LeaveType
    {
        return $this->company()->leaveTypes()->whereKey($id)->first();
    }

    private function buildQuery(Request $request): HasMany
    {
        $query = $this->company()->leaveTypes();
        $keyword = trim((string) $request->input('keyword', ''));

        $request->whenFilled('name', fn ($name) => $query->where('name', 'like', "%{$name}%"));
        $request->whenFilled('code', fn ($code) => $query->where('code', 'like', "%{$code}%"));

        if ($keyword !== '') {
            $query->where(function (Builder $subQuery) use ($keyword): void {
                $subQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $request->whenFilled('deduction_type', fn ($value) => $query->where('deduction_type', (int) $value));
        $request->whenFilled('unit_type', fn ($value) => $query->where('unit_type', (int) $value));
        $request->whenFilled('status', fn ($value) => $query->where('status', (int) $value));

        return $query->orderByDesc('id');
    }
}
