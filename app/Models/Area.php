<?php

namespace App\Models;

use App\Enums\AreaLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 全国行政区划表
 *
 * @property int $id
 * @property string $name 名称
 * @property string $code 行政区划代码
 * @property string|null $parent_code 父级code
 * @property AreaLevel $level 1省 2市 3区县
 * @property string|null $type 类型
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Area|null $parent
 * @property-read Collection<int, Area> $children
 */
#[Table('areas')]
#[Fillable([
    'name',
    'code',
    'parent_code',
    'level',
    'type',
])]
class Area extends Model
{
    protected function casts(): array
    {
        return [
            'level' => AreaLevel::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'code');
    }
}
