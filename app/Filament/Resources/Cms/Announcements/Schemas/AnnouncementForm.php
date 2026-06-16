<?php

namespace App\Filament\Resources\Cms\Announcements\Schemas;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsAnnouncementType;
use App\Enums\CmsPublishStatus;
use App\Enums\CmsTagCategory;
use App\Models\Area;
use App\Models\Cms\Tag;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->label('公告标题')->required()->maxLength(255),
                TextInput::make('sub_title')->label('副标题'),
                Select::make('type')->label('公告类型')->options(CmsAnnouncementType::class)->enum(CmsAnnouncementType::class)->required(),
                TextInput::make('publisher_name')->label('发布人名称'),
                Select::make('publisher_type')->label('发布人类型')->options(CmsAnnouncementPublisherType::class)->enum(CmsAnnouncementPublisherType::class)->required(),
                Select::make('province_code')
                    ->label('省份')
                    ->options(fn (): array => Area::provinceOptions())
                    ->searchable()
                    ->preload()
                    ->placeholder('请选择省份')
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $set('city_code', null);
                        $set('district_code', null);
                        $set('area_code', filled($state) ? $state : null);
                    }),
                Select::make('city_code')
                    ->label('城市')
                    ->options(fn (Get $get): array => Area::cityOptions($get('province_code')))
                    ->searchable()
                    ->placeholder('请选择城市')
                    ->disabled(fn (Get $get): bool => blank($get('province_code')))
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                        $set('district_code', null);
                        $set('area_code', Area::resolveAnnouncementAreaCode($get('province_code'), $state, null));
                    }),
                Select::make('district_code')
                    ->label('区县')
                    ->options(fn (Get $get): array => Area::districtOptions($get('province_code'), $get('city_code')))
                    ->searchable()
                    ->placeholder('请选择区县')
                    ->disabled(fn (Get $get): bool => blank($get('province_code')))
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                        $set('area_code', Area::resolveAnnouncementAreaCode($get('province_code'), $get('city_code'), $state));
                    }),
                Hidden::make('area_code'),
                TextInput::make('link_url')->label('公告链接'),
                TextInput::make('source_name')->label('来源名称'),
                TextInput::make('source_url')->label('来源地址'),
                Textarea::make('summary')->label('摘要')->rows(3),
                RichEditor::make('content')
                    ->label('正文')
                    ->columnSpanFull(),
                FileUpload::make('files')
                    ->label('附件列表')
                    ->multiple()
                    ->disk('oss')
                    ->directory('announcement')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): array => self::formatFilesForUpload($state))
                    ->dehydrateStateUsing(static fn (mixed $state): ?array => self::dehydrateUploadedFiles($state))
                    ->maxSize(20480)
                    ->columnSpanFull(),
                Select::make('tags')
                    ->label('标签')
                    ->relationship(
                        name: 'tags',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->enabled()->orderBy('category')->orderBy('sort')->orderBy('id'),
                    )
                    ->getOptionLabelFromRecordUsing(function (Tag $tag): string {
                        $categoryLabel = CmsTagCategory::tryFrom($tag->category)?->getLabel() ?? $tag->category;

                        return $categoryLabel.' / '.$tag->name;
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),
                DatePicker::make('published_at')->label('发布时间'),
                DatePicker::make('start_at')->label('生效开始时间'),
                DatePicker::make('end_at')->label('生效结束时间'),
                Toggle::make('is_top')->label('置顶')->default(false),
                Select::make('status')->label('状态')->options(CmsPublishStatus::class)->enum(CmsPublishStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                TextInput::make('read_count')->label('阅读人数')->numeric()->default(0),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function formatFilesForUpload(mixed $state): array
    {
        if (blank($state) || ! is_array($state)) {
            return [];
        }

        $paths = [];

        foreach ($state as $item) {
            if (is_string($item)) {
                $normalizedPath = self::normalizeOssPath($item);

                if (filled($normalizedPath)) {
                    $paths[] = $normalizedPath;
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $name = $item['name'] ?? null;
            $url = $item['url'] ?? null;

            if (filled($url)) {
                $normalizedPath = self::normalizeOssPath($url);

                if (filled($normalizedPath)) {
                    $paths[] = $normalizedPath;
                }

                continue;
            }

            if (filled($name)) {
                $normalizedPath = self::normalizeOssPath($name);

                if (filled($normalizedPath)) {
                    $paths[] = $normalizedPath;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return array<int, array{name: string, url: string}>|null
     */
    public static function dehydrateUploadedFiles(mixed $state): ?array
    {
        if (blank($state)) {
            return null;
        }

        $paths = is_array($state) ? $state : [$state];
        $files = [];

        foreach ($paths as $path) {
            $normalizedPath = self::normalizeOssPath($path);

            if (blank($normalizedPath)) {
                continue;
            }

            $files[] = [
                'name' => basename($normalizedPath),
                'url' => Storage::disk('oss')->url($normalizedPath),
            ];
        }

        return $files === [] ? null : $files;
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
