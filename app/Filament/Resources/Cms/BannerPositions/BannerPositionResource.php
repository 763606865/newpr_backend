<?php

namespace App\Filament\Resources\Cms\BannerPositions;

use App\Filament\Resources\Cms\BannerPositions\Pages\CreateBannerPosition;
use App\Filament\Resources\Cms\BannerPositions\Pages\EditBannerPosition;
use App\Filament\Resources\Cms\BannerPositions\Pages\ListBannerPositions;
use App\Filament\Resources\Cms\BannerPositions\Schemas\BannerPositionForm;
use App\Filament\Resources\Cms\BannerPositions\Tables\BannerPositionsTable;
use App\Models\Cms\BannerPosition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BannerPositionResource extends Resource
{
    protected static ?string $model = BannerPosition::class;

    protected static ?string $label = 'Banner版位';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return BannerPositionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannerPositionsTable::configure($table);
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
            'index' => ListBannerPositions::route('/'),
            'create' => CreateBannerPosition::route('/create'),
            'edit' => EditBannerPosition::route('/{record}/edit'),
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
