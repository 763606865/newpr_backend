<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcInterviewMode;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcNotificationType;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Notification;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\PersonalAccessTokenFactory;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
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

        app(ClientRepository::class)->createPersonalAccessGrantClient('RC Notifications Test', 'rc_users');
    }

    public function test_user_can_list_notifications_for_current_identity(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
        $otherUser = User::factory()->create();

        $notification = $this->createNotification($user, $identity, RcNotificationType::InterviewInvitation, false);
        $this->createNotification($otherUser, null, RcNotificationType::OfferSent, false);

        $response = $this->rcGetJson($user, $identity, '/rc/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $notification->id)
            ->assertJsonPath('data.data.0.user_identity_id', $identity->id)
            ->assertJsonPath('data.data.0.user_identity_type', RcIdentityType::JobSeeker->value)
            ->assertJsonPath('data.data.0.type', RcNotificationType::InterviewInvitation->value)
            ->assertJsonPath('data.data.0.is_read', false);
    }

    public function test_recruiter_identity_does_not_see_job_seeker_notifications(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $jobSeekerIdentity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

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

        $this->createNotification($user, $jobSeekerIdentity, RcNotificationType::InterviewInvitation, false);

        $this->rcGetJson($user, $jobSeekerIdentity, '/rc/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->rcGetJson($user, $recruiterIdentity, '/rc/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');

        $this->rcGetJson($user, $recruiterIdentity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_user_level_notifications_are_visible_in_all_identities(): void
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $jobSeekerIdentity = UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

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

        $this->createNotification($user, null, RcNotificationType::ApplicationStatusChanged, false);

        $this->rcGetJson($user, $jobSeekerIdentity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->rcGetJson($user, $recruiterIdentity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_user_can_filter_notifications_by_read_status(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();

        $unread = $this->createNotification($user, $identity, RcNotificationType::InterviewInvitation, false);
        $this->createNotification($user, $identity, RcNotificationType::OfferSent, true);

        $this->rcGetJson($user, $identity, '/rc/notifications?is_read=0')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $unread->id);

        $this->rcGetJson($user, $identity, '/rc/notifications?is_read=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.type', RcNotificationType::OfferSent->value);
    }

    public function test_user_can_get_unread_count(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();

        $this->createNotification($user, $identity, RcNotificationType::InterviewInvitation, false);
        $this->createNotification($user, $identity, RcNotificationType::OfferSent, false);
        $this->createNotification($user, $identity, RcNotificationType::ApplicationStatusChanged, true);

        $this->rcGetJson($user, $identity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_show_marks_notification_as_read(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
        $notification = $this->createNotification($user, $identity, RcNotificationType::InterviewInvitation, false);

        $this->rcGetJson($user, $identity, '/rc/notifications/'.$notification->id)
            ->assertOk()
            ->assertJsonPath('data.is_read', true)
            ->assertJsonPath('data.read_at', fn (mixed $value): bool => $value !== null);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
        $notification = $this->createNotification($user, $identity, RcNotificationType::OfferSent, false);

        $this->rcPostJson($user, $identity, '/rc/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read_for_current_identity(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();

        $this->createNotification($user, $identity, RcNotificationType::InterviewInvitation, false);
        $this->createNotification($user, $identity, RcNotificationType::OfferSent, false);
        $this->createNotification($user, null, RcNotificationType::ApplicationStatusChanged, false);

        $this->rcPostJson($user, $identity, '/rc/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated_count', 3)
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_user_cannot_access_other_users_notification(): void
    {
        [$user, $identity] = $this->createJobSeekerContext();
        $otherUser = User::factory()->create();
        $notification = $this->createNotification($otherUser, null, RcNotificationType::InterviewInvitation, false);

        $this->rcGetJson($user, $identity, '/rc/notifications/'.$notification->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '通知不存在。');

        $this->rcPostJson($user, $identity, '/rc/notifications/'.$notification->id.'/read')
            ->assertOk()
            ->assertJsonPath('code', 404);
    }

    public function test_invite_interview_creates_notification_for_candidate_job_seeker_identity(): void
    {
        $job = $this->createPublishedJob();
        [$jobSeeker, $jobSeekerIdentity] = $this->createJobSeekerContext();
        [$recruiter, $recruiterIdentity] = $this->createRecruiterContext($job->company_id);

        Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $applicationId = $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Offline->value,
            'location' => '南昌高新区',
        ]);

        $notification = Notification::query()
            ->where('user_id', $jobSeeker->id)
            ->where('type', RcNotificationType::InterviewInvitation->value)
            ->first();

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame($jobSeekerIdentity->id, $notification->user_identity_id);
        $this->assertSame($applicationId, $notification->payload['application_id']);

        $this->rcGetJson($jobSeeker, $jobSeekerIdentity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        UserIdentity::query()->create([
            'user_id' => $jobSeeker->id,
            'organization_type' => 'company',
            'organization_id' => $job->company_id,
            'organization_name' => '南昌示例科技有限公司',
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 0,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $recruiterIdentityForSeeker = UserIdentity::query()
            ->where('user_id', $jobSeeker->id)
            ->where('identity_type', RcIdentityType::Recruiter)
            ->first();

        $this->rcGetJson($jobSeeker, $recruiterIdentityForSeeker, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_candidate_response_actions_create_notifications_for_recruiter(): void
    {
        $job = $this->createPublishedJob();
        [$jobSeeker, $jobSeekerIdentity] = $this->createJobSeekerContext();
        [$recruiter, $recruiterIdentity] = $this->createRecruiterContext($job->company_id);

        Resume::query()->create([
            'user_id' => $jobSeeker->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
            'status' => RcResumeStatus::Normal,
            'is_primary' => 1,
        ]);

        $applicationId = $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications', ['job_id' => $job->id])
            ->json('data.id');

        $this->rcGetJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDay()->toDateTimeString(),
            'mode' => RcInterviewMode::Offline->value,
            'location' => '南昌高新区',
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/reject-interview', [
            'note' => '时间冲突',
        ]);

        $rejectInterviewNotification = Notification::query()
            ->where('user_id', $recruiter->id)
            ->where('type', RcNotificationType::InterviewInvitationRejected->value)
            ->first();

        $this->assertInstanceOf(Notification::class, $rejectInterviewNotification);
        $this->assertSame('求职者甲已拒绝「Laravel 工程师」的面试邀请', $rejectInterviewNotification->body);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/invite-interview', [
            'interview_at' => now()->addDays(2)->toDateTimeString(),
            'mode' => RcInterviewMode::Offline->value,
            'location' => '南昌高新区',
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-interview');

        $notification = Notification::query()
            ->where('user_id', $recruiter->id)
            ->where('type', RcNotificationType::InterviewInvitationAccepted->value)
            ->first();

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertSame($recruiterIdentity->id, $notification->user_identity_id);
        $this->assertSame($applicationId, $notification->payload['application_id']);
        $this->assertSame('求职者甲已接受「Laravel 工程师」的面试邀请', $notification->body);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
            'salary' => 18000,
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/reject-offer', [
            'note' => '薪资未达预期',
        ]);

        $rejectOfferNotification = Notification::query()
            ->where('user_id', $recruiter->id)
            ->where('type', RcNotificationType::OfferRejectedByCandidate->value)
            ->first();

        $this->assertInstanceOf(Notification::class, $rejectOfferNotification);
        $this->assertSame('求职者甲已拒绝「Laravel 工程师」的 Offer', $rejectOfferNotification->body);

        $this->rcPostJson($recruiter, $recruiterIdentity, '/rc/applications/'.$applicationId.'/send-offer', [
            'salary' => 20000,
        ]);

        $this->rcPostJson($jobSeeker, $jobSeekerIdentity, '/rc/applications/'.$applicationId.'/accept-offer');

        $acceptOfferNotification = Notification::query()
            ->where('user_id', $recruiter->id)
            ->where('type', RcNotificationType::OfferAcceptedByCandidate->value)
            ->first();

        $this->assertInstanceOf(Notification::class, $acceptOfferNotification);
        $this->assertSame('求职者甲已接受「Laravel 工程师」的 Offer', $acceptOfferNotification->body);

        $this->rcGetJson($recruiter, $recruiterIdentity, '/rc/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 4);
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
            'code' => 'JOB-NOTIFY-001',
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

    private function createNotification(
        User $user,
        ?UserIdentity $identity,
        RcNotificationType $type,
        bool $isRead,
    ): Notification {
        return Notification::query()->create([
            'user_id' => $user->id,
            'user_identity_id' => $identity?->id,
            'type' => $type,
            'title' => $type->getLabel(),
            'body' => '测试通知内容',
            'payload' => ['application_id' => 1],
            'read_at' => $isRead ? now() : null,
            'happened_at' => now(),
        ]);
    }

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
