<?php

namespace App\Models\Rc;

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
 * @property int $school_id 学校ID
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property-read SchoolActivity $activity 所属活动
 * @property-read School $school 关联学校
 */
#[Table('rc_school_activity_schools')]
#[Fillable([
    'activity_id',
    'school_id',
])]
class SchoolActivitySchool extends Model
{
    protected function casts(): array
    {
        return [
            'activity_id' => 'integer',
            'school_id' => 'integer',
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
