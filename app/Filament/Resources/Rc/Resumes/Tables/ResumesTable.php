<?php

namespace App\Filament\Resources\Rc\Resumes\Tables;

use App\Enums\RcResumeSourceType;
use App\Enums\RcResumeStatus;
use App\Filament\Resources\Rc\RcTable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResumesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('user_id')->label('用户ID')->sortable(),
                TextColumn::make('resume_no')->label('简历编号')->searchable(),
                TextColumn::make('title')->label('标题')->searchable(),
                RcTable::enumBadge('source_type', '来源', RcResumeSourceType::class),
                IconColumn::make('is_primary')->label('主简历')->boolean(),
                RcTable::enumBadge('status', '状态', RcResumeStatus::class, [1 => 'success']),
                TextColumn::make('updated_at')->label('更新时间')->dateTime(),
            ])
            ->filters([
                SelectFilter::make('source_type')
                    ->label('来源类型')
                    ->options(RcResumeSourceType::class),
                SelectFilter::make('status')
                    ->label('状态')
                    ->options(RcResumeStatus::class),
                Filter::make('date_range')
                    ->label('更新时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('updated_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('updated_at', '<=', $data['until']),
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
