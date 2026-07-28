<?php

namespace App\Services;

use App\Enums\RcAssetCode;
use App\Enums\RcAssetOwnerType;
use App\Enums\RcAssetSourceType;
use App\Enums\RcResumeRefreshQuotaType;
use App\Enums\RcResumeRefreshTrigger;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Rc\AssetLedger;
use App\Models\Rc\Resume;
use App\Models\Rc\ResumeRefreshLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class RcResumeRefreshService extends Service
{
    public function refresh(
        Resume $resume,
        User $user,
        RcResumeRefreshTrigger $trigger,
        bool $failWhenUnavailable = false,
    ): bool {
        return DB::transaction(function () use ($resume, $user, $trigger, $failWhenUnavailable): bool {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $resume = Resume::query()
                ->where('user_id', $user->id)
                ->whereKey($resume->id)
                ->lockForUpdate()
                ->firstOrFail();
            $refreshDate = now()->toDateString();

            if (! $this->hasUsedFreeRefresh($user, $refreshDate)) {
                $this->persistRefresh(
                    $resume,
                    $user,
                    $trigger,
                    RcResumeRefreshQuotaType::FreeDaily,
                    'free_daily',
                );

                return true;
            }

            try {
                $bizNo = 'resume_refresh:'.$resume->id.':'.Str::uuid();
                RcAssetService::make()->consumeOnce(
                    ownerType: RcAssetOwnerType::User,
                    ownerId: (int) $user->id,
                    assetCode: RcAssetCode::ResumeRefresh->value,
                    assetName: (string) RcAssetCode::ResumeRefresh->getLabel(),
                    quantity: 1,
                    sourceType: RcAssetSourceType::System,
                    sourceId: (int) $resume->id,
                    bizNo: $bizNo,
                    remark: '刷新简历：'.$resume->title,
                    extra: [
                        'scene' => 'resume_refresh',
                        'resume_id' => (int) $resume->id,
                        'trigger_type' => $trigger->value,
                    ],
                );
            } catch (InsufficientBalanceException) {
                if ($failWhenUnavailable) {
                    throw new InvalidArgumentException('今日免费刷新机会已使用，且简历刷新权益不足。');
                }

                return false;
            }

            $ledger = AssetLedger::query()->where('biz_no', $bizNo)->firstOrFail();
            $this->persistRefresh(
                $resume,
                $user,
                $trigger,
                RcResumeRefreshQuotaType::Asset,
                null,
                $ledger,
            );

            return true;
        });
    }

    private function hasUsedFreeRefresh(User $user, string $refreshDate): bool
    {
        $cached = $this->readRedisSummary((int) $user->id, $refreshDate);

        if ((int) ($cached['free_count'] ?? 0) > 0) {
            return true;
        }

        return ResumeRefreshLog::query()
            ->where('user_id', $user->id)
            ->whereDate('refresh_date', $refreshDate)
            ->where('quota_type', RcResumeRefreshQuotaType::FreeDaily->value)
            ->exists();
    }

    private function persistRefresh(
        Resume $resume,
        User $user,
        RcResumeRefreshTrigger $trigger,
        RcResumeRefreshQuotaType $quotaType,
        ?string $quotaKey,
        ?AssetLedger $ledger = null,
    ): void {
        $refreshedAt = now();

        ResumeRefreshLog::query()->create([
            'user_id' => $user->id,
            'resume_id' => $resume->id,
            'refresh_date' => $refreshedAt->toDateString(),
            'refreshed_at' => $refreshedAt,
            'trigger_type' => $trigger,
            'quota_type' => $quotaType,
            'quota_key' => $quotaKey,
            'asset_ledger_id' => $ledger?->id,
        ]);

        $resume->refreshed_at = $refreshedAt;
        $resume->save();

        DB::afterCommit(function () use ($user, $resume, $refreshedAt): void {
            $this->writeRedisSummary(
                (int) $user->id,
                (int) $resume->id,
                $refreshedAt->toDateString(),
            );
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readRedisSummary(int $userId, string $refreshDate): ?array
    {
        try {
            $value = Redis::connection((string) config('rc_stats.redis_connection'))
                ->get($this->redisKey($userId, $refreshDate));

            if (! is_string($value) || $value === '') {
                return null;
            }

            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function writeRedisSummary(int $userId, int $resumeId, string $refreshDate): void
    {
        try {
            $logs = ResumeRefreshLog::query()
                ->where('user_id', $userId)
                ->whereDate('refresh_date', $refreshDate);
            $summary = [
                'date' => $refreshDate,
                'resume_id' => $resumeId,
                'last_refreshed_at' => (clone $logs)->max('refreshed_at'),
                'refresh_count' => (clone $logs)->count(),
                'free_count' => (clone $logs)
                    ->where('quota_type', RcResumeRefreshQuotaType::FreeDaily->value)
                    ->count(),
                'asset_count' => (clone $logs)
                    ->where('quota_type', RcResumeRefreshQuotaType::Asset->value)
                    ->count(),
            ];
            $ttl = max(1, now()->diffInSeconds(now()->copy()->endOfDay()->addSecond(), false));

            Redis::connection((string) config('rc_stats.redis_connection'))->setex(
                $this->redisKey($userId, $refreshDate),
                $ttl,
                json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function redisKey(int $userId, string $refreshDate): string
    {
        return 'rc:resume-refresh:'.$userId.':'.$refreshDate;
    }
}
