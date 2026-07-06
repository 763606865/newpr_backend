<?php

namespace Tests\Feature;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Enums\CompanyStatus;
use App\Enums\RcJobStatus;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_supports_city_module_and_pagination_filters(): void
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

        $jobInCity = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-CAMPUS-001',
            'title' => '南昌校招岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $jobNational = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-CAMPUS-002',
            'title' => '全国校招岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $jobOtherCity = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-CAMPUS-003',
            'title' => '外地校招岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::CampusHotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $jobInCity->id,
            'city_code' => '360100',
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::CampusHotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $jobNational->id,
            'city_code' => null,
            'status' => CmsStatus::Enabled,
            'sort' => 2,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::CampusHotJob,
            'recommendable_type' => 'job',
            'recommendable_id' => $jobOtherCity->id,
            'city_code' => '440100',
            'status' => CmsStatus::Enabled,
            'sort' => 3,
        ]);

        HomeRecommendation::query()->create([
            'module_type' => CmsHomeRecommendationModuleType::CampusHotCompany,
            'recommendable_type' => 'company',
            'recommendable_id' => $company->id,
            'city_code' => '360100',
            'status' => CmsStatus::Enabled,
            'sort' => 1,
        ]);

        $this->getJson('/cms/home/recommendations?city_code=360100&module_type=5&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.per_page', 1)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.module_type', 5)
            ->assertJsonPath('data.data.0.job.title', '南昌校招岗位');
    }

    public function test_index_rejects_invalid_module_type(): void
    {
        $this->getJson('/cms/home/recommendations?module_type=999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['module_type']);
    }
}
