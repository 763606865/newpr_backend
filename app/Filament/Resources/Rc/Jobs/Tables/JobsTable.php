<?php

namespace App\Filament\Resources\Rc\Jobs\Tables;

use App\Enums\RcJobStatus;
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
                TextColumn::make('code')->label('职位编码')->searchable(),
                TextColumn::make('title')->label('职位名称')->searchable(),
                TextColumn::make('city_code')->label('城市编码')->placeholder('-'),
                TextColumn::make('workplace')->label('工作地点')->placeholder('-'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => RcJobStatus::tryFrom((int) $state)?->getLabel() ?? '-'),
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
