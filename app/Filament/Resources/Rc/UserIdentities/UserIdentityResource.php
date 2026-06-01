<?php

namespace App\Filament\Resources\Rc\UserIdentities;

use App\Filament\Resources\Rc\UserIdentities\Pages\EditUserIdentity;
use App\Filament\Resources\Rc\UserIdentities\Pages\ListUserIdentities;
use App\Filament\Resources\Rc\UserIdentities\Schemas\UserIdentityForm;
use App\Filament\Resources\Rc\UserIdentities\Tables\UserIdentitiesTable;
use App\Models\Rc\UserIdentity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserIdentityResource extends Resource
{
    protected static ?string $model = UserIdentity::class;

    protected static ?string $label = '身份绑定';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return UserIdentityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserIdentitiesTable::configure($table);
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
            'index' => ListUserIdentities::route('/'),
            'edit' => EditUserIdentity::route('/{record}/edit'),
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
