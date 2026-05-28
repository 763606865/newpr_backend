<?php

namespace App\Filament\Resources\Cms\ArticleCategories;

use App\Filament\Resources\Cms\ArticleCategories\Pages\CreateArticleCategory;
use App\Filament\Resources\Cms\ArticleCategories\Pages\EditArticleCategory;
use App\Filament\Resources\Cms\ArticleCategories\Pages\ListArticleCategories;
use App\Filament\Resources\Cms\ArticleCategories\Schemas\ArticleCategoryForm;
use App\Filament\Resources\Cms\ArticleCategories\Tables\ArticleCategoriesTable;
use App\Models\Cms\ArticleCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ArticleCategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static ?string $label = '文章分类';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'CMS';

    public static function form(Schema $schema): Schema
    {
        return ArticleCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticleCategoriesTable::configure($table);
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
            'index' => ListArticleCategories::route('/'),
            'create' => CreateArticleCategory::route('/create'),
            'edit' => EditArticleCategory::route('/{record}/edit'),
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
