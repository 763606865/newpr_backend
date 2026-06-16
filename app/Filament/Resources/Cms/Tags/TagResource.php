<?php

namespace App\Filament\Resources\Cms\Tags;

use App\Filament\Resources\Cms\Tags\Pages\CreateTag;
use App\Filament\Resources\Cms\Tags\Pages\EditTag;
use App\Filament\Resources\Cms\Tags\Pages\ListTags;
use App\Filament\Resources\Cms\Tags\Schemas\TagForm;
use App\Filament\Resources\Cms\Tags\Tables\TagsTable;
use App\Models\Cms\Tag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static ?string $label = '通用标签';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
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
