<?php

namespace App\Filament\Resources\Rc\Offers\Tables;

use App\Enums\RcOfferStatus;
use App\Enums\RcSalaryUnit;
use App\Filament\Resources\Rc\RcTable;
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
                TextColumn::make('company.name')->label('企业名称')->placeholder('-')->searchable(),
                TextColumn::make('receiveUser.name')->label('应聘人用户名称')->placeholder('-')->searchable(),
                TextColumn::make('salary')->label('薪资')->numeric(2),
                RcTable::enumBadge('salary_unit', '薪资单位', RcSalaryUnit::class),
                RcTable::enumBadge('status', '状态', RcOfferStatus::class),
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
