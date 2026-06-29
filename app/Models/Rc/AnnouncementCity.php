<?php

namespace App\Models\Rc;

use App\Models\Area;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 招聘公告工作城市关联表
 *
 * @property int $id 主键ID
 * @property int $announcement_id 公告ID
 * @property string $city_code 工作城市编码
 * @property-read Announcement $announcement 所属公告
 * @property-read Area|null $cityArea 工作城市
 */
#[Table('rc_announcement_cities')]
#[Fillable([
    'announcement_id',
    'city_code',
])]
class AnnouncementCity extends Model
{
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function cityArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'city_code', 'code');
    }
}
