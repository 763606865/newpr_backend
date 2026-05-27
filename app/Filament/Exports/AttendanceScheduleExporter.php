<?php

namespace App\Filament\Exports;

use App\Enums\AttendanceScheduleStatus;
use App\Models\Company;
use App\Models\Oa\AttendanceSchedule;
use Filament\Actions\Action;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Component;
use Illuminate\Support\Str;

class AttendanceScheduleExporter extends Exporter
{
    protected static ?string $model = AttendanceSchedule::class;

    /**
     * @return array<Component|Action>
     */
    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('company_id')
                ->label('所属企业')
                ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required(),
            DatePicker::make('date_from')
                ->label('开始日期')
                ->required(),
            DatePicker::make('date_until')
                ->label('结束日期')
                ->afterOrEqual('date_from')
                ->required(),
        ];
    }

    /**
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('company.name')
                ->label('所属企业'),
            ExportColumn::make('department.name')
                ->label('部门'),
            ExportColumn::make('employee.real_name')
                ->label('员工姓名'),
            ExportColumn::make('attendanceRule.name')
                ->label('考勤规则'),
            ExportColumn::make('date')
                ->label('考勤日期')
                ->formatStateUsing(fn ($state): string => $state ? (string) $state : ''),
            ExportColumn::make('actual_work_hours')
                ->label('实际工时'),
            ExportColumn::make('status')
                ->label('状态')
                ->formatStateUsing(fn (mixed $state): string => (string) (AttendanceScheduleStatus::tryFrom((int) $state)?->getLabel() ?? '未知')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = "考勤导出已完成，成功导出 {$export->successful_rows} 条记录。";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= " 失败 {$failedRowsCount} 条。";
        }

        return $body;
    }

    public function getFileName(Export $export): string
    {
        $companyId = (int) ($this->options['company_id'] ?? 0);
        $dateFrom = (string) ($this->options['date_from'] ?? '');
        $dateUntil = (string) ($this->options['date_until'] ?? '');

        $companyName = '';

        if ($companyId > 0) {
            $companyName = (string) (Company::query()->whereKey($companyId)->value('name') ?? '');
        }

        $normalizedCompanyName = Str::of($companyName)
            ->replaceMatches('/[^\p{L}\p{N}_-]+/u', '-')
            ->trim('-')
            ->value();

        $range = trim("{$dateFrom}_{$dateUntil}", '_');

        return implode('-', array_filter([
            'attendance-schedules',
            $normalizedCompanyName,
            $range,
            $export->getKey(),
        ]));
    }
}
