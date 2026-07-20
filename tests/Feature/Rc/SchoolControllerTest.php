<?php

namespace Tests\Feature\Rc;

use App\Enums\RcEducationLevel;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\SchoolProfileStatus;
use App\Models\Rc\UserIdentity;
use App\Models\School;
use App\Models\SchoolProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_bind_requires_campus_manager_identity(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $school->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为校招负责人身份。');
    }

    public function test_bind_links_existing_school_to_campus_manager_identity(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        $identity = $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $school->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.school.id', $school->id)
            ->assertJsonPath('data.school.school_code', '4111010001')
            ->assertJsonPath('data.identity.id', $identity->id)
            ->assertJsonPath('data.identity.organization_type', 'school')
            ->assertJsonPath('data.identity.organization_id', $school->id)
            ->assertJsonPath('data.identity.organization_name', $school->name)
            ->assertJsonPath('data.identity.job_title', '就业办主任')
            ->assertJsonPath('data.identity.has_basic_info', true);

        $this->assertDatabaseHas('rc_user_identities', [
            'id' => $identity->id,
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '就业办主任',
        ]);
    }

    public function test_bind_returns_validation_error_when_school_does_not_exist(): void
    {
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => '9999999999',
                'job_title' => '就业办主任',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['school_code']);
    }

    public function test_bind_requires_school_code(): void
    {
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'job_title' => '就业办主任',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['school_code']);
    }

    public function test_bind_rejects_disabled_school_profile(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'status' => SchoolProfileStatus::Disabled,
        ]);
        $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $school->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '该学校已被禁用，无法绑定。');
    }

    public function test_bind_rejects_when_same_school_already_bound(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        $this->createCampusManagerIdentity($user, [
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '已有岗位',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $school->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '您已绑定该学校。');
    }

    public function test_bind_creates_new_identity_when_current_identity_already_has_school(): void
    {
        $user = User::factory()->create();
        $schoolA = $this->createSchool();
        $schoolB = $this->createSchool([
            'name' => '复旦大学',
            'school_code' => '4131010003',
        ]);
        $boundIdentity = $this->createCampusManagerIdentity($user, [
            'organization_type' => 'school',
            'organization_id' => $schoolA->id,
            'organization_name' => $schoolA->name,
            'job_title' => '已有岗位',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $schoolB->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.school.id', $schoolB->id)
            ->assertJsonPath('data.identity.organization_id', $schoolB->id);

        $this->assertNotEquals($boundIdentity->id, $response->json('data.identity.id'));
        $this->assertDatabaseCount('rc_user_identities', 2);
    }

    public function test_bind_creates_reviewing_profile_when_missing(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/schools/bind', [
                'school_code' => $school->school_code,
                'job_title' => '就业办主任',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.school.profile.status', SchoolProfileStatus::Reviewing->value);

        $this->assertDatabaseHas('rc_school_profiles', [
            'school_code' => $school->school_code,
            'status' => SchoolProfileStatus::Reviewing->value,
        ]);
    }

    public function test_profile_show_requires_bound_campus_manager_school(): void
    {
        $user = User::factory()->create();
        $this->createCampusManagerIdentity($user);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/schools/profile');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为校招负责人身份并绑定学校。');
    }

    public function test_profile_show_returns_school_profile(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'short_name' => '北大',
            'status' => SchoolProfileStatus::Reviewing,
        ]);
        $this->createCampusManagerIdentity($user, [
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '就业办主任',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/schools/profile');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.profile.school_code', '4111010001')
            ->assertJsonPath('data.profile.short_name', '北大')
            ->assertJsonPath('data.profile.status', SchoolProfileStatus::Reviewing->value);
    }

    public function test_profile_update_updates_school_profile(): void
    {
        $user = User::factory()->create();
        $school = $this->createSchool();
        SchoolProfile::query()->create([
            'school_code' => $school->school_code,
            'status' => SchoolProfileStatus::Reviewing,
        ]);
        $this->createCampusManagerIdentity($user, [
            'organization_type' => 'school',
            'organization_id' => $school->id,
            'organization_name' => $school->name,
            'job_title' => '就业办主任',
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/schools/profile', [
                'short_name' => '北大',
                'contact_name' => '张老师',
                'contact_phone' => '13800000000',
                'intro' => '国内顶尖高校。',
                'official_logo' => 'uploads/rc/school-official-logo.png',
                'logo' => 'uploads/rc/school-logo.png',
                'education_levels' => [RcEducationLevel::Bachelor->value],
                'main_education_level' => RcEducationLevel::Bachelor->value,
                'allow_company_apply_activity' => false,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.profile.short_name', '北大')
            ->assertJsonPath('data.profile.contact_name', '张老师')
            ->assertJsonPath('data.profile.official_logo', 'uploads/rc/school-official-logo.png')
            ->assertJsonPath('data.profile.status', SchoolProfileStatus::Normal->value)
            ->assertJsonPath('data.profile.education_levels.0', RcEducationLevel::Bachelor->value)
            ->assertJsonPath('data.profile.allow_company_apply_activity', false);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'official_logo' => 'uploads/rc/school-official-logo.png',
        ]);
        $this->assertDatabaseHas('rc_school_profiles', [
            'school_code' => $school->school_code,
            'short_name' => '北大',
            'contact_name' => '张老师',
            'status' => SchoolProfileStatus::Normal->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createSchool(array $attributes = []): School
    {
        return School::query()->create(array_merge([
            'school_code' => '4111010001',
            'name' => '北京大学',
            'province' => '北京市',
            'city' => '北京市',
            'competent_dept' => '教育部',
            'type' => '本科',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCampusManagerIdentity(User $user, array $attributes = []): UserIdentity
    {
        return UserIdentity::query()->create(array_merge([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::CampusManager,
            'identity_name' => RcIdentityType::CampusManager->getLabel(),
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ], $attributes));
    }
}
