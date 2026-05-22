<?php

namespace Tests\Feature\Console;

use App\Jobs\GenerateCompanyAttendanceSchedulesJob;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceAssignment;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\User;
use App\Services\AttendanceScheduleGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GenerateAttendanceSchedulesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_generation_jobs_grouped_by_company(): void
    {
        [$company] = $this->createGenerationContext();

        Bus::fake();

        $this->artisan('attendance:generate-schedules', [
            'date' => '2026-05-22',
            '--days' => 3,
        ])->assertExitCode(0);

        Bus::assertDispatched(GenerateCompanyAttendanceSchedulesJob::class, function (GenerateCompanyAttendanceSchedulesJob $job) use ($company): bool {
            return $job->companyId === $company->id
                && $job->startDate === '2026-05-22'
                && $job->days === 3
                && $job->employeeId === null
                && $job->force === false;
        });
    }

    public function test_job_generates_future_three_days_schedules_and_rerun_will_not_duplicate(): void
    {
        [$company, $employee, $assignment] = $this->createGenerationContext();

        $job = new GenerateCompanyAttendanceSchedulesJob(
            companyId: $company->id,
            startDate: '2026-05-22',
            days: 3,
        );

        $job->handle(AttendanceScheduleGeneratorService::make());

        $schedules = AttendanceSchedule::query()
            ->where('employee_id', $employee->id)
            ->orderBy('date')
            ->get();

        $this->assertCount(3, $schedules);
        $this->assertSame('2026-05-22', $schedules[0]->date?->toDateString());
        $this->assertSame('2026-05-24', $schedules[2]->date?->toDateString());
        $this->assertSame($assignment->id, $schedules[0]->extra['assignment_id'] ?? null);
        $this->assertFalse($schedules[0]->is_rest_day);
        $this->assertSame('2026-05-22 09:00:00', $schedules[0]->std_start_time?->toDateTimeString());
        $this->assertSame('2026-05-22 18:00:00', $schedules[0]->std_end_time?->toDateTimeString());

        $job->handle(AttendanceScheduleGeneratorService::make());

        $this->assertSame(3, AttendanceSchedule::query()->where('employee_id', $employee->id)->count());
    }

    public function test_job_generates_big_small_week_rest_days(): void
    {
        [$company, $employee] = $this->createGenerationContext([
            'assignment' => [
                'effective_start_date' => '2026-05-18',
                'effective_end_date' => '2026-05-31',
                'start_anchor_date' => '2026-05-19',
                'cycle_type' => 2,
                'work_days' => 6,
                'rest_days' => 1,
                'extra' => [
                    'start_week_type' => 'big',
                    'big_week_rest_weekdays' => [0],
                    'small_week_rest_weekdays' => [6, 0],
                ],
            ],
        ]);

        $job = new GenerateCompanyAttendanceSchedulesJob(
            companyId: $company->id,
            startDate: '2026-05-23',
            days: 9,
        );

        $job->handle(AttendanceScheduleGeneratorService::make());

        $schedules = AttendanceSchedule::query()
            ->where('employee_id', $employee->id)
            ->orderBy('date')
            ->get()
            ->keyBy(fn (AttendanceSchedule $schedule) => $schedule->date?->toDateString());

        $this->assertFalse($schedules['2026-05-23']->is_rest_day);
        $this->assertTrue($schedules['2026-05-24']->is_rest_day);
        $this->assertFalse($schedules['2026-05-25']->is_rest_day);
        $this->assertTrue($schedules['2026-05-30']->is_rest_day);
        $this->assertTrue($schedules['2026-05-31']->is_rest_day);
        $this->assertNull($schedules['2026-05-24']->std_start_time);
        $this->assertNull($schedules['2026-05-30']->std_start_time);
        $this->assertNull($schedules['2026-05-31']->std_start_time);
    }

    /**
     * @param  array{assignment?:array<string, mixed>,rule?:array<string, mixed>}  $overrides
     * @return array{0:Company,1:Employee,2:AttendanceAssignment}
     */
    private function createGenerationContext(array $overrides = []): array
    {
        $company = Company::query()->create([
            'name' => '排班测试企业',
            'credit_code' => 'GEN-COMPANY-'.uniqid('', true),
            'status' => 1,
        ]);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'parent_id' => 0,
            'depth' => 1,
            'name' => '研发部',
            'type' => 1,
            'sort' => 1,
        ]);

        $user = User::query()->forceCreate([
            'name' => '排班测试用户',
            'phone' => '13'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => uniqid('schedule_', true).'@example.com',
            'avatar' => 'https://example.com/avatar.png',
            'password' => bcrypt('password'),
            'status' => 'active',
            'gender' => 0,
        ]);

        $employee = Employee::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_no' => 'E'.random_int(1000, 9999),
            'real_name' => '李四',
            'avatar' => 'https://example.com/avatar.png',
            'mobile' => '13'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'status' => 1,
            'entry_time' => now(),
        ]);

        $rule = AttendanceRule::query()->create(array_merge([
            'company_id' => $company->id,
            'name' => '标准工时',
            'code' => 'rule-'.uniqid('', true),
            'work_type' => 1,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'required_work_hours' => 8,
            'rest_duration_mins' => 60,
            'late_grace_mins' => 0,
            'early_leave_grace_mins' => 0,
            'status' => 1,
        ], $overrides['rule'] ?? []));

        $assignment = AttendanceAssignment::query()->create(array_merge([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_id' => $employee->id,
            'attendance_rule_id' => $rule->id,
            'effective_start_date' => '2026-05-22',
            'effective_end_date' => '2026-05-31',
            'cycle_type' => 1,
            'work_days' => 7,
            'rest_days' => 0,
            'priority' => 10,
            'status' => 1,
        ], $overrides['assignment'] ?? []));

        return [$company, $employee, $assignment];
    }
}
