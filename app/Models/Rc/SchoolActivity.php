<?php

namespace App\Models\Rc;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Cast\AliyunOss;
use App\Models\Company;
use App\Models\Model;
use App\Models\School;
use App\Support\SchoolActivityInviteCode;
use App\Support\ScoutQuery;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * 校园活动表
 *
 * @property int $id 主键ID
 * @property RcSchoolActivityType $type 活动类型
 * @property string $title 活动标题
 * @property string|null $cover_image 活动封面图
 * @property string|null $description 活动描述
 * @property string|null $link_url 活动外链地址
 * @property string|null $province_code 省
 * @property string|null $city_code 市
 * @property string|null $district_code 区/县
 * @property string|null $address 地址
 * @property Carbon|null $register_start_date 报名开始日期
 * @property Carbon|null $register_end_date 报名截止日期
 * @property Carbon|null $start_time 开始时间
 * @property Carbon|null $end_time 结束时间
 * @property RcSchoolActivityOrganizerType|null $organizer_type 主办方多态类型
 * @property int|null $organizer_id 主办方ID
 * @property int|null $booth_id 采用的展位模板ID
 * @property string|null $contact_name 对接负责人
 * @property string|null $contact_phone 联系电话
 * @property RcSchoolActivityStatus $status 状态
 * @property bool $is_hot 热门活动
 * @property int $sort 排序
 * @property array<int, mixed>|null $files 相关文件
 * @property array<string, mixed>|null $extra 扩展数据
 * @property string|null $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 * @property Carbon|null $deleted_at 删除时间
 * @property-read string $invite_code 活动邀请码
 * @property-read \Illuminate\Database\Eloquent\Model|null $organizer 主办方
 * @property-read SchoolBooth|null $booth 采用的展位模板
 *
 * @method static Builder draft()
 * @method static Builder published()
 * @method static Builder ended()
 * @method static Builder hot()
 * @method static Builder ofType(RcSchoolActivityType $type)
 * @method static Builder forOrganizer(RcSchoolActivityOrganizerType $organizerType, int $organizerId)
 * @method static Builder forRegion(?string $regionCode)
 * @method static Builder registerOpen()
 * @method static Builder availableForRecruiter()
 */
#[Table('rc_school_activities')]
#[Fillable([
    'type',
    'title',
    'cover_image',
    'description',
    'link_url',
    'province_code',
    'city_code',
    'district_code',
    'address',
    'register_start_date',
    'register_end_date',
    'start_time',
    'end_time',
    'organizer_type',
    'organizer_id',
    'booth_id',
    'contact_name',
    'contact_phone',
    'status',
    'is_hot',
    'sort',
    'files',
    'extra',
    'remark',
])]
class SchoolActivity extends Model
{
    use Searchable, SoftDeletes;

    protected $attributes = [
        'type' => RcSchoolActivityType::JobFair,
        'status' => RcSchoolActivityStatus::Draft,
        'is_hot' => false,
        'sort' => 0,
    ];

    protected static function booted(): void
    {
        static::saved(function (SchoolActivity $activity): void {
            $activityId = $activity->id;

            app()->terminating(function () use ($activityId): void {
                $fresh = SchoolActivity::query()
                    ->with(['organizer', 'schools'])
                    ->find($activityId);

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

    protected function casts(): array
    {
        return [
            'type' => RcSchoolActivityType::class,
            'cover_image' => AliyunOss::class.':oss,public,3600',
            'register_start_date' => 'datetime',
            'register_end_date' => 'datetime',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'organizer_type' => RcSchoolActivityOrganizerType::class,
            'organizer_id' => 'integer',
            'booth_id' => 'integer',
            'status' => RcSchoolActivityStatus::class,
            'is_hot' => 'boolean',
            'sort' => 'integer',
            'files' => 'array',
            'extra' => 'array',
        ];
    }

    public function organizer(): MorphTo
    {
        return $this->morphTo();
    }

    public function booth(): BelongsTo
    {
        return $this->belongsTo(SchoolBooth::class, 'booth_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'rc_school_activity_companies',
            'activity_id',
            'company_id',
        )->withTimestamps();
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(
            School::class,
            'rc_school_activity_schools',
            'activity_id',
            'school_id',
        )->withTimestamps();
    }

    public function companyApplications(): HasMany
    {
        return $this->hasMany(SchoolActivityCompany::class, 'activity_id');
    }

    public function schoolLinks(): HasMany
    {
        return $this->hasMany(SchoolActivitySchool::class, 'activity_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(SchoolActivityJob::class, 'activity_id');
    }

    public function activityBooths(): HasMany
    {
        return $this->hasMany(SchoolActivityBooth::class, 'activity_id');
    }

    public function searchableAs(): string
    {
        return 'rc_school_activities';
    }

    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed();
    }

    public function isPubliclySearchable(): bool
    {
        $status = $this->status instanceof RcSchoolActivityStatus
            ? $this->status
            : RcSchoolActivityStatus::tryFrom((int) $this->status);

        return $status === RcSchoolActivityStatus::Published;
    }

    public function isRegisterOpen(): bool
    {
        $now = now();

        $registerStartOpen = $this->register_start_date === null
            || $this->register_start_date <= $now;

        $registerEndOpen = $this->register_end_date === null
            || $this->register_end_date >= $now;

        return $registerStartOpen && $registerEndOpen;
    }

    public function isAvailableForRecruiter(): bool
    {
        return $this->isPubliclySearchable() && $this->isRegisterOpen();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['organizer', 'schools']);

        $extra = $this->extra ?? [];
        $keywords = collect($extra['keywords'] ?? [])
            ->filter(static fn (mixed $keyword): bool => filled($keyword))
            ->map(static fn (mixed $keyword): string => trim((string) $keyword))
            ->values()
            ->all();

        $schoolNames = $this->schools->pluck('name')->filter()->values();
        $schoolCodes = $this->schools->pluck('school_code')->filter()->values();

        $organizerName = match (true) {
            $this->organizer instanceof School => $this->organizer->name,
            $this->organizer instanceof Company => $this->organizer->name,
            default => null,
        };

        return [
            'id' => (int) $this->id,
            'type' => $this->type instanceof RcSchoolActivityType
                ? $this->type->value
                : (int) $this->type,
            'type_label' => $this->type?->getLabel(),
            'title' => $this->title,
            'description' => filled($this->description)
                ? html_entity_decode(strip_tags((string) $this->description))
                : null,
            'address' => $this->address,
            'link_url' => $this->link_url,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'province_code' => $this->province_code,
            'city_code' => $this->city_code,
            'district_code' => $this->district_code,
            'organizer_type' => $this->organizer_type?->value,
            'organizer_type_label' => $this->organizer_type?->getLabel(),
            'organizer_id' => $this->organizer_id !== null ? (int) $this->organizer_id : null,
            'organizer_name' => $organizerName,
            'school_names' => $schoolNames->implode(' '),
            'school_codes' => $schoolCodes->implode(' '),
            'school_ids' => $this->schools->pluck('id')->values()->all(),
            'keywords' => implode(' ', $keywords),
            'status' => $this->status instanceof RcSchoolActivityStatus
                ? $this->status->value
                : (int) $this->status,
            'is_hot' => (int) $this->is_hot,
            'sort' => (int) $this->sort,
            'is_public' => $this->isPubliclySearchable() ? 1 : 0,
            'is_register_open' => $this->isRegisterOpen() ? 1 : 0,
            'is_available' => $this->isAvailableForRecruiter() ? 1 : 0,
            'register_start_date' => ScoutQuery::timestamp($this->register_start_date),
            'register_end_date' => ScoutQuery::timestamp($this->register_end_date),
            'start_time' => ScoutQuery::timestamp($this->start_time),
            'end_time' => ScoutQuery::timestamp($this->end_time),
            'updated_at' => ScoutQuery::timestamp($this->getAttributes()['updated_at'] ?? null),
        ];
    }

    protected function inviteCode(): Attribute
    {
        return Attribute::get(
            fn (): string => SchoolActivityInviteCode::encode($this->id),
        );
    }

    #[Scope]
    protected function draft(Builder $query): void
    {
        $query->where('status', RcSchoolActivityStatus::Draft);
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', RcSchoolActivityStatus::Published);
    }

    #[Scope]
    protected function ended(Builder $query): void
    {
        $query->where('status', RcSchoolActivityStatus::Ended);
    }

    #[Scope]
    protected function hot(Builder $query): void
    {
        $query->where('is_hot', true);
    }

    #[Scope]
    protected function ofType(Builder $query, RcSchoolActivityType $type): void
    {
        $query->where('type', $type);
    }

    #[Scope]
    protected function forOrganizer(Builder $query, RcSchoolActivityOrganizerType $organizerType, int $organizerId): void
    {
        $query->where('organizer_type', $organizerType)
            ->where('organizer_id', $organizerId);
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
                    ->whereNull($table.'.district_code');
            })->orWhere($table.'.province_code', '=', $regionCode)
                ->orWhere($table.'.city_code', '=', $regionCode)
                ->orWhere($table.'.district_code', '=', $regionCode);
        });
    }

    #[Scope]
    protected function registerOpen(Builder $query): void
    {
        $now = now();

        $query->where(function (Builder $builder) use ($now): void {
            $builder->whereNull('register_start_date')
                ->orWhere('register_start_date', '<=', $now);
        })->where(function (Builder $builder) use ($now): void {
            $builder->whereNull('register_end_date')
                ->orWhere('register_end_date', '>=', $now);
        });
    }

    #[Scope]
    protected function availableForRecruiter(Builder $query): void
    {
        $query->published()->registerOpen();
    }
}
