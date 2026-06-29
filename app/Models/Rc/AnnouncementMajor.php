<?php

namespace App\Models\Rc;

use App\Models\Major;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 招聘公告专业关联表
 *
 * @property int $id 主键ID
 * @property int $announcement_id 公告ID
 * @property string $major_code 专业国标编码
 * @property-read Announcement $announcement 所属公告
 * @property-read Major|null $major 关联专业
 */
#[Table('rc_announcement_majors')]
#[Fillable([
    'announcement_id',
    'major_code',
])]
class AnnouncementMajor extends Model
{
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class, 'major_code', 'full_code');
    }
}
