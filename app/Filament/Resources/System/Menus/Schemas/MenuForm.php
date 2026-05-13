<?php

namespace App\Filament\Resources\System\Menus\Schemas;

use App\Enums\SystemMenuType;
use App\Models\C\Menu;
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
                    ->label('父菜单')
                    ->options(Menu::where('parent_id', 0)->pluck('menu_name', 'id'))
                    ->default(0)
                    ->placeholder('顶级菜单'),

                TextInput::make('menu_name')
                    ->label('菜单名称')
                    ->required()
                    ->maxLength(50),

                TextInput::make('menu_code')
                    ->label('菜单编码')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Select::make('menu_type')
                    ->label('菜单类型')
                    ->options(SystemMenuType::class)
                    ->required()
                    ->default(SystemMenuType::Menu),

                TextInput::make('path')
                    ->label('路由路径')
                    ->maxLength(255),

                TextInput::make('component')
                    ->label('前端组件')
                    ->maxLength(255),

                TextInput::make('icon')
                    ->label('图标')
                    ->maxLength(100),

                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),

                Toggle::make('visible')
                    ->label('是否显示')
                    ->default(true),
            ]);
    }
}
