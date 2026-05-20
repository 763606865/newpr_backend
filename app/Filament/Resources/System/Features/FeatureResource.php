<?php

namespace App\Filament\Resources\System\Features;

use App\Filament\Resources\System\Features\Pages\CreateFeature;
use App\Filament\Resources\System\Features\Pages\EditFeature;
use App\Filament\Resources\System\Features\Pages\ListFeatures;
use App\Filament\Resources\System\Features\Schemas\FeatureForm;
use App\Filament\Resources\System\Features\Tables\FeaturesTable;
use App\Models\Client\Feature;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = '功能点管理';

    protected static ?string $modelLabel = '功能点';

    protected static ?string $pluralModelLabel = '功能点';

    protected static string|UnitEnum|null $navigationGroup = '方案';

    public static function form(Schema $schema): Schema
    {
        return FeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturesTable::configure($table);
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
            'index' => ListFeatures::route('/'),
            'create' => CreateFeature::route('/create'),
            'edit' => EditFeature::route('/{record}/edit'),
        ];
    }
}
