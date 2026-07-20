<?php

namespace Tests\Unit\Services;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\CompanyProfile;
use App\Models\Rc\Job;
use App\Services\CmsHomeRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CmsHomeRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_grouped_for_home_returns_active_recommendations_by_module(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        CompanyProfile::query()->create([
            'company_id' => $company->id,
            'short_name' => '示例科技',
            'is_brand' => true,
        ]);

        $urgentJob = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-URGENT-001',
            'title' => '紧急 Java 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
            'is_urgent' => true,
        ]);

        $hotJob = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-HOT-001',
            'title' => '热招产品经理',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::UrgentJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $urgentJob->id,
            'title' => '紧急 Java 工程师',
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::HotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $hotJob->id,
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::FamousCompany,
            'recommendable_type' => 'company',
            'recommendable_id' => $company->id,
            'title' => '示例科技',
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::HotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $hotJob->id,
            'status' => CmsStatus::Disabled,
            'sort' => 99,
        ]);

        $payload = CmsHomeRecommendationService::make()->groupedForHome(null, Request::create('/cms/home', 'GET'));

        $this->assertCount(1, $payload['urgent_jobs']);
        $this->assertSame('紧急 Java 工程师', $payload['urgent_jobs'][0]['title']);
        $this->assertSame('紧急 Java 工程师', $payload['urgent_jobs'][0]['job']['title']);

        $this->assertCount(1, $payload['hot_jobs']);
        $this->assertSame('热招产品经理', $payload['hot_jobs'][0]['job']['title']);

        $this->assertCount(1, $payload['famous_companies']);
        $this->assertSame('示例科技', $payload['famous_companies'][0]['title']);
        $this->assertSame('示例科技', $payload['famous_companies'][0]['company']['display_name']);
    }

    public function test_grouped_for_home_excludes_expired_jobs(): void
    {
        $company = Company::query()->create([
            'name' => '过期职位企业',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $expiredJob = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-EXPIRED-001',
            'title' => '过期职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now()->subMonth(),
            'expired_at' => now()->subDay(),
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::HotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $expiredJob->id,
            'status' => CmsStatus::Enabled,
        ]);

        $payload = CmsHomeRecommendationService::make()->groupedForHome(null, Request::create('/cms/home', 'GET'));

        $this->assertSame([], $payload['hot_jobs']);
    }
}
