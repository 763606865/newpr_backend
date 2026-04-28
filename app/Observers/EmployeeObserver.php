<?php

namespace App\Observers;

use App\Enums\AttendanceAssignmentCycleType;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\Employee;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        if (blank($employee->company_id) || blank($employee->department_id)) {
            return;
        }

        $attendanceRuleIds = AttendanceRule::query()
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->whereJsonContains('applicable_scope->department_ids', (int) $employee->department_id)
            ->pluck('id')
            ->all();

        if (blank($attendanceRuleIds)) {
            return;
        }

        $effectiveStartDate = now()->toDateString();

        $employee->attendanceAssignments()->createMany(
            collect($attendanceRuleIds)
                ->map(fn (int $attendanceRuleId): array => [
                    'company_id' => $employee->company_id,
                    'department_id' => $employee->department_id,
                    'employee_id' => $employee->id,
                    'attendance_rule_id' => $attendanceRuleId,
                    'effective_start_date' => $effectiveStartDate,
                    'effective_end_date' => null,
                    'cycle_type' => AttendanceAssignmentCycleType::Fixed->value,
                    'work_days' => 7,
                    'rest_days' => 0,
                    'start_anchor_date' => null,
                    'priority' => 0,
                    'status' => 1,
                ])
                ->all()
        );
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        //
    }

    /**
     * Handle the Employee "force deleted" event.
     */
    public function forceDeleted(Employee $employee): void
    {
        //
    }
}
