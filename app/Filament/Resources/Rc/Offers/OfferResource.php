<?php

namespace App\Filament\Resources\Rc\Offers;

use App\Filament\Resources\Rc\Offers\Pages\EditOffer;
use App\Filament\Resources\Rc\Offers\Pages\ListOffers;
use App\Filament\Resources\Rc\Offers\Schemas\OfferForm;
use App\Filament\Resources\Rc\Offers\Tables\OffersTable;
use App\Models\Rc\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $label = 'Offer记录';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return OfferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
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
            'index' => ListOffers::route('/'),
            'edit' => EditOffer::route('/{record}/edit'),
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
