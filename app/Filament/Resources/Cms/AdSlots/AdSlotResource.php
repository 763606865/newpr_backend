<?php

namespace App\Filament\Resources\Cms\AdSlots;

use App\Filament\Resources\Cms\AdSlots\Pages\CreateAdSlot;
use App\Filament\Resources\Cms\AdSlots\Pages\EditAdSlot;
use App\Filament\Resources\Cms\AdSlots\Pages\ListAdSlots;
use App\Filament\Resources\Cms\AdSlots\Schemas\AdSlotForm;
use App\Filament\Resources\Cms\AdSlots\Tables\AdSlotsTable;
use App\Models\Cms\AdSlot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AdSlotResource extends Resource
{
    protected static ?string $model = AdSlot::class;

    protected static ?string $label = '广告位';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return AdSlotForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdSlotsTable::configure($table);
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
            'index' => ListAdSlots::route('/'),
            'create' => CreateAdSlot::route('/create'),
            'edit' => EditAdSlot::route('/{record}/edit'),
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
