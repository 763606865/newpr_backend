<?php

namespace App\Jobs\Rc;

use App\Models\Rc\AiResumeParseTask;
use App\Services\RcAiResumeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseAttachmentResumeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(public readonly int $taskId) {}

    public function handle(RcAiResumeService $service): void
    {
        $task = AiResumeParseTask::query()->find($this->taskId);

        if (! $task instanceof AiResumeParseTask) {
            return;
        }

        $service->processTask($task);
    }
}
