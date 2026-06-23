<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolActivityApplyStatus;
use App\Models\Model;
use App\Models\School;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 活动关联学校表
 *
 * @property int $id 主键ID
 * @property int $activity_id 活动ID
 * @property string|null $contact_name 院校对接联系人
 * @property string|null $contact_phone 联系电话
 * @property string|null $contact_email 联系邮箱
 * @property RcSchoolActivityApplyStatus|null $apply_status 进校申请状态
 * @property Carbon|null $apply_at 申请提交时间
 * @property string|null $remark 申请备注或审核意见
 * @property Carbon|null $updated_at 更新时间
 * @property-read SchoolActivity $activity 所属活动
 * @property-read School $school 关联学校
 */
#[Table('rc_school_activity_schools')]
#[Fillable([
    'activity_id',
    'school_id',
    'contact_name',
    'contact_phone',
    'contact_email',
    'apply_status',
    'apply_at',
    'remark',
])]
class SchoolActivitySchool extends Model
{
    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'school_id' => 'integer',
            'apply_status' => RcSchoolActivityApplyStatus::class,
            'apply_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(SchoolActivity::class, 'activity_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
