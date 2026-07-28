<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Enums\RcAssetCode;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use App\Models\Rc\AssetAccount;
use App\Models\Rc\AssetLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompaniesTableAuditActionTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_audit_action_is_visible_for_auditing_companies(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany([
            'status' => CompanyStatus::Auditing,
            'contact_phone' => '',
        ]);

        Livewire::test(ListCompanies::class)
            ->assertTableActionVisible('audit', $company);
    }

    public function test_audit_action_is_hidden_for_enabled_companies(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany(['status' => CompanyStatus::Enabled]);

        Livewire::test(ListCompanies::class)
            ->assertTableActionHidden('audit', $company);
    }

    public function test_approve_action_enables_company(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany([
            'status' => CompanyStatus::Auditing,
            'contact_phone' => '',
        ]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'approve'], $company, data: [
                'send_sms_notification' => false,
            ])
            ->assertNotified();

        $this->assertSame(CompanyStatus::Enabled, $company->fresh()->status);
        $this->assertSame(
            1,
            AssetAccount::query()
                ->where('owner_id', $company->id)
                ->where('asset_code', RcAssetCode::FullTimeJobPosting)
                ->value('balance'),
        );
        $this->assertSame(
            10,
            AssetAccount::query()
                ->where('owner_id', $company->id)
                ->where('asset_code', RcAssetCode::CampusJobPosting)
                ->value('balance'),
        );
        $this->assertSame(2, AssetLedger::query()->where('owner_id', $company->id)->count());
    }

    public function test_reject_action_disables_company(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany([
            'status' => CompanyStatus::Auditing,
            'contact_phone' => '',
        ]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'reject'], $company, data: [
                'send_sms_notification' => false,
            ])
            ->assertNotified();

        $this->assertSame(CompanyStatus::Disabled, $company->fresh()->status);
        $this->assertSame(0, AssetAccount::query()->where('owner_id', $company->id)->count());
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
            'status' => CompanyStatus::Auditing,
        ], $attributes));
    }
}
