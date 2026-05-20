<?php

namespace Tests\Feature\B;

use App\Enums\AttendanceScheduleStatus;
use App\Models\BUser;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceAssignment;
use App\Models\Oa\AttendanceRule;
use App\Models\Oa\AttendanceSchedule;
use App\Models\Oa\LeaveBalance;
use App\Models\Oa\LeaveType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Tests\TestCase;

class OaModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_attendance_rules_list_filters_and_delete_guard_work(): void
    {
        $company = Company::query()->create(['name' => '测试企业A']);
        $this->authenticateForCompany($company);

        $otherCompany = Company::query()->create(['name' => '测试企业B']);

        $targetRule = AttendanceRule::query()->create([
            'company_id' => $company->id,
            'name' => '行政班',
            'code' => 'AR-HR-001',
            'work_type' => 1,
            'status' => 1,
        ]);
        AttendanceRule::query()->create([
            'company_id' => $company->id,
            'name' => '夜班',
            'code' => 'AR-NIGHT-001',
            'work_type' => 2,
            'status' => 0,
        ]);
        AttendanceRule::query()->create([
            'company_id' => $otherCompany->id,
            'name' => '行政班',
            'code' => 'AR-HR-OTHER',
            'work_type' => 1,
            'status' => 1,
        ]);

        $listResponse = $this->getJson('/b/attendance-rules?keyword=HR-001&status=1&per_page=10');

        $listResponse->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $targetRule->id);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => '人事部',
            'type' => 1,
        ]);
        $employee = $this->createEmployee($company, $department);

        AttendanceAssignment::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_id' => $employee->id,
            'attendance_rule_id' => $targetRule->id,
            'effective_start_date' => '2026-01-01',
            'cycle_type' => 1,
            'work_days' => 5,
            'rest_days' => 2,
            'priority' => 1,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('oa_attendance_assignments', [
            'company_id' => $company->id,
            'attendance_rule_id' => $targetRule->id,
        ]);

        $deleteResponse = $this->deleteJson('/b/attendance-rules/'.$targetRule->id);

        $deleteResponse->assertStatus(422)
            ->assertJsonPath('message', '该考勤规则已被排班分配，无法删除。');
    }

    public function test_attendance_schedules_create_and_filter_work(): void
    {
        $company = Company::query()->create(['name' => '测试企业']);
        $this->authenticateForCompany($company);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => '运营部',
            'type' => 1,
        ]);
        $employee = $this->createEmployee($company, $department);

        $attendanceRule = AttendanceRule::query()->create([
            'company_id' => $company->id,
            'name' => '标准工时',
            'code' => 'AR-STD-001',
            'work_type' => 1,
            'status' => 1,
        ]);

        $createResponse = $this->postJson('/b/attendance-schedules', [
            'department_id' => $department->id,
            'employee_id' => $employee->id,
            'attendance_rule_id' => $attendanceRule->id,
            'date' => '2026-05-20',
            'status' => AttendanceScheduleStatus::Late->value,
            'late_mins' => 10,
            'actual_work_hours' => 7.5,
        ]);

        $createResponse->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.employee.id', $employee->id)
            ->assertJsonPath('data.attendance_rule_id', $attendanceRule->id);

        AttendanceSchedule::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_id' => $employee->id,
            'attendance_rule_id' => $attendanceRule->id,
            'date' => '2026-05-01',
            'status' => AttendanceScheduleStatus::Normal->value,
        ]);

        $listResponse = $this->getJson('/b/attendance-schedules?status=2&date_from=2026-05-10&date_to=2026-05-31');

        $listResponse->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.status', AttendanceScheduleStatus::Late->value);
    }

    public function test_leave_types_filter_and_delete_guard_work(): void
    {
        $company = Company::query()->create(['name' => '测试企业']);
        $this->authenticateForCompany($company);

        $annualLeave = LeaveType::query()->create([
            'company_id' => $company->id,
            'name' => '年假',
            'code' => 'ANNUAL',
            'deduction_type' => 1,
            'unit_type' => 1,
            'min_duration' => 0.5,
            'status' => 1,
        ]);
        LeaveType::query()->create([
            'company_id' => $company->id,
            'name' => '病假',
            'code' => 'SICK',
            'deduction_type' => 2,
            'unit_type' => 1,
            'min_duration' => 1,
            'status' => 0,
        ]);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => '行政部',
            'type' => 1,
        ]);
        $employee = $this->createEmployee($company, $department);

        LeaveBalance::query()->create([
            'company_id' => $company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $annualLeave->id,
            'year' => 2026,
            'valid_start_date' => '2026-01-01',
            'valid_end_date' => '2026-12-31',
            'total_days' => 10,
            'used_days' => 1,
            'balance_days' => 9,
        ]);

        $this->assertDatabaseHas('oa_leave_balances', [
            'company_id' => $company->id,
            'leave_type_id' => $annualLeave->id,
        ]);

        $listResponse = $this->getJson('/b/leave-types?keyword=ANNUAL&status=1');

        $listResponse->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $annualLeave->id);

        $deleteResponse = $this->deleteJson('/b/leave-types/'.$annualLeave->id);

        $deleteResponse->assertStatus(422)
            ->assertJsonPath('message', '该假期类型已有关联额度记录，无法删除。');
    }

    private function authenticateForCompany(Company $company): void
    {
        $user = BUser::query()->create([
            'name' => '测试B用户',
            'phone' => '13'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => 'buser'.random_int(1000, 9999).'@example.com',
            'password' => 'secret',
            'status' => 'active',
        ]);

        $token = new class($company) implements ScopeAuthorizable
        {
            public string $id;

            public string $responsible_type;

            public int $responsible_id;

            public Company $responsible;

            public function __construct(Company $company)
            {
                $this->id = (string) Str::uuid();
                $this->responsible_type = Company::class;
                $this->responsible_id = $company->id;
                $this->responsible = $company;
            }

            public function can(string $scope): bool
            {
                return true;
            }

            public function cant(string $scope): bool
            {
                return false;
            }
        };

        $this->actingAs($user->withAccessToken($token), 'b');
    }

    private function createEmployee(Company $company, Department $department): Employee
    {
        return Employee::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'employee_no' => 'E'.random_int(1000, 9999),
            'real_name' => '测试员工',
            'avatar' => 'https://example.com/avatar.png',
            'status' => 1,
        ]);

    }
}
