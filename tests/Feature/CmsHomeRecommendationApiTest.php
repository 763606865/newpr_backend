<?php

namespace Tests\Feature;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\CompanyProfile;
use App\Models\Rc\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsHomeRecommendationApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_home_index_returns_recommendation_modules(): void
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

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-HOME-001',
            'title' => '首页推荐职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
            'is_urgent' => true,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::UrgentJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $job->id,
            'status' => CmsStatus::Enabled,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::HotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $job->id,
            'status' => CmsStatus::Enabled,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::FamousCompany,
            'recommendable_type' => 'company',
            'recommendable_id' => $company->id,
            'status' => CmsStatus::Enabled,
        ]);

        $this->getJson('/cms/home')
            ->assertOk()
            ->assertJsonPath('data.urgent_jobs.0.job.title', '首页推荐职位')
            ->assertJsonPath('data.hot_jobs.0.job.title', '首页推荐职位')
            ->assertJsonPath('data.famous_companies.0.company.display_name', '示例科技');
    }
}
