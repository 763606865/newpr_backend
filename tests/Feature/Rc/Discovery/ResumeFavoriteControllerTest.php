<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcResumeStatus;
use App\Models\Company;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeFavorite;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeFavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
    }

    public function test_index_returns_favorited_resumes_for_current_company(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();
        $resume = $this->createDiscoverableResume('候选人甲');

        ResumeFavorite::query()->create([
            'user_id' => $recruiter->id,
            'company_id' => $company->id,
            'resume_id' => $resume->id,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/favorites/resumes')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '候选人甲')
            ->assertJsonPath('data.data.0.is_favorited', true)
            ->assertJsonMissingPath('data.data.0.phone');
    }

    public function test_index_only_returns_favorites_for_current_company(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();
        $otherCompany = Company::query()->create([
            'name' => '其他企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $currentCompanyResume = $this->createDiscoverableResume('当前企业候选人');
        $otherCompanyResume = $this->createDiscoverableResume('其他企业候选人');

        ResumeFavorite::query()->create([
            'user_id' => $recruiter->id,
            'company_id' => $company->id,
            'resume_id' => $currentCompanyResume->id,
        ]);

        ResumeFavorite::query()->create([
            'user_id' => $recruiter->id,
            'company_id' => $otherCompany->id,
            'resume_id' => $otherCompanyResume->id,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->getJson('/rc/talent/favorites/resumes')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.full_name', '当前企业候选人');
    }

    public function test_favorite_requires_recruiter_company(): void
    {
        $user = User::factory()->create();
        $resume = $this->createDiscoverableResume();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/resumes/'.$resume->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为招聘方身份并绑定企业。');
    }

    public function test_recruiter_can_favorite_and_unfavorite_resume(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();
        $resume = $this->createDiscoverableResume();

        $this->actingAs($recruiter, 'rc')
            ->postJson('/rc/talent/resumes/'.$resume->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.is_favorited', true);

        $this->assertDatabaseHas('rc_resume_favorites', [
            'user_id' => $recruiter->id,
            'company_id' => $company->id,
            'resume_id' => $resume->id,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->deleteJson('/rc/talent/resumes/'.$resume->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', false);

        $this->assertDatabaseMissing('rc_resume_favorites', [
            'user_id' => $recruiter->id,
            'company_id' => $company->id,
            'resume_id' => $resume->id,
        ]);
    }

    public function test_favorite_is_scoped_by_company(): void
    {
        [$recruiter, $company] = $this->createRecruiterContext();
        $otherCompany = Company::query()->create([
            'name' => '其他企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $resume = $this->createDiscoverableResume();

        ResumeFavorite::query()->create([
            'user_id' => $recruiter->id,
            'company_id' => $otherCompany->id,
            'resume_id' => $resume->id,
        ]);

        $this->actingAs($recruiter, 'rc')
            ->postJson('/rc/talent/resumes/'.$resume->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->assertSame(2, ResumeFavorite::query()->count());
    }

    private function createDiscoverableResume(string $fullName = '候选人甲'): Resume
    {
        $candidate = User::factory()->create();

        return Resume::query()->create([
            'user_id' => $candidate->id,
            'title' => '求职简历',
            'full_name' => $fullName,
            'phone' => '13800138000',
            'email' => 'candidate@example.com',
            'status' => RcResumeStatus::Normal,
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
