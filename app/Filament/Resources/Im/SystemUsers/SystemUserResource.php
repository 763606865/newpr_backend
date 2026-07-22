<?php

namespace App\Filament\Resources\Im\SystemUsers;

use App\Filament\Resources\Im\SystemUsers\Pages\CreateSystemUser;
use App\Filament\Resources\Im\SystemUsers\Pages\EditSystemUser;
use App\Filament\Resources\Im\SystemUsers\Pages\ListSystemUsers;
use App\Filament\Resources\Im\SystemUsers\Schemas\SystemUserForm;
use App\Filament\Resources\Im\SystemUsers\Tables\SystemUsersTable;
use App\Models\ImSystemUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SystemUserResource extends Resource
{
    protected static ?string $model = ImSystemUser::class;

    protected static ?string $label = '系统用户';

    protected static ?string $pluralLabel = '系统用户';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'IM';

    public static function form(Schema $schema): Schema
    {
        return SystemUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemUsersTable::configure($table);
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
            'index' => ListSystemUsers::route('/'),
            'create' => CreateSystemUser::route('/create'),
            'edit' => EditSystemUser::route('/{record}/edit'),
        ];
    }
}
