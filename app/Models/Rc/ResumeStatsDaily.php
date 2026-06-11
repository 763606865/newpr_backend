<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 招聘简历浏览量日统计表
 *
 * @property int $id 主键ID
 * @property int $user_id 用户ID
 * @property int $resume_id 简历ID
 * @property Carbon $stat_date 统计日期
 * @property int $views_total 浏览量（PV）
 * @property int $views_uv 独立访客数（UV）
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read User $user 所属用户
 * @property-read Resume $resume 所属简历
 */
#[Table('rc_resume_stats_daily')]
#[Fillable([
    'user_id',
    'resume_id',
    'stat_date',
    'views_total',
    'views_uv',
])]
class ResumeStatsDaily extends Model
{
    protected $attributes = [
        'views_total' => 0,
        'views_uv' => 0,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'resume_id' => 'integer',
            'stat_date' => 'date',
            'views_total' => 'integer',
            'views_uv' => 'integer',
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
}
