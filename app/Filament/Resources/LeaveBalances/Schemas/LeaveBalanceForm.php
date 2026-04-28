<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use App\Models\Oa\Company;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class LeaveBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        $companies = Company::query()->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('company_id')
                    ->label('所属企业')
                    ->options($companies)
                    ->live()
                    ->searchable()
                    ->required(),
                Select::make('user_id')
                    ->label('员工')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'phone'])
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => sprintf('%s（%s）', $record->name ?: $record->nickname ?: '未命名用户', $record->phone))
                    ->required(),
                Select::make('leave_type_id')
                    ->label('假期类型')
                    ->relationship(
                        'leaveType',
                        'name',
                        modifyQueryUsing: fn ($query, Get $get) => $query->where('company_id', $get('company_id')),
                    )
                    ->searchable()
                    ->required(),
                TextInput::make('year')
                    ->label('归属年份')
                    ->integer()
                    ->minValue(2000)
                    ->maxValue(3000)
                    ->unique(
                        table: 'oa_leave_balances',
                        column: 'year',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('user_id', $get('user_id'))
                            ->where('leave_type_id', $get('leave_type_id')),
                    )
                    ->required(),
                DatePicker::make('valid_start_date')
                    ->label('有效期开始')
                    ->required(),
                DatePicker::make('valid_end_date')
                    ->label('有效期结束')
                    ->afterOrEqual('valid_start_date')
                    ->required(),
                TextInput::make('total_days')
                    ->label('总授予额度')
                    ->numeric()
                    ->minValue(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set('balance_days', self::calculateBalanceDays($get('total_days'), $get('used_days'))))
                    ->required(),
                TextInput::make('used_days')
                    ->label('已使用额度')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => $set('balance_days', self::calculateBalanceDays($get('total_days'), $get('used_days'))))
                    ->required(),
                TextInput::make('balance_days')
                    ->label('剩余额度')
                    ->numeric()
                    ->readOnly()
                    ->required(),
                TextInput::make('overtime_source_id')
                    ->label('来源加班单ID')
                    ->numeric(),
            ]);
    }

    protected static function calculateBalanceDays(mixed $totalDays, mixed $usedDays): float
    {
        $total = is_numeric($totalDays) ? (float) $totalDays : 0;
        $used = is_numeric($usedDays) ? (float) $usedDays : 0;

        return round($total - $used, 2);
    }
}
