<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Job;
use App\Models\Rc\UserCompanyBlacklist;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_guest_can_get_company_recommendations(): void
    {
        $recommendedCompany = $this->createCompanyWithProfile('南昌示例科技有限公司', [
            'short_name' => '示例科技',
            'city_code' => '360100',
            'is_brand' => 1,
            'brand_sort' => 100,
        ]);
        $noJobCompany = $this->createCompanyWithProfile('南昌无职位企业', [
            'city_code' => '360100',
        ]);
        $otherCityCompany = $this->createCompanyWithProfile('深圳示例科技有限公司', [
            'city_code' => '440300',
        ]);

        $this->createPublishedJob($recommendedCompany, 'JOB-COMPANY-REC-001');
        $this->createPublishedJob($otherCityCompany, 'JOB-COMPANY-REC-002');

        $this->getJson('/rc/talent/companies/recommend?city_code=360100')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $recommendedCompany->id)
            ->assertJsonPath('data.data.0.display_name', '示例科技')
            ->assertJsonPath('data.data.0.stat.public_jobs', 1)
            ->assertJsonPath('data.recommendation.strategy', 'city_public_jobs');

        $this->assertSame(0, $noJobCompany->jobs()->count());
    }

    public function test_company_search_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/companies')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_search_companies_and_exclude_blacklisted_companies(): void
    {
        $user = $this->createJobSeekerContext();
        $matchedCompany = $this->createCompanyWithProfile('南昌示例科技有限公司', [
            'short_name' => '示例科技',
            'city_code' => '360100',
            'industry_codes' => ['internet'],
        ]);
        $blockedCompany = $this->createCompanyWithProfile('南昌示例外包有限公司', [
            'short_name' => '示例外包',
            'city_code' => '360100',
            'industry_codes' => ['internet'],
        ]);
        $otherCityCompany = $this->createCompanyWithProfile('深圳示例科技有限公司', [
            'short_name' => '深圳示例',
            'city_code' => '440300',
            'industry_codes' => ['internet'],
        ]);

        $this->createPublishedJob($matchedCompany, 'JOB-COMPANY-SEARCH-001');
        $this->createPublishedJob($blockedCompany, 'JOB-COMPANY-SEARCH-002');
        $this->createPublishedJob($otherCityCompany, 'JOB-COMPANY-SEARCH-003');

        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $blockedCompany->id,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/companies?keyword='.urlencode('示例').'&city_code=360100')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $matchedCompany->id)
            ->assertJsonPath('data.data.0.profile.city_code', '360100')
            ->assertJsonPath('data.data.0.stat.public_jobs', 1);
    }

    /**
     * @param  array<string, mixed>  $profileAttributes
     */
    private function createCompanyWithProfile(string $name, array $profileAttributes = []): Company
    {
        $company = Company::query()->create([
            'name' => $name,
            'credit_code' => '91360100MA'.strtoupper(substr(md5($name), 0, 8)),
            'status' => CompanyStatus::Enabled,
        ]);

        CompanyProfile::query()->create(array_merge([
            'company_id' => $company->id,
            'short_name' => $name,
            'city_code' => null,
            'industry_codes' => [],
            'profile_status' => CompanyProfileStatus::Complete,
            'is_brand' => 0,
            'brand_sort' => 0,
        ], $profileAttributes));

        return $company;
    }

    private function createPublishedJob(Company $company, string $code): Job
    {
        return Job::query()->create([
            'company_id' => $company->id,
            'code' => $code,
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);
    }

    private function createJobSeekerContext(): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }
}
