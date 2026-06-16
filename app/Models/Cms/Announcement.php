<?php

namespace App\Models\Cms;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Models\Cms\Concerns\InteractsWithCmsTags;
use App\Models\Model;
use App\Support\ScoutQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * 门户公告表
 *
 * @property int $id 主键ID
 * @property string|null $organization_type 发布机构多态类型
 * @property int|null $organization_id 发布机构多态ID
 * @property string|null $publisher_name 发布人名称
 * @property CmsAnnouncementPublisherType $publisher_type 发布人类型
 * @property string|null $province_code 省份编码
 * @property string|null $city_code 城市编码
 * @property string|null $district_code 区县编码
 * @property string|null $area_code 行政区划编码
 * @property string $title 公告标题
 * @property string|null $sub_title 公告副标题
 * @property string|null $link_url 公告链接
 * @property CmsAnnouncementType $type 公告类型
 * @property string|null $source_name 来源名称
 * @property string|null $source_url 来源地址
 * @property string|null $summary 公告摘要
 * @property string|null $content 公告正文
 * @property Carbon|null $published_at 发布时间
 * @property Carbon|null $start_at 生效开始时间
 * @property Carbon|null $end_at 生效结束时间
 * @property int $is_top 是否置顶
 * @property CmsPublishStatus $status 状态
 * @property int $sort 排序
 * @property array<string, mixed>|null $extra 扩展字段
 * @property int $read_count 阅读人数
 * @property array<int, mixed>|null $files 附件列表
 * @property-read Collection<int, Tag> $tags 标签列表
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Model|null $organization 发布机构
 *
 * @method static Builder createdBetween(?string $from, ?string $to)
 * @method static Builder forCity(?string $cityCode)
 * @method static Builder forRegion(?string $regionCode)
 * @method static Builder enabled()
 * @method static Builder withTags(array $tags, bool $matchAll = true)
 * @method static Builder forPublisherTypes(array $publisherTypes)
 */
#[Table('cms_announcements')]
#[Fillable([
    'organization_type',
    'organization_id',
    'publisher_name',
    'publisher_type',
    'province_code',
    'city_code',
    'district_code',
    'area_code',
    'title',
    'sub_title',
    'link_url',
    'type',
    'source_name',
    'source_url',
    'summary',
    'content',
    'published_at',
    'start_at',
    'end_at',
    'is_top',
    'status',
    'sort',
    'extra',
    'read_count',
    'files',
])]
#[Visible([
    'id',
    'organization_type',
    'organization_id',
    'publisher_name',
    'publisher_type',
    'province_code',
    'city_code',
    'district_code',
    'area_code',
    'title',
    'sub_title',
    'link_url',
    'type',
    'source_name',
    'source_url',
    'summary',
    'content',
    'published_at',
    'start_at',
    'end_at',
    'is_top',
    'status',
    'sort',
    'extra',
    'read_count',
    'files',
    'created_at',
    'updated_at',
])]
class Announcement extends Model
{
    use InteractsWithCmsTags;
    use Searchable;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saved(function (Announcement $announcement): void {
            $announcementId = $announcement->id;

            app()->terminating(function () use ($announcementId): void {
                $fresh = Announcement::query()->with('tags')->find($announcementId);

                if ($fresh === null) {
                    return;
                }

                if ($fresh->shouldBeSearchable()) {
                    $fresh->searchable();
                } else {
                    $fresh->unsearchable();
                }
            });
        });
    }

    protected $attributes = [
        'type' => CmsAnnouncementType::System,
        'publisher_type' => CmsAnnouncementPublisherType::System,
        'is_top' => 0,
        'status' => CmsPublishStatus::Draft,
        'sort' => 0,
        'read_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'publisher_type' => CmsAnnouncementPublisherType::class,
            'type' => CmsAnnouncementType::class,
            'published_at' => 'datetime:Y-m-d H:i:s',
            'start_at' => 'datetime:Y-m-d H:i:s',
            'end_at' => 'datetime:Y-m-d H:i:s',
            'is_top' => 'boolean',
            'status' => CmsPublishStatus::class,
            'sort' => 'integer',
            'read_count' => 'integer',
            'extra' => 'array',
            'files' => 'array',
        ];
    }

    public function organization(): MorphTo
    {
        return $this->morphTo();
    }

    public function incrementReadCount(): void
    {
        $this->increment('read_count');
    }

    public function searchableAs(): string
    {
        return 'cms_announcements';
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed();
    }

    public function isPubliclySearchable(): bool
    {
        $status = $this->status instanceof CmsPublishStatus
            ? $this->status
            : CmsPublishStatus::tryFrom((int) $this->status);

        return $status === CmsPublishStatus::Published;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('tags');

        $tags = $this->tags;
        $tagNames = $tags->pluck('name')->filter()->values();
        $tagSlugs = $tags->pluck('slug')->filter()->values();

        return [
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'summary' => $this->summary,
            'content' => filled($this->content)
                ? html_entity_decode(strip_tags((string) $this->content))
                : null,
            'publisher_name' => $this->publisher_name,
            'source_name' => $this->source_name,
            'type' => $this->type instanceof CmsAnnouncementType
                ? $this->type->value
                : $this->type,
            'type_label' => $this->type?->getLabel(),
            'publisher_type' => $this->publisher_type instanceof CmsAnnouncementPublisherType
                ? $this->publisher_type->value
                : $this->publisher_type,
            'publisher_type_label' => $this->publisher_type?->getLabel(),
            'province_code' => $this->province_code,
            'city_code' => $this->city_code,
            'district_code' => $this->district_code,
            'area_code' => $this->area_code,
            'status' => $this->status instanceof CmsPublishStatus
                ? $this->status->value
                : (int) $this->status,
            'is_top' => (int) $this->is_top,
            'is_public' => $this->isPubliclySearchable() ? 1 : 0,
            'tag_names' => $tagNames->implode(' '),
            'tag_slugs' => $tagSlugs->implode(' '),
            'tags' => $tagNames->merge($tagSlugs)->implode(' '),
            'tag_ids' => $tags->pluck('id')->values()->all(),
            'published_at' => ScoutQuery::timestamp($this->published_at),
            'start_at' => ScoutQuery::timestamp($this->start_at),
            'end_at' => ScoutQuery::timestamp($this->end_at),
            'updated_at' => ScoutQuery::timestamp($this->getAttributes()['updated_at'] ?? null),
        ];
    }

    #[Scope]
    protected function createdBetween(Builder $query, ?string $from, ?string $to): void
    {
        if (filled($from)) {
            $query->where($this->getTable().'.created_at', '>=', $from);
        }

        if (filled($to)) {
            $query->where($this->getTable().'.created_at', '<=', $to);
        }
    }

    #[Scope]
    protected function forCity(Builder $query, ?string $cityCode): void
    {
        if (blank($cityCode)) {
            return;
        }

        $table = $this->getTable();

        $query->where(function (Builder $builder) use ($cityCode, $table): void {
            $builder->where($table.'.city_code', '=', $cityCode)
                ->orWhereNull($table.'.city_code');
        });
    }

    #[Scope]
    protected function forRegion(Builder $query, ?string $regionCode): void
    {
        if (blank($regionCode)) {
            return;
        }

        $table = $this->getTable();

        $query->where(function (Builder $builder) use ($regionCode, $table): void {
            $builder->where(function (Builder $global) use ($table): void {
                $global->whereNull($table.'.province_code')
                    ->whereNull($table.'.city_code')
                    ->whereNull($table.'.district_code')
                    ->whereNull($table.'.area_code');
            })->orWhere($table.'.province_code', '=', $regionCode)
                ->orWhere($table.'.city_code', '=', $regionCode)
                ->orWhere($table.'.district_code', '=', $regionCode)
                ->orWhere($table.'.area_code', '=', $regionCode);
        });
    }

    #[Scope]
    protected function forPublisherTypes(Builder $query, array $publisherTypes): void
    {
        if ($publisherTypes === []) {
            return;
        }

        $query->whereIn($this->getTable().'.publisher_type', $publisherTypes);
    }

    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsPublishStatus::Published->value);
    }
}
