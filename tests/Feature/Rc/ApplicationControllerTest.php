<?php

namespace Tests\Feature\Rc;

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
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
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

        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Applications Test', 'rc_users');
    }

    public function test_apply_requires_current_job_seeker_identity(): void
    {
        $user = User::factory()->create();
        $job = $this->createPublishedJob();

        $response = $this
            ->rcPostJson($user, null, '/rc/applications', ['job_id' => $job->id]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_recruiter_token_cannot_apply_even_when_user_has_job_seeker_identity(): void
    {
        $job = $this->createPublishedJob();
        [$user, $recruiterIdentity] = $this->createRecruiterContext($job->company_id);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 0,
            'status' => RcIdentityStatus::Enabled,
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

        $this
            ->rcPostJson($user, $recruiterIdentity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_apply_with_primary_resume(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
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
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id]);

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
        [$user, $identity] = $this->createJobSeekerContext();
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
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk()
            ->assertJsonPath('code', 200);

        $response = $this
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '您已投递过该职位。');
    }

    public function test_job_seeker_can_reapply_after_withdraw(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
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
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id]);

        $applicationId = $applyResponse->json('data.id');

        $this
            ->rcPostJson($user, $identity, '/rc/applications/'.$applicationId.'/withdraw')
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Withdrawn->value);

        $this
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.status', RcApplicationStatus::Pending->value)
            ->assertJsonPath('data.id', $applicationId);
    }

    public function test_recruiter_token_cannot_withdraw_even_when_user_has_job_seeker_identity(): void
    {
        $job = $this->createPublishedJob();
        [$user, $jobSeekerIdentity] = $this->createJobSeekerContext();
        $company = Company::query()->findOrFail($job->company_id);

        $recruiterIdentity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 0,
            'status' => RcIdentityStatus::Enabled,
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

        $applicationId = $this
            ->rcPostJson($user, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $withdrawResponse = $this
            ->rcPostJson($user, $recruiterIdentity, '/rc/applications/'.$applicationId.'/withdraw');

        if ($withdrawResponse->json('code') !== 422) {
            fwrite(STDERR, json_encode($withdrawResponse->json(), JSON_UNESCAPED_UNICODE).PHP_EOL);
        }

        $withdrawResponse
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_list_and_show_own_applications(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
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
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk();

        $applicationId = $applyResponse->json('data.id');

        $this
            ->rcGetJson($user, $identity, '/rc/applications')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.job.title', 'Laravel 工程师');

        $this
            ->rcGetJson($user, $identity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.resume.full_name', '求职者甲')
            ->assertJsonMissingPath('data.resume_snapshot');
    }

    public function test_recruiter_can_list_and_show_company_applications(): void
    {
        [$jobSeeker, $jobSeekerIdentity] = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();
        [$recruiter, $recruiterIdentity] = $this->createRecruiterContext($job->company_id);

        Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $applyResponse = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk();

        $applicationId = $applyResponse->json('data.id');

        $listResponse = $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications?job_id='.$job->id);

        if ($listResponse->json('data.data.0.candidate.full_name') !== '候选人甲') {
            fwrite(STDERR, json_encode($listResponse->json('data.data.0'), JSON_UNESCAPED_UNICODE).PHP_EOL);
        }

        $listResponse
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.candidate.full_name', '候选人甲')
            ->assertJsonMissingPath('data.data.0.resume')
            ->assertJsonMissingPath('data.data.0.resume_snapshot')
            ->assertJsonMissingPath('data.data.0.candidate.phone');

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.resume_snapshot.full_name', '候选人甲')
            ->assertJsonPath('data.resume_snapshot.phone', '13800138000');
    }

    public function test_apply_assigns_default_stage_when_configured(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
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
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id]);

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

    /**
     * @return array{0: User, 1: UserIdentity}
     */
    private function createJobSeekerContext(): array
    {
        $user = User::factory()->create();

        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return [$user, $identity];
    }

    /**
     * @return array{0: User, 1: UserIdentity}
     */
    private function createRecruiterContext(int $companyId): array
    {
        $user = User::factory()->create();
        $company = Company::query()->findOrFail($companyId);

        $identity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return [$user, $identity];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rcGetJson(User $user, ?UserIdentity $identity, string $uri)
    {
        return $this
            ->withHeader('Authorization', 'Bearer '.$this->rcBearerToken($user, $identity))
            ->getJson($uri);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rcPostJson(User $user, ?UserIdentity $identity, string $uri, array $data = [])
    {
        return $this
            ->withHeader('Authorization', 'Bearer '.$this->rcBearerToken($user, $identity))
            ->postJson($uri, $data);
    }

    private function rcBearerToken(User $user, ?UserIdentity $identity): string
    {
        $tokenResult = app(PersonalAccessTokenFactory::class)->make(
            $user->getAuthIdentifier(),
            'rc',
            [],
            'rc_users',
        );

        $token = $tokenResult->getToken();

        if ($token instanceof Token && $identity instanceof UserIdentity) {
            $token->responsible_type = UserIdentity::class;
            $token->responsible_id = $identity->id;
            $token->save();
        }

        return $tokenResult->accessToken;
    }
}
