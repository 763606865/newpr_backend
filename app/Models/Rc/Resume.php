<?php

namespace App\Models\Rc;

use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * 招聘简历表
 *
 * @property int $id 主键ID
 * @property int $user_id 关联用户ID
 * @property string $resume_no 简历编号
 * @property string $title 简历名称
 * @property int $source_type 来源类型
 * @property string|null $file_url 简历文件地址
 * @property string|null $file_name 简历文件名称
 * @property string|null $file_ext 文件后缀
 * @property string|null $text_content 简历文本内容
 * @property array<string, mixed>|null $parsed_data 解析后的结构化数据
 * @property int $is_primary 是否主简历
 * @property int $status 状态
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read User $user 所属用户
 */
#[Table('rc_resumes')]
#[Fillable([
    'user_id',
    'resume_no',
    'title',
    'source_type',
    'file_url',
    'file_name',
    'file_ext',
    'text_content',
    'parsed_data',
    'is_primary',
    'status',
    'extra',
])]
class Resume extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'source_type' => RcResumeSourceType::Upload,
        'is_primary' => 0,
        'status' => RcResumeStatus::Normal,
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'source_type' => RcResumeSourceType::class,
            'is_primary' => 'integer',
            'status' => RcResumeStatus::class,
            'parsed_data' => 'array',
            'extra' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'resume_id');
    }

    public function talentPoolMembers(): HasMany
    {
        return $this->hasMany(TalentPoolMember::class, 'resume_id');
    }
}
