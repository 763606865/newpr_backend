<?php

namespace App\Filament\Resources\System\Plans\Schemas;

use App\Enums\SystemPlanStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('plan_name')
                    ->label('方案名称')
                    ->required()
                    ->maxLength(50),

                TextInput::make('plan_code')
                    ->label('方案编码')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('price')
                    ->label('方案价格')
                    ->numeric()
                    ->default(0.00)
                    ->prefix('¥'),

                TextInput::make('duration')
                    ->label('方案时长(天)')
                    ->numeric()
                    ->default(0)
                    ->helperText('0表示永久'),

                TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),

                Textarea::make('remark')
                    ->label('方案描述')
                    ->maxLength(65535),

                Select::make('status')
                    ->label('状态')
                    ->options(SystemPlanStatus::class)
                    ->required()
                    ->default(SystemPlanStatus::Enabled),
            ]);
    }
}
