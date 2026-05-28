<?php

namespace App\Filament\Resources\Cms\Ads;

use App\Filament\Resources\Cms\Ads\Pages\CreateAd;
use App\Filament\Resources\Cms\Ads\Pages\EditAd;
use App\Filament\Resources\Cms\Ads\Pages\ListAds;
use App\Filament\Resources\Cms\Ads\Schemas\AdForm;
use App\Filament\Resources\Cms\Ads\Tables\AdsTable;
use App\Models\Cms\Ad;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    protected static ?string $label = '广告';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return AdForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdsTable::configure($table);
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
            'index' => ListAds::route('/'),
            'create' => CreateAd::route('/create'),
            'edit' => EditAd::route('/{record}/edit'),
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
