<?php

namespace App\Filament\Resources\Rc\AssetDefinitions;

use App\Filament\Resources\Rc\AssetDefinitions\Pages\CreateAssetDefinition;
use App\Filament\Resources\Rc\AssetDefinitions\Pages\EditAssetDefinition;
use App\Filament\Resources\Rc\AssetDefinitions\Pages\ListAssetDefinitions;
use App\Filament\Resources\Rc\AssetDefinitions\Schemas\AssetDefinitionForm;
use App\Filament\Resources\Rc\AssetDefinitions\Tables\AssetDefinitionsTable;
use App\Models\Rc\AssetDefinition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AssetDefinitionResource extends Resource
{
    protected static ?string $model = AssetDefinition::class;

    protected static ?string $label = '权益';

    protected static ?string $pluralLabel = '权益配置';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'RC招聘';

    protected static ?int $navigationSort = 89;

    protected static ?string $recordTitleAttribute = 'asset_name';

    public static function form(Schema $schema): Schema
    {
        return AssetDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetDefinitionsTable::configure($table);
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
            'index' => ListAssetDefinitions::route('/'),
            'create' => CreateAssetDefinition::route('/create'),
            'edit' => EditAssetDefinition::route('/{record}/edit'),
        ];
    }
}
