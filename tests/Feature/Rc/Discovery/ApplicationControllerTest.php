<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationFlowActionType;
use App\Enums\RcApplicationStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStageStatus;
use App\Enums\RcJobStatus;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\JobStage;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
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

    public function test_apply_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();
        $job = $this->createPublishedJob();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_apply_with_primary_resume(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.status', RcApplicationStatus::Pending->value)
            ->assertJsonPath('data.resume_id', $resume->id)
            ->assertJsonPath('data.job.title', 'Laravel 工程师');

        $this->assertDatabaseHas('rc_applications', [
            'job_id' => $job->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending->value,
        ]);

        $application = Application::query()->firstOrFail();

        $this->assertIsArray($application->resume_snapshot);
        $this->assertSame('求职者甲', $application->resume_snapshot['full_name']);
        $this->assertSame('13800138000', $application->resume_snapshot['phone']);

        $this->assertDatabaseHas('rc_application_flows', [
            'application_id' => $application->id,
            'action_type' => RcApplicationFlowActionType::Transfer->value,
        ]);
    }

    public function test_apply_rejects_duplicate_active_application(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply')
            ->assertOk()
            ->assertJsonPath('code', 200);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '您已投递过该职位。');
    }

    public function test_job_seeker_can_reapply_after_withdraw(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $applyResponse = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply');

        $applicationId = $applyResponse->json('data.id');

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/applications/'.$applicationId.'/withdraw')
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Withdrawn->value);

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.status', RcApplicationStatus::Pending->value)
            ->assertJsonPath('data.id', $applicationId);
    }

    public function test_job_seeker_can_list_applications(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply')
            ->assertOk();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/applications');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.job.title', 'Laravel 工程师');
    }

    public function test_apply_assigns_default_stage_when_configured(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        $stage = JobStage::query()->create([
            'company_id' => $job->company_id,
            'code' => 'screening',
            'name' => '简历筛选',
            'sort' => 1,
            'is_default' => 1,
            'status' => RcJobStageStatus::Enabled,
        ]);

        Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/apply');

        $response
            ->assertOk()
            ->assertJsonPath('data.current_stage_id', $stage->id);
    }

    private function createPublishedJob(): Job
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        return Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-APPLY-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
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
