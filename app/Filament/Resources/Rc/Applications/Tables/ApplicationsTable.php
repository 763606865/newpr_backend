<?php

namespace App\Filament\Resources\Rc\Applications\Tables;

use App\Enums\RcApplicationSourceType;
use App\Enums\RcApplicationStatus;
use App\Filament\Resources\Rc\RcTable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('企业名称')->placeholder('-')->searchable(),
                TextColumn::make('job.title')->label('职位')->placeholder('-')->searchable(),
                TextColumn::make('candidateUser.name')->label('候选人用户名称')->placeholder('-')->searchable(),
                RcTable::enumBadge('source_type', '来源', RcApplicationSourceType::class),
                RcTable::enumBadge('status', '状态', RcApplicationStatus::class),
                TextColumn::make('applied_at')->label('投递时间')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('source_type')
                    ->label('来源类型')
                    ->options(RcApplicationSourceType::class),
                SelectFilter::make('status')
                    ->label('投递状态')
                    ->options(RcApplicationStatus::class),
                Filter::make('applied_range')
                    ->label('投递时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('applied_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('applied_at', '<=', $data['until']),
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
