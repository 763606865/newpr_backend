<?php

namespace App\Filament\Resources\Rc\SchoolActivities\Schemas;

use App\Enums\RcSchoolActivityOrganizerType;
use App\Enums\RcSchoolActivityStatus;
use App\Enums\RcSchoolActivityType;
use App\Models\Area;
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
use Illuminate\Support\Facades\Storage;

class SchoolActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('活动类型')
                    ->options(RcSchoolActivityType::class)
                    ->required()
                    ->default(RcSchoolActivityType::JobFair),
                TextInput::make('title')
                    ->label('标题')
                    ->required(),
                FileUpload::make('cover_image')
                    ->label('封面图')
                    ->image()
                    ->disk('oss')
                    ->directory('school-activity')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(5120)
                    ->columnSpanFull(),
                RichEditor::make('description')
                    ->label('活动描述')
                    ->columnSpanFull(),
                TextInput::make('link_url')
                    ->label('外链地址')
                    ->url()
                    ->columnSpanFull(),
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
                    }),
                Select::make('city_code')
                    ->label('城市')
                    ->options(function (Get $get, ?string $state): array {
                        return Area::optionsWithSelected(
                            Area::cityOptions($get('province_code')),
                            $state,
                        );
                    })
                    ->searchable()
                    ->placeholder('请选择城市')
                    ->disabled(fn (Get $get, ?string $state): bool => blank($get('province_code')) && blank($state))
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        $set('district_code', null);
                    }),
                Select::make('district_code')
                    ->label('区县')
                    ->options(function (Get $get, ?string $state): array {
                        return Area::optionsWithSelected(
                            Area::districtOptions($get('province_code'), $get('city_code')),
                            $state,
                        );
                    })
                    ->searchable()
                    ->placeholder('请选择区县')
                    ->disabled(fn (Get $get, ?string $state): bool => blank($get('province_code')) && blank($get('city_code')) && blank($state)),
                TextInput::make('address')
                    ->label('详细地址'),
                DateTimePicker::make('register_start_date')
                    ->label('报名开始时间'),
                DateTimePicker::make('register_end_date')
                    ->label('报名截止时间'),
                DateTimePicker::make('start_time')
                    ->label('开始时间'),
                DateTimePicker::make('end_time')
                    ->label('结束时间'),
                Select::make('organizer_type')
                    ->label('主办方类型')
                    ->options(RcSchoolActivityOrganizerType::class),
                TextInput::make('organizer_id')
                    ->label('主办方ID')
                    ->numeric(),
                Select::make('booth_id')
                    ->label('展位模板')
                    ->relationship('booth', 'name'),
                TextInput::make('contact_name')
                    ->label('联系人'),
                TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->tel(),
                Select::make('status')
                    ->label('状态')
                    ->options(RcSchoolActivityStatus::class)
                    ->required()
                    ->default(RcSchoolActivityStatus::Draft),
                Toggle::make('is_hot')
                    ->label('热门')
                    ->default(false),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                FileUpload::make('files')
                    ->label('附件')
                    ->multiple()
                    ->disk('oss')
                    ->directory('school-activity/files')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): array => self::formatFilesForUpload($state))
                    ->dehydrateStateUsing(static fn (mixed $state): ?array => self::dehydrateUploadedFiles($state))
                    ->maxSize(20480)
                    ->columnSpanFull(),
                Textarea::make('remark')
                    ->label('备注')
                    ->columnSpanFull(),
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
