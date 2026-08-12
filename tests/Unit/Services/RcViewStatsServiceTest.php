<?php

namespace Tests\Unit\Services;

use App\Models\Rc\Announcement;
use App\Models\Rc\Job;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeStatsDaily;
use App\Models\User;
use App\Services\RcViewStatsService;
use App\Services\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class RcViewStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-11 15:30:00');
        config([
            'rc_stats.redis_connection' => 'default',
            'rc_stats.key_prefix' => 'rc:view',
            'rc_stats.key_ttl_days' => 8,
        ]);

        $reflection = new \ReflectionClass(Service::class);
        $instances = $reflection->getProperty('instances');
        $instances->setAccessible(true);
        $instances->setValue(null, []);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_get_job_daily_views_reads_pv_and_uv_from_redis(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:job:12:pv:2026-06-11')
            ->andReturn('18');
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:job:12:uv:2026-06-11')
            ->andReturn(7);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $result = RcViewStatsService::make()->getJobDailyViews(12, '2026-06-11');

        $this->assertSame([
            'views_total' => 18,
            'views_uv' => 7,
        ], $result);
    }

    public function test_record_announcement_view_increments_read_count(): void
    {
        $announcement = Announcement::query()->create([
            'title' => '测试招聘公告',
            'read_count' => 9,
        ]);

        RcViewStatsService::make()->recordAnnouncementView($announcement);

        $this->assertSame(10, $announcement->read_count);
        $this->assertSame(10, $announcement->refresh()->read_count);
    }

    public function test_get_resume_daily_views_defaults_missing_keys_to_zero(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('get')
            ->once()
            ->with('rc:view:resume:5:pv:2026-06-11')
            ->andReturn(null);
        $connection->shouldReceive('pfcount')
            ->once()
            ->with('rc:view:resume:5:uv:2026-06-11')
            ->andReturn(0);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $result = RcViewStatsService::make()->getResumeDailyViews(5, '2026-06-11');

        $this->assertSame([
            'views_total' => 0,
            'views_uv' => 0,
        ], $result);
    }

    public function test_get_resume_total_views_for_ids_sums_archived_and_today_redis(): void
    {
        $user = User::factory()->create();

        $resume = Resume::query()->create([
            'user_id' => $user->id,
            'title' => '张三的简历',
            'full_name' => '张三',
            'phone' => '13800001111',
            'email' => 'zhang@example.com',
        ]);

        ResumeStatsDaily::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'stat_date' => '2026-06-10',
            'views_total' => 12,
            'views_uv' => 4,
        ]);

        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('mget')
            ->once()
            ->with(['rc:view:resume:'.$resume->id.':pv:2026-06-11'])
            ->andReturn(['5']);

        Redis::shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $totals = RcViewStatsService::make()->getResumeTotalViewsForIds([$resume->id]);

        $this->assertSame([$resume->id => 17], $totals);
    }

    public function test_record_job_view_does_not_throw_when_redis_fails(): void
    {
        Redis::shouldReceive('connection')->once()->with('default')->andThrow(new \RuntimeException('redis down'));

        $job = new Job(['id' => 1]);

        RcViewStatsService::make()->recordJobView($job, new User(['id' => 1]));

        $this->assertTrue(true);
    }
}
