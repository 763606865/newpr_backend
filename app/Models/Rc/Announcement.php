<?php

namespace App\Models\Rc;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Models\Cast\AliyunOss;
use App\Models\Cms\Concerns\InteractsWithCmsTags;
use App\Models\Model;
use App\Support\ScoutQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * 招聘公告表
 *
 * @property int $id 主键ID
 * @property string|null $organization_type 发布机构多态类型
 * @property int|null $organization_id 发布机构多态ID
 * @property string|null $publisher_name 发布人名称
 * @property CmsAnnouncementPublisherType $publisher_type 发布人类型
 * @property string $title 公告标题
 * @property string|null $sub_title 公告副标题
 * @property string|null $cover 推广图
 * @property string|null $summary 公告摘要
 * @property string|null $content 公告正文
 * @property string|null $link_url 官网外链地址
 * @property array<int, int>|null $employment_types 工作类型列表
 * @property RcEducationLevel|null $education_level 最低学历要求
 * @property array<int, int>|null $graduation_years 面向毕业年份
 * @property string|null $major_requirement 专业要求说明
 * @property bool $is_nationwide 是否全国招聘
 * @property Carbon|null $apply_start_at 报名开始时间
 * @property Carbon|null $apply_end_at 报名截止时间
 * @property RcAnnouncementApplyDeadlineType $apply_deadline_type 截止类型
 * @property Carbon|null $published_at 发布时间
 * @property Carbon|null $expired_at 失效时间
 * @property bool $is_top 是否置顶
 * @property CmsPublishStatus $status 状态
 * @property int $sort 排序
 * @property string|null $source_name 来源名称
 * @property string|null $source_url 来源地址
 * @property int $read_count 阅读人数
 * @property array<string, mixed>|null $extra 扩展字段
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read Model|null $organization 发布机构
 * @property-read Collection<int, AnnouncementCity> $cities 工作城市关联
 * @property-read Collection<int, AnnouncementMajor> $majors 专业关联
 *
 * @method static Builder published()
 */
#[Table('rc_announcements')]
#[Fillable([
    'organization_type',
    'organization_id',
    'publisher_name',
    'publisher_type',
    'title',
    'sub_title',
    'cover',
    'summary',
    'content',
    'link_url',
    'employment_types',
    'education_level',
    'graduation_years',
    'major_requirement',
    'is_nationwide',
    'apply_start_at',
    'apply_end_at',
    'apply_deadline_type',
    'published_at',
    'expired_at',
    'is_top',
    'status',
    'sort',
    'source_name',
    'source_url',
    'read_count',
    'extra',
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
                $fresh = Announcement::query()
                    ->with(['tags', 'cities.cityArea', 'majors.major'])
                    ->find($announcementId);

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
        'publisher_type' => CmsAnnouncementPublisherType::Other,
        'apply_deadline_type' => RcAnnouncementApplyDeadlineType::Fixed,
        'is_nationwide' => false,
        'is_top' => false,
        'status' => CmsPublishStatus::Draft,
        'sort' => 0,
        'read_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'organization_id' => 'integer',
            'publisher_type' => CmsAnnouncementPublisherType::class,
            'cover' => AliyunOss::class.':oss,public,3600',
            'employment_types' => 'array',
            'education_level' => RcEducationLevel::class,
            'graduation_years' => 'array',
            'is_nationwide' => 'boolean',
            'apply_start_at' => 'datetime:Y-m-d H:i:s',
            'apply_end_at' => 'datetime:Y-m-d H:i:s',
            'apply_deadline_type' => RcAnnouncementApplyDeadlineType::class,
            'published_at' => 'datetime:Y-m-d H:i:s',
            'expired_at' => 'datetime:Y-m-d H:i:s',
            'is_top' => 'boolean',
            'status' => CmsPublishStatus::class,
            'sort' => 'integer',
            'read_count' => 'integer',
            'extra' => 'array',
        ];
    }

    public function organization(): MorphTo
    {
        return $this->morphTo();
    }

    public function cities(): HasMany
    {
        return $this->hasMany(AnnouncementCity::class, 'announcement_id');
    }

    public function majors(): HasMany
    {
        return $this->hasMany(AnnouncementMajor::class, 'announcement_id');
    }

    /**
     * @return list<string>
     */
    public static function discoveryRelations(): array
    {
        return ['tags', 'cities.cityArea', 'majors.major'];
    }

    public function searchableAs(): string
    {
        return 'rc_announcements';
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed();
    }

    public function isPubliclySearchable(): bool
    {
        if ($this->status !== CmsPublishStatus::Published) {
            return false;
        }

        if ($this->expired_at !== null && $this->expired_at->lessThan(now())) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['tags', 'cities.cityArea', 'majors.major']);

        $tags = $this->tags;
        $tagNames = $tags->pluck('name')->filter()->values();
        $tagSlugs = $tags->pluck('slug')->filter()->values();
        $cityCodes = $this->cities->pluck('city_code')->filter()->values();
        $cityNames = $this->cities->pluck('cityArea.name')->filter()->values();
        $majorCodes = $this->majors->pluck('major_code')->filter()->values();
        $majorNames = $this->majors->pluck('major.name')->filter()->values();
        $employmentTypes = collect($this->employment_types ?? [])
            ->map(static fn (mixed $value): int => (int) $value)
            ->values()
            ->all();
        $graduationYears = collect($this->graduation_years ?? [])
            ->map(static fn (mixed $value): int => (int) $value)
            ->values()
            ->all();

        return [
            'title' => $this->title,
            'sub_title' => $this->sub_title,
            'summary' => $this->summary,
            'content' => filled($this->content)
                ? html_entity_decode(strip_tags((string) $this->content))
                : null,
            'publisher_name' => $this->publisher_name,
            'publisher_type' => $this->publisher_type instanceof CmsAnnouncementPublisherType
                ? $this->publisher_type->value
                : (int) $this->publisher_type,
            'publisher_type_label' => $this->publisher_type?->getLabel(),
            'employment_types' => $employmentTypes,
            'employment_type_labels' => implode(' ', $this->employmentTypeLabels()),
            'education_level' => $this->education_level instanceof RcEducationLevel
                ? $this->education_level->value
                : $this->education_level,
            'education_level_label' => $this->education_level?->getLabel(),
            'graduation_years' => $graduationYears,
            'graduation_year_labels' => implode(' ', $this->graduationYearLabels()),
            'major_requirement' => $this->major_requirement,
            'city_codes' => $cityCodes->all(),
            'city_names' => $cityNames->implode(' '),
            'major_codes' => $majorCodes->all(),
            'major_names' => $majorNames->implode(' '),
            'is_nationwide' => (int) $this->is_nationwide,
            'link_url' => $this->link_url,
            'source_name' => $this->source_name,
            'status' => $this->status instanceof CmsPublishStatus
                ? $this->status->value
                : (int) $this->status,
            'is_top' => (int) $this->is_top,
            'sort' => (int) $this->sort,
            'is_public' => $this->isPubliclySearchable() ? 1 : 0,
            'is_apply_open' => $this->isApplyOpen() ? 1 : 0,
            'apply_deadline_type' => $this->apply_deadline_type instanceof RcAnnouncementApplyDeadlineType
                ? $this->apply_deadline_type->value
                : (int) $this->apply_deadline_type,
            'tag_names' => $tagNames->implode(' '),
            'tag_slugs' => $tagSlugs->implode(' '),
            'tags' => $tagNames->merge($tagSlugs)->implode(' '),
            'tag_ids' => $tags->pluck('id')->values()->all(),
            'published_at' => ScoutQuery::timestamp($this->published_at),
            'apply_start_at' => ScoutQuery::timestamp($this->apply_start_at),
            'apply_end_at' => ScoutQuery::timestamp($this->apply_end_at),
            'expired_at' => ScoutQuery::timestamp($this->expired_at),
            'updated_at' => ScoutQuery::timestamp($this->getAttributes()['updated_at'] ?? null),
        ];
    }

    /**
     * @param  array<int, string>  $cityCodes
     */
    public function syncCityCodes(array $cityCodes): void
    {
        $cityCodes = array_values(array_unique(array_filter(
            $cityCodes,
            static fn (mixed $cityCode): bool => filled($cityCode),
        )));

        $this->cities()->delete();

        foreach ($cityCodes as $cityCode) {
            $this->cities()->create([
                'city_code' => (string) $cityCode,
            ]);
        }

        if ($this->exists) {
            $this->searchable();
        }
    }

    /**
     * @param  array<int, string>  $majorCodes
     */
    public function syncMajorCodes(array $majorCodes): void
    {
        $majorCodes = array_values(array_unique(array_filter(
            $majorCodes,
            static fn (mixed $majorCode): bool => filled($majorCode),
        )));

        $this->majors()->delete();

        foreach ($majorCodes as $majorCode) {
            $this->majors()->create([
                'major_code' => (string) $majorCode,
            ]);
        }

        if ($this->exists) {
            $this->searchable();
        }
    }

    public function isApplyOpen(): bool
    {
        if ($this->status !== CmsPublishStatus::Published) {
            return false;
        }

        $now = now();

        if ($this->apply_start_at !== null && $this->apply_start_at->greaterThan($now)) {
            return false;
        }

        if ($this->apply_deadline_type === RcAnnouncementApplyDeadlineType::UntilFilled) {
            return true;
        }

        if ($this->apply_end_at !== null && $this->apply_end_at->lessThan($now)) {
            return false;
        }

        return true;
    }

    public function isApplyClosingSoon(): bool
    {
        if ($this->apply_deadline_type === RcAnnouncementApplyDeadlineType::UntilFilled) {
            return false;
        }

        if ($this->apply_end_at === null || ! $this->isApplyOpen()) {
            return false;
        }

        return $this->apply_end_at->lessThanOrEqualTo(now()->addDays(3));
    }

    public function applyStatusLabel(): string
    {
        if (! $this->isApplyOpen()) {
            return '已截止';
        }

        if ($this->isApplyClosingSoon()) {
            return '即将截止';
        }

        return '正在报名';
    }

    /**
     * @return list<string>
     */
    public function employmentTypeLabels(): array
    {
        return collect($this->employment_types ?? [])
            ->map(static function (mixed $value): ?string {
                $enum = RcJobEmploymentType::tryFrom((int) $value);

                return $enum?->getLabel();
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function graduationYearLabels(): array
    {
        return collect($this->graduation_years ?? [])
            ->map(static fn (mixed $year): string => ((int) $year).'届')
            ->values()
            ->all();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where($this->getTable().'.status', '=', CmsPublishStatus::Published->value);
    }
}
