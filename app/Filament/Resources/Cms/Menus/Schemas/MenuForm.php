<?php

namespace App\Filament\Resources\Cms\Menus\Schemas;

use App\Enums\CmsLinkType;
use App\Enums\CmsOpenTarget;
use App\Enums\CmsStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('父级菜单')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->placeholder('顶级菜单')
                    ->dehydrateStateUsing(fn ($state): int => (int) ($state ?: 0)),
                TextInput::make('name')->label('菜单名称')->required(),
                TextInput::make('code')->label('菜单编码')->maxLength(64),
                Select::make('link_type')->label('链接类型')->options(CmsLinkType::class)->enum(CmsLinkType::class)->required(),
                TextInput::make('link_url')->label('跳转地址'),
                TextInput::make('icon')->label('菜单图标'),
                TextInput::make('image')->label('菜单图片'),
                Select::make('target')->label('打开方式')->options(CmsOpenTarget::class)->enum(CmsOpenTarget::class)->required(),
                Toggle::make('is_show')->label('是否展示')->default(true),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
                DateTimePicker::make('start_at')->label('生效开始时间'),
                DateTimePicker::make('end_at')->label('生效结束时间'),
                KeyValue::make('extra')->label('扩展字段'),
            ]);
    }
}
