<?php

namespace App\Filament\Resources\Cms\Tags\Schemas;

use App\Enums\CmsStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('category')
                    ->label('分类')
                    ->required()
                    ->maxLength(64)
                    ->placeholder('如 rc、exam、announcement')
                    ->helperText('同一分类下标签名称不可重复'),
                TextInput::make('name')
                    ->label('标签名称')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')->label('标签别名')->maxLength(128),
                Select::make('status')->label('状态')->options(CmsStatus::class)->enum(CmsStatus::class)->required(),
                TextInput::make('sort')->label('排序')->numeric()->default(0),
            ]);
    }
}
