<?php

namespace App\Filament\Resources\Cms\HomeRecommendations\Schemas;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Enums\CmsStatus;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Filament\Support\AreaCascadeFormFields;
use App\Models\Company;
use App\Models\Rc\Job;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class HomeRecommendationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('module_type')
                    ->label('推荐模块')
                    ->options(CmsHomeRecommendationModuleType::class)
                    ->enum(CmsHomeRecommendationModuleType::class)
                    ->required()
                    ->live(),
                Select::make('job_id')
                    ->label('推荐职位')
                    ->options(fn (): array => self::jobOptionsForModule(CmsHomeRecommendationModuleType::HotJob))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::isNormalJobModule($get('module_type')))
                    ->required(fn (Get $get): bool => self::isNormalJobModule($get('module_type'))),
                Select::make('campus_job_id')
                    ->label('推荐职位（热门校招）')
                    ->options(fn (): array => self::jobOptionsForModule(CmsHomeRecommendationModuleType::CampusHotJob))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::isCampusJobModule($get('module_type')))
                    ->required(fn (Get $get): bool => self::isCampusJobModule($get('module_type'))),
                Select::make('company_id')
                    ->label('推荐企业')
                    ->options(fn (): array => Company::query()
                        ->enabled()
                        ->with('profile')
                        ->whereHas('profile')
                        ->orderBy('id')
                        ->limit(200)
                        ->get()
                        ->mapWithKeys(fn (Company $company): array => [
                            $company->id => sprintf(
                                '%s（#%d）',
                                $company->profile?->short_name ?: $company->name,
                                $company->id,
                            ),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => self::isCompanyModule($get('module_type')))
                    ->required(fn (Get $get): bool => self::isCompanyModule($get('module_type'))),
                ...AreaCascadeFormFields::makeTwoLevel(),
                TextInput::make('title')
                    ->label('推荐标题')
                    ->maxLength(255)
                    ->helperText('留空时将使用职位或企业名称。'),
                FileUpload::make('cover_image')
                    ->label('推荐展示图')
                    ->image()
                    ->disk('oss')
                    ->directory('home-recommendations')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static function (mixed $state): ?string {
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
                    })
                    ->maxSize(5120)
                    ->columnSpanFull(),
                TextInput::make('link_url')
                    ->label('跳转链接')
                    ->maxLength(500),
                DateTimePicker::make('start_at')
                    ->label('推荐开始时间'),
                DateTimePicker::make('end_at')
                    ->label('推荐结束时间'),
                Select::make('status')
                    ->label('状态')
                    ->options(CmsStatus::class)
                    ->enum(CmsStatus::class)
                    ->required(),
                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                TextInput::make('order_id')
                    ->label('关联订单ID')
                    ->numeric()
                    ->helperText('预留字段，后续可与付费订单绑定。'),
            ]);
    }

    private static function isJobModule(mixed $moduleType): bool
    {
        $enum = self::resolveModuleType($moduleType);

        return $enum?->isJobModule() ?? false;
    }

    private static function isNormalJobModule(mixed $moduleType): bool
    {
        $enum = self::resolveModuleType($moduleType);

        return $enum instanceof CmsHomeRecommendationModuleType && in_array(
            $enum,
            [CmsHomeRecommendationModuleType::UrgentJob, CmsHomeRecommendationModuleType::HotJob],
            true,
        );
    }

    private static function isCampusJobModule(mixed $moduleType): bool
    {
        return self::resolveModuleType($moduleType) === CmsHomeRecommendationModuleType::CampusHotJob;
    }

    private static function isCompanyModule(mixed $moduleType): bool
    {
        return self::resolveModuleType($moduleType)?->isCompanyModule() ?? false;
    }

    private static function resolveModuleType(mixed $moduleType): ?CmsHomeRecommendationModuleType
    {
        if ($moduleType instanceof CmsHomeRecommendationModuleType) {
            return $moduleType;
        }

        return CmsHomeRecommendationModuleType::tryFrom((int) $moduleType);
    }

    /**
     * @return array<int, string>
     */
    private static function jobOptionsForModule(CmsHomeRecommendationModuleType $moduleType): array
    {
        $query = Job::query()
            ->where('status', RcJobStatus::Published)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(200);

        match ($moduleType) {
            CmsHomeRecommendationModuleType::CampusHotJob => $query->where('employment_type', RcJobEmploymentType::Campus),
            default => $query,
        };

        return $query->get()
            ->mapWithKeys(fn (Job $job): array => [
                $job->id => sprintf('%s（#%d）', $job->title, $job->id),
            ])
            ->all();
    }
}
