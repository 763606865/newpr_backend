<?php

namespace App\Filament\Resources\Cms\Ads\Schemas;

use App\Enums\CmsAdType;
use App\Enums\CmsStatus;
use App\Models\Cms\AdSlot;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('slot_id')
                    ->label('广告位')
                    ->relationship('slot', 'name')
                    ->required()
                    ->options(fn (): array => AdSlot::query()->orderBy('name')->pluck('name', 'id')->all()),
                TextInput::make('city_code')->label('城市编码')->maxLength(32),
                TextInput::make('title')->label('广告标题')->required(),
                Select::make('type')->label('广告类型')->options(CmsAdType::class)->enum(CmsAdType::class)->required(),
                FileUpload::make('image')
                    ->label('图片')
                    ->image()
                    ->disk('oss')
                    ->directory('ads')
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
                Textarea::make('text_content')->label('文本内容')->rows(3),
                Textarea::make('code_content')->label('代码内容')->rows(4),
                TextInput::make('link_url')->label('跳转地址'),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
