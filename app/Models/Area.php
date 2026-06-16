<?php

namespace App\Models;

use App\Enums\AreaLevel;
use App\Observers\AreaMetaObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
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
#[ObservedBy(AreaMetaObserver::class)]
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

    /**
     * @return array<string, string>
     */
    public static function provinceOptions(): array
    {
        return static::query()
            ->where('level', AreaLevel::Province)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function cityOptions(?string $provinceCode): array
    {
        if (blank($provinceCode)) {
            return [];
        }

        return static::query()
            ->where('parent_code', $provinceCode)
            ->where('level', AreaLevel::City)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function districtOptions(?string $provinceCode, ?string $cityCode): array
    {
        $parentCode = filled($cityCode) ? $cityCode : $provinceCode;

        if (blank($parentCode)) {
            return [];
        }

        return static::query()
            ->where('parent_code', $parentCode)
            ->where('level', AreaLevel::District)
            ->orderBy('code')
            ->pluck('name', 'code')
            ->all();
    }

    public static function resolveAnnouncementAreaCode(?string $provinceCode, ?string $cityCode, ?string $districtCode): ?string
    {
        if (filled($districtCode)) {
            return $districtCode;
        }

        if (filled($cityCode)) {
            return $cityCode;
        }

        return filled($provinceCode) ? $provinceCode : null;
    }
}
