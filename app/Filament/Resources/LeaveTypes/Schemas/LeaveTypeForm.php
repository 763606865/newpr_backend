<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use App\Enums\LeaveTypeDeductionType;
use App\Models\Company;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class LeaveTypeForm
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
                    ->label('假期名称')
                    ->required()
                    ->maxLength(50),
                TextInput::make('code')
                    ->label('假期编码')
                    ->required()
                    ->unique(
                        table: 'oa_leave_types',
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('company_id', $get('company_id')),
                    )
                    ->maxLength(32),
                Select::make('deduction_type')
                    ->label('扣薪类型')
                    ->options(LeaveTypeDeductionType::class)
                    ->default(LeaveTypeDeductionType::Full)
                    ->required(),
                Select::make('unit_type')
                    ->label('请假单位')
                    ->options([
                        1 => '按天',
                        2 => '按小时',
                    ])
                    ->default(1)
                    ->required(),
                TextInput::make('min_duration')
                    ->label('最小请假时长')
                    ->numeric()
                    ->minValue(0.1)
                    ->default(0.5)
                    ->required(),
                Select::make('need_attachment')
                    ->label('必须附件')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->default(0)
                    ->required(),
                Select::make('allow_negative')
                    ->label('允许透支')
                    ->options([
                        0 => '否',
                        1 => '是',
                    ])
                    ->default(0)
                    ->required(),
                TextInput::make('max_continuous_days')
                    ->label('最大连续天数')
                    ->integer()
                    ->minValue(1),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        1 => '启用',
                        0 => '停用',
                    ])
                    ->default(1)
                    ->required(),
            ]);
    }
}
