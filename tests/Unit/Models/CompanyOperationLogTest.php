<?php

namespace Tests\Unit\Models;

use App\Enums\CompanyOperationAction;
use App\Enums\CompanyStatus;
use App\Models\AdminUser;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyOperationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_has_operation_logs_relationship(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $admin = AdminUser::query()->create([
            'name' => '运营管理员',
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
        ]);

        $log = CompanyOperationLog::query()->create([
            'company_id' => $company->id,
            'operator_id' => $admin->id,
            'operator_type' => $admin->getMorphClass(),
            'action' => CompanyOperationAction::AuditApproved,
            'summary' => '审批通过',
            'created_at' => now(),
        ]);

        $this->assertTrue($company->operationLogs->contains($log));
        $this->assertTrue($log->company->is($company));
        $this->assertTrue($log->operator->is($admin));
        $this->assertFalse($log->isSystemAction());
    }

    public function test_company_auditor_relationship(): void
    {
        $admin = AdminUser::query()->create([
            'name' => '运营管理员',
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
        ]);

        $company = Company::query()->create([
            'auditor_id' => $admin->id,
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $this->assertTrue($company->auditor->is($admin));
    }
}
