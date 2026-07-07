<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcNotificationType;
use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityBusinessStatus;
use App\Enums\RcSchoolActivityJobAuditStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Enums\RcSchoolActivityMode;
use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Job;
use App\Models\Rc\Notification;
use App\Models\Rc\SchoolActivity;
use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\SchoolProfile;
use App\Models\User;
use App\Services\RcSchoolActivityApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SchoolActivityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_campus_manager_can_manage_booths_and_areas(): void
    {
        $school = $this->createSchool();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $boothResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/booths', [
                'name' => '体育馆 A 区',
                'address' => '主校区体育馆',
            ])
            ->assertOk()
            ->assertJsonPath('data.booth.name', '体育馆 A 区');

        $boothId = (int) $boothResponse->json('data.booth.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/booths/{$boothId}/areas", [
                'name' => 'A 区',
                'start_no' => 1,
                'end_no' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('data.area.total_booth_count', 10);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/schools/booths')
            ->assertOk()
            ->assertJsonPath('data.data.0.areas_count', 1)
            ->assertJsonPath('data.data.0.total_booth_count', 10);
    }

    public function test_campus_manager_can_manage_activity_and_invite_company(): void
    {
        $school = $this->createSchool();
        $company = $this->createCompany();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $booth = $this->createBoothWithAreas($school);

        $activityResponse = $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/activities', [
                'type' => RcSchoolActivityType::DualSelection->value,
                'title' => '2026 春季双选会',
                'booth_id' => $booth->id,
                'activity_mode' => RcSchoolActivityMode::Online->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Draft->value)
            ->assertJsonPath('data.activity.booth_id', $booth->id)
            ->assertJsonPath('data.activity.activity_mode', RcSchoolActivityMode::Online->value)
            ->assertJsonPath('data.activity.business_status', RcSchoolActivityBusinessStatus::Draft->value);

        $activityId = (int) $activityResponse->json('data.activity.id');

        $this->actingAs($user, 'rc')
            ->getJson("/rc/schools/activities/{$activityId}")
            ->assertOk()
            ->assertJsonCount(10, 'data.activity.activity_booths');

        $activityBoothId = (int) $this->actingAs($user, 'rc')
            ->getJson("/rc/schools/activities/{$activityId}")
            ->json('data.activity.activity_booths.0.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Published->value);

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/company-invitations", [
                'company_id' => $company->id,
                'activity_booth_id' => $activityBoothId,
            ])
            ->assertOk()
            ->assertJsonPath('data.application.apply_status', RcSchoolActivityApplyStatus::Approved->value)
            ->assertJsonPath('data.application.join_source', RcSchoolActivityJoinSource::SchoolInvite->value);

        $this->assertDatabaseHas('rc_school_activity_booths', [
            'id' => $activityBoothId,
            'company_id' => $company->id,
        ]);
    }

    public function test_campus_manager_can_update_activity_mode_and_receive_business_status(): void
    {
        $school = $this->createSchool();
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $activityId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '活动模式测试',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($user, 'rc')
            ->putJson("/rc/schools/activities/{$activityId}", [
                'activity_mode' => RcSchoolActivityMode::Online->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.activity.activity_mode', RcSchoolActivityMode::Online->value)
            ->assertJsonPath('data.activity.activity_mode_label', RcSchoolActivityMode::Online->getLabel())
            ->assertJsonPath('data.activity.business_status', RcSchoolActivityBusinessStatus::Draft->value);
    }

    public function test_recruiter_can_apply_submit_jobs_and_campus_manager_can_review(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = $this->createCompany();
        $campusUser = User::factory()->create();
        $recruiterUser = User::factory()->create();
        $this->createCampusManagerIdentity($campusUser, $school);
        $this->createRecruiterIdentity($recruiterUser, $company);

        $activityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '春季招聘会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $applicationResponse = $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/apply", [
                'remark' => '希望参加',
            ])
            ->assertOk()
            ->assertJsonPath('data.application.apply_status', RcSchoolActivityApplyStatus::Pending->value);

        $applicationId = (int) $applicationResponse->json('data.application.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/company-applications/{$applicationId}/approve")
            ->assertOk()
            ->assertJsonPath('data.application.apply_status', RcSchoolActivityApplyStatus::Approved->value);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB001',
            'title' => '后端工程师',
        ]);

        $submitResponse = $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/jobs", [
                'job_ids' => [$job->id],
            ])
            ->assertOk()
            ->assertJsonPath('data.activity_jobs.0.audit_status', RcSchoolActivityJobAuditStatus::Pending->value);

        $activityJobId = (int) $submitResponse->json('data.activity_jobs.0.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/job-applications/{$activityJobId}/approve")
            ->assertOk()
            ->assertJsonPath('data.activity_job.audit_status', RcSchoolActivityJobAuditStatus::Approved->value);

        $this->actingAs($recruiterUser, 'rc')
            ->getJson("/rc/companies/school-activities/{$activityId}/my-application")
            ->assertOk()
            ->assertJsonPath('data.application.apply_status', RcSchoolActivityApplyStatus::Approved->value);

        $this->actingAs($recruiterUser, 'rc')
            ->getJson("/rc/companies/school-activities/{$activityId}/jobs")
            ->assertOk()
            ->assertJsonPath('data.data.0.job.title', '后端工程师');
    }

    public function test_recruiter_can_list_participated_and_available_school_activities(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = $this->createCompany();
        $campusUser = User::factory()->create();
        $recruiterUser = User::factory()->create();
        $this->createCampusManagerIdentity($campusUser, $school);
        $this->createRecruiterIdentity($recruiterUser, $company);

        $invitedActivityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '邀约双选会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$invitedActivityId}/publish")
            ->assertOk();

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$invitedActivityId}/company-invitations", [
                'company_id' => $company->id,
            ])
            ->assertOk();

        $appliedActivityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '申请双选会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$appliedActivityId}/publish")
            ->assertOk();

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$appliedActivityId}/apply")
            ->assertOk();

        $openActivityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '可报名活动',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$openActivityId}/publish")
            ->assertOk();

        $organizerActivity = SchoolActivity::query()->create([
            'type' => RcSchoolActivityType::Presentation,
            'title' => '企业宣讲会',
            'status' => RcSchoolActivityStatus::Published,
            'organizer_type' => RcSchoolActivityOrganizerType::Company,
            'organizer_id' => $company->id,
        ]);

        RcSchoolActivityApplicationService::make()->ensureOrganizerCompanyApplication($organizerActivity);

        $mineResponse = $this->actingAs($recruiterUser, 'rc')
            ->getJson('/rc/companies/school-activities')
            ->assertOk()
            ->assertJsonCount(3, 'data.data');

        $mineItems = collect($mineResponse->json('data.data'))->keyBy('activity.title');

        $this->assertSame(RcSchoolActivityJoinSource::Organizer->value, $mineItems['企业宣讲会']['application']['join_source']);
        $this->assertTrue($mineItems['企业宣讲会']['is_organizer']);
        $this->assertSame(RcSchoolActivityJoinSource::CompanyApply->value, $mineItems['申请双选会']['application']['join_source']);
        $this->assertFalse($mineItems['申请双选会']['is_organizer']);
        $this->assertSame(RcSchoolActivityJoinSource::SchoolInvite->value, $mineItems['邀约双选会']['application']['join_source']);
        $this->assertFalse($mineItems['邀约双选会']['is_organizer']);

        $availableResponse = $this->actingAs($recruiterUser, 'rc')
            ->getJson('/rc/companies/school-activities/available')
            ->assertOk()
            ->assertJsonCount(4, 'data.data');

        $availableTitles = collect($availableResponse->json('data.data'))->pluck('title')->all();
        $this->assertContains('可报名活动', $availableTitles);
    }

    public function test_recruiter_can_create_and_manage_company_organized_activity(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = $this->createCompany();
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $createResponse = $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::Presentation->value,
                'title' => '企业进校宣讲会',
                'school_codes' => [$school->school_code],
                'start_time' => now()->addWeek()->toDateTimeString(),
                'activity_mode' => RcSchoolActivityMode::Online->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Draft->value)
            ->assertJsonPath('data.activity.organizer_type', RcSchoolActivityOrganizerType::Company->value)
            ->assertJsonPath('data.activity.schools.0.school_code', $school->school_code)
            ->assertJsonPath('data.activity.business_status', RcSchoolActivityBusinessStatus::Draft->value);

        $activityId = (int) $createResponse->json('data.activity.id');

        $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::DualSelection->value,
                'title' => '非法双选会',
                'school_codes' => [$school->school_code],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        $this->actingAs($recruiterUser, 'rc')
            ->getJson('/rc/companies/school-activities/organized')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.title', '企业进校宣讲会');

        $this->actingAs($recruiterUser, 'rc')
            ->getJson("/rc/companies/school-activities/{$activityId}")
            ->assertOk()
            ->assertJsonPath('data.activity.title', '企业进校宣讲会');

        $this->actingAs($recruiterUser, 'rc')
            ->putJson("/rc/companies/school-activities/{$activityId}", [
                'title' => '企业进校宣讲会（更新）',
                'activity_mode' => RcSchoolActivityMode::Offline->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.activity.title', '企业进校宣讲会（更新）')
            ->assertJsonPath('data.activity.activity_mode', RcSchoolActivityMode::Offline->value)
            ->assertJsonPath('data.activity.business_status', RcSchoolActivityBusinessStatus::Draft->value);

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Published->value);

        $this->assertDatabaseHas('rc_school_activity_companies', [
            'activity_id' => $activityId,
            'company_id' => $company->id,
            'join_source' => RcSchoolActivityJoinSource::Organizer->value,
            'apply_status' => RcSchoolActivityApplyStatus::Approved->value,
        ]);

        $this->actingAs($recruiterUser, 'rc')
            ->getJson('/rc/companies/school-activities')
            ->assertOk()
            ->assertJsonPath('data.data.0.is_organizer', true);

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/end")
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Ended->value);
    }

    public function test_recruiter_can_create_offline_job_fair_without_school_codes(): void
    {
        $company = $this->createCompany();
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $createResponse = $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::JobFair->value,
                'title' => '企业线下招聘会',
                'address' => '企业总部大厅',
            ])
            ->assertOk()
            ->assertJsonPath('data.activity.type', RcSchoolActivityType::JobFair->value)
            ->assertJsonPath('data.activity.schools', [])
            ->assertJsonPath('data.activity.activity_mode', RcSchoolActivityMode::Offline->value);

        $activityId = (int) $createResponse->json('data.activity.id');

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk()
            ->assertJsonPath('data.activity.status', RcSchoolActivityStatus::Published->value);

        $this->assertDatabaseMissing('rc_school_activity_schools', [
            'activity_id' => $activityId,
        ]);
    }

    public function test_recruiter_cannot_create_presentation_without_school_codes(): void
    {
        $company = $this->createCompany();
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::Presentation->value,
                'title' => '缺少院校的宣讲会',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['school_codes']);
    }

    public function test_recruiter_cannot_create_on_campus_job_fair_when_school_disallows_apply(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => false,
        ]);
        $company = $this->createCompany();
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::JobFair->value,
                'title' => '企业招聘会',
                'school_codes' => [$school->school_code],
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '院校「北京大学」暂未开放企业自主进校申请。');
    }

    public function test_activity_show_returns_activity_booths_list(): void
    {
        $disk = Mockery::mock();
        $disk->shouldReceive('url')
            ->once()
            ->with('uploads/companies/logo.png')
            ->andReturn('https://cdn.example.com/uploads/companies/logo.png');

        Storage::shouldReceive('disk')
            ->once()
            ->with('oss')
            ->andReturn($disk);

        $school = $this->createSchool();
        $company = $this->createCompany();
        CompanyProfile::query()->create([
            'company_id' => $company->id,
            'short_name' => '示例科技',
            'logo' => 'uploads/companies/logo.png',
        ]);
        $booth = $this->createBoothWithAreas($school);
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $activityId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '展位列表测试',
                'booth_id' => $booth->id,
            ])
            ->json('data.activity.id');

        $activityBoothId = (int) $this->actingAs($user, 'rc')
            ->getJson("/rc/schools/activities/{$activityId}")
            ->assertOk()
            ->assertJsonPath('data.activity.booth_id', $booth->id)
            ->assertJsonCount(1, 'data.activity.booth.areas')
            ->assertJsonPath('data.activity.booth.areas.0.code', 'A')
            ->assertJsonCount(10, 'data.activity.activity_booths')
            ->assertJsonPath('data.activity.activity_booths.0.booth_no', 'A-01')
            ->assertJsonPath('data.activity.activity_booths.0.company_id', null)
            ->json('data.activity.activity_booths.0.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/company-invitations", [
                'company_id' => $company->id,
                'activity_booth_id' => $activityBoothId,
            ])
            ->assertOk();

        $this->actingAs($user, 'rc')
            ->getJson("/rc/schools/activities/{$activityId}")
            ->assertOk()
            ->assertJsonPath('data.activity.activity_booths.0.company_id', $company->id)
            ->assertJsonPath('data.activity.activity_booths.0.company.id', $company->id)
            ->assertJsonPath('data.activity.activity_booths.0.company.name', $company->name)
            ->assertJsonPath('data.activity.companies.0.id', $company->id)
            ->assertJsonPath('data.activity.companies.0.display_name', '示例科技')
            ->assertJsonPath('data.activity.companies.0.display_logo', 'https://cdn.example.com/uploads/companies/logo.png');
    }

    public function test_invite_company_notifies_all_company_recruiters(): void
    {
        $school = $this->createSchool();
        $company = $this->createCompany();
        $recruiterUserA = User::factory()->create();
        $recruiterUserB = User::factory()->create();
        $campusUser = User::factory()->create();
        $this->createCampusManagerIdentity($campusUser, $school);
        $this->createRecruiterIdentity($recruiterUserA, $company);
        UserIdentity::query()->create([
            'user_id' => $recruiterUserB->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => 'HR 主管',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $activityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '2026 春季双选会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/company-invitations", [
                'company_id' => $company->id,
            ])
            ->assertOk();

        $this->assertSame(2, Notification::query()
            ->where('type', RcNotificationType::SchoolActivityCompanyInvited)
            ->count());

        $this->assertDatabaseHas('rc_notifications', [
            'user_id' => $recruiterUserA->id,
            'type' => RcNotificationType::SchoolActivityCompanyInvited->value,
            'title' => '校招活动邀约',
        ]);

        $this->assertDatabaseHas('rc_notifications', [
            'user_id' => $recruiterUserB->id,
            'type' => RcNotificationType::SchoolActivityCompanyInvited->value,
        ]);
    }

    public function test_approve_company_application_notifies_all_company_recruiters(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = $this->createCompany();
        $recruiterUserA = User::factory()->create();
        $recruiterUserB = User::factory()->create();
        $campusUser = User::factory()->create();
        $this->createCampusManagerIdentity($campusUser, $school);
        $this->createRecruiterIdentity($recruiterUserA, $company);
        UserIdentity::query()->create([
            'user_id' => $recruiterUserB->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => '招聘经理',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        $activityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '春季招聘会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $applicationId = (int) $this->actingAs($recruiterUserA, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/apply")
            ->json('data.application.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/company-applications/{$applicationId}/approve")
            ->assertOk();

        $this->assertSame(2, Notification::query()
            ->where('type', RcNotificationType::SchoolActivityCompanyApproved)
            ->count());

        $this->assertDatabaseHas('rc_notifications', [
            'user_id' => $recruiterUserA->id,
            'type' => RcNotificationType::SchoolActivityCompanyApproved->value,
            'title' => '校招活动审批通过',
        ]);

        $this->assertDatabaseHas('rc_notifications', [
            'user_id' => $recruiterUserB->id,
            'type' => RcNotificationType::SchoolActivityCompanyApproved->value,
        ]);
    }

    public function test_cannot_update_booth_config_after_publish_or_company_invitation(): void
    {
        $school = $this->createSchool();
        $company = $this->createCompany();
        $boothA = $this->createBoothWithAreas($school, ['name' => '体育馆 A 区']);
        $boothB = $this->createBoothWithAreas($school, ['name' => '体育馆 B 区']);
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user, $school);

        $activityId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '展位锁定测试',
                'booth_id' => $boothA->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $this->actingAs($user, 'rc')
            ->putJson("/rc/schools/activities/{$activityId}", [
                'booth_id' => $boothB->id,
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '活动已发布或已结束，不可修改展位配置。');

        $draftActivityId = (int) $this->actingAs($user, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '草稿活动',
                'booth_id' => $boothA->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($user, 'rc')
            ->postJson("/rc/schools/activities/{$draftActivityId}/company-invitations", [
                'company_id' => $company->id,
            ])
            ->assertOk();

        $this->actingAs($user, 'rc')
            ->putJson("/rc/schools/activities/{$draftActivityId}", [
                'booth_id' => $boothB->id,
            ])
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '活动已有企业报名记录，不可修改展位配置。');
    }

    private function createSchool(): School
    {
        return School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
    }

    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ], $attributes));
    }

    private function createCampusManagerIdentity(User $user, School $school): UserIdentity
    {
        return UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => RcIdentityType::CampusManager->getLabel(),
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '就业办主任',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);
    }

    private function createRecruiterIdentity(User $user, Company $company): UserIdentity
    {
        return UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => RcIdentityType::Recruiter->getLabel(),
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'job_title' => 'HR',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createBoothWithAreas(School $school, array $attributes = []): SchoolBooth
    {
        $booth = SchoolBooth::query()->create(array_merge([
            'school_code' => $school->school_code,
            'name' => '体育馆 A 区',
        ], $attributes));

        SchoolBoothArea::query()->create([
            'booth_id' => $booth->id,
            'code' => 'A',
            'name' => 'A 区',
            'start_no' => 1,
            'end_no' => 10,
            'total_booth_count' => 10,
        ]);

        return $booth;
    }
}
