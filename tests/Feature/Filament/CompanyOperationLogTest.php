<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyOperationAction;
use App\Enums\CompanyStatus;
use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Jobs\BatchRebindCompanyPlansJob;
use App\Models\Biz\Plan;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use App\Services\SysPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompanyOperationLogTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_create_company_records_created_log(): void
    {
        $admin = $this->actingAsFilamentAdmin();

        Livewire::test(CreateCompany::class)
            ->fillForm([
                'name' => '新建企业有限公司',
                'credit_code' => '91360100MA0000001X',
                'legal_person' => '张三',
                'contact_phone' => '13800000001',
                'address' => '南昌市红谷滩新区',
                'status' => CompanyStatus::Enabled,
            ])
            ->call('create')
            ->assertNotified();

        $company = Company::query()->where('credit_code', '91360100MA0000001X')->first();

        $this->assertNotNull($company);
        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Created->value,
            'operator_id' => $admin->id,
            'operator_type' => 'admin_user',
        ]);
    }

    public function test_update_company_records_updated_log(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm([
                'name' => '更新后的企业名称',
            ])
            ->call('save')
            ->assertNotified();

        $log = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where('action', CompanyOperationAction::Updated)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->operator_id);
        $this->assertSame('南昌示例科技有限公司', $log->changes['before']['name']);
        $this->assertSame('更新后的企业名称', $log->changes['after']['name']);
    }

    public function test_update_company_status_records_status_changed_log(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany(['status' => CompanyStatus::Enabled]);

        Livewire::test(EditCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm([
                'status' => CompanyStatus::Disabled,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::StatusChanged->value,
        ]);

        $log = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where('action', CompanyOperationAction::StatusChanged)
            ->first();

        $this->assertSame(CompanyStatus::Enabled->value, $log->changes['before']['status']);
        $this->assertSame(CompanyStatus::Disabled->value, $log->changes['after']['status']);
    }

    public function test_delete_company_from_table_records_deleted_log(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        Livewire::test(ListCompanies::class)
            ->callTableAction('delete', $company)
            ->assertNotified();

        $this->assertSoftDeleted($company);
        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Deleted->value,
            'operator_id' => $admin->id,
        ]);
    }

    public function test_audit_approve_records_audit_approved_log_and_auditor_id(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany(['status' => CompanyStatus::Auditing]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'approve'], $company)
            ->assertNotified();

        $company->refresh();

        $this->assertSame(CompanyStatus::Enabled, $company->status);
        $this->assertSame($admin->id, $company->auditor_id);
        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::AuditApproved->value,
            'operator_id' => $admin->id,
        ]);
    }

    public function test_audit_reject_records_audit_rejected_log_and_auditor_id(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany(['status' => CompanyStatus::Auditing]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'reject'], $company)
            ->assertNotified();

        $company->refresh();

        $this->assertSame(CompanyStatus::Disabled, $company->status);
        $this->assertSame($admin->id, $company->auditor_id);
        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::AuditRejected->value,
            'operator_id' => $admin->id,
        ]);
    }

    public function test_bind_plan_records_plan_bound_log(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();
        $plan = $this->createPlan();

        Livewire::test(ListCompanies::class)
            ->callTableAction('bindPlan', $company, data: [
                'plan_id' => $plan->id,
                'pay_amount' => 888.00,
                'remark' => '测试绑定',
            ])
            ->assertNotified();

        $log = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where('action', CompanyOperationAction::PlanBound)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($plan->id, $log->changes['after']['plan_id']);
        $this->assertEquals(888.00, $log->extra['ship']['pay_amount']);
        $this->assertSame('测试绑定', $log->extra['ship']['remark']);
    }

    public function test_refresh_plan_records_plan_refreshed_log(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();
        $plan = $this->createPlan();

        SysPlanService::make()->resolve($company, $plan, [
            'pay_amount' => 500.00,
            'remark' => '首次绑定',
        ]);

        $beforeShipId = $company->companyPlans()->where('is_current', 1)->value('ship_id');

        Livewire::test(ListCompanies::class)
            ->callTableAction('refreshPlan', $company)
            ->assertNotified();

        $log = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where('action', CompanyOperationAction::PlanRefreshed)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($beforeShipId, $log->changes['before']['ship_id']);
        $this->assertNotSame($beforeShipId, $log->changes['after']['ship_id']);
        $this->assertSame($plan->id, $log->changes['after']['plan_id']);
    }

    public function test_operation_logs_action_displays_company_logs(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'operator_id' => $admin->id,
            'operator_type' => 'admin_user',
            'action' => CompanyOperationAction::Created,
            'summary' => '创建企业',
            'created_at' => now(),
        ]);

        Livewire::test(ListCompanies::class)
            ->assertTableActionVisible('operationLogs', $company)
            ->mountTableAction('operationLogs', $company)
            ->assertMountedActionModalSee('创建企业')
            ->assertMountedActionModalSee($admin->name);
    }

    public function test_operation_logs_action_filters_by_action_type(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Created,
            'summary' => '日志记录-创建',
            'created_at' => now(),
        ]);

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'action' => CompanyOperationAction::AuditApproved,
            'summary' => '日志记录-审批',
            'created_at' => now(),
        ]);

        Livewire::test(ListCompanies::class)
            ->mountTableAction('operationLogs', $company)
            ->setTableActionData([
                'log_action' => CompanyOperationAction::AuditApproved->value,
            ])
            ->assertMountedActionModalSee('日志记录-审批')
            ->assertMountedActionModalDontSee('日志记录-创建');
    }

    public function test_operation_logs_action_filters_by_operator(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'operator_id' => $admin->id,
            'operator_type' => 'admin_user',
            'action' => CompanyOperationAction::Created,
            'summary' => '管理员操作',
            'created_at' => now(),
        ]);

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'action' => CompanyOperationAction::PlanRefreshed,
            'summary' => '系统刷新套餐',
            'created_at' => now(),
        ]);

        Livewire::test(ListCompanies::class)
            ->mountTableAction('operationLogs', $company)
            ->setTableActionData([
                'log_operator' => 'system',
            ])
            ->assertMountedActionModalSee('系统刷新套餐')
            ->assertMountedActionModalDontSee('管理员操作');
    }

    public function test_operation_logs_action_paginates_logs_with_twenty_per_page(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        for ($index = 1; $index <= 21; $index++) {
            CompanyOperationLog::query()->create([
                'company_id' => $company->id,
                'action' => CompanyOperationAction::Updated,
                'summary' => '分页日志-'.$index,
                'created_at' => now()->subMinutes($index),
            ]);
        }

        Livewire::test(ListCompanies::class)
            ->mountTableAction('operationLogs', $company)
            ->assertMountedActionModalSee('分页日志-1')
            ->assertMountedActionModalSee('分页日志-20')
            ->assertMountedActionModalDontSee('分页日志-21')
            ->assertMountedActionModalSee('共 21 条，第 1 / 2 页（每页 20 条）')
            ->setTableActionData(['log_page' => 2])
            ->assertMountedActionModalSee('分页日志-21')
            ->assertMountedActionModalDontSee('分页日志-1');
    }

    public function test_operation_logs_action_filters_by_date_range(): void
    {
        $this->actingAsFilamentAdmin();
        $company = $this->createCompany();

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Created,
            'summary' => '较早日志',
            'created_at' => now()->subDays(10),
        ]);

        CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Updated,
            'summary' => '最近日志',
            'created_at' => now()->subDay(),
        ]);

        Livewire::test(ListCompanies::class)
            ->mountTableAction('operationLogs', $company)
            ->setTableActionData([
                'log_from' => now()->subDays(2)->toDateString(),
                'log_until' => now()->toDateString(),
            ])
            ->assertMountedActionModalSee('最近日志')
            ->assertMountedActionModalDontSee('较早日志');
    }

    public function test_batch_rebind_job_records_plan_batch_rebound_logs(): void
    {
        $admin = $this->actingAsFilamentAdmin();
        $plan = $this->createPlan();
        $companyA = $this->createCompany(['credit_code' => '91360100MA0000000A', 'name' => '企业A']);
        $companyB = $this->createCompany(['credit_code' => '91360100MA0000000B', 'name' => '企业B']);

        $service = SysPlanService::make();
        $service->resolve($companyA, $plan);
        $service->resolve($companyB, $plan);

        BatchRebindCompanyPlansJob::dispatchSync($plan->id, $admin->id);

        $this->assertSame(
            2,
            CompanyOperationLog::query()
                ->where('action', CompanyOperationAction::PlanBatchRebound)
                ->count(),
        );

        $log = CompanyOperationLog::query()
            ->where('company_id', $companyA->id)
            ->where('action', CompanyOperationAction::PlanBatchRebound)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->operator_id);
        $this->assertSame($plan->id, $log->extra['plan_id']);
        $this->assertNotSame($log->changes['before']['ship_id'], $log->changes['after']['ship_id']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ], $attributes));
    }

    private function createPlan(): Plan
    {
        return Plan::query()->create([
            'plan_name' => '标准套餐',
            'plan_code' => 'standard_plan',
            'price' => 999.00,
            'duration' => 365,
            'sort' => 1,
            'status' => SystemPlanStatus::Enabled,
        ]);
    }
}
