<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\ResumeRecommendationCriteriaResolver;
use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeRecommendationCriteriaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_uses_job_strategy_when_company_has_published_job(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000001A',
            'status' => CompanyStatus::Enabled,
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-REC-RESUME-003',
            'title' => '产品经理',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'description' => '负责产品规划',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $criteria = (new ResumeRecommendationCriteriaResolver)->resolve(
            new ResumeRecommendationContext(
                user: $user,
                company: $company,
            ),
        );

        $this->assertSame('job', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['current_city_code']);
    }

    public function test_resolver_falls_back_to_default_when_no_publishable_job(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000001B',
            'status' => CompanyStatus::Enabled,
        ]);

        $criteria = (new ResumeRecommendationCriteriaResolver)->resolve(
            new ResumeRecommendationContext(
                user: $user,
                company: $company,
            ),
        );

        $this->assertSame('default', $criteria->strategy);
        $this->assertSame([], $criteria->searchFilters);
    }
}
