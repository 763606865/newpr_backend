<?php

namespace App\Filament\Resources\Cms\FriendLinks;

use App\Filament\Resources\Cms\FriendLinks\Pages\CreateFriendLink;
use App\Filament\Resources\Cms\FriendLinks\Pages\EditFriendLink;
use App\Filament\Resources\Cms\FriendLinks\Pages\ListFriendLinks;
use App\Filament\Resources\Cms\FriendLinks\Schemas\FriendLinkForm;
use App\Filament\Resources\Cms\FriendLinks\Tables\FriendLinksTable;
use App\Models\Cms\FriendLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class FriendLinkResource extends Resource
{
    protected static ?string $model = FriendLink::class;

    protected static ?string $label = '友情链接';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return FriendLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FriendLinksTable::configure($table);
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
            'index' => ListFriendLinks::route('/'),
            'create' => CreateFriendLink::route('/create'),
            'edit' => EditFriendLink::route('/{record}/edit'),
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
