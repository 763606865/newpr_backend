<?php

namespace App\Models;

use App\Enums\AreaLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 学校表
 *
 * @property int $id
 * @property string $name 名称
 * @property string $code 学校编码
 * @property string|null $parent_code 父级code
 * @property AreaLevel $level 1省 2市 3区县
 * @property string|null $type 类型
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read School|null $parent
 * @property-read Collection<int, School> $children
 *
 * @method static Builder roots()
 * @method static Builder atLevel(int|AreaLevel $level)
 */
#[Table('schools')]
#[Fillable([
    'name',
    'code',
    'parent_code',
    'level',
    'type',
])]
class School extends Model
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

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull($this->getTable().'.parent_code');
    }

    #[Scope]
    protected function atLevel(Builder $query, int|AreaLevel $level): void
    {
        $levelValue = $level instanceof AreaLevel ? $level->value : $level;

        $query->where($this->getTable().'.level', '=', $levelValue);
    }
}
