<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
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

        $company = $this->createCompany(['status' => CompanyStatus::Auditing]);

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

        $company = $this->createCompany(['status' => CompanyStatus::Auditing]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'approve'], $company)
            ->assertNotified();

        $this->assertSame(CompanyStatus::Enabled, $company->fresh()->status);
    }

    public function test_reject_action_disables_company(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany(['status' => CompanyStatus::Auditing]);

        Livewire::test(ListCompanies::class)
            ->callTableAction(['audit', 'reject'], $company)
            ->assertNotified();

        $this->assertSame(CompanyStatus::Disabled, $company->fresh()->status);
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
