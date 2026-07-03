<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyFundingStage;
use App\Enums\CompanyNatureType;
use App\Enums\CompanyProfileStatus;
use App\Enums\CompanyScaleType;
use App\Enums\CompanyStatus;
use App\Enums\RcApplicationStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Rc\Application;
use App\Models\Rc\CompanyAlbum;
use App\Models\Rc\CompanyFavorite;
use App\Models\Rc\Job;
use App\Models\Rc\JobFavorite;
use App\Models\Rc\Position;
use App\Models\Rc\Resume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDetailControllerTest extends TestCase
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

    public function test_guest_can_view_company_detail_with_public_jobs(): void
    {
        $company = $this->createDiscoverableCompany();

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-COMPANY-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-COMPANY-002',
            'title' => '草稿岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '草稿',
            'status' => RcJobStatus::Draft,
        ]);

        CompanyAlbum::query()->create([
            'company_id' => $company->id,
            'title' => '办公环境',
            'image' => 'uploads/rc/company-albums/office.jpg',
            'type' => 1,
            'sort' => 20,
            'status' => 1,
        ]);
        CompanyAlbum::query()->create([
            'company_id' => $company->id,
            'title' => '停用图片',
            'image' => 'uploads/rc/company-albums/disabled.jpg',
            'type' => 4,
            'sort' => 10,
            'status' => 0,
        ]);

        $response = $this->getJson('/rc/talent/companies/'.$company->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.company.id', $company->id)
            ->assertJsonPath('data.company.display_name', '示例科技')
            ->assertJsonPath('data.company.profile.introduction', '专注招聘系统研发')
            ->assertJsonPath('data.company.albums.0.title', '办公环境')
            ->assertJsonPath('data.company.albums.0.image', 'uploads/rc/company-albums/office.jpg')
            ->assertJsonPath('data.company.albums.0.type_label', '办公环境')
            ->assertJsonCount(1, 'data.company.albums')
            ->assertJsonPath('data.company.stat.public_jobs', 1)
            ->assertJsonPath('data.jobs.total', 1)
            ->assertJsonPath('data.jobs.data.0.title', 'Laravel 工程师')
            ->assertJsonMissingPath('data.jobs.data.0.is_applied')
            ->assertJsonMissingPath('data.jobs.data.0.is_favorited')
            ->assertJsonMissingPath('data.is_favorited');
    }

    public function test_authenticated_user_sees_job_applied_and_favorited_status_in_batch(): void
    {
        $user = User::factory()->create();
        $company = $this->createDiscoverableCompany();

        $appliedJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-COMPANY-APPLIED',
            'title' => '已投递岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责后端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $favoritedJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-COMPANY-FAVORITE',
            'title' => '已收藏岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责前端开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $plainJob = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-COMPANY-PLAIN',
            'title' => '普通岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责测试',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '求职简历',
            'full_name' => '求职者甲',
            'phone' => '13800138000',
            'email' => 'seeker@example.com',
        ]);

        Application::query()->create([
            'company_id' => $company->id,
            'job_id' => $appliedJob->id,
            'candidate_user_id' => $user->id,
            'resume_id' => $resume->id,
            'status' => RcApplicationStatus::Pending,
            'applied_at' => now(),
        ]);

        JobFavorite::query()->create([
            'user_id' => $user->id,
            'job_id' => $favoritedJob->id,
        ]);

        $response = $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/companies/'.$company->id.'?per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('data.jobs.total', 3);

        $jobsByTitle = collect($response->json('data.jobs.data'))->keyBy('title');

        $this->assertTrue($jobsByTitle['已投递岗位']['is_applied']);
        $this->assertFalse($jobsByTitle['已投递岗位']['is_favorited']);
        $this->assertFalse($jobsByTitle['已收藏岗位']['is_applied']);
        $this->assertTrue($jobsByTitle['已收藏岗位']['is_favorited']);
        $this->assertFalse($jobsByTitle['普通岗位']['is_applied']);
        $this->assertFalse($jobsByTitle['普通岗位']['is_favorited']);
    }

    public function test_authenticated_user_sees_favorite_status_on_company_detail(): void
    {
        $user = User::factory()->create();
        $company = $this->createDiscoverableCompany();

        CompanyFavorite::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->actingAs($user, 'rc')
            ->getJson('/rc/talent/companies/'.$company->id)
            ->assertOk()
            ->assertJsonPath('data.is_favorited', true);
    }

    public function test_disabled_company_returns_not_found(): void
    {
        $company = Company::query()->create([
            'name' => '禁用企业',
            'credit_code' => '91360100MA0000000D',
            'status' => CompanyStatus::Disabled,
        ]);

        $this->getJson('/rc/talent/companies/'.$company->id)
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '企业不存在或不可查看。');
    }

    private function createDiscoverableCompany(): Company
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ]);

        CompanyProfile::query()->create([
            'company_id' => $company->id,
            'short_name' => '示例科技',
            'city_code' => '360100',
            'scale_type' => CompanyScaleType::From100To499,
            'nature_type' => CompanyNatureType::Private,
            'introduction' => '专注招聘系统研发',
            'benefit_tags' => ['social_insurance'],
            'funding_stage' => CompanyFundingStage::SeriesA,
            'profile_status' => CompanyProfileStatus::Complete,
        ]);

        return $company;
    }
}
