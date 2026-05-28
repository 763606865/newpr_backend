<?php

namespace App\Models\Rc;

use App\Enums\RcApplicationFlowActionType;
use App\Models\Company;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘投递流转记录表
 *
 * @property int $id 主键ID
 * @property int $company_id 企业ID
 * @property int $application_id 投递ID
 * @property int|null $from_stage_id 原阶段ID
 * @property int|null $to_stage_id 目标阶段ID
 * @property int $action_type 动作类型
 * @property int|null $operator_user_id 操作人用户ID
 * @property string|null $note 备注
 * @property Carbon|null $happened_at 发生时间
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Company $company 所属企业
 * @property-read Application $application 所属投递
 */
#[Table('rc_application_flows')]
#[Fillable([
    'company_id',
    'application_id',
    'from_stage_id',
    'to_stage_id',
    'action_type',
    'operator_user_id',
    'note',
    'happened_at',
    'extra',
])]
class ApplicationFlow extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'action_type' => RcApplicationFlowActionType::Transfer,
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'application_id' => 'integer',
            'from_stage_id' => 'integer',
            'to_stage_id' => 'integer',
            'action_type' => RcApplicationFlowActionType::class,
            'operator_user_id' => 'integer',
            'happened_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'to_stage_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }
}
