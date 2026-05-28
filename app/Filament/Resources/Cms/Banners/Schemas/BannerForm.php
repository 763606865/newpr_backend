<?php

namespace App\Filament\Resources\Cms\Banners\Schemas;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Models\Cms\BannerPosition;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('position_id')
                    ->label('版位')
                    ->relationship('position', 'name')
                    ->required()
                    ->options(fn (): array => BannerPosition::query()->orderBy('name')->pluck('name', 'id')->all()),
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('title')->label('标题')->required(),
                FileUpload::make('image')
                    ->label('图片')
                    ->image()
                    ->disk('oss')
                    ->directory('banner')
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
                    ->maxSize(10240), // 10MB
                Select::make('link_type')->label('链接类型')->options(CmsLinkType::class)->enum(CmsLinkType::class)->required(),
                TextInput::make('link_url')->label('跳转地址'),
                Select::make('target')->label('打开方式')->options(CmsOpenTarget::class)->enum(CmsOpenTarget::class)->required(),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
