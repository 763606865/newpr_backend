<?php

namespace App\Models\Rc;

use App\Enums\RcResumeExposureStatus;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * RC 简历曝光记录表
 *
 * @property int $id
 * @property int $resume_id
 * @property int $user_id
 * @property int|null $asset_ledger_id
 * @property Carbon $started_at
 * @property Carbon $expired_at
 * @property RcResumeExposureStatus $status
 * @property array<string, mixed>|null $extra
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Resume $resume
 * @property-read User $user
 * @property-read AssetLedger|null $assetLedger
 * @property-read Collection<int, ResumeExposureStatsDaily> $statsDaily
 *
 * @method static Builder active(?Carbon $at = null)
 */
#[Table('rc_resume_exposures')]
#[Fillable([
    'resume_id',
    'user_id',
    'asset_ledger_id',
    'started_at',
    'expired_at',
    'status',
    'extra',
])]
class ResumeExposure extends Model
{
    protected $attributes = [
        'status' => RcResumeExposureStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'resume_id' => 'integer',
            'user_id' => 'integer',
            'asset_ledger_id' => 'integer',
            'started_at' => 'datetime',
            'expired_at' => 'datetime',
            'status' => RcResumeExposureStatus::class,
            'extra' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assetLedger(): BelongsTo
    {
        return $this->belongsTo(AssetLedger::class, 'asset_ledger_id');
    }

    public function statsDaily(): HasMany
    {
        return $this->hasMany(ResumeExposureStatsDaily::class, 'exposure_id');
    }

    #[Scope]
    protected function active(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query
            ->where('status', RcResumeExposureStatus::Active->value)
            ->where('started_at', '<=', $at)
            ->where('expired_at', '>', $at);
    }
}
