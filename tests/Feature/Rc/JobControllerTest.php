<?php

namespace Tests\Feature\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
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

class JobControllerTest extends TestCase
{
    use RefreshDatabase;

    private const POSITION_CODE = 'backend-developer';

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        Position::query()->create([
            'name' => '后端开发',
            'code' => self::POSITION_CODE,
            'sort' => 1,
        ]);
    }

    public function test_index_requires_recruiter_company(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_store_creates_draft_job(): void
    {
        [$user] = $this->createRecruiterContext();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs', $this->validJobPayload([
                'title' => '高级后端工程师',
                'description' => null,
                'workplace' => null,
                'education_level' => null,
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.title', '高级后端工程师')
            ->assertJsonPath('data.status', RcJobStatus::Draft->value)
            ->assertJsonPath('data.position.code', self::POSITION_CODE)
            ->assertJsonPath('data.keywords.0', 'Java');

        $this->assertDatabaseHas('rc_jobs', [
            'title' => '高级后端工程师',
            'status' => RcJobStatus::Draft->value,
            'position_code' => self::POSITION_CODE,
        ]);
    }

    public function test_store_publishes_job_when_status_is_published(): void
    {
        [$user] = $this->createRecruiterContext();

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs', $this->validJobPayload([
                'status' => RcJobStatus::Published->value,
            ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.status', RcJobStatus::Published->value)
            ->assertJsonPath('data.workplace', '南昌市高新区示例路 88 号');

        $this->assertNotNull($response->json('data.published_at'));
    }

    public function test_publish_rejects_incomplete_job(): void
    {
        [$user, $company] = $this->createRecruiterContext();

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-DRAFT-001',
            'title' => '未完善职位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'status' => RcJobStatus::Draft,
        ]);

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs/'.$job->id.'/publish');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请选择职位类别。');
    }

    public function test_publish_publishes_draft_job(): void
    {
        [$user, $company] = $this->createRecruiterContext();

        $job = Job::query()->create(array_merge($this->validJobAttributes(), [
            'company_id' => $company->id,
            'code' => 'JOB-DRAFT-002',
            'status' => RcJobStatus::Draft,
        ]));

        $response = $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs/'.$job->id.'/publish');

        $response
            ->assertOk()
            ->assertJsonPath('data.status', RcJobStatus::Published->value);

        $this->assertDatabaseHas('rc_jobs', [
            'id' => $job->id,
            'status' => RcJobStatus::Published->value,
        ]);
    }

    public function test_index_only_returns_current_company_jobs(): void
    {
        [$user, $company] = $this->createRecruiterContext();
        $otherCompany = Company::query()->create([
            'name' => '其他企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        Job::query()->create(array_merge($this->validJobAttributes(), [
            'company_id' => $company->id,
            'code' => 'JOB-A-001',
            'title' => '本公司职位',
        ]));

        Job::query()->create(array_merge($this->validJobAttributes(), [
            'company_id' => $otherCompany->id,
            'code' => 'JOB-B-001',
            'title' => '其他企业职位',
        ]));

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.title', '本公司职位');
    }

    public function test_update_and_destroy_job(): void
    {
        [$user, $company] = $this->createRecruiterContext();

        $job = Job::query()->create(array_merge($this->validJobAttributes(), [
            'company_id' => $company->id,
            'code' => 'JOB-UPDATE-001',
            'status' => RcJobStatus::Draft,
        ]));

        $this
            ->actingAs($user, 'rc')
            ->putJson('/rc/jobs/'.$job->id, [
                'title' => '资深后端工程师',
                'headcount' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', '资深后端工程师')
            ->assertJsonPath('data.headcount', 3);

        $this
            ->actingAs($user, 'rc')
            ->deleteJson('/rc/jobs/'.$job->id)
            ->assertOk()
            ->assertJsonPath('code', 200);

        $this->assertSoftDeleted('rc_jobs', ['id' => $job->id]);
    }

    public function test_pause_and_close_job(): void
    {
        [$user, $company] = $this->createRecruiterContext();

        $job = Job::query()->create(array_merge($this->validJobAttributes(), [
            'company_id' => $company->id,
            'code' => 'JOB-STATUS-001',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]));

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs/'.$job->id.'/pause')
            ->assertOk()
            ->assertJsonPath('data.status', RcJobStatus::Paused->value);

        $this
            ->actingAs($user, 'rc')
            ->postJson('/rc/jobs/'.$job->id.'/close')
            ->assertOk()
            ->assertJsonPath('data.status', RcJobStatus::Closed->value);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function createRecruiterContext(): array
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'organization_type' => 'company',
            'organization_id' => $company->id,
            'organization_name' => $company->name,
            'identity_type' => RcIdentityType::Recruiter,
            'identity_name' => '招聘方',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return [$user, $company];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validJobPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => '高级后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime->value,
            'position_code' => self::POSITION_CODE,
            'description' => str_repeat('负责核心业务开发。', 10),
            'education_level' => RcEducationLevel::Bachelor->value,
            'experience_min' => 3,
            'experience_max' => 5,
            'salary_min' => 15000,
            'salary_max' => 25000,
            'workplace' => '南昌市高新区示例路 88 号',
            'headcount' => 2,
            'keywords' => ['Java', 'Laravel'],
            'show_headcount' => true,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function validJobAttributes(): array
    {
        return [
            'title' => '高级后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'position_code' => self::POSITION_CODE,
            'description' => str_repeat('负责核心业务开发。', 10),
            'education_level' => RcEducationLevel::Bachelor->value,
            'experience_min' => 3,
            'experience_max' => 5,
            'salary_min' => 15000,
            'salary_max' => 25000,
            'workplace' => '南昌市高新区示例路 88 号',
            'headcount' => 2,
            'extra' => [
                'keywords' => ['Java'],
                'show_headcount' => true,
            ],
        ];
    }
}
