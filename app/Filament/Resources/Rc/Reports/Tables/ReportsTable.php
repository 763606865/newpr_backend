<?php

namespace App\Filament\Resources\Rc\Reports\Tables;

use App\Enums\RcReportReasonType;
use App\Enums\RcReportStatus;
use App\Filament\Resources\Rc\RcTable;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Models\Rc\Report;
use App\Models\Rc\Resume;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.name')->label('举报用户')->placeholder('-')->searchable(),
                TextColumn::make('creatorIdentity.identity_name')->label('举报身份')->placeholder('-'),
                TextColumn::make('reportable_type')
                    ->label('对象类型')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::formatReportableType($state)),
                TextColumn::make('reportable_title')
                    ->label('举报对象')
                    ->state(fn (Report $record): string => self::formatReportableTitle($record))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->orWhereHasMorph('reportable', [Job::class], fn (Builder $morphQuery): Builder => $morphQuery->where('title', 'like', "%{$search}%"))
                                ->orWhereHasMorph('reportable', [Company::class], fn (Builder $morphQuery): Builder => $morphQuery->where('name', 'like', "%{$search}%"))
                                ->orWhereHasMorph('reportable', [Resume::class], fn (Builder $morphQuery): Builder => $morphQuery->where('title', 'like', "%{$search}%"));
                        });
                    })
                    ->placeholder('-'),
                RcTable::enumBadge('reason_type', '举报原因', RcReportReasonType::class, [
                    RcReportReasonType::FalseInformation->value => 'warning',
                    RcReportReasonType::Fraud->value => 'danger',
                    RcReportReasonType::IllegalContent->value => 'danger',
                    RcReportReasonType::Harassment->value => 'warning',
                    RcReportReasonType::Other->value => 'gray',
                ]),
                TextColumn::make('reason')->label('原因说明')->placeholder('-')->limit(24)->searchable(),
                RcTable::enumBadge('status', '处理状态', RcReportStatus::class, [
                    RcReportStatus::Pending->value => 'warning',
                    RcReportStatus::Processing->value => 'info',
                    RcReportStatus::Resolved->value => 'success',
                    RcReportStatus::Rejected->value => 'gray',
                ]),
                TextColumn::make('handler.name')->label('处理人')->placeholder('-'),
                TextColumn::make('handled_at')->label('处理时间')->dateTime()->placeholder('-')->sortable(),
                TextColumn::make('created_at')->label('举报时间')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('reportable_type')
                    ->label('对象类型')
                    ->options([
                        'job' => '职位',
                        'company' => '企业',
                        'resume' => '简历',
                    ]),
                SelectFilter::make('reason_type')
                    ->label('举报原因')
                    ->options(RcReportReasonType::class),
                SelectFilter::make('status')
                    ->label('处理状态')
                    ->options(RcReportStatus::class),
                Filter::make('created_range')
                    ->label('举报时间')
                    ->schema([
                        DatePicker::make('from')->label('开始'),
                        DatePicker::make('until')->label('结束'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('created_at', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $subQuery): Builder => $subQuery->whereDate('created_at', '<=', $data['until']),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function formatReportableType(?string $type): string
    {
        return match ($type) {
            'job' => '职位',
            'company' => '企业',
            'resume' => '简历',
            default => $type ?? '-',
        };
    }

    private static function formatReportableTitle(Report $report): string
    {
        $reportable = $report->reportable;

        return match (true) {
            $reportable instanceof Job => $reportable->title,
            $reportable instanceof Company => $reportable->name,
            $reportable instanceof Resume => $reportable->title,
            default => '-',
        };
    }
}
