<?php

namespace Tests\Feature\Api;

use App\Enums\AttendanceClockLogClockMethod;
use App\Enums\AttendanceClockState;
use App\Enums\AttendanceScheduleStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_clock_creates_log_and_marks_schedule_missing_card(): void
    {
        [$employee, $schedule, $clockAt] = $this->createClockContext();

        $result = AttendanceService::make()->clock($employee, [
            'clock_method' => AttendanceClockLogClockMethod::App->value,
            'idempotency_key' => 'clock-test-1',
            'punch_time' => $clockAt->toDateTimeString(),
        ]);

        $this->assertFalse($result['idempotent']);
        $this->assertSame(1, $result['punch_type']);
        $this->assertSame(1, $employee->attendanceClockLogs()->count());

        $schedule->refresh();
        $this->assertNotNull($schedule->actual_start_time);
        $this->assertNull($schedule->actual_end_time);
        $this->assertSame(AttendanceScheduleStatus::MissingCard->value, (int) $schedule->status);
    }

    public function test_today_returns_clock_in_state_before_any_clock(): void
    {
        [$employee, $schedule] = $this->createClockContext();

        $this->assertFalse($schedule->has_clocked_in);
        $this->assertFalse($schedule->has_clocked_out);
        $this->assertFalse($schedule->is_clock_finished);
        $this->assertSame(AttendanceClockState::ClockIn, $schedule->clock_state);
        $this->assertSame(1, $schedule->next_punch_type?->value);

        $result = AttendanceService::make()->today($employee, Carbon::parse('2026-05-22 08:00:00'));

        $this->assertTrue($result['has_schedule']);
        $this->assertTrue($result['can_clock']);
        $this->assertSame(AttendanceClockState::ClockIn->value, $result['clock_state']);
        $this->assertSame(1, $result['next_punch_type']);
    }

    public function test_today_returns_clock_out_state_after_first_clock(): void
    {
        [$employee, $schedule, $clockAt] = $this->createClockContext();

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-today-1',
            'punch_time' => $clockAt->toDateTimeString(),
        ]);

        $schedule->refresh();
        $this->assertTrue($schedule->has_clocked_in);
        $this->assertFalse($schedule->has_clocked_out);
        $this->assertFalse($schedule->is_clock_finished);
        $this->assertSame(AttendanceClockState::ClockOut, $schedule->clock_state);
        $this->assertSame(2, $schedule->next_punch_type?->value);

        $result = AttendanceService::make()->today($employee, $clockAt);

        $this->assertSame(AttendanceClockState::ClockOut->value, $result['clock_state']);
        $this->assertSame(2, $result['next_punch_type']);
    }

    public function test_today_returns_finished_state_after_two_clocks(): void
    {
        [$employee, $schedule, $clockAt] = $this->createClockContext();

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-today-2-1',
            'punch_time' => $clockAt->toDateTimeString(),
        ]);

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-today-2-2',
            'punch_time' => $clockAt->copy()->addHours(8)->toDateTimeString(),
        ]);

        $schedule->refresh();
        $this->assertTrue($schedule->has_clocked_in);
        $this->assertTrue($schedule->has_clocked_out);
        $this->assertTrue($schedule->is_clock_finished);
        $this->assertSame(AttendanceClockState::Finished, $schedule->clock_state);
        $this->assertNull($schedule->next_punch_type);

        $result = AttendanceService::make()->today($employee, $clockAt);

        $this->assertSame(AttendanceClockState::Finished->value, $result['clock_state']);
        $this->assertFalse($result['can_clock']);
        $this->assertNull($result['next_punch_type']);
    }

    public function test_same_idempotency_key_will_not_create_duplicate_clock_log(): void
    {
        [$employee] = $this->createClockContext();

        $firstResult = AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-2',
        ]);

        $secondResult = AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-2',
        ]);

        $this->assertFalse($firstResult['idempotent']);
        $this->assertTrue($secondResult['idempotent']);
        $this->assertSame(1, $employee->attendanceClockLogs()->count());
        $this->assertSame($firstResult['clock_log_id'], $secondResult['clock_log_id']);
    }

    public function test_second_clock_generates_clock_out_and_updates_schedule_end_time(): void
    {
        [$employee, $schedule, $clockAt] = $this->createClockContext();

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-3-1',
            'punch_time' => $clockAt->toDateTimeString(),
        ]);

        $clockOutAt = $clockAt->copy()->addHours(8);
        $result = AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-3-2',
            'punch_time' => $clockOutAt->toDateTimeString(),
        ]);

        $this->assertSame(2, $result['punch_type']);

        $schedule->refresh();
        $this->assertNotNull($schedule->actual_end_time);
        $this->assertGreaterThan(0, (float) $schedule->actual_work_hours);
        $this->assertSame(2, $employee->attendanceClockLogs()->count());
    }

    public function test_second_clock_within_five_minutes_will_be_rejected(): void
    {
        [$employee, $schedule, $clockAt] = $this->createClockContext();

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-4-1',
            'punch_time' => $clockAt->toDateTimeString(),
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('上班卡与下班卡至少间隔 5 分钟，请稍后再试。');

        AttendanceService::make()->clock($employee, [
            'idempotency_key' => 'clock-test-4-2',
            'punch_time' => $clockAt->copy()->addMinutes(3)->toDateTimeString(),
        ]);

        $schedule->refresh();
        $this->assertNull($schedule->actual_end_time);
        $this->assertSame(1, $employee->attendanceClockLogs()->count());
    }

    /**
     * @return array{0:Employee,1:AttendanceSchedule,2:Carbon}
     */
    private function createClockContext(): array
    {
        $company = Company::query()->create([
            'name' => '测试企业',
            'credit_code' => 'TEST-COMPANY-'.uniqid('', true),
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
            'name' => '测试用户',
            'phone' => '13'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => uniqid('clock_', true).'@example.com',
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
            'real_name' => '张三',
            'avatar' => 'https://example.com/avatar.png',
            'mobile' => '13'.str_pad((string) random_int(1, 999999999), 9, '0', STR_PAD_LEFT),
            'status' => 1,
            'entry_time' => now(),
        ]);

        $rule = AttendanceRule::query()->create([
            'company_id' => $company->id,
            'name' => '标准工时',
            'code' => 'rule-'.uniqid('', true),
            'work_type' => 1,
            'late_grace_mins' => 0,
            'early_leave_grace_mins' => 0,
            'rest_duration_mins' => 60,
            'status' => 1,
        ]);

        $clockAt = now()->setTime(9, 0, 0);
        $schedule = AttendanceSchedule::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_id' => $employee->id,
            'attendance_rule_id' => $rule->id,
            'date' => $clockAt->toDateString(),
            'std_start_time' => $clockAt->copy()->setTime(9, 0, 0),
            'std_end_time' => $clockAt->copy()->setTime(18, 0, 0),
            'std_work_hours' => 8,
            'is_rest_day' => 0,
            'is_overnight' => 0,
            'work_type' => 1,
            'status' => AttendanceScheduleStatus::Pending->value,
        ]);

        return [$employee, $schedule, $clockAt];
    }
}
