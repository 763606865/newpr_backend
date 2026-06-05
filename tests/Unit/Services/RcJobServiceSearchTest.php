<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use App\Services\RcJobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RcJobServiceSearchTest extends TestCase
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

    public function test_paginate_for_company_searches_by_keyword(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $otherCompany = Company::query()->create([
            'name' => '其他企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-MATCH-001',
            'title' => 'Java 后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '熟悉 Spring Boot 与微服务',
            'status' => RcJobStatus::Published,
        ]);

        Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-MISS-001',
            'title' => '产品经理',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责需求分析',
            'status' => RcJobStatus::Published,
        ]);

        Job::query()->create([
            'company_id' => $otherCompany->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-OTHER-001',
            'title' => 'Java 架构师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '熟悉 Java 生态',
            'status' => RcJobStatus::Published,
        ]);

        $paginator = RcJobService::make()->paginateForCompany($company, 15, [
            'keyword' => 'Java',
        ]);

        $this->assertSame(1, $paginator->total());
        $this->assertSame('Java 后端工程师', $paginator->items()[0]->title);
    }
}
