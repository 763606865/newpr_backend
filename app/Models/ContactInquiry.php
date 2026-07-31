<?php

namespace App\Models;

use App\Enums\RcContactInquiryStatus;
use App\Enums\RcContactProduct;
use Database\Factories\ContactInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * C 端联系我们申请。
 *
 * @property int $id
 * @property string $name 姓名或称呼
 * @property string $phone 联系电话
 * @property string|null $company_name 公司名称
 * @property string|null $source 信息来源
 * @property RcContactProduct $product 咨询产品
 * @property string $content 申请或咨询内容
 * @property RcContactInquiryStatus $status 回访状态
 * @property int|null $follow_up_admin_user_id 跟进运营人员ID
 * @property string|null $follow_up_note 跟进备注
 * @property Carbon $submitted_at 申请提交时间
 * @property Carbon|null $followed_up_at 回访时间
 * @property string|null $ip 提交IP
 * @property string|null $user_agent 提交端User-Agent
 * @property array<string, mixed>|null $extra 扩展信息
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read AdminUser|null $followUpAdmin 跟进运营人员
 */
#[Table('contact_inquiries')]
#[Fillable([
    'name',
    'phone',
    'company_name',
    'source',
    'product',
    'content',
    'status',
    'follow_up_admin_user_id',
    'follow_up_note',
    'submitted_at',
    'followed_up_at',
    'ip',
    'user_agent',
    'extra',
])]
class ContactInquiry extends Model
{
    /** @use HasFactory<ContactInquiryFactory> */
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => RcContactInquiryStatus::Pending,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product' => RcContactProduct::class,
            'status' => RcContactInquiryStatus::class,
            'follow_up_admin_user_id' => 'integer',
            'submitted_at' => 'datetime:Y-m-d H:i:s',
            'followed_up_at' => 'datetime:Y-m-d H:i:s',
            'extra' => 'array',
        ];
    }

    public function followUpAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'follow_up_admin_user_id');
    }

    /**
     * 记录运营人员已完成回访。
     */
    public function markAsFollowedUp(AdminUser $admin, ?string $note = null): void
    {
        $this->forceFill([
            'status' => RcContactInquiryStatus::FollowedUp,
            'follow_up_admin_user_id' => $admin->id,
            'follow_up_note' => $note,
            'followed_up_at' => now(),
        ])->save();
    }
}
