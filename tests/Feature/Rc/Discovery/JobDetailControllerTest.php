<?php

namespace Tests\Feature\Rc\Discovery;

use App\Enums\CompanyStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class JobDetailControllerTest extends TestCase
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

    public function test_guest_can_view_public_job_detail(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'position_code' => 'backend-developer',
            'code' => 'JOB-DETAIL-001',
            'title' => 'Laravel 工程师',
            'employment_type' => RcJobEmploymentType::FullTime,
            'city_code' => '360100',
            'description' => '负责后端开发',
            'requirement' => '熟悉 Laravel',
            'status' => RcJobStatus::Published,
            'published_at' => now(),
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
            ->with('rc:view:job:'.$job->id.':pv:'.now()->toDateString());
        $connection->shouldReceive('expire')
            ->once()
            ->with('rc:view:job:'.$job->id.':pv:'.now()->toDateString(), \Mockery::type('int'));
        $connection->shouldNotReceive('pfadd');

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $response = $this->getJson('/rc/talent/jobs/'.$job->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.id', $job->id)
            ->assertJsonPath('data.title', 'Laravel 工程师')
            ->assertJsonPath('data.company.name', '南昌示例科技有限公司')
            ->assertJsonPath('data.position.code', 'backend-developer');
    }

    public function test_draft_job_returns_not_found(): void
    {
        $company = Company::query()->create([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-DETAIL-002',
            'title' => '草稿岗位',
            'employment_type' => RcJobEmploymentType::FullTime,
            'description' => '草稿',
            'status' => RcJobStatus::Draft,
        ]);

        Redis::shouldReceive('connection')->never();

        $response = $this->getJson('/rc/talent/jobs/'.$job->id);

        $response
            ->assertOk()
            ->assertJsonPath('code', 404)
            ->assertJsonPath('message', '职位不存在或已下架。');
    }
}
