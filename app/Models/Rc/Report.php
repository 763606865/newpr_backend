<?php

namespace App\Models\Rc;

use App\Enums\RcReportReasonType;
use App\Enums\RcReportStatus;
use App\Models\AdminUser;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘举报表
 *
 * @property int $id 主键ID
 * @property int $user_id 举报用户ID
 * @property int $creator_user_identity_id 身份ID
 * @property string $reportable_type 举报对象类型
 * @property int $reportable_id 举报对象ID
 * @property RcReportReasonType $reason_type 举报原因类型
 * @property string|null $reason 举报原因
 * @property string|null $description 举报说明
 * @property array<int, string>|null $attachments 举报凭证附件
 * @property RcReportStatus $status 处理状态
 * @property int|null $handler_admin_user_id 处理管理员ID
 * @property string|null $handle_result 处理结果
 * @property Carbon|null $handled_at 处理时间
 * @property string|null $ip 举报IP
 * @property string|null $user_agent User-Agent
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $user 举报用户
 * @property-read UserIdentity $creatorIdentity 举报身份
 * @property-read AdminUser|null $handler 处理管理员
 * @property-read \Illuminate\Database\Eloquent\Model $reportable 举报对象
 */
#[Table('rc_reports')]
#[Fillable([
    'user_id',
    'creator_user_identity_id',
    'reportable_type',
    'reportable_id',
    'reason_type',
    'reason',
    'description',
    'attachments',
    'status',
    'handler_admin_user_id',
    'handle_result',
    'handled_at',
    'ip',
    'user_agent',
    'extra',
])]
class Report extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'status' => RcReportStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'creator_user_identity_id' => 'integer',
            'reportable_id' => 'integer',
            'reason_type' => RcReportReasonType::class,
            'attachments' => 'array',
            'status' => RcReportStatus::class,
            'handler_admin_user_id' => 'integer',
            'handled_at' => 'datetime:Y-m-d H:i:s',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creatorIdentity(): BelongsTo
    {
        return $this->belongsTo(UserIdentity::class, 'creator_user_identity_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'handler_admin_user_id');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }
}
