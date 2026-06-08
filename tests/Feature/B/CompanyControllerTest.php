<?php

namespace Tests\Feature\B;

use App\Enums\CompanyOperationAction;
use App\Enums\CompanyStatus;
use App\Models\BUser;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_store_records_created_operation_log_for_b_user(): void
    {
        $user = $this->createBUser();
        $this->actingAs($user, 'b');

        $response = $this->postJson('/b/companies', $this->companyPayload([
            'credit_code' => '91360100MA0000001X',
            'name' => 'B端入驻企业有限公司',
        ]));

        $response->assertOk()
            ->assertJsonPath('code', 200);

        $company = Company::query()->where('credit_code', '91360100MA0000001X')->first();

        $this->assertNotNull($company);
        $this->assertSame(CompanyStatus::Auditing, $company->status);

        $this->assertDatabaseHas('company_operation_logs', [
            'company_id' => $company->id,
            'action' => CompanyOperationAction::Created->value,
            'operator_id' => $user->id,
            'operator_type' => 'b_user',
        ]);
    }

    public function test_store_rejects_duplicate_credit_code_without_creating_log(): void
    {
        $user = $this->createBUser();
        $this->actingAs($user, 'b');

        Company::query()->create([
            'name' => '已存在企业',
            'credit_code' => '91360100MA0000002X',
            'status' => CompanyStatus::Auditing,
        ]);

        $response = $this->postJson('/b/companies', $this->companyPayload([
            'credit_code' => '91360100MA0000002X',
            'name' => '已存在企业',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['credit_code']);

        $this->assertSame(0, CompanyOperationLog::query()->count());
    }

    public function test_update_records_updated_operation_log_for_b_user(): void
    {
        $user = $this->createBUser();
        $company = Company::query()->create([
            'name' => '原始企业名称',
            'credit_code' => '91360100MA0000003X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '原始地址',
            'status' => CompanyStatus::Enabled,
        ]);

        $this->actingAs($user, 'b');

        $response = $this->putJson('/b/companies/'.$company->id, $this->companyPayload([
            'credit_code' => '91360100MA0000003X',
            'name' => '更新后的企业名称',
            'address' => '更新后的地址',
        ]));

        $response->assertOk()
            ->assertJsonPath('code', 200);

        $log = CompanyOperationLog::query()
            ->where('company_id', $company->id)
            ->where('action', CompanyOperationAction::Updated)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->operator_id);
        $this->assertSame('b_user', $log->operator_type);
        $this->assertSame('原始企业名称', $log->changes['before']['name']);
        $this->assertSame('更新后的企业名称', $log->changes['after']['name']);
        $this->assertSame('原始地址', $log->changes['before']['address']);
        $this->assertSame('更新后的地址', $log->changes['after']['address']);
    }

    private function createBUser(): BUser
    {
        return BUser::query()->create([
            'name' => '测试B用户',
            'phone' => '13'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT),
            'email' => 'buser'.random_int(1000, 9999).'@example.com',
            'password' => 'secret',
            'status' => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '测试企业有限公司',
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '张三',
            'contact_phone' => '13800138000',
            'address' => '南昌市红谷滩新区',
        ], $overrides);
    }
}
