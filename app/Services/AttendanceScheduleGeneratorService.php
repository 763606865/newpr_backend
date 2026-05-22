<?php

namespace App\Services;

use App\Enums\AttendanceAssignmentCycleType;
use App\Enums\AttendanceRuleWorkType;
use App\Enums\AttendanceScheduleStatus;
use App\Enums\CompanyStatus;
use App\Enums\EmployeeStatus;
use App\Models\Oa\AttendanceAssignment;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AttendanceScheduleGeneratorService extends Service
{
    /**
     * 为指定企业生成未来几天的考勤排班。
     *
     * @return array{company_id:int,start_date:string,end_date:string,days:int,created:int,updated:int,skipped:int}
     */
    public function generateForCompany(int $companyId, Carbon|string $startDate, int $days = 3, ?int $employeeId = null, bool $force = false): array
    {
        $startDate = $startDate instanceof Carbon
            ? $startDate->copy()->startOfDay()
            : Carbon::parse($startDate)->startOfDay();
        $days = max(1, $days);
        $endDate = $startDate->copy()->addDays($days - 1)->endOfDay();

        $assignments = $this->buildAssignmentQuery($companyId, $startDate, $endDate, $employeeId)->get();

        $summary = [
            'company_id' => $companyId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'days' => $days,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $startDate->copy()->addDays($offset);

            foreach ($this->resolveAssignmentsForDate($assignments, $date) as $assignment) {
                $result = $this->upsertSchedule($assignment, $date, $force);
                $summary[$result]++;
            }
        }

        return $summary;
    }

    /**
     * @return Builder<AttendanceAssignment>
     */
    private function buildAssignmentQuery(int $companyId, Carbon $startDate, Carbon $endDate, ?int $employeeId)
    {
        return AttendanceAssignment::query()
            ->with([
                'attendanceRule',
                'employee',
            ])
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->whereDate('effective_start_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate): void {
                $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $startDate->toDateString());
            })
            ->whereHas('company', fn ($query) => $query->where('status', CompanyStatus::Enabled->value))
            ->whereHas('employee', fn ($query) => $query->where('status', EmployeeStatus::Active->value))
            ->whereHas('attendanceRule', fn ($query) => $query->where('status', 1))
            ->orderByDesc('priority')
            ->orderByDesc('id');
    }

    /**
     * @param  Collection<int, AttendanceAssignment>  $assignments
     * @return array<int, AttendanceAssignment>
     */
    private function resolveAssignmentsForDate(Collection $assignments, Carbon $date): array
    {
        $selectedAssignments = [];

        foreach ($assignments as $assignment) {
            if (! $this->isAssignmentEffectiveOnDate($assignment, $date)) {
                continue;
            }

            $selectedAssignments[$assignment->employee_id] ??= $assignment;
        }

        return array_values($selectedAssignments);
    }

    private function isAssignmentEffectiveOnDate(AttendanceAssignment $assignment, Carbon $date): bool
    {
        if ($assignment->effective_start_date->gt($date)) {
            return false;
        }

        return ! $assignment->effective_end_date || $assignment->effective_end_date->gte($date);
    }

    private function upsertSchedule(AttendanceAssignment $assignment, Carbon $date, bool $force): string
    {
        $payload = $this->buildSchedulePayload($assignment, $date);

        $schedule = AttendanceSchedule::query()
            ->where('company_id', $assignment->company_id)
            ->where('employee_id', $assignment->employee_id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if (! $schedule) {
            AttendanceSchedule::query()->create($payload);

            return 'created';
        }

        if (! $force) {
            return 'skipped';
        }

        if ($schedule->attendanceClockLogs()->exists()) {
            return 'skipped';
        }

        $schedule->fill($payload)->save();

        return 'updated';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchedulePayload(AttendanceAssignment $assignment, Carbon $date): array
    {
        /** @var AttendanceRule $rule */
        $rule = $assignment->attendanceRule;
        $isRestDay = $this->resolveRestDay($assignment, $date);

        $stdStartTime = null;
        $stdEndTime = null;
        $stdWorkHours = 0;
        $isOvernight = false;

        if (! $isRestDay) {
            $stdStartTime = $this->combineDateAndTime($date, $rule->start_time);
            $stdEndTime = $this->combineDateAndTime($date, $rule->end_time);
            $isOvernight = (bool) $rule->is_overnight;

            if ($stdStartTime && $stdEndTime && ($isOvernight || $stdEndTime->lessThanOrEqualTo($stdStartTime))) {
                $stdEndTime = $stdEndTime->copy()->addDay();
                $isOvernight = true;
            }

            $stdWorkHours = $this->resolveStdWorkHours($rule, $stdStartTime, $stdEndTime);
        }

        return [
            'company_id' => $assignment->company_id,
            'department_id' => $assignment->department_id,
            'employee_id' => $assignment->employee_id,
            'attendance_rule_id' => $assignment->attendance_rule_id,
            'date' => $date->toDateString(),
            'std_start_time' => $stdStartTime,
            'std_end_time' => $stdEndTime,
            'std_work_hours' => $stdWorkHours,
            'is_rest_day' => $isRestDay,
            'is_overnight' => $isOvernight,
            'work_type' => $rule->work_type instanceof AttendanceRuleWorkType ? $rule->work_type->value : (int) $rule->work_type,
            'actual_start_time' => null,
            'actual_end_time' => null,
            'actual_work_hours' => 0,
            'status' => AttendanceScheduleStatus::Pending->value,
            'late_mins' => 0,
            'early_leave_mins' => 0,
            'absence_mins' => 0,
            'extra' => [
                'generator' => 'attendance:generate-schedules',
                'assignment_id' => $assignment->id,
                'cycle_type' => $assignment->cycle_type instanceof AttendanceAssignmentCycleType ? $assignment->cycle_type->value : (int) $assignment->cycle_type,
                'generated_at' => now()->toDateTimeString(),
            ],
        ];
    }

    private function resolveRestDay(AttendanceAssignment $assignment, Carbon $date): bool
    {
        $cycleType = $assignment->cycle_type instanceof AttendanceAssignmentCycleType
            ? $assignment->cycle_type
            : AttendanceAssignmentCycleType::from((int) $assignment->cycle_type);

        return match ($cycleType) {
            AttendanceAssignmentCycleType::Fixed => false,
            AttendanceAssignmentCycleType::Shift => $this->resolveShiftRestDay($assignment, $date),
            AttendanceAssignmentCycleType::Do_X_Rest_Y => $this->resolveDoXRestYRestDay($assignment, $date),
        };
    }

    private function resolveShiftRestDay(AttendanceAssignment $assignment, Carbon $date): bool
    {
        $extra = is_array($assignment->extra) ? $assignment->extra : [];
        $anchorDate = ($assignment->start_anchor_date ?? $assignment->effective_start_date)
            ->copy()
            ->startOfWeek(Carbon::MONDAY);
        $currentWeekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekOffset = (int) floor($anchorDate->diffInDays($currentWeekStart, false) / 7);

        if ($weekOffset < 0) {
            return false;
        }

        $startWeekType = (string) ($extra['start_week_type'] ?? 'big');
        $isBigWeek = $weekOffset % 2 === 0;

        if ($startWeekType === 'small') {
            $isBigWeek = ! $isBigWeek;
        }

        $restWeekdays = $isBigWeek
            ? $this->normalizeWeekdays($extra['big_week_rest_weekdays'] ?? [Carbon::SUNDAY])
            : $this->normalizeWeekdays($extra['small_week_rest_weekdays'] ?? [Carbon::SATURDAY, Carbon::SUNDAY]);

        return in_array($date->dayOfWeek, $restWeekdays, true);
    }

    private function resolveDoXRestYRestDay(AttendanceAssignment $assignment, Carbon $date): bool
    {
        $anchorDate = ($assignment->start_anchor_date ?? $assignment->effective_start_date)->copy()->startOfDay();
        $offsetDays = $anchorDate->diffInDays($date->copy()->startOfDay(), false);

        if ($offsetDays < 0) {
            return false;
        }

        $workDays = max(1, (int) $assignment->work_days);
        $restDays = max(0, (int) $assignment->rest_days);

        if ($restDays === 0) {
            return false;
        }

        $cycleLength = $workDays + $restDays;
        $cycleDay = $offsetDays % $cycleLength;

        return $cycleDay >= $workDays;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeWeekdays(mixed $weekdays): array
    {
        if (! is_array($weekdays)) {
            return [Carbon::SUNDAY];
        }

        $normalized = array_values(array_unique(array_filter(
            array_map(static fn ($weekday): int => (int) $weekday, $weekdays),
            static fn (int $weekday): bool => $weekday >= Carbon::SUNDAY && $weekday <= Carbon::SATURDAY,
        )));

        return $normalized === [] ? [Carbon::SUNDAY] : $normalized;
    }

    private function combineDateAndTime(Carbon $date, ?string $time): ?Carbon
    {
        if (! $time) {
            return null;
        }

        return Carbon::parse($date->toDateString().' '.$time);
    }

    private function resolveStdWorkHours(AttendanceRule $rule, ?Carbon $stdStartTime, ?Carbon $stdEndTime): float
    {
        if ($rule->required_work_hours !== null) {
            return (float) $rule->required_work_hours;
        }

        if (! $stdStartTime || ! $stdEndTime) {
            return 0;
        }

        $workedMins = max(0, $stdStartTime->diffInMinutes($stdEndTime, false));
        $restDurationMins = (int) $rule->rest_duration_mins;

        return round(max(0, $workedMins - $restDurationMins) / 60, 2);
    }
}
