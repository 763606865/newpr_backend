<?php

namespace App\Filament\Resources\Cms\ArticleTags\Schemas;

use App\Enums\CmsStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleTagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('标签名称')->required(),
                TextInput::make('slug')->label('标签别名')->maxLength(128),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
            ]);
    }
}
