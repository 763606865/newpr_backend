<?php

namespace App\Filament\Resources\Rc\Offers\Tables;

use App\Enums\RcOfferStatus;
use App\Enums\RcSalaryUnit;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('offer_no')->label('Offer编号')->searchable(),
                TextColumn::make('company_id')->label('企业ID')->sortable(),
                TextColumn::make('application_id')->label('投递ID')->sortable(),
                TextColumn::make('salary_min')->label('最低薪资')->numeric(2),
                TextColumn::make('salary_max')->label('最高薪资')->numeric(2),
                TextColumn::make('salary_unit')
                    ->label('薪资单位')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof RcSalaryUnit
                        ? $state->getLabel() ?? '-'
                        : RcSalaryUnit::tryFrom((int) $state)?->getLabel() ?? '-'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof RcOfferStatus
                        ? $state->getLabel() ?? '-'
                        : RcOfferStatus::tryFrom((int) $state)?->getLabel() ?? '-'),
                TextColumn::make('sent_at')->label('发送时间')->dateTime()->placeholder('-')->sortable(),
                TextColumn::make('replied_at')->label('回复时间')->dateTime()->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('salary_unit')
                    ->label('薪资单位')
                    ->options(RcSalaryUnit::class),
                SelectFilter::make('status')
                    ->label('Offer状态')
                    ->options(RcOfferStatus::class),
                Filter::make('sent_range')
                    ->label('发送时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('sent_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('sent_at', '<=', $data['until']),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
