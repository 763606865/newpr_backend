<?php

namespace App\B\Controllers;

use App\Enums\AttendanceScheduleStatus;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceScheduleController extends Controller
{
    /**
     * 考勤记录列表
     *
     * GET /b/attendance-schedules
     *
     * @throws \Exception
     */
    public function index(Request $request): JsonResponse
    {
        $monthRange = $this->resolveMonthRange($request);
        $attendanceSchedules = $this->buildQuery($request, $monthRange)
            ->paginate($this->getPerPage($request));

        return $this->success([
            'month' => [
                'month' => $monthRange['month'],
                'start_date' => $monthRange['start_date'],
                'end_date' => $monthRange['end_date'],
            ],
            'list' => $attendanceSchedules,
        ]);
    }

    /**
     * 考勤记录详情
     *
     * GET /b/attendance-schedules/{id}
     *
     * @throws \Exception
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $employee = $this->findEmployeeInCurrentCompany($id);

        if (! $employee) {
            return $this->error('员工不存在。', Response::HTTP_NOT_FOUND);
        }

        $monthRange = $this->resolveMonthRange($request);
        $query = $this->buildEmployeeScheduleQuery($employee->id, $monthRange);

        $schedules = $query
            ->orderBy('date')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'department_id',
                'attendance_rule_id',
                'date',
                'std_start_time',
                'std_end_time',
                'std_work_hours',
                'actual_start_time',
                'actual_end_time',
                'actual_work_hours',
                'status',
                'is_rest_day',
                'late_mins',
                'early_leave_mins',
                'absence_mins',
            ]);

        return $this->success([
            'month' => [
                'month' => $monthRange['month'],
                'start_date' => $monthRange['start_date'],
                'end_date' => $monthRange['end_date'],
            ],
            'employee' => $employee->load([
                'department:id,name',
                'position:id,name',
            ]),
            'summary' => $this->buildSummary((clone $query)),
            'schedules' => $schedules,
        ]);
    }

    /**
     * 导出月度考勤明细
     *
     * GET /b/attendance-schedules/export
     *
     * @throws \Exception
     */
    public function export(Request $request): StreamedResponse
    {
        $monthRange = $this->resolveMonthRange($request);
        $fileName = sprintf('attendance-schedules-%s.csv', $monthRange['month']);

        $query = $this->buildExportQuery($request, $monthRange);

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                '日期',
                '部门',
                '员工工号',
                '员工姓名',
                '考勤规则',
                '状态',
                '是否休息日',
                '标准工时',
                '实际工时',
                '迟到分钟',
                '早退分钟',
                '缺勤分钟',
            ]);

            $query->chunkById(200, function ($schedules) use ($handle): void {
                foreach ($schedules as $schedule) {
                    fputcsv($handle, [
                        $schedule->date?->toDateString(),
                        $schedule->department?->name ?? '',
                        $schedule->employee?->employee_no ?? '',
                        $schedule->employee?->real_name ?? '',
                        $schedule->attendanceRule?->name ?? '',
                        $this->resolveStatusLabel((int) $schedule->status),
                        $schedule->is_rest_day ? '是' : '否',
                        (string) ($schedule->std_work_hours ?? 0),
                        (string) ($schedule->actual_work_hours ?? 0),
                        (int) $schedule->late_mins,
                        (int) $schedule->early_leave_mins,
                        (int) $schedule->absence_mins,
                    ]);
                }
            }, 'id');

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function findEmployeeInCurrentCompany(string $id): ?Employee
    {
        return Employee::query()
            ->where('company_id', $this->company()->id)
            ->whereKey($id)
            ->first(['id', 'company_id', 'department_id', 'position_id', 'employee_no', 'real_name', 'mobile']);
    }

    /**
     * @param  array{month:string,start_date:string,end_date:string}  $monthRange
     */
    private function buildQuery(Request $request, array $monthRange): Builder
    {
        $query = Employee::query()
            ->where('company_id', $this->company()->id)
            ->with([
                'department:id,name',
            ]);

        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->where(function (Builder $subQuery) use ($keyword): void {
                $subQuery->where('employee_no', 'like', "%{$keyword}%")
                    ->orWhere('real_name', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%");
            });
        }

        $request->whenFilled('employee_no', fn ($value) => $query->where('employee_no', 'like', "%{$value}%"));
        $request->whenFilled('real_name', fn ($value) => $query->where('real_name', 'like', "%{$value}%"));
        $request->whenFilled('department_id', fn ($value) => $query->where('department_id', (int) $value));
        $request->whenFilled('employee_id', fn ($value) => $query->whereKey((int) $value));
        $status = $request->filled('status') ? (int) $request->input('status') : null;

        $query->whereHas('attendanceSchedules', function (Builder $scheduleQuery) use ($monthRange, $status): void {
            $scheduleQuery->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']]);

            if ($status !== null) {
                $scheduleQuery->where('status', $status);
            }
        });

        $betweenScope = function (Builder $scheduleQuery) use ($monthRange): void {
            $scheduleQuery->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']]);
        };

        $query->withCount([
            'attendanceSchedules as schedule_days' => fn (Builder $scheduleQuery) => $betweenScope($scheduleQuery),
            'attendanceSchedules as normal_days' => fn (Builder $scheduleQuery) => $scheduleQuery
                ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
                ->where('status', AttendanceScheduleStatus::Normal->value),
            'attendanceSchedules as late_days' => fn (Builder $scheduleQuery) => $scheduleQuery
                ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
                ->where('status', AttendanceScheduleStatus::Late->value),
            'attendanceSchedules as early_days' => fn (Builder $scheduleQuery) => $scheduleQuery
                ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
                ->where('status', AttendanceScheduleStatus::Early->value),
            'attendanceSchedules as missing_card_days' => fn (Builder $scheduleQuery) => $scheduleQuery
                ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
                ->where('status', AttendanceScheduleStatus::MissingCard->value),
            'attendanceSchedules as absence_days' => fn (Builder $scheduleQuery) => $scheduleQuery
                ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
                ->where('status', AttendanceScheduleStatus::Absence->value),
        ])->withSum([
            'attendanceSchedules as total_actual_work_hours' => fn (Builder $scheduleQuery) => $betweenScope($scheduleQuery),
        ], 'actual_work_hours');

        return $query
            ->orderByDesc('id');
    }

    /**
     * @param  array{month:string,start_date:string,end_date:string}  $monthRange
     */
    private function buildEmployeeScheduleQuery(int $employeeId, array $monthRange): Builder
    {
        return $this->company()->attendanceSchedules()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']])
            ->with([
                'department:id,name',
                'employee:id,employee_no,real_name,mobile',
                'attendanceRule:id,name,code',
            ]);
    }

    /**
     * @param  array{month:string,start_date:string,end_date:string}  $monthRange
     */
    private function buildExportQuery(Request $request, array $monthRange): Builder
    {
        $query = $this->company()->attendanceSchedules()
            ->with([
                'department:id,name',
                'employee:id,employee_no,real_name,mobile,department_id',
                'attendanceRule:id,name,code',
            ])
            ->whereBetween('date', [$monthRange['start_date'], $monthRange['end_date']]);

        $keyword = trim((string) $request->input('keyword', ''));

        if ($keyword !== '') {
            $query->whereHas('employee', function (Builder $employeeQuery) use ($keyword): void {
                $employeeQuery->where('employee_no', 'like', "%{$keyword}%")
                    ->orWhere('real_name', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%");
            });
        }

        $request->whenFilled('employee_id', fn ($value) => $query->where('employee_id', (int) $value));
        $request->whenFilled('department_id', fn ($value) => $query->where('department_id', (int) $value));
        $request->whenFilled('status', fn ($value) => $query->where('status', (int) $value));

        return $query
            ->orderByDesc('id');
    }

    /**
     * @return array{month:string,start_date:string,end_date:string}
     */
    private function resolveMonthRange(Request $request): array
    {
        $month = trim((string) $request->input('month', ''));

        try {
            $currentMonth = $month !== ''
                ? CarbonImmutable::createFromFormat('Y-m', $month)
                : CarbonImmutable::now();
        } catch (\Throwable) {
            $currentMonth = CarbonImmutable::now();
        }

        return [
            'month' => $currentMonth->format('Y-m'),
            'start_date' => $currentMonth->startOfMonth()->toDateString(),
            'end_date' => $currentMonth->endOfMonth()->toDateString(),
        ];
    }

    private function buildSummary(Builder $query): array
    {
        return [
            'schedule_days' => (clone $query)->count(),
            'normal_days' => (clone $query)->where('status', AttendanceScheduleStatus::Normal->value)->count(),
            'late_days' => (clone $query)->where('status', AttendanceScheduleStatus::Late->value)->count(),
            'early_days' => (clone $query)->where('status', AttendanceScheduleStatus::Early->value)->count(),
            'missing_card_days' => (clone $query)->where('status', AttendanceScheduleStatus::MissingCard->value)->count(),
            'absence_days' => (clone $query)->where('status', AttendanceScheduleStatus::Absence->value)->count(),
            'total_late_mins' => (int) ((clone $query)->sum('late_mins') ?? 0),
            'total_early_leave_mins' => (int) ((clone $query)->sum('early_leave_mins') ?? 0),
            'total_absence_mins' => (int) ((clone $query)->sum('absence_mins') ?? 0),
            'total_actual_work_hours' => (float) ((clone $query)->sum('actual_work_hours') ?? 0),
        ];
    }

    private function resolveStatusLabel(int $status): string
    {
        $label = AttendanceScheduleStatus::tryFrom($status)?->getLabel();

        if ($label instanceof HtmlString) {
            return $label->toHtml();
        }

        return is_string($label) ? $label : '未知';
    }
}
