<?php

namespace Tests\Feature\Console;

use App\Jobs\Rc\SyncViewStatsBatchJob;
use App\Services\RcViewStatsArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SyncViewStatsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_batch_jobs_for_job_and_resume_entities(): void
    {
        Queue::fake();

        $archive = Mockery::mock(RcViewStatsArchiveService::class);
        $archive->shouldReceive('discoverEntityIds')
            ->once()
            ->with('job', '2026-06-10')
            ->andReturn([1, 2, 3]);
        $archive->shouldReceive('discoverEntityIds')
            ->once()
            ->with('resume', '2026-06-10')
            ->andReturn([10, 11]);

        $this->instance(RcViewStatsArchiveService::class, $archive);

        $this->artisan('rc:sync-view-stats 2026-06-10 --type=all --batch=2')
            ->assertSuccessful();

        Queue::assertPushed(SyncViewStatsBatchJob::class, 3);

        Queue::assertPushed(SyncViewStatsBatchJob::class, function (SyncViewStatsBatchJob $job): bool {
            return $job->type === 'job'
                && $job->statDate === '2026-06-10'
                && $job->entityIds === [1, 2];
        });

        Queue::assertPushed(SyncViewStatsBatchJob::class, function (SyncViewStatsBatchJob $job): bool {
            return $job->type === 'job'
                && $job->statDate === '2026-06-10'
                && $job->entityIds === [3];
        });

        Queue::assertPushed(SyncViewStatsBatchJob::class, function (SyncViewStatsBatchJob $job): bool {
            return $job->type === 'resume'
                && $job->statDate === '2026-06-10'
                && $job->entityIds === [10, 11];
        });
    }

    public function test_command_defaults_to_yesterday_when_date_is_missing(): void
    {
        Queue::fake();

        $yesterday = now()->subDay()->toDateString();

        $archive = Mockery::mock(RcViewStatsArchiveService::class);
        $archive->shouldReceive('discoverEntityIds')
            ->once()
            ->with('job', $yesterday)
            ->andReturn([]);
        $archive->shouldReceive('discoverEntityIds')
            ->once()
            ->with('resume', $yesterday)
            ->andReturn([]);

        $this->instance(RcViewStatsArchiveService::class, $archive);

        $this->artisan('rc:sync-view-stats')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
