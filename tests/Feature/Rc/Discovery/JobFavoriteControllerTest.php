<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcApplicationStatus;
use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Application;
use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFavoriteControllerTest extends TestCase
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
    }

    public function test_index_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/favorites/jobs')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_index_returns_favorited_jobs_with_applied_status(): void
    {
        $user = $this->createJobSeekerContext();
        $appliedJob = $this->createPublishedJob('JOB-FAV-LIST-001', '已投递岗位');
        $favoriteOnlyJob = $this->createPublishedJob('JOB-FAV-LIST-002', '仅收藏岗位');

        JobFavorite::query()->create([
            'user_id' => $user->id,
            'job_id' => $appliedJob->id,
            'created_at' => now()->subMinute(),
        ]);

        JobFavorite::query()->create([
            'user_id' => $user->id,
            'job_id' => $favoriteOnlyJob->id,
            'created_at' => now(),
        ]);

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
        ]);

        Application::query()->create([
            'company_id' => $appliedJob->company_id,
            'job_id' => $appliedJob->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/favorites/jobs');

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.data.0.title', '仅收藏岗位')
            ->assertJsonPath('data.data.0.is_favorited', true)
            ->assertJsonPath('data.data.0.is_applied', false)
            ->assertJsonPath('data.data.1.title', '已投递岗位')
            ->assertJsonPath('data.data.1.is_favorited', true)
            ->assertJsonPath('data.data.1.is_applied', true);
    }

    public function test_favorite_requires_job_seeker_identity(): void
    {
        $user = User::factory()->create();
        $job = $this->createPublishedJob();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', '请先切换为求职者身份。');
    }

    public function test_job_seeker_can_favorite_and_unfavorite_job(): void
    {
        $user = $this->createJobSeekerContext();
        $job = $this->createPublishedJob();

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.is_favorited', true);

        $this->assertDatabaseHas('rc_job_favorites', [
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);

        $this->assertSame(1, JobFavorite::query()->count());

        $this->actingAs($user, 'rc')
            ->deleteJson('/rc/talent/jobs/'.$job->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('data.is_favorited', false);

        $this->assertDatabaseMissing('rc_job_favorites', [
            'user_id' => $user->id,
            'job_id' => $job->id,
        ]);
    }

    public function test_favorite_draft_job_returns_not_found(): void
    {
        $user = $this->createJobSeekerContext();
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-FAV-DRAFT',
            'title' => '草稿岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '草稿',
            'status' => RcJobStatus::Draft,
        ]);

        $this->actingAs($user, 'rc')
            ->postJson('/rc/talent/jobs/'.$job->id.'/favorite')
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '职位不存在或已下架。');
    }

    private function createPublishedJob(string $code = 'JOB-FAV-001', string $title = 'Laravel 工程师'): Job
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000'.substr($code, -1),
            'status' => CompanyStatus::Enabled,
        ]);

        return Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => $code,
            'title' => $title,
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);
    }

    private function createJobSeekerContext(): User
    {
        $user = User::factory()->create();

        UserIdentity::query()->create([
            'user_id' => $user->id,
            'identity_type' => RcIdentityType::JobSeeker,
            'identity_name' => '求职者',
            'is_default' => 1,
            'status' => RcIdentityStatus::Enabled,
        ]);

        return $user;
    }
}
