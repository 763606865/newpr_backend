<?php

namespace App\Filament\Resources\Cms\FriendLinks\Schemas;

use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use App\Filament\Support\AreaCascadeFormFields;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FriendLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...AreaCascadeFormFields::makeTwoLevel(),
                TextInput::make('name')->label('友链名称')->required(),
                TextInput::make('url')->label('友链地址')->required(),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('oss')
                    ->directory('friend-link')
                    ->visibility(config('filesystems.disks.oss.visibility', 'public'))
                    ->formatStateUsing(static fn (mixed $state): ?string => self::normalizeOssPath($state))
                    ->maxSize(2048),
                Select::make('target')->label('打开方式')->options(CmsOpenTarget::class)->enum(CmsOpenTarget::class)->required(),
                TextInput::make('rel')->label('rel属性'),
                TextInput::make('description')->label('描述'),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
            ]);
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
