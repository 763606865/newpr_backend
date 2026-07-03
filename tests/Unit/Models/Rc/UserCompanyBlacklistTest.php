<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Rc\UserCompanyBlacklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCompanyBlacklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_user_and_company(): void
    {
        $user = User::factory()->create();
        $company = $this->createCompany();

        $blacklist = UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'remark' => '老东家',
        ]);

        $this->assertTrue($blacklist->user->is($user));
        $this->assertTrue($blacklist->company->is($company));
        $this->assertSame('老东家', $blacklist->remark);
    }

    public function test_it_filters_by_user_and_company(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $company = $this->createCompany();
        $anotherCompany = $this->createCompany();

        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);
        UserCompanyBlacklist::query()->create([
            'user_id' => $anotherUser->id,
            'company_id' => $company->id,
        ]);
        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $anotherCompany->id,
        ]);

        $this->assertSame(2, UserCompanyBlacklist::query()->forUser($user)->count());
        $this->assertSame(2, UserCompanyBlacklist::query()->forCompany($company)->count());
        $this->assertSame(1, UserCompanyBlacklist::query()->forUser($user)->forCompany($company)->count());
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '示例科技有限公司',
            'credit_code' => '91360100MA'.strtoupper(substr(uniqid(), -8)),
            'status' => CompanyStatus::Enabled,
        ]);
    }
}
