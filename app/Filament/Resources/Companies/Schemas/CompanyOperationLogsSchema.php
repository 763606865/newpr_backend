<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Enums\CompanyOperationAction;
use App\Models\Company;
use App\Models\CompanyOperationLog;
use App\Services\CompanyOperationLogService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyOperationLogsSchema
{
    /**
     * @return array<int, RepeatableEntry|Section>
     */
    public static function components(): array
    {
        return [
            Section::make('筛选条件')
                ->schema(self::filterComponents())
                ->columns(4)
                ->compact(),
            self::logsTableComponent(),
            self::paginationSection(),
        ];
    }

    /**
     * @return array<int, DatePicker|Select>
     */
    private static function filterComponents(): array
    {
        return [
            DatePicker::make('log_from')
                ->label('开始时间')
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set): mixed => $set('log_page', 1)),
            DatePicker::make('log_until')
                ->label('结束时间')
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set): mixed => $set('log_page', 1)),
            Select::make('log_action')
                ->label('操作类型')
                ->options(CompanyOperationAction::class)
                ->placeholder('全部')
                ->searchable()
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set): mixed => $set('log_page', 1)),
            Select::make('log_operator')
                ->label('操作人')
                ->options(fn (Company $record): array => CompanyOperationLogService::make()->operatorFilterOptions($record))
                ->placeholder('全部')
                ->searchable()
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set): mixed => $set('log_page', 1)),
        ];
    }

    private static function logsTableComponent(): RepeatableEntry
    {
        return RepeatableEntry::make('filtered_operation_logs')
            ->hiddenLabel()
            ->placeholder('暂无操作日志')
            ->getStateUsing(function (Company $record, Get $get): array {
                return self::paginator($record, $get)->items();
            })
            ->table([
                TableColumn::make('操作时间'),
                TableColumn::make('操作类型'),
                TableColumn::make('摘要'),
                TableColumn::make('操作人'),
                TableColumn::make('IP'),
            ])
            ->schema([
                TextEntry::make('created_at')
                    ->label('操作时间')
                    ->dateTime('Y-m-d H:i:s'),
                TextEntry::make('action')
                    ->label('操作类型')
                    ->badge()
                    ->formatStateUsing(fn (CompanyOperationAction $state): string => $state->getLabel()),
                TextEntry::make('summary')
                    ->label('摘要')
                    ->placeholder('-'),
                TextEntry::make('operator_display')
                    ->label('操作人')
                    ->state(fn (CompanyOperationLog $record): string => CompanyOperationLogService::make()->formatOperatorLabel($record)),
                TextEntry::make('ip')
                    ->label('IP')
                    ->placeholder('-'),
            ])
            ->contained(false);
    }

    private static function paginationSection(): Section
    {
        return Section::make()
            ->schema([
                TextEntry::make('log_pagination_summary')
                    ->hiddenLabel()
                    ->state(function (Company $record, Get $get): string {
                        $paginator = self::paginator($record, $get);

                        if ($paginator->total() === 0) {
                            return '暂无记录';
                        }

                        return sprintf(
                            '共 %d 条，第 %d / %d 页（每页 %d 条）',
                            $paginator->total(),
                            $paginator->currentPage(),
                            max(1, $paginator->lastPage()),
                            CompanyOperationLogService::LOGS_PER_PAGE,
                        );
                    }),
                Select::make('log_page')
                    ->label('页码')
                    ->options(function (Company $record, Get $get): array {
                        $paginator = self::paginator($record, $get);
                        $options = [];

                        for ($page = 1; $page <= max(1, $paginator->lastPage()); $page++) {
                            $options[$page] = "第 {$page} 页";
                        }

                        return $options;
                    })
                    ->default(1)
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (Company $record, Get $get): bool => self::paginator($record, $get)->lastPage() > 1),
            ])
            ->columns(2)
            ->compact();
    }

    private static function paginator(Company $record, Get $get): LengthAwarePaginator
    {
        return CompanyOperationLogService::make()->paginateForCompany(
            $record,
            self::filtersFromGet($get),
            (int) ($get('log_page') ?: 1),
        );
    }

    /**
     * @return array{from: mixed, until: mixed, action: mixed, operator: mixed}
     */
    private static function filtersFromGet(Get $get): array
    {
        return [
            'from' => $get('log_from'),
            'until' => $get('log_until'),
            'action' => $get('log_action'),
            'operator' => $get('log_operator'),
        ];
    }
}
