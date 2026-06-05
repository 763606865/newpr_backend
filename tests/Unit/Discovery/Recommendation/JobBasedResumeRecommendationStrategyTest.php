<?php

namespace Tests\Unit\Discovery\Recommendation;

use App\Discovery\Recommendation\ResumeRecommendationContext;
use App\Discovery\Recommendation\Strategies\JobBasedResumeRecommendationStrategy;
use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobBasedResumeRecommendationStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_criteria_maps_published_job_to_resume_filters(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-REC-RESUME-001',
            'title' => '后端开发工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'education_level' => RcEducationLevel::Bachelor->value,
            'experience_min' => 3,
            'experience_max' => 8,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $strategy = new JobBasedResumeRecommendationStrategy;
        $context = new ResumeRecommendationContext(
            user: $user,
            company: $company,
            jobIdHint: $job->id,
        );

        $this->assertTrue($strategy->supports($context));

        $criteria = $strategy->criteria($context);

        $this->assertSame('job', $criteria->strategy);
        $this->assertSame('360100', $criteria->searchFilters['current_city_code']);
        $this->assertSame(RcEducationLevel::Bachelor->value, $criteria->searchFilters['highest_education_level']);
        $this->assertSame(3, $criteria->searchFilters['work_years_min']);
        $this->assertSame(8, $criteria->searchFilters['work_years_max']);
        $this->assertSame('后端开发工程师', $criteria->searchFilters['keyword']);
        $this->assertSame($job->id, $criteria->meta['job_id']);
    }

    public function test_supports_false_when_job_is_not_publishable(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-REC-RESUME-002',
            'title' => '草稿岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '草稿',
            'status' => RcJobStatus::Draft,
        ]);

        $strategy = new JobBasedResumeRecommendationStrategy;
        $context = new ResumeRecommendationContext(
            user: $user,
            company: $company,
            jobIdHint: $job->id,
        );

        $this->assertFalse($strategy->supports($context));
    }
}
