<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\UserCompanyBlacklist;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSearchControllerTest extends TestCase
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

    public function test_index_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_index_returns_matching_jobs_for_job_seeker(): void
    {
        $jobSeeker = $this->createJobSeekerContext();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-SEEK-001',
            'title' => '高级 Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端研发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-SEEK-002',
            'title' => '产品经理',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责需求分析',
            'status' => RcJobStatus::Draft,
        ]);

        $response = $this
            ->actingAs($jobSeeker, 'rc')
            ->getJson('/rc/talent/jobs?keyword=Laravel');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '高级 Laravel 工程师')
            ->assertJsonPath('data.data.0.company.name', '南昌示例科技有限公司');
    }

    public function test_index_returns_applied_and_favorited_flags_for_each_job(): void
    {
        $jobSeeker = $this->createJobSeekerContext();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $appliedJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-SEEK-APPLIED',
            'title' => '已投递岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端研发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $favoriteJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-SEEK-FAVORITE',
            'title' => '已收藏岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端研发',
            'status' => RcJobStatus::Published,
            'published_at' => now()->subMinute(),
        ]);

        JobFavorite::query()->create([
            'user_id' => $jobSeeker->id,
            'job_id' => $favoriteJob->id,
        ]);

        $resume = Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
        ]);

        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $appliedJob->id,
            'candidate_user_id' => $jobSeeker->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);

        $response = $this
            ->actingAs($jobSeeker, 'rc')
            ->getJson('/rc/talent/jobs?company_id='.$company->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 2);

        $jobs = collect($response->json('data.data'))->keyBy('title');

        $this->assertTrue($jobs['已投递岗位']['is_applied']);
        $this->assertFalse($jobs['已投递岗位']['is_favorited']);
        $this->assertFalse($jobs['已收藏岗位']['is_applied']);
        $this->assertTrue($jobs['已收藏岗位']['is_favorited']);
    }

    public function test_index_excludes_jobs_from_blacklisted_companies(): void
    {
        $jobSeeker = $this->createJobSeekerContext();
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
            'user_id' => $jobSeeker->id,
            'company_id' => $blockedCompany->id,
        ]);

        Job::query()->create([
            'company_id' => $blockedCompany->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-BLOCKED-001',
            'title' => '不可见岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '被拉黑企业岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $visibleCompany->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-VISIBLE-001',
            'title' => '可见岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '可见企业岗位',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this
            ->actingAs($jobSeeker, 'rc')
            ->getJson('/rc/talent/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '可见岗位');
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
