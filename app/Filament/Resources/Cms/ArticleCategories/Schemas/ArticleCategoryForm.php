<?php

namespace App\Filament\Resources\Cms\ArticleCategories\Schemas;

use App\Enums\CmsStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('父级分类')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->placeholder('顶级分类')
                    ->dehydrateStateUsing(fn ($state): int => (int) ($state ?: 0)),
                TextInput::make('name')->label('分类名称')->required(),
                TextInput::make('slug')->label('别名')->maxLength(128),
                TextInput::make('cover')->label('封面图'),
                TextInput::make('description')->label('描述'),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
            ]);
    }
}
