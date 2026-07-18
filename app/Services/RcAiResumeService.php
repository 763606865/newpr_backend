<?php

namespace App\Services;

use App\Enums\RcAiResumeParseStatus;
use App\Enums\RcIdentityType;
use App\Jobs\Rc\ParseAttachmentResumeJob;
use App\Libs\Facades\AI;
use App\Models\Rc\AiResumeParseTask;
use App\Models\Rc\UserIdentity;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class RcAiResumeService extends Service
{
    public function createParseTask(UserIdentity $identity, string $fileUrl): AiResumeParseTask
    {
        if ($identity->identity_type !== RcIdentityType::JobSeeker) {
            throw new InvalidArgumentException('请先切换为求职者身份。');
        }

        /** @var AiResumeParseTask $task */
        $task = AiResumeParseTask::query()->create([
            'user_id' => $identity->user_id,
            'identity_id' => $identity->id,
            'file_url' => $fileUrl,
            'provider' => $this->resumeParseDriver(),
            'status' => RcAiResumeParseStatus::Pending,
        ]);

        ParseAttachmentResumeJob::dispatch($task->id);

        return $task;
    }

    public function findTaskForIdentity(UserIdentity $identity, int $taskId): ?AiResumeParseTask
    {
        return AiResumeParseTask::query()
            ->where('id', $taskId)
            ->where('identity_id', $identity->id)
            ->first();
    }

    public function processTask(AiResumeParseTask $task): void
    {
        if ($task->status === RcAiResumeParseStatus::Succeeded) {
            return;
        }

        $task->forceFill([
            'status' => RcAiResumeParseStatus::Processing,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ])->save();

        try {
            $result = $this->parseAttachmentResume($task->identity, $task->file_url);

            $task->forceFill([
                'provider' => $result['provider'],
                'status' => RcAiResumeParseStatus::Succeeded,
                'parsed_resume' => $result['parsed_resume'],
                'error_message' => null,
                'finished_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $task->forceFill([
                'status' => RcAiResumeParseStatus::Failed,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ])->save();
        }
    }

    /**
     * @return array{
     *     provider: string,
     *     file_url: string,
     *     parsed_resume: array<string, mixed>
     * }
     */
    public function parseAttachmentResume(UserIdentity $identity, string $fileUrl): array
    {
        if ($identity->identity_type !== RcIdentityType::JobSeeker) {
            throw new InvalidArgumentException('请先切换为求职者身份。');
        }

        $result = AI::parseResumeByFileUrl($fileUrl, $this->resumeParseDriver());

        return [
            'provider' => (string) $result['provider'],
            'file_url' => (string) $result['file_url'],
            'parsed_resume' => $result['data'],
        ];
    }

    private function resumeParseDriver(): string
    {
        return (string) config('ai.resume_parse_driver', config('ai.default', 'custom'));
    }
}
