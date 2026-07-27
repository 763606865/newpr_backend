<?php

namespace App\Filament\Resources\Rc\BizPlans\Tables;

use App\Enums\RcBizPlanBillingCycle;
use App\Enums\RcBizPlanProductType;
use App\Enums\RcBizPlanStatus;
use App\Enums\RcBizPlanTargetSide;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BizPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('plan_name')
                    ->label('商品名称')
                    ->searchable(),
                TextColumn::make('plan_code')
                    ->label('商品编码')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('target_side')
                    ->label('目标用户')
                    ->badge(),
                TextColumn::make('product_type')
                    ->label('商品类型')
                    ->badge(),
                TextColumn::make('price')
                    ->label('销售价格')
                    ->money('CNY')
                    ->sortable(),
                TextColumn::make('billing_cycle')
                    ->label('计费周期'),
                TextColumn::make('duration')
                    ->label('有效期')
                    ->formatStateUsing(static fn (int $state): string => $state === 0 ? '永久' : $state.' 天'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (RcBizPlanStatus $state): string => match ($state) {
                        RcBizPlanStatus::Enabled => 'success',
                        RcBizPlanStatus::Disabled => 'gray',
                    }),
                TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('更新时间')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('target_side')
                    ->label('目标用户')
                    ->options(RcBizPlanTargetSide::class),
                SelectFilter::make('product_type')
                    ->label('商品类型')
                    ->options(RcBizPlanProductType::class),
                SelectFilter::make('billing_cycle')
                    ->label('计费周期')
                    ->options(RcBizPlanBillingCycle::class),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(RcBizPlanStatus::class),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
