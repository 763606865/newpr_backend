<?php

namespace Tests\Unit\Models\Rc;

use App\Enums\AreaLevel;
use App\Enums\CompanyStatus;
use App\Models\Area;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_relationship_resolves_by_position_code(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
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
            'code' => 'JOB-20260604-001',
            'title' => '高级后端工程师',
        ]);

        $this->assertSame('backend-developer', $job->position_code);
        $this->assertSame('后端开发', $job->position?->name);
    }

    public function test_position_code_is_nullable(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-20260604-002',
            'title' => '产品经理',
        ]);

        $this->assertNull($job->position_code);
        $this->assertNull($job->position);
    }

    public function test_city_area_relationship_resolves_city_name(): void
    {
        Area::query()->create([
            'code' => '360100',
            'name' => '南昌市',
            'parent_code' => '360000',
            'level' => AreaLevel::City,
        ]);

        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-20260604-003',
            'title' => 'Java 工程师',
            'city_code' => '360100',
        ]);

        $this->assertSame('360100', $job->city_code);
        $this->assertSame('南昌市', $job->cityArea?->name);
    }
}
