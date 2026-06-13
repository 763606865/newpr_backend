<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationFlowActionType;
use App\Enums\RcApplicationStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcInterviewMode;
use App\Enums\RcInterviewStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStageStatus;
use App\Enums\RcJobStatus;
use App\Enums\RcOfferStatus;
use App\Enums\RcResumeStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\JobStage;
use App\Models\Rc\Offer;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeLanguage;
use App\Models\Rc\ResumeSkill;
use App\Models\Rc\ResumeWork;
use App\Models\Rc\UserIdentity;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $this->assertArrayHasKey('works', $application->resume_snapshot);
        $this->assertArrayHasKey('educations', $application->resume_snapshot);
        $this->assertArrayHasKey('languages', $application->resume_snapshot);
        $this->assertArrayHasKey('skills', $application->resume_snapshot);

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

        $this
            ->rcPostJson($user, $recruiterIdentity, '/rc/applications/'.$applicationId.'/withdraw')
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

        $resume = Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $this->seedResumeSections($resume, $jobSeeker->id);

        $applyResponse = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->assertOk();

        $applicationId = $applyResponse->json('data.id');

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications?job_id='.$job->id)
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.candidate.full_name', '候选人甲')
            ->assertJsonMissingPath('data.data.0.resume')
            ->assertJsonMissingPath('data.data.0.resume_snapshot')
            ->assertJsonMissingPath('data.data.0.candidate.phone');

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Screening->value)
            ->assertJsonPath('data.status_label', '筛选中')
            ->assertJsonPath('data.resume_snapshot.full_name', '候选人甲')
            ->assertJsonPath('data.resume_snapshot.phone', '138****8000')
            ->assertJsonPath('data.resume_snapshot.email', 'can******@example.com')
            ->assertJsonPath('data.resume_snapshot.works.0.position', '后端开发')
            ->assertJsonPath('data.resume_snapshot.educations.0.school_name', '浙江大学')
            ->assertJsonPath('data.resume_snapshot.languages.0.language', '英语')
            ->assertJsonPath('data.resume_snapshot.skills.0.skill_name', 'Laravel');
    }

    public function test_recruiter_show_falls_back_to_resume_relations_when_snapshot_sections_empty(): void
    {
        [$jobSeeker, $jobSeekerIdentity] = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();
        [$recruiter, $recruiterIdentity] = $this->createRecruiterContext($job->company_id);

        $resume = Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->seedResumeSections($resume, $jobSeeker->id);

        Application::query()->whereKey($applicationId)->update([
            'resume_snapshot' => [
                'full_name' => '候选人甲',
                'phone' => '13800138000',
                'email' => 'candidate@example.com',
                'works' => [],
                'educations' => [],
                'languages' => [],
                'skills' => [],
            ],
        ]);

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.resume_snapshot.phone', '138****8000')
            ->assertJsonPath('data.resume_snapshot.email', 'can******@example.com')
            ->assertJsonPath('data.resume_snapshot.works.0.position', '后端开发')
            ->assertJsonPath('data.resume_snapshot.educations.0.school_name', '浙江大学');
    }

    public function test_job_seeker_show_includes_structured_resume_sections(): void
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

        $this->seedResumeSections($resume, $user->id);

        $applicationId = $this
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this
            ->rcGetJson($user, $identity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.resume.full_name', '求职者甲')
            ->assertJsonPath('data.resume.works.0.position', '后端开发')
            ->assertJsonPath('data.resume.educations.0.school_name', '浙江大学')
            ->assertJsonPath('data.resume.languages.0.language', '英语')
            ->assertJsonPath('data.resume.skills.0.skill_name', 'Laravel');
    }

    public function test_job_seeker_show_does_not_change_application_status(): void
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

        $applicationId = $this
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this
            ->rcGetJson($user, $identity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Pending->value);
    }

    public function test_recruiter_can_run_application_flow_operations(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Screening->value);

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
                'interview_at' => now()->addDay()->toDateTimeString(),
                'mode' => RcInterviewMode::Online->value,
                'meeting_url' => 'https://meet.example.com/room-1',
                'interviewer_name' => '张经理',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Screening->value)
            ->assertJsonPath('data.pending_interview_invitation.interviewer_name', '张经理');

        $this->assertDatabaseHas('rc_interviews', [
            'application_id' => $applicationId,
            'interviewer_name' => '张经理',
            'status' => RcInterviewStatus::AwaitingCandidate->value,
        ]);

        $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-interview')
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Interviewing->value)
            ->assertJsonMissingPath('data.pending_interview_invitation');

        $this->assertDatabaseHas('rc_interviews', [
            'application_id' => $applicationId,
            'status' => RcInterviewStatus::Scheduled->value,
        ]);

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
                'salary' => 18000,
                'salary_unit' => RcSalaryUnit::Month->value,
                'has_probation' => true,
                'remuneration_note' => '五险一金，年终奖按公司制度',
                'attendance_note' => '周一至周五 9:00-18:00',
                'entry_date' => now()->addMonth()->toDateString(),
                'extra' => [
                    'probation_months' => 3,
                    'probation_salary' => '15000.00',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Offering->value);

        $offer = Offer::query()->where('application_id', $applicationId)->firstOrFail();
        $this->assertSame(RcOfferStatus::Sent, $offer->status);
        $this->assertSame($jobSeeker->id, $offer->receive_user_id);
        $this->assertSame($jobSeekerIdentity->id, $offer->receive_user_identity_id);
        $this->assertSame('18000.00', (string) $offer->salary);
        $this->assertTrue($offer->has_probation);
        $this->assertSame('五险一金，年终奖按公司制度', $offer->remuneration_note);
        $this->assertSame(3, $offer->extra['probation_months']);

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('data.offer.salary', '18000.00')
            ->assertJsonPath('data.offer.status', RcOfferStatus::Sent->value)
            ->assertJsonPath('data.offer.has_probation', true);

        $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-offer', [
                'note' => '期待加入团队',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Offering->value)
            ->assertJsonPath('data.offer.status', RcOfferStatus::Accepted->value);

        $offer->refresh();
        $this->assertSame(RcOfferStatus::Accepted, $offer->status);
        $this->assertNotNull($offer->replied_at);

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/hire')
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Hired->value);

        $offer->refresh();
        $this->assertSame(RcOfferStatus::Accepted, $offer->status);
    }

    public function test_recruiter_cannot_hire_without_offer_accepted(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Online->value,
            'meeting_url' => 'https://meet.example.com/room-1',
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-interview');

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
            'salary' => 18000,
        ]);

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/hire')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '候选人尚未接受 Offer。');
    }

    public function test_job_seeker_cannot_accept_offer_without_sent_offer(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Online->value,
            'meeting_url' => 'https://meet.example.com/room-1',
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-interview');

        $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-offer')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '当前状态不可接受 Offer。');
    }

    public function test_job_seeker_can_reject_offer_and_return_to_interviewing(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Online->value,
            'meeting_url' => 'https://meet.example.com/room-1',
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-interview');

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
            'salary' => 18000,
        ]);

        $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/reject-offer', [
                'note' => '薪资未达预期',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Interviewing->value)
            ->assertJsonMissingPath('data.offer');

        $offer = Offer::query()->where('application_id', $applicationId)->firstOrFail();
        $this->assertSame(RcOfferStatus::Rejected, $offer->status);
        $this->assertNotNull($offer->replied_at);

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
                'salary' => 20000,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Offering->value);

        $offer->refresh();
        $this->assertSame(RcOfferStatus::Sent, $offer->status);
        $this->assertSame('20000.00', (string) $offer->salary);
    }

    public function test_job_seeker_can_reject_interview_invitation_and_return_to_screening(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Online->value,
            'meeting_url' => 'https://meet.example.com/room-1',
        ]);

        $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/reject-interview', [
                'note' => '时间冲突',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Screening->value)
            ->assertJsonMissingPath('data.pending_interview_invitation');

        $this->assertDatabaseHas('rc_interviews', [
            'application_id' => $applicationId,
            'status' => RcInterviewStatus::Cancelled->value,
        ]);
    }

    public function test_recruiter_can_reject_application(): void
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

        $applicationId = $this
            ->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this
            ->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId)
            ->assertOk();

        $this
            ->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/reject', [
                'note' => '与岗位不匹配',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', RcApplicationStatus::Rejected->value);

        $this->assertDatabaseHas('rc_application_flows', [
            'application_id' => $applicationId,
            'action_type' => RcApplicationFlowActionType::Reject->value,
            'note' => '与岗位不匹配',
        ]);
    }

    public function test_recruiter_flow_action_requires_recruiter_identity(): void
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

        $applicationId = $this
            ->rcPostJson($user, $identity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this
            ->rcPostJson($user, $identity, '/rc/applications/'.$applicationId.'/reject')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
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

    private function seedResumeSections(Resume $resume, int $userId): void
    {
        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $userId,
            'company_name' => '杭州示例科技有限公司',
            'position' => 'Laravel 工程师',
            'position_code' => 'backend-developer',
            'start_date' => '2022-01-01',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $userId,
            'school_name' => '浙江大学',
            'major' => '软件工程',
            'start_date' => '2018-09-01',
        ]);

        ResumeLanguage::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $userId,
            'language' => '英语',
        ]);

        ResumeSkill::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $userId,
            'skill_name' => 'Laravel',
        ]);
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
        $this->resetRcAuth();

        return $this
            ->withHeader('Authorization', 'Bearer '.$this->rcBearerToken($user, $identity))
            ->getJson($uri);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function rcPostJson(User $user, ?UserIdentity $identity, string $uri, array $data = [])
    {
        $this->resetRcAuth();

        return $this
            ->withHeader('Authorization', 'Bearer '.$this->rcBearerToken($user, $identity))
            ->postJson($uri, $data);
    }

    private function resetRcAuth(): void
    {
        Auth::forgetGuards();
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
