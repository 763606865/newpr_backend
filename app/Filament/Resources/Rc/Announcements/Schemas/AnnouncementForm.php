<?php

namespace App\Filament\Resources\Rc\Announcements\Schemas;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsPublishStatus;
use App\Enums\CmsTagCategory;
use App\Enums\MajorLevel;
use App\Enums\RcAnnouncementApplyDeadlineType;
use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Models\Area;
use App\Models\Cms\Tag;
use App\Models\Major;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('公告标题')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('sub_title')
                    ->label('副标题')
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('publisher_name')
                    ->label('发布人名称')
                    ->required()
                    ->maxLength(255),
                Select::make('publisher_type')
                    ->label('发布人类型')
                    ->options(CmsAnnouncementPublisherType::class)
                    ->enum(CmsAnnouncementPublisherType::class)
                    ->required(),
                TextInput::make('link_url')
                    ->label('官网外链')
                    ->url()
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('cover')
                    ->label('推广图')
                    ->image()
                    ->disk('oss')
                    ->directory('rc-announcement')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(5120),
                Select::make('employment_types')
                    ->label('工作类型')
                    ->options(RcJobEmploymentType::class)
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('graduation_years')
                    ->label('面向届别')
                    ->options(self::graduationYearOptions())
                    ->multiple()
                    ->preload()
                    ->searchable(),
                Select::make('education_level')
                    ->label('学历要求')
                    ->options(RcEducationLevel::class)
                    ->enum(RcEducationLevel::class),
                Textarea::make('major_requirement')
                    ->label('专业要求说明')
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('major_codes')
                    ->label('专业筛选')
                    ->options(fn (): array => Major::query()
                        ->enabled()
                        ->atLevel(MajorLevel::Major)
                        ->orderBy('name')
                        ->pluck('name', 'full_code')
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
                Toggle::make('is_nationwide')
                    ->label('全国招聘')
                    ->default(false)
                    ->live(),
                Select::make('work_province_code')
                    ->label('工作省份')
                    ->options(fn (): array => Area::provinceOptions())
                    ->searchable()
                    ->preload()
                    ->placeholder('请选择省份')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('city_codes', []);
                    })
                    ->hidden(fn (Get $get): bool => (bool) $get('is_nationwide')),
                Select::make('city_codes')
                    ->label('工作城市')
                    ->options(fn (Get $get): array => Area::cityOptions($get('work_province_code')))
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->placeholder('请先选择省份')
                    ->disabled(fn (Get $get): bool => blank($get('work_province_code')))
                    ->hidden(fn (Get $get): bool => (bool) $get('is_nationwide'))
                    ->columnSpanFull(),
                DateTimePicker::make('apply_start_at')
                    ->label('报名开始时间'),
                Select::make('apply_deadline_type')
                    ->label('截止类型')
                    ->options(RcAnnouncementApplyDeadlineType::class)
                    ->enum(RcAnnouncementApplyDeadlineType::class)
                    ->default(RcAnnouncementApplyDeadlineType::Fixed)
                    ->required()
                    ->live(),
                DateTimePicker::make('apply_end_at')
                    ->label('报名截止时间')
                    ->hidden(function (Get $get): bool {
                        $deadlineType = $get('apply_deadline_type');
                        $value = $deadlineType instanceof RcAnnouncementApplyDeadlineType
                            ? $deadlineType->value
                            : (int) ($deadlineType ?? RcAnnouncementApplyDeadlineType::Fixed->value);

                        return $value === RcAnnouncementApplyDeadlineType::UntilFilled->value;
                    }),
                Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditor::make('content')
                    ->label('正文')
                    ->columnSpanFull(),
                Select::make('tags')
                    ->label('标签')
                    ->relationship(
                        name: 'tags',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->whereIn('category', [
                                CmsTagCategory::Rc->value,
                                CmsTagCategory::Job->value,
                                CmsTagCategory::Announcement->value,
                            ])
                            ->enabled()
                            ->orderBy('category')
                            ->orderBy('sort')
                            ->orderBy('id'),
                    )
                    ->getOptionLabelFromRecordUsing(function (Tag $tag): string {
                        $categoryLabel = CmsTagCategory::tryFrom($tag->category)?->getLabel() ?? $tag->category;

                        return $categoryLabel.' / '.$tag->name;
                    })
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),
                TextInput::make('source_name')
                    ->label('来源名称'),
                TextInput::make('source_url')
                    ->label('来源地址')
                    ->url(),
                DateTimePicker::make('published_at')
                    ->label('发布时间'),
                DateTimePicker::make('expired_at')
                    ->label('失效时间'),
                Toggle::make('is_top')
                    ->label('置顶')
                    ->default(false),
                Select::make('status')
                    ->label('状态')
                    ->options(CmsPublishStatus::class)
                    ->enum(CmsPublishStatus::class)
                    ->required()
                    ->default(CmsPublishStatus::Draft),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                TextInput::make('read_count')
                    ->label('阅读人数')
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function graduationYearOptions(): array
    {
        $currentYear = (int) now()->format('Y');

        return collect(range($currentYear - 1, $currentYear + 3))
            ->mapWithKeys(static fn (int $year): array => [$year => $year.'届'])
            ->all();
    }

    private static function normalizeOssPath(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        if (is_array($state)) {
            $state = array_values($state)[0] ?? null;
        }

        if (! is_string($state) || $state === '') {
            return null;
        }

        if (str_starts_with($state, 'http://') || str_starts_with($state, 'https://')) {
            $path = parse_url($state, PHP_URL_PATH);

            return is_string($path) ? ltrim($path, '/') : null;
        }

        return ltrim($state, '/');
    }
}
