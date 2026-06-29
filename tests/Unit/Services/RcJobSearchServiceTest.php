<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use App\Services\RcJobSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RcJobSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'sort' => 1,
        ]);
    }

    public function test_search_finds_public_jobs_by_keyword(): void
    {
        $company = $this->createCompany('南昌示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-PUBLIC-001',
            'title' => 'Laravel 后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责核心业务 API 开发',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-DRAFT-001',
            'title' => 'Laravel 实习生',
            'employment_type' => RcJobEmploymentType::Internship,
            'description' => '参与 Laravel 项目',
            'status' => RcJobStatus::Draft,
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'keyword' => 'Laravel',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('Laravel 后端工程师', $paginator->items()[0]->title);
    }

    public function test_search_excludes_expired_jobs(): void
    {
        $company = $this->createCompany('杭州示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-EXPIRED-001',
            'title' => 'Java 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '熟悉 Java 生态',
            'status' => RcJobStatus::Published,
            'published_at' => now()->subMonth(),
            'expired_at' => Carbon::yesterday(),
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'keyword' => 'Java',
        ]);

        $this->assertSame(0, $paginator->total());
    }

    public function test_search_filters_by_city_code(): void
    {
        $company = $this->createCompany('深圳示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-CITY-001',
            'title' => '深圳后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '440300',
            'description' => '深圳办公',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-CITY-002',
            'title' => '南昌后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'description' => '南昌办公',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'city_code' => '440300',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('440300', $paginator->items()[0]->city_code);
    }

    public function test_search_sorts_urgent_jobs_before_newer_non_urgent_jobs(): void
    {
        $company = $this->createCompany('广州示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-NORMAL-001',
            'title' => '普通岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '普通招聘',
            'status' => RcJobStatus::Published,
            'is_urgent' => false,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-URGENT-001',
            'title' => '紧急岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '紧急招聘',
            'status' => RcJobStatus::Published,
            'is_urgent' => true,
            'published_at' => now()->subDay(),
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'keyword' => '岗位',
        ]);

        $this->assertSame(2, $paginator->total());
        $this->assertSame('紧急岗位', $paginator->items()[0]->title);
        $this->assertSame('普通岗位', $paginator->items()[1]->title);
    }

    public function test_search_without_keyword_sorts_urgent_jobs_via_database_when_elastic_driver(): void
    {
        config(['scout.driver' => 'elastic']);

        $company = $this->createCompany('成都示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-DB-NORMAL-001',
            'title' => '普通筛选岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '普通招聘',
            'status' => RcJobStatus::Published,
            'is_urgent' => false,
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-DB-URGENT-001',
            'title' => '紧急筛选岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '紧急招聘',
            'status' => RcJobStatus::Published,
            'is_urgent' => true,
            'published_at' => now()->subDay(),
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'company_id' => $company->id,
        ]);

        $this->assertSame(2, $paginator->total());
        $this->assertSame('紧急筛选岗位', $paginator->items()[0]->title);
        $this->assertSame('普通筛选岗位', $paginator->items()[1]->title);
    }

    public function test_search_ignores_expired_urgent_highlight_when_sorting(): void
    {
        config(['scout.driver' => 'elastic']);

        $company = $this->createCompany('重庆示例科技有限公司');

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-EXPIRED-URGENT-001',
            'title' => '过期紧急岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '紧急已过期',
            'status' => RcJobStatus::Published,
            'is_urgent' => true,
            'urgent_until' => Carbon::yesterday(),
            'published_at' => now(),
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-ACTIVE-URGENT-001',
            'title' => '有效紧急岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '紧急有效',
            'status' => RcJobStatus::Published,
            'is_urgent' => true,
            'urgent_until' => Carbon::tomorrow(),
            'published_at' => now()->subDay(),
        ]);

        $paginator = RcJobSearchService::make()->search(15, [
            'company_id' => $company->id,
        ]);

        $this->assertSame(2, $paginator->total());
        $this->assertSame('有效紧急岗位', $paginator->items()[0]->title);
        $this->assertSame('过期紧急岗位', $paginator->items()[1]->title);
    }

    private function createCompany(string $name): Company
    {
        return Company::query()->create([
            'name' => $name,
            'credit_code' => '91360100'.strtoupper(substr(md5($name), 0, 8)),
            'status' => CompanyStatus::Enabled,
        ]);
    }
}
