<?php

namespace App\Filament\Resources\System\Features\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feature_name')
                    ->label('功能名称')
                    ->required()
                    ->maxLength(50),

                TextInput::make('feature_code')
                    ->label('功能编码')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),

                Select::make('menu_id')
                    ->label('所属菜单')
                    ->relationship('menu', 'menu_name')
                    ->required()
                    ->searchable(),

                Textarea::make('description')
                    ->label('功能描述')
                    ->maxLength(255),

                Toggle::make('status')
                    ->label('状态')
                    ->default(true),
            ]);
    }
}
