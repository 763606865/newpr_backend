<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcEducationLevel;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcResumeExposureStatus;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeExposure;
use App\Models\Rc\ResumeWork;
use App\Models\Rc\UserCompanyBlacklist;
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
            'position_code' => 'backend-developer',
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

    public function test_index_excludes_candidates_who_blacklisted_recruiter_company(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();
        $blockedCandidate = User::factory()->create();
        $visibleCandidate = User::factory()->create();

        UserCompanyBlacklist::query()->create([
            'user_id' => $blockedCandidate->id,
            'company_id' => $company->id,
        ]);

        Resume::query()->create([
            'user_id' => $blockedCandidate->id,
            'title' => '被屏蔽简历',
            'full_name' => '候选人甲',
            'phone' => '13800138010',
            'email' => 'blocked@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        Resume::query()->create([
            'user_id' => $visibleCandidate->id,
            'title' => '可见简历',
            'full_name' => '候选人乙',
            'phone' => '13800138011',
            'email' => 'visible@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $response = $this
            ->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '候选人乙');
    }

    public function test_index_prioritizes_refreshed_resumes(): void
    {
        [$recruiter] = $this->createRecruiterContext();

        $olderCandidate = User::factory()->create();
        Resume::query()->create([
            'user_id' => $olderCandidate->id,
            'title' => '已刷新简历',
            'full_name' => '已刷新候选人',
            'phone' => '13800138020',
            'email' => 'refreshed@example.com',
            'refreshed_at' => now(),
            'status' => RcResumeStatus::Normal,
        ]);

        $newerCandidate = User::factory()->create();
        Resume::query()->create([
            'user_id' => $newerCandidate->id,
            'title' => '普通简历',
            'full_name' => '普通候选人',
            'phone' => '13800138021',
            'email' => 'normal@example.com',
            'status' => RcResumeStatus::Normal,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes')
            ->assertOk()
            ->assertJsonPath('data.data.0.full_name', '已刷新候选人')
            ->assertJsonPath('data.data.0.refreshed_at', fn (mixed $value): bool => filled($value));
    }

    public function test_index_mixes_active_exposure_and_records_impression_stats(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();

        foreach (range(1, 2) as $index) {
            $candidate = User::factory()->create();
            Resume::query()->create([
                'user_id' => $candidate->id,
                'title' => '普通简历'.$index,
                'full_name' => '普通候选人'.$index,
                'phone' => '1380013810'.$index,
                'email' => 'normal'.$index.'@example.com',
                'status' => RcResumeStatus::Normal,
            ]);
        }

        $promotedCandidate = User::factory()->create();
        $promotedResume = Resume::query()->create([
            'user_id' => $promotedCandidate->id,
            'title' => '曝光简历',
            'full_name' => '曝光候选人',
            'phone' => '13800138103',
            'email' => 'promoted@example.com',
            'status' => RcResumeStatus::Normal,
        ]);
        $exposure = ResumeExposure::query()->create([
            'resume_id' => $promotedResume->id,
            'user_id' => $promotedCandidate->id,
            'started_at' => now()->subHour(),
            'expired_at' => now()->addWeek(),
            'status' => RcResumeExposureStatus::Active,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/resumes?per_page=3')
            ->assertOk()
            ->assertJsonCount(3, 'data.data')
            ->assertJsonPath('data.data.2.id', $promotedResume->id)
            ->assertJsonPath('data.data.2.is_promoted', true)
            ->assertJsonPath('data.data.2.promotion_id', $exposure->id)
            ->assertJsonPath('data.data.2.promotion_label', '推广');

        $this->assertDatabaseHas('rc_resume_exposure_stats_daily', [
            'exposure_id' => $exposure->id,
            'resume_id' => $promotedResume->id,
            'company_id' => $company->id,
            'stat_date' => now()->toDateString(),
            'impressions' => 1,
        ]);
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
