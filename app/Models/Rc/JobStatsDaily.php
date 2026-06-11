<?php

namespace App\Models\Rc;

use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 招聘职位浏览量日统计表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int|null $user_id 职位发布人用户ID
 * @property int $job_id 职位ID
 * @property Carbon $stat_date 统计日期
 * @property int $views_total 浏览量（PV）
 * @property int $views_uv 独立访客数（UV）
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read Company $company 所属企业
 * @property-read User|null $user 职位发布人
 * @property-read Job $job 所属职位
 */
#[Table('rc_job_stats_daily')]
#[Fillable([
    'company_id',
    'user_id',
    'job_id',
    'stat_date',
    'views_total',
    'views_uv',
])]
class JobStatsDaily extends Model
{
    protected $attributes = [
        'views_total' => 0,
        'views_uv' => 0,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'user_id' => 'integer',
            'job_id' => 'integer',
            'stat_date' => 'date',
            'views_total' => 'integer',
            'views_uv' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
