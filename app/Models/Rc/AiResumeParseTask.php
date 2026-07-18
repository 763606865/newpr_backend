<?php

namespace App\Models\Rc;

use App\Enums\RcAiResumeParseStatus;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 求职者 AI 简历解析任务表
 *
 * @property int $id
 * @property int $user_id
 * @property int $identity_id
 * @property string $file_url
 * @property string|null $provider
 * @property RcAiResumeParseStatus $status
 * @property array<string, mixed>|null $parsed_resume
 * @property string|null $error_message
 * @property int $token_cost
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read UserIdentity $identity
 */
#[Table('rc_ai_resume_parse_tasks')]
#[Fillable([
    'user_id',
    'identity_id',
    'file_url',
    'provider',
    'status',
    'parsed_resume',
    'error_message',
    'token_cost',
    'started_at',
    'finished_at',
])]
class AiResumeParseTask extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'identity_id' => 'integer',
            'status' => RcAiResumeParseStatus::class,
            'parsed_resume' => 'array',
            'token_cost' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(UserIdentity::class, 'identity_id');
    }
}
