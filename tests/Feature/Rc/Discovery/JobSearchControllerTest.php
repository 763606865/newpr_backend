<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
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
