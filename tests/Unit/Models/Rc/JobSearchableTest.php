<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSearchableTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_searchable_array_includes_company_and_keywords(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        Position::query()->create([
            'name' => '后端开发',
            'code' => 'backend-developer',
            'sort' => 1,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-SEARCH-001',
            'title' => '高级后端工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '负责 Laravel 核心业务研发',
            'status' => RcJobStatus::Published,
            'extra' => [
                'keywords' => ['Java', 'Laravel'],
            ],
        ]);

        $searchable = $job->fresh(['company', 'position'])->toSearchableArray();

        $this->assertSame('rc_jobs', $job->searchableAs());
        $this->assertTrue($job->shouldBeSearchable());
        $this->assertSame($company->id, $searchable['company_id']);
        $this->assertSame('南昌示例科技有限公司', $searchable['company_name']);
        $this->assertSame('后端开发', $searchable['position_name']);
        $this->assertSame('Java Laravel', $searchable['keywords']);
        $this->assertSame(RcJobStatus::Published->value, $searchable['status']);
        $this->assertSame(1, $searchable['is_public']);
        $this->assertTrue($job->isPubliclySearchable());
    }
}
