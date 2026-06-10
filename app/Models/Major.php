<?php

namespace App\Models;

use App\Enums\MajorEducationType;
use App\Enums\MajorLevel;
use App\Enums\MajorStatus;
use App\Observers\MajorMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 专业表
 *
 * @property int $id
 * @property string $full_code 专业国标编码
 * @property string $name 专业名称
 * @property MajorLevel $level 层级：1大类 2专业类 3专业
 * @property string|null $parent_code 父级编码
 * @property MajorEducationType $type 学历类型
 * @property string $tag 扩展标签
 * @property int $sort 排序
 * @property MajorStatus $status 状态
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Major|null $parent
 * @property-read Collection<int, Major> $children
 *
 * @method static Builder enabled()
 * @method static Builder roots()
 * @method static Builder atLevel(int|MajorLevel $level)
 * @method static Builder ofType(MajorEducationType|string $type)
 */
#[Table('majors')]
#[Fillable([
    'full_code',
    'name',
    'level',
    'parent_code',
    'type',
    'tag',
    'sort',
    'status',
])]
#[ObservedBy(MajorMetaObserver::class)]
class Major extends Model
{
    protected $attributes = [
        'tag' => '',
        'sort' => 0,
        'status' => MajorStatus::Enabled,
    ];

    protected function casts(): array
    {
        return [
            'level' => MajorLevel::class,
            'type' => MajorEducationType::class,
            'sort' => 'integer',
            'status' => MajorStatus::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_code', 'full_code');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_code', 'full_code');
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', MajorStatus::Enabled->value);
    }

    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull($this->getTable().'.parent_code');
    }

    #[Scope]
    protected function atLevel(Builder $query, int|MajorLevel $level): void
    {
        $levelValue = $level instanceof MajorLevel ? $level->value : $level;

        $query->where($this->getTable().'.level', '=', $levelValue);
    }

    #[Scope]
    protected function ofType(Builder $query, MajorEducationType|string $type): void
    {
        $typeValue = $type instanceof MajorEducationType ? $type->value : $type;

        $query->where($this->getTable().'.type', $typeValue);
    }
}
