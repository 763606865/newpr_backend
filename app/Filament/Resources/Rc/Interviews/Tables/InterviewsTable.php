<?php

namespace App\Filament\Resources\Rc\Interviews\Tables;

use App\Enums\RcInterviewMode;
use App\Enums\RcInterviewResult;
use App\Enums\RcInterviewStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InterviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('company_id')->label('企业ID')->sortable(),
                TextColumn::make('application_id')->label('投递ID')->sortable(),
                TextColumn::make('stage_id')->label('阶段ID')->placeholder('-')->sortable(),
                TextColumn::make('interviewer_name')->label('面试官')->placeholder('-')->searchable(),
                TextColumn::make('interview_at')->label('面试时间')->dateTime()->sortable(),
                TextColumn::make('duration_mins')->label('时长(分钟)')->placeholder('-'),
                TextColumn::make('mode')
                    ->label('方式')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof RcInterviewMode
                        ? $state->getLabel() ?? '-'
                        : RcInterviewMode::tryFrom((int) $state)?->getLabel() ?? '-'),
                TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof RcInterviewStatus
                        ? $state->getLabel() ?? '-'
                        : RcInterviewStatus::tryFrom((int) $state)?->getLabel() ?? '-'),
                TextColumn::make('result')
                    ->label('结果')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof RcInterviewResult
                        ? $state->getLabel() ?? '-'
                        : RcInterviewResult::tryFrom((int) $state)?->getLabel() ?? '-'),
            ])
            ->filters([
                SelectFilter::make('mode')
                    ->label('面试方式')
                    ->options(RcInterviewMode::class),
                SelectFilter::make('status')
                    ->label('面试状态')
                    ->options(RcInterviewStatus::class),
                SelectFilter::make('result')
                    ->label('面试结果')
                    ->options(RcInterviewResult::class),
                Filter::make('interview_range')
                    ->label('面试时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('interview_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('interview_at', '<=', $data['until']),
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
