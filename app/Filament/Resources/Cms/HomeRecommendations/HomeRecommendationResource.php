<?php

namespace App\Filament\Resources\Cms\HomeRecommendations;

use App\Filament\Resources\Cms\HomeRecommendations\Pages\CreateHomeRecommendation;
use App\Filament\Resources\Cms\HomeRecommendations\Pages\EditHomeRecommendation;
use App\Filament\Resources\Cms\HomeRecommendations\Pages\ListHomeRecommendations;
use App\Filament\Resources\Cms\HomeRecommendations\Schemas\HomeRecommendationForm;
use App\Filament\Resources\Cms\HomeRecommendations\Tables\HomeRecommendationsTable;
use App\Models\Cms\HomeRecommendation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class HomeRecommendationResource extends Resource
{
    protected static ?string $model = HomeRecommendation::class;

    protected static ?string $label = '首页推荐位';

    protected static ?string $navigationLabel = '首页推荐位';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return HomeRecommendationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomeRecommendationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomeRecommendations::route('/'),
            'create' => CreateHomeRecommendation::route('/create'),
            'edit' => EditHomeRecommendation::route('/{record}/edit'),
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
