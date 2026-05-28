<?php

namespace App\Filament\Resources\Rc\UserIdentityBinds;

use App\Filament\Resources\Rc\UserIdentityBinds\Pages\EditUserIdentityBind;
use App\Filament\Resources\Rc\UserIdentityBinds\Pages\ListUserIdentityBinds;
use App\Filament\Resources\Rc\UserIdentityBinds\Schemas\UserIdentityBindForm;
use App\Filament\Resources\Rc\UserIdentityBinds\Tables\UserIdentityBindsTable;
use App\Models\Rc\UserIdentityBind;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserIdentityBindResource extends Resource
{
    protected static ?string $model = UserIdentityBind::class;

    protected static ?string $label = '身份绑定';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return UserIdentityBindForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserIdentityBindsTable::configure($table);
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
            'index' => ListUserIdentityBinds::route('/'),
            'edit' => EditUserIdentityBind::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
