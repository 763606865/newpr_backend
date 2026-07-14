<?php

namespace App\Models\Rc;

use App\Models\Model;
use App\Observers\Rc\PositionMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * 常见职位表
 *
 * @property int $id 主键ID
 * @property string $name 职位名称
 * @property string $code 职位代码
 * @property int|null $parent_id 父级职位ID
 * @property int $sort 排序
 * @property int|null $depth 层级
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Position|null $parent 父级职位
 * @property-read Collection<int, Position> $children 子级职位
 */
#[Table('rc_positions')]
#[Fillable([
    'name',
    'code',
    'parent_id',
    'sort',
    'depth',
    'extra',
])]
#[ObservedBy(PositionMetaObserver::class)]
class Position extends Model
{
    use SoftDeletes, Searchable;

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
            'depth' => 'integer',
            'extra' => 'array',
        ];
    }

    protected function parentId(): Attribute
    {
        return Attribute::make(
            get: static fn (?int $value): ?int => $value === 0 ? null : $value,
            set: static fn (?int $value): ?int => $value === 0 ? null : $value,
        );
    }

    /**
     * 父级职位
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子级职位
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Prepare the data array for Scout indexing.
     */
    public function toSearchableArray(): array
    {
        $parentName = $this->parent?->name ?? null;

        $aliases = '';
        if (is_array($this->extra) && !empty($this->extra)) {
            // Prefer explicit aliases key if present
            if (isset($this->extra['aliases']) && is_array($this->extra['aliases'])) {
                $aliases = implode(' ', array_filter(array_map('strval', $this->extra['aliases'])));
            } elseif (isset($this->extra['aliases'])) {
                $aliases = (string) $this->extra['aliases'];
            } else {
                // Fallback: join all extra values
                $aliases = implode(' ', array_filter(array_map('strval', $this->extra)));
            }
        }

        $text = trim(sprintf('%s %s %s', $this->name ?? '', $parentName ?? '', $aliases));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'parent_name' => $parentName,
            'depth' => $this->depth,
            'aliases' => $aliases,
            'text' => $text,
        ];
    }
}
