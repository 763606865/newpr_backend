<?php

namespace Tests\Feature;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcSchoolActivityApplyStatus;
use App\Enums\RcSchoolActivityJoinSource;
use App\Enums\RcSchoolActivityType;
use App\Models\Company;
use App\Models\Rc\SchoolBooth;
use App\Models\Rc\SchoolBoothArea;
use App\Models\Rc\SchoolProfile;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\User;
use App\Support\SchoolActivityInviteCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolActivityInviteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_school_organized_invite_page_allows_company_registration(): void
    {
        $school = $this->createSchool();
        $campusUser = User::factory()->create();
        $this->createCampusManagerIdentity($campusUser, $school);

        $activityId = (int) $this->actingAs($campusUser, 'rc')
            ->postJson('/rc/schools/activities', [
                'title' => '2026 春季双选会',
                'booth_id' => $this->createBoothWithAreas($school)->id,
            ])
            ->json('data.activity.id');

        $this->actingAs($campusUser, 'rc')
            ->postJson("/rc/schools/activities/{$activityId}/publish")
            ->assertOk();

        $inviteCode = SchoolActivityInviteCode::encode($activityId);

        $this->getJson('/cms/school-activities/invite/'.urlencode($inviteCode))
            ->assertOk()
            ->assertJsonPath('data.inviter_name', '北京大学')
            ->assertJsonPath('data.invitation_message', '北京大学邀请你参加2026 春季双选会')
            ->assertJsonPath('data.invite_target', 'company')
            ->assertJsonPath('data.activity.id', $activityId)
            ->assertJsonPath('data.activity.invite_code', $inviteCode);

        $this->postJson('/cms/school-activities/invite/'.urlencode($inviteCode).'/companies', [
            'name' => '新邀约企业有限公司',
            'credit_code' => '91360100MA0000000C',
            'contact_phone' => '13800000000',
        ])
            ->assertOk()
            ->assertJsonPath('data.company.name', '新邀约企业有限公司')
            ->assertJsonPath('data.application.apply_status', RcSchoolActivityApplyStatus::Approved->value)
            ->assertJsonPath('data.application.join_source', RcSchoolActivityJoinSource::SchoolInvite->value);
    }

    public function test_company_organized_invite_page_allows_school_contact_registration(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $activityId = (int) $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::Presentation->value,
                'title' => '企业进校宣讲会',
                'school_codes' => [$school->school_code],
            ])
            ->json('data.activity.id');

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk();

        $inviteCode = SchoolActivityInviteCode::encode($activityId);

        $this->getJson('/cms/school-activities/invite/'.urlencode($inviteCode))
            ->assertOk()
            ->assertJsonPath('data.inviter_name', '南昌示例科技有限公司')
            ->assertJsonPath('data.invitation_message', '南昌示例科技有限公司邀请贵校参与企业进校宣讲会')
            ->assertJsonPath('data.invite_target', 'school');

        $this->postJson('/cms/school-activities/invite/'.urlencode($inviteCode).'/schools', [
            'school_code' => $school->school_code,
            'contact_name' => '张老师',
            'contact_phone' => '13800138000',
            'contact_email' => 'zhang@pku.edu.cn',
        ])
            ->assertOk()
            ->assertJsonPath('data.school_application.school_code', $school->school_code)
            ->assertJsonPath('data.school_application.contact_name', '张老师')
            ->assertJsonPath('data.school_application.apply_status', RcSchoolActivityApplyStatus::Pending->value);

        $this->assertDatabaseHas('rc_school_activity_schools', [
            'activity_id' => $activityId,
            'school_id' => $school->id,
            'contact_name' => '张老师',
            'apply_status' => RcSchoolActivityApplyStatus::Pending->value,
        ]);
    }

    public function test_company_organized_job_fair_without_preset_schools_allows_school_registration(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $activityId = (int) $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::JobFair->value,
                'title' => '企业线下招聘会',
            ])
            ->json('data.activity.id');

        $this->assertDatabaseMissing('rc_school_activity_schools', [
            'activity_id' => $activityId,
        ]);

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk();

        $inviteCode = SchoolActivityInviteCode::encode($activityId);

        $this->getJson('/cms/school-activities/invite/'.urlencode($inviteCode))
            ->assertOk()
            ->assertJsonPath('data.invite_target', 'school');

        $this->postJson('/cms/school-activities/invite/'.urlencode($inviteCode).'/schools', [
            'school_code' => $school->school_code,
            'contact_name' => '李老师',
            'contact_phone' => '13800138001',
        ])
            ->assertOk()
            ->assertJsonPath('data.school_application.school_code', $school->school_code)
            ->assertJsonPath('data.school_application.apply_status', RcSchoolActivityApplyStatus::Pending->value);

        $this->assertDatabaseHas('rc_school_activity_schools', [
            'activity_id' => $activityId,
            'school_id' => $school->id,
            'contact_name' => '李老师',
            'apply_status' => RcSchoolActivityApplyStatus::Pending->value,
        ]);
    }

    public function test_presentation_rejects_school_not_in_target_list(): void
    {
        $targetSchool = $this->createSchool();
        $otherSchool = School::query()->create([
            'school_code' => '4111010002',
            'name' => '清华大学',
        ]);
        SchoolProfile::query()->create([
            'school_code' => $otherSchool->school_code,
            'allow_company_apply_activity' => true,
        ]);
        SchoolProfile::query()->create([
            'school_code' => $targetSchool->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $activityId = (int) $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::Presentation->value,
                'title' => '企业进校宣讲会',
                'school_codes' => [$targetSchool->school_code],
            ])
            ->json('data.activity.id');

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk();

        $inviteCode = SchoolActivityInviteCode::encode($activityId);

        $this->postJson('/cms/school-activities/invite/'.urlencode($inviteCode).'/schools', [
            'school_code' => $otherSchool->school_code,
            'contact_name' => '王老师',
            'contact_phone' => '13800138002',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', '该院校不在活动申请入校名单内。');
    }

    public function test_company_registration_is_rejected_for_company_organized_activity(): void
    {
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'allow_company_apply_activity' => true,
        ]);
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);
        $recruiterUser = User::factory()->create();
        $this->createRecruiterIdentity($recruiterUser, $company);

        $activityId = (int) $this->actingAs($recruiterUser, 'rc')
            ->postJson('/rc/companies/school-activities', [
                'type' => RcSchoolActivityType::Presentation->value,
                'title' => '企业进校宣讲会',
                'school_codes' => [$school->school_code],
            ])
            ->json('data.activity.id');

        $this->actingAs($recruiterUser, 'rc')
            ->postJson("/rc/companies/school-activities/{$activityId}/publish")
            ->assertOk();

        $inviteCode = SchoolActivityInviteCode::encode($activityId);

        $this->postJson('/cms/school-activities/invite/'.urlencode($inviteCode).'/companies', [
            'name' => '其他企业有限公司',
            'credit_code' => '91360100MA0000000D',
            'contact_phone' => '13800000001',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', '仅学校主办的活动支持此操作。');
    }

    private function createSchool(): School
    {
        return School::query()->create([
            'school_code' => '4111010001',
            'name' => '北京大学',
        ]);
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

    private function createBoothWithAreas(School $school): SchoolBooth
    {
        $booth = SchoolBooth::query()->create([
            'school_code' => $school->school_code,
            'name' => '体育馆 A 区',
        ]);

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
