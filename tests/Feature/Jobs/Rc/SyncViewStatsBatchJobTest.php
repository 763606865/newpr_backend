<?php

namespace Tests\Feature\Jobs\Rc;

use App\Enums\CompanyStatus;
use App\Jobs\Rc\SyncViewStatsBatchJob;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\JobStatsDaily;
use App\Services\RcViewStatsArchiveService;
use App\Services\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class SyncViewStatsBatchJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'rc_stats.redis_connection' => 'default',
            'rc_stats.key_prefix' => 'rc:view',
        ]);

        $reflection = new \ReflectionClass(Service::class);
        $instances = $reflection->getProperty('instances');
        $instances->setAccessible(true);
        $instances->setValue(null, []);
    }

    public function test_handle_syncs_job_batch_into_mysql(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000Z',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-20260610-003',
            'title' => '测试职位',
        ]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:job:'.$job->id.':pv:2026-06-10')
            ->andReturn('7');
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:job:'.$job->id.':uv:2026-06-10')
            ->andReturn(3);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $batchJob = new SyncViewStatsBatchJob('job', '2026-06-10', [$job->id]);
        $batchJob->handle(app(RcViewStatsArchiveService::class));

        $this->assertTrue(
            JobStatsDaily::query()
                ->where('job_id', $job->id)
                ->whereDate('stat_date', '2026-06-10')
                ->where('views_total', 7)
                ->where('views_uv', 3)
                ->exists()
        );
    }
}
