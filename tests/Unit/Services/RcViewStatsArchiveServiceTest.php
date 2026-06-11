<?php

namespace Tests\Unit\Services;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\JobStatsDaily;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use App\Models\User;
use App\Services\RcViewStatsArchiveService;
use App\Services\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class RcViewStatsArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'rc_stats.redis_connection' => 'default',
            'rc_stats.key_prefix' => 'rc:view',
            'database.redis.options.prefix' => '',
        ]);

        $reflection = new \ReflectionClass(Service::class);
        $instances = $reflection->getProperty('instances');
        $instances->setAccessible(true);
        $instances->setValue(null, []);
    }

    public function test_discover_entity_ids_scans_redis_with_null_initial_cursor(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('phpredis extension is not available.');
        }

        config([
            'rc_stats.redis_connection' => 'default',
            'rc_stats.key_prefix' => 'rc:view',
            'database.redis.options.prefix' => '',
        ]);

        $connection = Redis::connection('default');
        $pvKey = 'rc:view:resume:42:pv:2026-06-11';

        try {
            $connection->del($pvKey);
            $connection->set($pvKey, '3');

            $entityIds = RcViewStatsArchiveService::make()->discoverEntityIds('resume', '2026-06-11');

            $this->assertContains(42, $entityIds);
        } finally {
            $connection->del($pvKey);
        }
    }

    public function test_discover_entity_ids_parses_job_pv_keys_from_redis_scan(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('client')->andReturn(null);
        $connection->shouldReceive('scan')
            ->once()
            ->with(null, [
                'match' => 'rc:view:job:*:pv:2026-06-10',
                'count' => 1000,
            ])
            ->andReturn([0, [
                'rc:view:job:12:pv:2026-06-10',
                'rc:view:job:34:pv:2026-06-10',
                'rc:view:resume:99:pv:2026-06-10',
            ]]);

        Redis::shouldReceive('connection')->with('default')->andReturn($connection);

        $entityIds = RcViewStatsArchiveService::make()->discoverEntityIds('job', '2026-06-10');

        $this->assertSame([12, 34], $entityIds);
    }

    public function test_sync_job_batch_upserts_daily_stats_from_redis(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000X',
            'status' => CompanyStatus::Enabled,
        ]);

        $user = User::factory()->create();

        $job = Job::query()->create([
            'company_id' => $company->id,
            'creator_user_id' => $user->id,
            'code' => 'JOB-20260610-001',
            'title' => '后端工程师',
        ]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:job:'.$job->id.':pv:2026-06-10')
            ->andReturn('15');
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:job:'.$job->id.':uv:2026-06-10')
            ->andReturn(6);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $synced = RcViewStatsArchiveService::make()->syncJobBatch([$job->id], '2026-06-10');

        $this->assertSame(1, $synced);
        $this->assertTrue(
            JobStatsDaily::query()
                ->where('job_id', $job->id)
                ->whereDate('stat_date', '2026-06-10')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->where('views_total', 15)
                ->where('views_uv', 6)
                ->exists()
        );
    }

    public function test_sync_job_batch_updates_existing_daily_stats(): void
    {
        $company = Company::query()->create([
            'name' => '示例企业',
            'credit_code' => '91360100MA0000000Y',
            'status' => CompanyStatus::Enabled,
        ]);

        $job = Job::query()->create([
            'company_id' => $company->id,
            'code' => 'JOB-20260610-002',
            'title' => '产品经理',
        ]);

        JobStatsDaily::query()->create([
            'company_id' => $company->id,
            'job_id' => $job->id,
            'stat_date' => '2026-06-10',
            'views_total' => 1,
            'views_uv' => 1,
        ]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:job:'.$job->id.':pv:2026-06-10')
            ->andReturn('20');
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:job:'.$job->id.':uv:2026-06-10')
            ->andReturn(8);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        RcViewStatsArchiveService::make()->syncJobBatch([$job->id], '2026-06-10');

        $this->assertDatabaseHas('rc_job_stats_daily', [
            'job_id' => $job->id,
            'views_total' => 20,
            'views_uv' => 8,
        ]);
        $this->assertSame(1, JobStatsDaily::query()->where('job_id', $job->id)->count());
    }

    public function test_sync_resume_batch_upserts_daily_stats_from_redis(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '张三的简历',
            'full_name' => '张三',
            'phone' => '13800001111',
            'email' => 'zhang@example.com',
        ]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:resume:'.$resume->id.':pv:2026-06-10')
            ->andReturn('9');
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:resume:'.$resume->id.':uv:2026-06-10')
            ->andReturn(4);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $synced = RcViewStatsArchiveService::make()->syncResumeBatch([$resume->id], '2026-06-10');

        $this->assertSame(1, $synced);
        $this->assertTrue(
            ResumeStatsDaily::query()
                ->where('resume_id', $resume->id)
                ->whereDate('stat_date', '2026-06-10')
                ->where('user_id', $user->id)
                ->where('views_total', 9)
                ->where('views_uv', 4)
                ->exists()
        );
    }
}
