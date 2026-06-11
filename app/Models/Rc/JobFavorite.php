<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 求职者职位收藏表
 *
 * @property int $id 主键ID
 * @property int $user_id 用户ID
 * @property int $job_id 职位ID
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read User $user 收藏用户
 * @property-read Job $job 收藏职位
 */
#[Table('rc_job_favorites')]
#[Fillable([
    'user_id',
    'job_id',
])]
class JobFavorite extends Model
{
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'job_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }
}
