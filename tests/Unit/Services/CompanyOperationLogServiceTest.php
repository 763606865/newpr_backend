<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyOperationAction;
use App\Enums\CompanyStatus;
use App\Models\AdminUser;
use App\Models\BUser;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use App\Services\CompanyOperationLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOperationLogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_persists_system_action_when_operator_is_not_provided(): void
    {
        $company = $this->createCompany();

        $log = CompanyOperationLogService::make()->record(
            company: $company,
            action: CompanyOperationAction::PlanRefreshed,
            changes: CompanyOperationLogService::make()->buildChanges(
                ['plan_code' => 'trial_plan'],
                ['plan_code' => 'standard_plan'],
            ),
            ip: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        $this->assertInstanceOf(CompanyOperationLog::class, $log);
        $this->assertTrue($log->isSystemAction());
        $this->assertSame(CompanyOperationAction::PlanRefreshed, $log->action);
        $this->assertSame('刷新套餐', $log->summary);
        $this->assertSame('127.0.0.1', $log->ip);
        $this->assertSame('PHPUnit', $log->user_agent);
        $this->assertSame('trial_plan', $log->changes['before']['plan_code']);
        $this->assertSame('standard_plan', $log->changes['after']['plan_code']);
    }

    public function test_record_persists_admin_operator(): void
    {
        $company = $this->createCompany();
        $admin = AdminUser::query()->create([
            'name' => '运营管理员',
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
        ]);

        $log = CompanyOperationLogService::make()->record(
            company: $company,
            action: CompanyOperationAction::AuditApproved,
            summary: '审批通过企业入驻',
            operator: $admin,
        );

        $log->load('operator');

        $this->assertFalse($log->isSystemAction());
        $this->assertSame('admin_user', $log->operator_type);
        $this->assertSame($admin->id, $log->operator_id);
        $this->assertTrue($log->operator->is($admin));
    }

    public function test_record_resolves_authenticated_admin_operator(): void
    {
        $company = $this->createCompany();
        $admin = AdminUser::query()->create([
            'name' => '运营管理员',
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin');

        $log = CompanyOperationLogService::make()->record(
            company: $company,
            action: CompanyOperationAction::PlanBound,
        );

        $this->assertSame('admin_user', $log->operator_type);
        $this->assertSame($admin->id, $log->operator_id);
    }

    public function test_record_persists_b_user_operator(): void
    {
        $company = $this->createCompany();
        $bUser = BUser::query()->create([
            'phone' => '13800138000',
            'name' => 'B端用户',
            'password' => bcrypt('password'),
        ]);

        $log = CompanyOperationLogService::make()->record(
            company: $company,
            action: CompanyOperationAction::Updated,
            operator: $bUser,
        );

        $log->load('operator');

        $this->assertSame('b_user', $log->operator_type);
        $this->assertSame($bUser->id, $log->operator_id);
        $this->assertTrue($log->operator->is($bUser));
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
    }
}
