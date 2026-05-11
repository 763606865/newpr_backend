<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'companyPlans' => fn ($q) => $q->where('is_current', 1)->with('plan'),
            ]))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('名称'),
                TextColumn::make('credit_code')->label('统一信用代码'),
                TextColumn::make('contact_phone')->label('联系电话'),
                TextColumn::make('companyPlans.0.plan.plan_name')
                    ->label('当前套餐')
                    ->placeholder('无'),
                TextColumn::make('companyPlans.0.status')
                    ->label('套餐状态')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        0 => '未开始',
                        1 => '进行中',
                        2 => '已完成',
                        3 => '已取消',
                        default => '未知',
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        0 => 'gray',
                        1 => 'success',
                        2 => 'info',
                        3 => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('无'),
                ToggleColumn::make('status')->label('状态'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
