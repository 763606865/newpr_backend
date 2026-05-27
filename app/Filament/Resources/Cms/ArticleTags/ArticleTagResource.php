<?php

namespace App\Filament\Resources\Cms\ArticleTags;

use App\Filament\Resources\Cms\ArticleTags\Pages\CreateArticleTag;
use App\Filament\Resources\Cms\ArticleTags\Pages\EditArticleTag;
use App\Filament\Resources\Cms\ArticleTags\Pages\ListArticleTags;
use App\Filament\Resources\Cms\ArticleTags\Schemas\ArticleTagForm;
use App\Filament\Resources\Cms\ArticleTags\Tables\ArticleTagsTable;
use App\Models\Cms\ArticleTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ArticleTagResource extends Resource
{
    protected static ?string $model = ArticleTag::class;

    protected static ?string $label = '文章标签';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS门户';

    public static function form(Schema $schema): Schema
    {
        return ArticleTagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleTagsTable::configure($table);
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
            'index' => ListArticleTags::route('/'),
            'create' => CreateArticleTag::route('/create'),
            'edit' => EditArticleTag::route('/{record}/edit'),
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
