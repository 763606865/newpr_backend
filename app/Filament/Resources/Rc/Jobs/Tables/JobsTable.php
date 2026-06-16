<?php

namespace App\Filament\Resources\Rc\Jobs\Tables;

use App\Enums\RcJobStatus;
use App\Filament\Resources\Rc\RcTable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('company.name')->label('企业')->placeholder('-')->searchable(),
                TextColumn::make('position.name')->label('常用职位')->placeholder('-'),
                TextColumn::make('title')->label('职位名称')->searchable(),
                TextColumn::make('cityArea.name')->label('城市名称')->placeholder('-'),
                TextColumn::make('workplace')->label('工作地点')->placeholder('-'),
                RcTable::enumBadge('status', '状态', RcJobStatus::class),
                TextColumn::make('published_at')->label('发布时间')->dateTime(),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(RcJobStatus::class),
                Filter::make('published_range')
                    ->label('发布时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('published_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('published_at', '<=', $data['until']),
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
