<?php

namespace App\Models\Rc;

use App\Enums\RcResumeRefreshQuotaType;
use App\Enums\RcResumeRefreshTrigger;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $resume_id
 * @property Carbon $refresh_date
 * @property Carbon $refreshed_at
 * @property RcResumeRefreshTrigger $trigger_type
 * @property RcResumeRefreshQuotaType $quota_type
 * @property string|null $quota_key
 * @property int|null $asset_ledger_id
 * @property array<string, mixed>|null $extra
 */
#[Table('rc_resume_refresh_logs')]
#[Fillable([
    'user_id',
    'resume_id',
    'refresh_date',
    'refreshed_at',
    'trigger_type',
    'quota_type',
    'quota_key',
    'asset_ledger_id',
    'extra',
])]
class ResumeRefreshLog extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'resume_id' => 'integer',
            'refresh_date' => 'date',
            'refreshed_at' => 'datetime',
            'trigger_type' => RcResumeRefreshTrigger::class,
            'quota_type' => RcResumeRefreshQuotaType::class,
            'asset_ledger_id' => 'integer',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class, 'resume_id');
    }

    public function assetLedger(): BelongsTo
    {
        return $this->belongsTo(AssetLedger::class, 'asset_ledger_id');
    }
}
