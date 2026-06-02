<?php

namespace App\Filament\Resources\Rc\Positions\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')->label('ID')->disabled()->dehydrated(false),
                TextInput::make('name')->label('职位名称')->required()->maxLength(255),
                TextInput::make('code')
                    ->label('职位编码')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('parent_id')
                    ->label('父级职位')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('sort')->orderBy('id'),
                    )
                    ->searchable()
                    ->preload()
                    ->placeholder('无'),
                TextInput::make('sort')->label('排序')->numeric()->default(0)->required(),
                KeyValue::make('extra')->label('扩展字段')->columnSpanFull(),
            ]);
    }
}
