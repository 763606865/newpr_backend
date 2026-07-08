<?php

namespace App\Filament\Resources\System\Menus;

use App\Filament\Resources\System\Menus\Pages\CreateMenu;
use App\Filament\Resources\System\Menus\Pages\EditMenu;
use App\Filament\Resources\System\Menus\Pages\ListMenus;
use App\Filament\Resources\System\Menus\Schemas\MenuForm;
use App\Filament\Resources\System\Menus\Tables\MenusTable;
use App\Models\Oa\Menu;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = '菜单管理';

    protected static ?string $modelLabel = '菜单';

    protected static ?string $pluralModelLabel = '菜单';

    protected static string|UnitEnum|null $navigationGroup = '后台管理';

    public static function form(Schema $schema): Schema
    {
        return MenuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}
