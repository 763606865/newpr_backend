<?php

namespace App\Models\Rc;

use App\Enums\RcJobStageStatus;
use App\Models\Company;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘流程阶段表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property string $code 阶段编码
 * @property string $name 阶段名称
 * @property int $sort 排序
 * @property int $is_default 是否默认阶段
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 */
#[Table('rc_job_stages')]
#[Fillable(['company_id', 'code', 'name', 'sort', 'is_default', 'status', 'extra'])]
class JobStage extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'sort' => 0,
        'is_default' => 0,
        'status' => RcJobStageStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'sort' => 'integer',
            'is_default' => 'integer',
            'status' => RcJobStageStatus::class,
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'current_stage_id');
    }
}
