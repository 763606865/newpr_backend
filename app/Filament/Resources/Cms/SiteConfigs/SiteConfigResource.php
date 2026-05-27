<?php

namespace App\Filament\Resources\Cms\SiteConfigs;

use App\Filament\Resources\Cms\SiteConfigs\Pages\CreateSiteConfig;
use App\Filament\Resources\Cms\SiteConfigs\Pages\EditSiteConfig;
use App\Filament\Resources\Cms\SiteConfigs\Pages\ListSiteConfigs;
use App\Filament\Resources\Cms\SiteConfigs\Schemas\SiteConfigForm;
use App\Filament\Resources\Cms\SiteConfigs\Tables\SiteConfigsTable;
use App\Models\Cms\SiteConfig;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SiteConfigResource extends Resource
{
    protected static ?string $model = SiteConfig::class;

    protected static ?string $label = '站点配置';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS门户';

    public static function form(Schema $schema): Schema
    {
        return SiteConfigForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteConfigsTable::configure($table);
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
            'index' => ListSiteConfigs::route('/'),
            'create' => CreateSiteConfig::route('/create'),
            'edit' => EditSiteConfig::route('/{record}/edit'),
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
