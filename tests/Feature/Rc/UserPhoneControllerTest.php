<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcInterviewStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Interview;
use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class UserPhoneControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        config()->set('sms.driver', null);
    }

    public function test_lookup_phone_reports_availability(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);
        User::factory()->create([
            'phone' => '13800138001',
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/phone/lookup?phone=13800138001')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.phone', '13800138001')
            ->assertJsonPath('data.exists', true)
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.is_current_user', false);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/phone/lookup?phone=13800138002')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.exists', false)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.is_current_user', false);
    }

    public function test_send_phone_verification_code_for_available_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/users/phone/verification-code', [
                'phone' => '13800138002',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertTrue(Cache::has('auth:verification:change_phone:phone:'.md5('13800138002')));
    }

    public function test_update_phone_with_verification_code(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        config()->set('app.debug', true);
        config()->set('app.skip_accounts', ['13800138002']);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138002',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.phone', '13800138002');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone' => '13800138002',
        ]);
    }

    public function test_update_phone_rejects_occupied_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);
        User::factory()->create([
            'phone' => '13800138001',
        ]);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138001',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '手机号已被其他用户使用。');
    }

    public function test_update_phone_rejects_invalid_code(): void
    {
        $user = User::factory()->create([
            'phone' => '13800138000',
        ]);

        $this->actingAs($user, 'rc')
            ->putJson('/rc/users/phone', [
                'phone' => '13800138002',
                'code' => '123456',
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '验证码错误或已失效。');
    }

    public function test_stats_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/jobseeker/stats')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_stats_returns_job_seeker_dashboard_counts(): void
    {
        $user = $this->createJobSeekerContext();
        $otherUser = $this->createJobSeekerContext();
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000U',
            'status' => CompanyStatus::Enabled,
        ]);
        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-USER-STATS-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);
        $otherJob = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-USER-STATS-002',
            'title' => '前端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);
        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
        ]);
        $otherResume = Resume::query()->create([
            'user_id' => $otherUser->id,
            'title' => '其他简历',
            'full_name' => '求职者乙',
            'phone' => '13800138001',
            'email' => 'other-seeker@example.com',
        ]);

        $application = Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $job->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);
        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $otherJob->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Withdrawn,
            'applied_at' => now(),
        ]);
        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $otherJob->id,
            'candidate_user_id' => $otherUser->id,
            'resume_id' => $otherResume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);

        Interview::query()->create([
            'company_id' => $company->id,
            'application_id' => $application->id,
            'status' => RcInterviewStatus::AwaitingCandidate,
            'interview_at' => now()->addDay(),
        ]);
        Interview::query()->create([
            'company_id' => $company->id,
            'application_id' => $application->id,
            'status' => RcInterviewStatus::Finished,
            'interview_at' => now()->subDay(),
        ]);

        JobFavorite::query()->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);
        JobFavorite::query()->create([
            'user_id' => $otherUser->id,
            'job_id' => $otherJob->id,
        ]);

        ResumeStatsDaily::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'stat_date' => now()->subDay()->toDateString(),
            'views_total' => 8,
            'views_uv' => 3,
        ]);
        ResumeStatsDaily::query()->create([
            'user_id' => $otherUser->id,
            'resume_id' => $otherResume->id,
            'stat_date' => now()->subDay()->toDateString(),
            'views_total' => 20,
            'views_uv' => 5,
        ]);

        $connection = \Mockery::mock(Connection::class);
        $connection->shouldReceive('mget')
            ->once()
            ->with(['rc:view:resume:'.$resume->id.':pv:'.now()->toDateString()])
            ->andReturn(['4']);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/users/jobseeker/stats')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.applications', 2)
            ->assertJsonPath('data.pending_interviews', 1)
            ->assertJsonPath('data.favorite_jobs', 1)
            ->assertJsonPath('data.resume_views', 12);
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
