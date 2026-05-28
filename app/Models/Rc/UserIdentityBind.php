<?php

namespace App\Models\Rc;

use App\Enums\RcIdentityStatus;
use App\Enums\RcIdentityType;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘用户身份绑定表
 *
 * @property int $id 主键ID
 * @property int $user_id 本地用户ID
 * @property int|null $company_id 企业ID
 * @property int $identity_type 身份类型
 * @property string $identity_name 身份名称
 * @property string|null $organization_name 所属机构名称
 * @property string|null $job_title 头衔/岗位名称
 * @property int $is_default 是否默认身份
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $user 所属用户
 */
#[Table('rc_user_identities')]
#[Fillable([
    'user_id',
    'company_id',
    'identity_type',
    'identity_name',
    'organization_name',
    'job_title',
    'is_default',
    'status',
    'extra',
])]
class UserIdentityBind extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'is_default' => 0,
        'status' => RcIdentityStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'company_id' => 'integer',
            'identity_type' => RcIdentityType::class,
            'is_default' => 'integer',
            'status' => RcIdentityStatus::class,
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
