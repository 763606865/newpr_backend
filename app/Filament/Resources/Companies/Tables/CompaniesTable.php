<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyPlanStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['currentPlans']))
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('name')->label('名称'),
                TextColumn::make('credit_code')->label('统一信用代码'),
                TextColumn::make('contact_phone')->label('联系电话'),
                TextColumn::make('currentPlans.plan_name')
                    ->label('当前套餐')
                    ->placeholder('无'),
                TextColumn::make('currentPlans.pivot.status')
                    ->label('套餐状态')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $status = CompanyPlanStatus::tryFrom((int) $state);

                        return $status?->getLabel() ?? '无';
                    })
                    ->color(function ($state): string {
                        return match (CompanyPlanStatus::tryFrom((int) $state)) {
                            CompanyPlanStatus::Disabled => 'gray',
                            CompanyPlanStatus::Enabled => 'success',
                            CompanyPlanStatus::Pause => 'warning',
                            default => 'gray',
                        };
                    })
                    ->placeholder('无'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
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
