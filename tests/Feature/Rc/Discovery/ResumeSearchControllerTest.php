<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeWork;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeSearchControllerTest extends TestCase
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
            ->getJson('/rc/talent/resumes');

        $response
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_index_returns_matching_resumes_for_recruiter(): void
    {
        [$recruiter] = $this->createRecruiterContext();

        $candidate = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $candidate->id,
            'title' => '求职简历',
            'full_name' => '候选人甲',
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'highest_education_level' => RcEducationLevel::Bachelor,
            'status' => RcResumeStatus::Normal,
        ]);

        ResumeWork::query()->create([
            'resume_id' => $resume->id,
            'user_id' => $candidate->id,
            'company_name' => '杭州示例科技有限公司',
            'position' => 'Laravel 工程师',
            'start_date' => '2022-01-01',
            'description' => '负责后端 API 开发',
        ]);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes?keyword=Laravel');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '候选人甲');
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
