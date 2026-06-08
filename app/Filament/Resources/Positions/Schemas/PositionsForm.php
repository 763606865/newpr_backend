<?php

namespace App\Filament\Resources\Positions\Schemas;

use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PositionsForm
{
    public static function configure(Schema $schema): Schema
    {
        $companies = Company::query()->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('company_id')
                    ->label('所属企业')
                    ->options($companies)
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->label('岗位名称')
                    ->required()
                    ->maxLength(60),
                TextInput::make('code')
                    ->label('岗位编码')
                    ->required()
                    ->maxLength(60),
                Toggle::make('is_leader')
                    ->label('管理岗')
                    ->default(false),
                TextInput::make('sort')
                    ->label('排序号')
                    ->default(99)
                    ->numeric(),
                Textarea::make('remark')->label('备注'),
            ]);
    }
}
