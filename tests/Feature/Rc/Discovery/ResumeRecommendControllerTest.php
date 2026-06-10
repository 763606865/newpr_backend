<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeWork;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeRecommendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_recruiter_company(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/resumes/recommend');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_recruiter_gets_job_based_recommendations_for_specific_job(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-REC-RESUME-CTRL-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'education_level' => RcEducationLevel::Bachelor->value,
            'experience_min' => 3,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $matchedCandidate = User::factory()->create();
        $matchedResume = Resume::query()->create([
            'user_id' => $matchedCandidate->id,
            'title' => '求职简历',
            'full_name' => '匹配候选人',
            'phone' => '13800138000',
            'email' => 'matched@example.com',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'current_city_code' => '360100',
            'work_years' => 5,
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeWork::query()->create([
            'resume_id' => $matchedResume->id,
            'user_id' => $matchedCandidate->id,
            'company_name' => '南昌示例科技有限公司',
            'position' => 'Laravel 工程师',
            'start_date' => '2020-01-01',
            'description' => '负责 Laravel API 开发',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $matchedResume->id,
            'user_id' => $matchedCandidate->id,
            'school_name' => '南昌大学',
            'major' => '计算机科学',
            'degree' => RcEducationLevel::Bachelor,
            'start_date' => '2016-09-01',
            'end_date' => '2020-06-01',
        ]);

        $otherCandidate = User::factory()->create();
        Resume::query()->create([
            'user_id' => $otherCandidate->id,
            'title' => '另一份简历',
            'full_name' => '外地候选人',
            'phone' => '13800138001',
            'email' => 'other@example.com',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'current_city_code' => '440300',
            'work_years' => 5,
            'status' => RcResumeStatus::Normal,
        ]);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes/recommend?job_id='.$job->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.recommendation.strategy', 'job')
            ->assertJsonPath('data.recommendation.job_id', $job->id)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '匹配候选人');
    }

    public function test_recruiter_without_publishable_job_gets_default_recommendations(): void
    {
        [$recruiter] = $this->createRecruiterContext();

        $candidate = User::factory()->create();

        Resume::query()->create([
            'user_id' => $candidate->id,
            'title' => '求职简历',
            'full_name' => '默认推荐候选人',
            'phone' => '13800138002',
            'email' => 'default@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes/recommend');

        $response
            ->assertOk()
            ->assertJsonPath('data.recommendation.strategy', 'default')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '默认推荐候选人');
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function createRecruiterContext(): array
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return [$user, $company];
    }
}
