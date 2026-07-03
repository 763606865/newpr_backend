<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationStatus;
use App\Enums\RcEmploymentType;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeIntention;
use App\Models\Rc\UserCompanyBlacklist;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRecommendControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'sort' => 1,
        ]);
    }

    public function test_guest_can_get_local_high_salary_recommendations_without_auth(): void
    {
        $company = $this->createCompany();

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-GUEST-001',
            'title' => '高薪后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '负责核心业务开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-GUEST-002',
            'title' => '低薪实习生',
            'employment_type' => RcJobEmploymentType::Internship,
            'city_code' => '360100',
            'salary_min' => 3000,
            'salary_max' => 5000,
            'description' => '实习岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-GUEST-003',
            'title' => '外地高薪岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '440300',
            'salary_min' => 15000,
            'salary_max' => 25000,
            'description' => '深圳办公',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/rc/talent/jobs/recommend?city_code=360100');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.recommendation.strategy', 'guest_local')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '高薪后端工程师');
    }

    public function test_logged_in_user_does_not_see_applied_jobs_in_recommendations(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createCompany();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $appliedJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-APPLIED-001',
            'title' => '已投递岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '已投递',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-OPEN-001',
            'title' => '未投递岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '可推荐',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $appliedJob->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/jobs/recommend?city_code=360100')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '未投递岗位');
    }

    public function test_logged_in_user_does_not_see_jobs_from_blacklisted_companies_in_recommendations(): void
    {
        $user = $this->createJobSeekerContext();
        $blockedCompany = Company::query()->create([
            'name' => '黑名单企业',
            'credit_code' => '91360100MA0000000B',
            'status' => CompanyStatus::Enabled,
        ]);
        $visibleCompany = Company::query()->create([
            'name' => '可见企业',
            'credit_code' => '91360100MA0000000C',
            'status' => CompanyStatus::Enabled,
        ]);

        UserCompanyBlacklist::query()->create([
            'user_id' => $user->id,
            'company_id' => $blockedCompany->id,
        ]);

        Job::query()->create([
            'company_id' => $blockedCompany->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-BLOCKED-001',
            'title' => '黑名单企业岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '不应推荐',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $visibleCompany->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-VISIBLE-001',
            'title' => '可推荐岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '可推荐',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/jobs/recommend?city_code=360100')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '可推荐岗位');
    }

    public function test_logged_in_user_can_see_withdrawn_jobs_in_recommendations(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createCompany();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $withdrawnJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-WITHDRAWN-001',
            'title' => '已撤回投递岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 12000,
            'salary_max' => 18000,
            'description' => '已撤回',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $withdrawnJob->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Withdrawn,
            'applied_at' => now()->subDay(),
            'withdrawn_at' => now(),
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/jobs/recommend?city_code=360100')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '已撤回投递岗位');
    }

    public function test_logged_in_user_with_intention_gets_intention_recommendations(): void
    {
        $user = $this->createJobSeekerContext();
        $company = $this->createCompany();
        $position = Position::query()->where('code', 'backend-developer')->firstOrFail();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        ResumeIntention::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $user->id,
            'expected_city_code' => '360100',
            'expected_position_id' => $position->id,
            'employment_type' => RcEmploymentType::FullTime,
            'salary_min' => 15000,
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-INT-001',
            'title' => '匹配的后端岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'salary_min' => 15000,
            'salary_max' => 25000,
            'description' => 'Laravel 开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-REC-INT-002',
            'title' => '不匹配的产品岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '440300',
            'salary_min' => 15000,
            'salary_max' => 25000,
            'description' => '产品规划',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/jobs/recommend');

        $response
            ->assertOk()
            ->assertJsonPath('data.recommendation.strategy', 'intention')
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '匹配的后端岗位');
    }

    private function createCompany(): Company
    {
        return Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
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
