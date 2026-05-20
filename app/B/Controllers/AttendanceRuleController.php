<?php

namespace App\B\Controllers;

use App\B\Requests\AttendanceRuleRequest;
use App\Enums\AttendanceRuleWorkType;
use App\Exceptions\BadRequestException;
use App\Models\Department;
use App\Models\Oa\AttendanceAssignment;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceRuleController extends Controller
{
    /**
     * 考勤规则列表
     *
     * GET /b/attendance-rules
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $attendanceRules = $this->buildQuery($request)
            ->paginate((int) $request->input('per_page', 20));

        return $this->success($attendanceRules);
    }

    /**
     * 创建页数据
     *
     * GET /b/attendance-rules/create
     *
     * @return JsonResponse
     * @throws BadRequestException
     * @throws \Exception
     */
    public function create(): JsonResponse
    {
        return $this->success([
            'departments' => Department::query()
                ->where('company_id', $this->company()->id)
                ->orderBy('sort')
                ->orderBy('id')
                ->get(['id', 'name', 'sort']),
            'work_type_options' => AttendanceRuleWorkType::cases(),
            'status_options' => [
                ['value' => 1, 'label' => '启用'],
                ['value' => 0, 'label' => '停用'],
            ],
        ]);
    }

    /**
     * 创建考勤规则
     *
     * POST /b/attendance-rules
     *
     * @param AttendanceRuleRequest $request
     * @return JsonResponse
     * @throws BadRequestException
     */
    public function store(AttendanceRuleRequest $request): JsonResponse
    {
        $attendanceRule = $this->company()->attendanceRules()->create($request->validated());

        return $this->success($attendanceRule);
    }

    /**
     * 考勤规则详情
     *
     * GET /b/attendance-rules/{id}
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function show(string $id): JsonResponse
    {
        $attendanceRule = $this->findAttendanceRuleInCurrentCompany($id);

        if (! $attendanceRule) {
            return $this->error('考勤规则不存在。', Response::HTTP_NOT_FOUND);
        }

        return $this->success($attendanceRule);
    }

    /**
     * 编辑页数据
     *
     * GET /b/attendance-rules/{id}/edit
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
     * 更新考勤规则
     *
     * PUT /b/attendance-rules/{id}
     *
     * @param AttendanceRuleRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(AttendanceRuleRequest $request, string $id): JsonResponse
    {
        $attendanceRule = $this->findAttendanceRuleInCurrentCompany($id);

        if (! $attendanceRule) {
            return $this->error('考勤规则不存在。', Response::HTTP_NOT_FOUND);
        }

        $attendanceRule->fill($request->validated())->save();

        return $this->success($attendanceRule->refresh());
    }

    /**
     * 删除考勤规则
     *
     * DELETE /b/attendance-rules/{id}
     *
     * @param string $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(string $id): JsonResponse
    {
        $attendanceRule = $this->findAttendanceRuleInCurrentCompany($id);

        if (! $attendanceRule) {
            return $this->error('考勤规则不存在。', Response::HTTP_NOT_FOUND);
        }

        $hasAssignments = AttendanceAssignment::query()
            ->where('attendance_rule_id', $attendanceRule->id)
            ->exists();

        if ($hasAssignments) {
            return $this->error('该考勤规则已被排班分配，无法删除。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $hasSchedules = AttendanceSchedule::query()
            ->where('attendance_rule_id', $attendanceRule->id)
            ->exists();

        if ($hasSchedules) {
            return $this->error('该考勤规则下存在考勤记录，无法删除。', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $attendanceRule->delete();

        return $this->success();
    }

    private function findAttendanceRuleInCurrentCompany(string $id): ?AttendanceRule
    {
        return $this->company()->attendanceRules()->whereKey($id)->first();
    }

    private function buildQuery(Request $request): HasMany
    {
        $query = $this->company()->attendanceRules();
        $keyword = trim((string) $request->input('keyword', ''));

        $request->whenFilled('name', function ($name) use ($query): void {
            $query->where('name', 'like', "%{$name}%");
        });

        $request->whenFilled('code', function ($code) use ($query): void {
            $query->where('code', 'like', "%{$code}%");
        });

        if ($keyword !== '') {
            $query->where(function (Builder $subQuery) use ($keyword): void {
                $subQuery->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        $request->whenFilled('work_type', fn ($value) => $query->where('work_type', (int) $value));
        $request->whenFilled('status', fn ($value) => $query->where('status', (int) $value));

        return $query->orderByDesc('id');
    }
}
