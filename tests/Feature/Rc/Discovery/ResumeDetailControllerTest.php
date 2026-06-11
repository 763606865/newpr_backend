<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeEducation;
use App\Models\Rc\ResumeWork;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ResumeDetailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_requires_recruiter_company(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'rc')
            ->getJson('/rc/talent/resumes/1');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_recruiter_can_view_masked_resume_detail_with_relations(): void
    {
        [$recruiter] = $this->createRecruiterContext();
        $candidate = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $candidate->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'file_url' => 'uploads/rc/resume/example.pdf',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $candidate->id,
            'company_name' => '杭州示例科技有限公司',
            'position' => 'Laravel 工程师',
            'position_code' => 'backend-developer',
            'start_date' => '2022-01-01',
            'description' => '负责后端 API 开发',
        ]);

        ResumeEducation::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $candidate->id,
            'school_name' => '浙江大学',
            'major' => '软件工程',
            'start_date' => '2018-09-01',
        ]);

        $connection = \Mockery::mock(Connection::class);
        $connection->shouldReceive('pipeline')
            ->once()
            ->with(\Mockery::type('callable'))
            ->andReturnUsing(function (callable $callback) use ($connection): void {
                $callback($connection);
            });
        $connection->shouldReceive('incr')
            ->once()
            ->with('rc:view:resume:'.$resume->id.':pv:'.now()->toDateString());
        $connection->shouldReceive('expire')->twice()->with(\Mockery::type('string'), \Mockery::type('int'));
        $connection->shouldReceive('pfadd')
            ->once()
            ->with('rc:view:resume:'.$resume->id.':uv:'.now()->toDateString(), ['user:'.$recruiter->id]);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes/'.$resume->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.full_name', '候选人甲')
            ->assertJsonPath('data.works.0.position', 'Laravel 工程师')
            ->assertJsonPath('data.educations.0.school_name', '浙江大学')
            ->assertJsonMissingPath('data.phone')
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.file_url')
            ->assertJsonMissingPath('data.display_file_url');
    }

    public function test_resume_search_list_does_not_expose_private_fields(): void
    {
        [$recruiter] = $this->createRecruiterContext();
        $candidate = User::factory()->create();

        Resume::query()->create([
            'user_id' => $candidate->id,
            'title' => '求职简历',
            'full_name' => '候选人乙',
            'phone' => '13800138001',
            'email' => 'private@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes');

        $response
            ->assertOk()
            ->assertJsonPath('data.data.0.full_name', '候选人乙')
            ->assertJsonMissingPath('data.data.0.phone')
            ->assertJsonMissingPath('data.data.0.email');
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
}
