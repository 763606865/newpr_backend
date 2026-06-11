<?php

namespace App\Jobs\Rc;

use App\Services\RcViewStatsArchiveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncViewStatsBatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  list<int>  $entityIds
     */
    public function __construct(
        public readonly string $type,
        public readonly string $statDate,
        public readonly array $entityIds,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RcViewStatsArchiveService $archive): void
    {
        $synced = match ($this->type) {
            'job' => $archive->syncJobBatch($this->entityIds, $this->statDate),
            'resume' => $archive->syncResumeBatch($this->entityIds, $this->statDate),
            default => 0,
        };

        Log::info('rc view stats synced', [
            'type' => $this->type,
            'stat_date' => $this->statDate,
            'requested' => count($this->entityIds),
            'synced' => $synced,
        ]);
    }
}
